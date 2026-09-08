<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database,View};
use PDO;

final class ManagementController
{
    private const STATUS_LABELS=[
        'NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera',
        'RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'
    ];

    private function requireManagement(): void
    {
        Auth::requireLogin();
        if (!in_array(Auth::role(), ['ADMIN','SEMIADMIN'], true) && !Auth::can('management.view')) {
            header('Location: '.APP_BASE_URL.'/dashboard');
            exit;
        }
    }

    public function dashboard(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();
        $filters=$this->filters();
        [$where,$params]=$this->where($filters);

        $k=$pdo->prepare("SELECT
            COUNT(*) total,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')),0) abiertos,
            COALESCE(SUM(t.status='RESOLVED'),0) resueltos,
            COALESCE(SUM(t.status='CLOSED'),0) cerrados,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','REOPENED') AND t.assigned_to IS NULL),0) sin_asignar,
            COALESCE(SUM(t.resolution_due_at IS NOT NULL AND t.resolution_due_at<NOW() AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')),0) vencidos,
            ROUND(AVG(CASE WHEN t.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at) END),1) promedio_primera_respuesta,
            ROUND(AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at)/60 END),1) promedio_resolucion_horas,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL AND t.resolved_at<=t.resolution_due_at THEN 1 ELSE 0 END),0) sla_ok,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL THEN 1 ELSE 0 END),0) sla_medidos,
            COALESCE(SUM(tr.ticket_id IS NOT NULL),0) soluciones_documentadas,
            COALESCE(SUM(tr.is_reusable=1),0) soluciones_reutilizables
            FROM tickets t LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id {$where}");
        $k->execute($params); $kpis=$k->fetch()?:[];
        $kpis['sla_porcentaje']=(int)($kpis['sla_medidos']??0)>0?round(((int)$kpis['sla_ok']/(int)$kpis['sla_medidos'])*100,1):null;

        $byStatus=$this->group($pdo,"SELECT t.status label,COUNT(*) total FROM tickets t {$where} GROUP BY t.status ORDER BY total DESC",$params);
        $byCategory=$this->group($pdo,"SELECT COALESCE(c.name,'Sin categoría') label,COUNT(*) total FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id {$where} GROUP BY c.id,c.name ORDER BY total DESC LIMIT 8",$params);
        $byPark=$this->group($pdo,"SELECT COALESCE(p.name,'Sin ubicación') label,COUNT(*) total FROM tickets t LEFT JOIN parks p ON p.id=t.park_id {$where} GROUP BY p.id,p.name ORDER BY total DESC LIMIT 8",$params);
        $byAssignee=$this->group($pdo,"SELECT COALESCE(u.full_name,'Sin asignar') label,COUNT(*) total,COALESCE(SUM(t.status IN('IN_PROGRESS','PENDING','REOPENED')),0) activos FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to {$where} GROUP BY u.id,u.full_name ORDER BY total DESC LIMIT 8",$params);
        $trend=$this->group($pdo,"SELECT DATE_FORMAT(t.created_at,'%Y-%m') periodo,COUNT(*) total,COALESCE(SUM(t.status IN('RESOLVED','CLOSED')),0) completados FROM tickets t {$where} GROUP BY DATE_FORMAT(t.created_at,'%Y-%m') ORDER BY periodo",$params);

        $detail=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.status,t.priority,t.created_at,t.first_response_at,t.resolved_at,t.resolution_due_at,
            p.name park_name,c.name category_name,u.full_name assigned_name,t.requester_name
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN users u ON u.id=t.assigned_to
            {$where} ORDER BY t.created_at DESC LIMIT 80");
        $detail->execute($params);

        $catalogs=$this->catalogs($pdo);
        View::render('management/dashboard',[
            'user'=>Auth::user(),'filters'=>$filters,'kpis'=>$kpis,'byStatus'=>$byStatus,'byCategory'=>$byCategory,'byPark'=>$byPark,'byAssignee'=>$byAssignee,'trend'=>$trend,
            'tickets'=>$detail->fetchAll(),'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],
        ]);
    }

    public function reports(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();
        $filters=$this->filters();
        [$where,$params]=$this->where($filters);

        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,t.requester_name,t.requester_email,t.requester_phone,t.subject,t.description,t.priority,t.status,
            p.name park_name,a.name area_name,c.name category_name,u.full_name assigned_name,t.assigned_at,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at,
            tr.resolution_type,tr.root_cause,tr.solution_applied,tr.preventive_action,tr.is_reusable,ru.full_name resolution_author
            FROM tickets t
            LEFT JOIN parks p ON p.id=t.park_id
            LEFT JOIN areas a ON a.id=t.area_id
            LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN users u ON u.id=t.assigned_to
            LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id
            LEFT JOIN users ru ON ru.id=tr.resolved_by
            {$where}
            ORDER BY t.created_at DESC LIMIT 500");
        $q->execute($params);
        $rows=$q->fetchAll();
        $events=$this->eventsByTicket($pdo,array_map(static fn(array $r):(int)=>(int)$r['id'],$rows));
        foreach($rows as &$row){
            $row['lifecycle']=$this->lifecycle($row,$events[(int)$row['id']]??[]);
        }
        unset($row);

        $reportStats=$this->reportStats($rows);
        $catalogs=$this->catalogs($pdo);
        View::render('management/reports',[
            'user'=>Auth::user(),'filters'=>$filters,'rows'=>$rows,'reportStats'=>$reportStats,
            'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],
        ]);
    }

    public function export(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();
        $filters=$this->filters();
        [$where,$params]=$this->where($filters);

        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,t.requester_name,t.requester_email,t.requester_phone,
            COALESCE(p.name,'') park_name,COALESCE(a.name,'') area_name,COALESCE(c.name,'') category_name,
            t.subject,t.description,t.priority,t.status,COALESCE(u.full_name,'') assigned_name,t.assigned_at,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at,
            COALESCE(tr.resolution_type,'') resolution_type,COALESCE(tr.root_cause,'') root_cause,
            COALESCE(tr.solution_applied,'') solution_applied,COALESCE(tr.preventive_action,'') preventive_action,
            COALESCE(ru.full_name,'') resolution_author
            FROM tickets t
            LEFT JOIN parks p ON p.id=t.park_id
            LEFT JOIN areas a ON a.id=t.area_id
            LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN users u ON u.id=t.assigned_to
            LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id
            LEFT JOIN users ru ON ru.id=tr.resolved_by
            {$where}
            ORDER BY t.created_at DESC");
        $q->execute($params);
        $rows=$q->fetchAll();
        $events=$this->eventsByTicket($pdo,array_map(static fn(array $r):(int)=>(int)$r['id'],$rows));

        Audit::log('REPORT_EXPORTED','report',null,null,null,['filters'=>$filters]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="helpdesk_informe_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";
        $out=fopen('php://output','w');
        fputcsv($out,[
            'Ticket','Creado','Solicitante','Correo','Teléfono','Parque','Área','Categoría','Asunto','Descripción','Prioridad','Estado actual','Responsable',
            'Asignado','Primera respuesta','Resuelto','Cerrado','Límite primera respuesta','Límite resolución',
            'Minutos hasta asignación','Minutos primera respuesta','Minutos en cola','Minutos trabajando','Minutos en espera','Minutos resuelto antes de cierre','Minutos hasta resolución','Minutos totales del caso','Cambios de estado','Historial de estados',
            'Tipo de solución','Causa encontrada','Solución aplicada','Prevención / seguimiento','Documentado por'
        ],';');

        foreach($rows as $r){
            $life=$this->lifecycle($r,$events[(int)$r['id']]??[]);
            $history=[];
            foreach($life['transitions'] as $t){
                $history[]=date('d/m/Y H:i',strtotime($t['at'])).' · '.($t['from_label']??'—').' → '.($t['to_label']??'—').(!empty($t['actor'])?' · '.$t['actor']:'');
            }
            fputcsv($out,[
                $r['ticket_number'],$r['created_at'],$r['requester_name'],$r['requester_email'],$r['requester_phone'],$r['park_name'],$r['area_name'],$r['category_name'],
                $r['subject'],$r['description'],$r['priority'],self::STATUS_LABELS[$r['status']]??$r['status'],$r['assigned_name'],$r['assigned_at'],$r['first_response_at'],$r['resolved_at'],$r['closed_at'],$r['first_response_due_at'],$r['resolution_due_at'],
                $life['assignment_minutes'],$life['first_response_minutes'],$life['queue_minutes'],$life['work_minutes'],$life['pending_minutes'],$life['resolved_wait_minutes'],$life['resolution_minutes'],$life['total_minutes'],count($life['transitions']),implode(' | ',$history),
                $r['resolution_type'],$r['root_cause'],$r['solution_applied'],$r['preventive_action'],$r['resolution_author']
            ],';');
        }
        fclose($out);exit;
    }

    private function eventsByTicket(PDO $pdo,array $ticketIds): array
    {
        $ticketIds=array_values(array_unique(array_filter(array_map('intval',$ticketIds),static fn(int $id):bool=>$id>0)));
        if(!$ticketIds)return[];
        $placeholders=implode(',',array_fill(0,count($ticketIds),'?'));
        $q=$pdo->prepare("SELECT te.ticket_id,te.event_type,te.actor_type,te.old_value,te.new_value,te.created_at,u.full_name actor_name
            FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id
            WHERE te.ticket_id IN ({$placeholders})
            ORDER BY te.ticket_id,te.created_at,te.id");
        $q->execute($ticketIds);
        $grouped=[];
        foreach($q->fetchAll() as $event)$grouped[(int)$event['ticket_id']][]=$event;
        return$grouped;
    }

    private function lifecycle(array $ticket,array $events): array
    {
        $created=strtotime((string)$ticket['created_at'])?:time();
        $initial=null;
        foreach($events as $event){
            $new=$this->statusFromJson($event['new_value']??null);
            $old=$this->statusFromJson($event['old_value']??null);
            if(($event['event_type']??'')==='CREATED'&&$new){$initial=$new;break;}
            if($old){$initial=$old;break;}
        }
        if(!$initial)$initial=(string)($ticket['status']??'NEW');

        $current=$initial;
        $statusStarted=$created;
        $durations=[];
        $segments=[];
        $transitions=[];

        foreach($events as $event){
            $new=$this->statusFromJson($event['new_value']??null);
            if(!$new||$new===$current)continue;
            $at=strtotime((string)$event['created_at']);
            if(!$at||$at<$statusStarted)continue;
            $minutes=max(0,(int)round(($at-$statusStarted)/60));
            $durations[$current]=($durations[$current]??0)+$minutes;
            $segments[]=['status'=>$current,'label'=>self::STATUS_LABELS[$current]??$current,'from'=>date('Y-m-d H:i:s',$statusStarted),'to'=>(string)$event['created_at'],'minutes'=>$minutes];
            $actor=trim((string)($event['actor_name']??''));
            if($actor==='')$actor=($event['actor_type']??'SYSTEM')==='PUBLIC'?'Solicitante':'Sistema';
            $transitions[]=[
                'from'=>$current,'to'=>$new,'from_label'=>self::STATUS_LABELS[$current]??$current,'to_label'=>self::STATUS_LABELS[$new]??$new,
                'at'=>(string)$event['created_at'],'actor'=>$actor,'event_type'=>(string)($event['event_type']??'')
            ];
            $current=$new;
            $statusStarted=$at;
        }

        $closedTs=!empty($ticket['closed_at'])?(strtotime((string)$ticket['closed_at'])?:null):null;
        $end=$closedTs?:time();
        if($end<$statusStarted)$end=$statusStarted;
        $finalMinutes=max(0,(int)round(($end-$statusStarted)/60));
        $durations[$current]=($durations[$current]??0)+$finalMinutes;
        $segments[]=['status'=>$current,'label'=>self::STATUS_LABELS[$current]??$current,'from'=>date('Y-m-d H:i:s',$statusStarted),'to'=>$closedTs?(string)$ticket['closed_at']:null,'minutes'=>$finalMinutes];

        $minutesBetween=static function($from,$to):?int{
            if(empty($from)||empty($to))return null;
            $a=strtotime((string)$from);$b=strtotime((string)$to);
            return($a&&$b&&$b>=$a)?(int)round(($b-$a)/60):null;
        };

        return[
            'initial_status'=>$initial,
            'current_status'=>$current,
            'durations'=>$durations,
            'segments'=>$segments,
            'transitions'=>$transitions,
            'queue_minutes'=>(int)(($durations['NEW']??0)+($durations['AVAILABLE']??0)),
            'work_minutes'=>(int)(($durations['IN_PROGRESS']??0)+($durations['REOPENED']??0)),
            'pending_minutes'=>(int)($durations['PENDING']??0),
            'resolved_wait_minutes'=>(int)($durations['RESOLVED']??0),
            'assignment_minutes'=>$minutesBetween($ticket['created_at']??null,$ticket['assigned_at']??null),
            'first_response_minutes'=>$minutesBetween($ticket['created_at']??null,$ticket['first_response_at']??null),
            'resolution_minutes'=>$minutesBetween($ticket['created_at']??null,$ticket['resolved_at']??null),
            'total_minutes'=>$minutesBetween($ticket['created_at']??null,$ticket['closed_at']??date('Y-m-d H:i:s')),
        ];
    }

    private function statusFromJson($json): ?string
    {
        if(!is_string($json)||trim($json)==='')return null;
        $data=json_decode($json,true);
        if(!is_array($data))return null;
        $status=strtoupper(trim((string)($data['status']??'')));
        return isset(self::STATUS_LABELS[$status])?$status:null;
    }

    private function reportStats(array $rows): array
    {
        $documented=0;$first=[];$resolution=[];$work=[];$pending=[];$changes=0;
        foreach($rows as $row){
            if(!empty($row['solution_applied']))$documented++;
            $life=$row['lifecycle']??[];
            if(isset($life['first_response_minutes'])&&$life['first_response_minutes']!==null)$first[]=(int)$life['first_response_minutes'];
            if(isset($life['resolution_minutes'])&&$life['resolution_minutes']!==null)$resolution[]=(int)$life['resolution_minutes'];
            if(isset($life['work_minutes']))$work[]=(int)$life['work_minutes'];
            if(isset($life['pending_minutes']))$pending[]=(int)$life['pending_minutes'];
            $changes+=count($life['transitions']??[]);
        }
        $avg=static fn(array $v):?float=>$v?round(array_sum($v)/count($v),1):null;
        $total=count($rows);
        return[
            'total'=>$total,'documented'=>$documented,'undocumented'=>$total-$documented,
            'documented_pct'=>$total?round(($documented/$total)*100,1):0,
            'avg_first_response_minutes'=>$avg($first),'avg_resolution_minutes'=>$avg($resolution),
            'avg_work_minutes'=>$avg($work),'avg_pending_minutes'=>$avg($pending),'status_changes'=>$changes,
        ];
    }

    private function catalogs(PDO $pdo):array
    {
        return [
            'parks'=>$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll(),
            'categories'=>$pdo->query("SELECT id,name FROM ticket_categories WHERE is_active=1 ORDER BY name")->fetchAll(),
            'supportUsers'=>$pdo->query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status='ACTIVE' AND u.deleted_at IS NULL AND u.access_type='INTERNAL' AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll(),
        ];
    }

    private function filters(): array
    {
        return [
            'from'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['from']??''))?(string)$_GET['from']:date('Y-m-01'),
            'to'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['to']??''))?(string)$_GET['to']:date('Y-m-d'),
            'park_id'=>(int)($_GET['park_id']??0),'category_id'=>(int)($_GET['category_id']??0),'assigned_to'=>(int)($_GET['assigned_to']??0),
            'status'=>strtoupper(trim((string)($_GET['status']??''))),'priority'=>strtoupper(trim((string)($_GET['priority']??''))),
        ];
    }

    private function where(array $f): array
    {
        $w=['t.deleted_at IS NULL','t.created_at>=?','t.created_at<DATE_ADD(?,INTERVAL 1 DAY)'];$p=[$f['from'],$f['to']];
        if($f['park_id']>0){$w[]='t.park_id=?';$p[]=$f['park_id'];}
        if($f['category_id']>0){$w[]='t.category_id=?';$p[]=$f['category_id'];}
        if($f['assigned_to']>0){$w[]='t.assigned_to=?';$p[]=$f['assigned_to'];}
        if(in_array($f['status'],['NEW','AVAILABLE','IN_PROGRESS','PENDING','RESOLVED','CLOSED','REOPENED','CANCELLED'],true)){$w[]='t.status=?';$p[]=$f['status'];}
        if(in_array($f['priority'],['LOW','MEDIUM','HIGH','CRITICAL'],true)){$w[]='t.priority=?';$p[]=$f['priority'];}
        return [' WHERE '.implode(' AND ',$w),$p];
    }

    private function group(PDO $pdo,string $sql,array $params): array{$q=$pdo->prepare($sql);$q->execute($params);return$q->fetchAll();}
}
