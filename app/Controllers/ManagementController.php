<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,View};
use App\Services\{AgendaService,KnowledgeMetricsService,ScopeService,TicketLifecycleService};
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
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('management.view')){
            header('Location: '.APP_BASE_URL.'/dashboard');exit;
        }
    }

    private function requireReports(): void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('reports.view')&&!Auth::can('management.view')){
            header('Location: '.APP_BASE_URL.'/dashboard');exit;
        }
    }

    public function dashboard(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();$filters=$this->filters();[$where,$params]=$this->where($filters);

        $k=$pdo->prepare("SELECT
            COUNT(*) total,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')),0) abiertos,
            COALESCE(SUM(t.status='RESOLVED'),0) resueltos,
            COALESCE(SUM(t.status='CLOSED'),0) cerrados,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','REOPENED') AND t.assigned_to IS NULL),0) sin_asignar,
            COALESCE(SUM(t.status='PENDING'),0) en_espera,
            COALESCE(SUM(t.resolution_due_at IS NOT NULL AND t.resolution_due_at<NOW() AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')),0) vencidos,
            ROUND(AVG(CASE WHEN t.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at) END),1) promedio_primera_respuesta,
            ROUND(AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at)/60 END),1) promedio_resolucion_horas,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL AND t.resolved_at<=t.resolution_due_at THEN 1 ELSE 0 END),0) sla_ok,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL THEN 1 ELSE 0 END),0) sla_medidos,
            COALESCE(SUM(tr.ticket_id IS NOT NULL),0) soluciones_documentadas,
            COALESCE(SUM(tr.is_reusable=1),0) soluciones_reutilizables
            FROM tickets t LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id {$where}");
        $k->execute($params);$kpis=$k->fetch()?:[];
        $kpis['sla_porcentaje']=(int)($kpis['sla_medidos']??0)>0?round(((int)$kpis['sla_ok']/(int)$kpis['sla_medidos'])*100,1):null;

        $byStatus=$this->group($pdo,"SELECT t.status label,COUNT(*) total FROM tickets t {$where} GROUP BY t.status ORDER BY total DESC",$params);
        $byCategory=$this->group($pdo,"SELECT COALESCE(pc.name,c.name,'Sin categoría') label,COUNT(*) total FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN ticket_categories pc ON pc.id=c.parent_id {$where} GROUP BY COALESCE(pc.id,c.id),COALESCE(pc.name,c.name) ORDER BY total DESC LIMIT 8",$params);
        $byPark=$this->group($pdo,"SELECT COALESCE(p.name,'Sin ubicación') label,COUNT(*) total FROM tickets t LEFT JOIN parks p ON p.id=t.park_id {$where} GROUP BY p.id,p.name ORDER BY total DESC LIMIT 8",$params);
        $byAssignee=$this->group($pdo,"SELECT COALESCE(u.full_name,'Sin asignar') label,COUNT(*) total,COALESCE(SUM(t.status IN('IN_PROGRESS','PENDING','REOPENED')),0) activos FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to {$where} GROUP BY u.id,u.full_name ORDER BY total DESC LIMIT 8",$params);
        $byPendingReason=$this->group($pdo,"SELECT t.pending_reason_code label,COUNT(*) total FROM tickets t {$where} AND t.status='PENDING' AND t.pending_reason_code IS NOT NULL GROUP BY t.pending_reason_code ORDER BY total DESC",$params);
        $trend=$this->group($pdo,"SELECT DATE_FORMAT(t.created_at,'%Y-%m') periodo,COUNT(*) total,COALESCE(SUM(t.status IN('RESOLVED','CLOSED')),0) completados FROM tickets t {$where} GROUP BY DATE_FORMAT(t.created_at,'%Y-%m') ORDER BY periodo",$params);

        $detail=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.description,t.status,t.priority,t.pending_reason_code,t.pending_note,t.created_at,t.first_response_at,t.resolved_at,t.resolution_due_at,
            p.name park_name,c.name category_name,u.full_name assigned_name,COALESCE(req.full_name,t.requester_name) requester_name
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL
            {$where} ORDER BY t.created_at DESC LIMIT 80");
        $detail->execute($params);

        $catalogs=$this->catalogs($pdo);$scopeLabel=(new ScopeService())->scopeLabel();
        View::render('management/dashboard',[
            'user'=>Auth::user(),'filters'=>$filters,'kpis'=>$kpis,'byStatus'=>$byStatus,'byCategory'=>$byCategory,'byPark'=>$byPark,'byAssignee'=>$byAssignee,'byPendingReason'=>$byPendingReason,'trend'=>$trend,
            'tickets'=>$detail->fetchAll(),'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],'pendingReasons'=>WorkflowController::PENDING_REASONS,'scopeLabel'=>$scopeLabel,
        ]);
    }

    public function reports(): void
    {
        $this->requireReports();
        $pdo=Database::pdo();$filters=$this->filters();[$where,$params]=$this->where($filters);

        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,COALESCE(req.full_name,t.requester_name) requester_name,COALESCE(req.email,t.requester_email) requester_email,COALESCE(req.phone,t.requester_phone) requester_phone,t.subject,t.description,t.priority,t.status,t.pending_reason_code,t.pending_note,
            p.name park_name,a.name area_name,c.name category_name,u.full_name assigned_name,t.assigned_at,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at,
            tr.resolution_type,tr.root_cause,tr.solution_applied,tr.preventive_action,tr.is_reusable,ru.full_name resolution_author
            FROM tickets t
            LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN users ru ON ru.id=tr.resolved_by LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL
            {$where} ORDER BY t.created_at DESC LIMIT 500");
        $q->execute($params);$rows=$q->fetchAll();
        $events=$this->eventsByTicket($pdo,array_map(static fn(array $r):int=>(int)$r['id'],$rows));
        foreach($rows as &$row)$row['lifecycle']=TicketLifecycleService::analyze($row,$events[(int)$row['id']]??[]);
        unset($row);

        $reportStats=$this->reportStats($rows);$catalogs=$this->catalogs($pdo);$scopeLabel=(new ScopeService())->scopeLabel();
        $canKnowledge=in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)||Auth::can('knowledge.view');
        $knowledgeReport=$canKnowledge?(new KnowledgeMetricsService())->reportSummary($filters['from'],$filters['to']):null;
        $canActivities=in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)||Auth::can('activities.view');
        $activityReport=$canActivities?(new AgendaService())->reportSummary($filters):null;
        View::render('management/reports',[
            'user'=>Auth::user(),'filters'=>$filters,'rows'=>$rows,'reportStats'=>$reportStats,
            'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],'pendingReasons'=>WorkflowController::PENDING_REASONS,'scopeLabel'=>$scopeLabel,
            'canKnowledge'=>$canKnowledge,'knowledgeReport'=>$knowledgeReport,
            'canActivities'=>$canActivities,'activityReport'=>$activityReport,
        ]);
    }

    private function eventsByTicket(PDO $pdo,array $ticketIds):array
    {
        $ticketIds=array_values(array_unique(array_filter(array_map('intval',$ticketIds),static fn(int $id):bool=>$id>0)));
        if(!$ticketIds)return[];$ph=implode(',',array_fill(0,count($ticketIds),'?'));
        $q=$pdo->prepare("SELECT te.ticket_id,te.event_type,te.actor_type,te.old_value,te.new_value,te.created_at,u.full_name actor_name
            FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id IN ({$ph}) ORDER BY te.ticket_id,te.created_at,te.id");
        $q->execute($ticketIds);$grouped=[];foreach($q->fetchAll() as $event)$grouped[(int)$event['ticket_id']][]=$event;return$grouped;
    }

    private function reportStats(array $rows):array
    {
        $documented=0;$first=[];$resolution=[];$work=[];$pending=[];$changes=0;$pendingReasons=[];$pendingReasonMinutes=[];
        foreach($rows as $row){
            if(!empty($row['solution_applied']))$documented++;
            $life=$row['lifecycle']??[];
            if(isset($life['first_response_minutes'])&&$life['first_response_minutes']!==null)$first[]=(int)$life['first_response_minutes'];
            if(isset($life['resolution_minutes'])&&$life['resolution_minutes']!==null)$resolution[]=(int)$life['resolution_minutes'];
            if(isset($life['work_minutes']))$work[]=(int)$life['work_minutes'];if(isset($life['pending_minutes']))$pending[]=(int)$life['pending_minutes'];
            $changes+=count($life['transitions']??[]);
            if(!empty($row['pending_reason_code']))$pendingReasons[$row['pending_reason_code']]=($pendingReasons[$row['pending_reason_code']]??0)+1;
            foreach(($life['pending_reason_minutes']??[]) as $code=>$minutes)$pendingReasonMinutes[$code]=($pendingReasonMinutes[$code]??0)+(int)$minutes;
        }
        $avg=static fn(array $v):?float=>$v?round(array_sum($v)/count($v),1):null;$total=count($rows);
        return[
            'total'=>$total,'documented'=>$documented,'undocumented'=>$total-$documented,'documented_pct'=>$total?round(($documented/$total)*100,1):0,
            'avg_first_response_minutes'=>$avg($first),'avg_resolution_minutes'=>$avg($resolution),'avg_work_minutes'=>$avg($work),'avg_pending_minutes'=>$avg($pending),
            'status_changes'=>$changes,'pending_reasons'=>$pendingReasons,'pending_reason_minutes'=>$pendingReasonMinutes,
        ];
    }

    private function catalogs(PDO $pdo):array
    {
        $parks=$pdo->query("SELECT id,name,region_id FROM parks WHERE is_active=1 ORDER BY name")->fetchAll();
        if(Auth::role()==='SUPERVISOR'){
            $scope=new ScopeService();
            $parks=array_values(array_filter($parks,static fn(array $p):bool=>$scope->canAccessOrganization((int)($p['region_id']??0),(int)$p['id'],null)));
        }
        return['parks'=>$parks,'categories'=>$pdo->query("SELECT c.id,c.code,c.parent_id,CASE WHEN p.id IS NULL THEN c.name ELSE CONCAT(p.name,' · ',c.name) END name FROM ticket_categories c LEFT JOIN ticket_categories p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name")->fetchAll(),'supportUsers'=>$pdo->query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status='ACTIVE' AND u.deleted_at IS NULL AND u.access_type='INTERNAL' AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll()];
    }

    private function filters():array
    {
        return['from'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['from']??''))?(string)$_GET['from']:date('Y-m-01'),'to'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['to']??''))?(string)$_GET['to']:date('Y-m-d'),'park_id'=>(int)($_GET['park_id']??0),'category_id'=>(int)($_GET['category_id']??0),'assigned_to'=>(int)($_GET['assigned_to']??0),'status'=>strtoupper(trim((string)($_GET['status']??''))),'priority'=>strtoupper(trim((string)($_GET['priority']??'')))];
    }

    private function where(array $f):array
    {
        $w=['t.deleted_at IS NULL','t.created_at>=?','t.created_at<DATE_ADD(?,INTERVAL 1 DAY)'];$p=[$f['from'],$f['to']];
        [$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
        if($scopeSql!=='1=1'){$w[]=$scopeSql;array_push($p,...$scopeParams);}
        if($f['park_id']>0){$w[]='t.park_id=?';$p[]=$f['park_id'];}if($f['category_id']>0){$w[]='t.category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)';$p[]=$f['category_id'];$p[]=$f['category_id'];}if($f['assigned_to']>0){$w[]='t.assigned_to=?';$p[]=$f['assigned_to'];}
        if(in_array($f['status'],array_keys(self::STATUS_LABELS),true)){$w[]='t.status=?';$p[]=$f['status'];}if(in_array($f['priority'],['LOW','MEDIUM','HIGH','CRITICAL'],true)){$w[]='t.priority=?';$p[]=$f['priority'];}
        return[' WHERE '.implode(' AND ',$w),$p];
    }

    private function group(PDO $pdo,string $sql,array $params):array{$q=$pdo->prepare($sql);$q->execute($params);return$q->fetchAll();}
}
