<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database};
use App\Services\XlsxExportService;
use PDO;

final class XlsxExportController
{
    private const STATUS_LABELS=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
    private const PRIORITY_LABELS=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
    private const RESOLUTION_LABELS=['CONFIGURATION'=>'Configuración','RESTART'=>'Reinicio / restablecimiento','REPLACEMENT'=>'Cambio / reemplazo','PROVIDER'=>'Gestión con proveedor','USER_GUIDANCE'=>'Orientación al usuario','SOFTWARE'=>'Software / aplicación','NETWORK'=>'Red / conectividad','HARDWARE'=>'Hardware / equipo','PERMISSION'=>'Acceso / permisos','MAINTENANCE'=>'Mantenimiento','OTHER'=>'Otro'];

    public function export(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();$filters=$this->filters();[$where,$params]=$this->where($filters);
        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,t.requester_name,t.requester_email,t.requester_phone,
            COALESCE(p.name,'') park_name,COALESCE(a.name,'') area_name,COALESCE(c.name,'') category_name,
            t.subject,t.description,t.priority,t.status,COALESCE(u.full_name,'') assigned_name,t.assigned_at,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at,
            COALESCE(tr.resolution_type,'') resolution_type,COALESCE(tr.root_cause,'') root_cause,
            COALESCE(tr.solution_applied,'') solution_applied,COALESCE(tr.preventive_action,'') preventive_action,
            COALESCE(ru.full_name,'') resolution_author
            FROM tickets t
            LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN users ru ON ru.id=tr.resolved_by
            {$where} ORDER BY t.created_at DESC");
        $q->execute($params);$records=$q->fetchAll();
        $events=$this->eventsByTicket($pdo,array_map(static fn(array $r):int=>(int)$r['id'],$records));

        $headers=['Ticket','Creado','Solicitante','Correo','Teléfono','Parque','Área','Categoría','Asunto','Descripción','Prioridad','Estado actual','Responsable','Asignado','Primera respuesta','Resuelto','Cerrado','Límite primera respuesta','Límite resolución','Min. hasta asignación','Min. primera respuesta','Min. en cola','Min. trabajando','Min. en espera','Min. resuelto antes de cierre','Min. hasta resolución','Min. totales del caso','Cambios de estado','Historial de estados','Tipo de solución','Causa encontrada','Solución aplicada','Prevención / seguimiento','Documentado por'];
        $rows=[];$documented=0;$resolved=0;$totalFirst=0;$countFirst=0;$totalResolution=0;$countResolution=0;$statusChanges=0;

        foreach($records as $r){
            $life=$this->lifecycle($r,$events[(int)$r['id']]??[]);$history=[];
            foreach($life['transitions'] as $t){$history[]=date('d/m/Y H:i',strtotime($t['at'])).' · '.($t['from_label']??'—').' → '.($t['to_label']??'—').(!empty($t['actor'])?' · '.$t['actor']:'');}
            if(trim((string)$r['solution_applied'])!=='')$documented++;
            if(in_array((string)$r['status'],['RESOLVED','CLOSED'],true))$resolved++;
            if($life['first_response_minutes']!==null){$totalFirst+=(int)$life['first_response_minutes'];$countFirst++;}
            if($life['resolution_minutes']!==null){$totalResolution+=(int)$life['resolution_minutes'];$countResolution++;}
            $statusChanges+=count($life['transitions']);
            $rows[]=[
                $r['ticket_number'],$this->date($r['created_at']),$r['requester_name'],$r['requester_email'],$r['requester_phone'],$r['park_name'],$r['area_name'],$r['category_name'],$r['subject'],$r['description'],
                self::PRIORITY_LABELS[$r['priority']]??$r['priority'],self::STATUS_LABELS[$r['status']]??$r['status'],$r['assigned_name'],$this->date($r['assigned_at']),$this->date($r['first_response_at']),$this->date($r['resolved_at']),$this->date($r['closed_at']),$this->date($r['first_response_due_at']),$this->date($r['resolution_due_at']),
                $life['assignment_minutes']??'', $life['first_response_minutes']??'',(int)$life['queue_minutes'],(int)$life['work_minutes'],(int)$life['pending_minutes'],(int)$life['resolved_wait_minutes'],$life['resolution_minutes']??'', $life['total_minutes']??'',count($life['transitions']),implode("\n",$history),
                self::RESOLUTION_LABELS[$r['resolution_type']]??$r['resolution_type'],$r['root_cause'],$r['solution_applied'],$r['preventive_action'],$r['resolution_author']
            ];
        }

        $filterText=$this->filterDescription($pdo,$filters);
        $summaryRows=[
            ['Tickets exportados',count($records)],['Resueltos / cerrados',$resolved],['Con solución documentada',$documented],['Sin solución documentada',max(0,count($records)-$documented)],
            ['Primera respuesta promedio (min)',$countFirst?round($totalFirst/$countFirst,1):''],['Hasta resolución promedio (min)',$countResolution?round($totalResolution/$countResolution,1):''],['Cambios de estado registrados',$statusChanges],['Filtros aplicados',$filterText],['Generado',date('d/m/Y H:i:s')]
        ];

        Audit::log('REPORT_EXPORTED_XLSX','report',null,null,null,['filters'=>$filters,'rows'=>count($records)]);
        XlsxExportService::download('helpdesk_informe_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Resumen del informe','subtitle'=>$filterText,'headers'=>['Indicador','Valor'],'rows'=>$summaryRows],
            ['name'=>'Tickets','title'=>'Helpdesk Carrousel · Detalle de tickets','subtitle'=>$filterText,'headers'=>$headers,'rows'=>$rows],
        ]);
    }

    private function requireManagement():void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('reports.view')&&!Auth::can('management.view')){header('Location: '.APP_BASE_URL.'/dashboard');exit;}
    }

    private function filters():array
    {
        $today=date('Y-m-d');$monthStart=date('Y-m-01');
        $from=trim((string)($_GET['from']??$monthStart));$to=trim((string)($_GET['to']??$today));
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from))$from=$monthStart;if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to))$to=$today;
        return['from'=>$from,'to'=>$to,'park_id'=>max(0,(int)($_GET['park_id']??0)),'category_id'=>max(0,(int)($_GET['category_id']??0)),'assigned_to'=>max(0,(int)($_GET['assigned_to']??0)),'status'=>strtoupper(trim((string)($_GET['status']??''))),'priority'=>strtoupper(trim((string)($_GET['priority']??'')))];
    }

    private function where(array $f):array
    {
        $clauses=['t.deleted_at IS NULL','t.created_at>=?','t.created_at<DATE_ADD(?,INTERVAL 1 DAY)'];$params=[$f['from'],$f['to']];
        if($f['park_id']>0){$clauses[]='t.park_id=?';$params[]=$f['park_id'];}
        if($f['category_id']>0){$clauses[]='t.category_id=?';$params[]=$f['category_id'];}
        if($f['assigned_to']>0){$clauses[]='t.assigned_to=?';$params[]=$f['assigned_to'];}
        if(array_key_exists($f['status'],self::STATUS_LABELS)){$clauses[]='t.status=?';$params[]=$f['status'];}
        if(array_key_exists($f['priority'],self::PRIORITY_LABELS)){$clauses[]='t.priority=?';$params[]=$f['priority'];}
        return[' WHERE '.implode(' AND ',$clauses),$params];
    }

    private function eventsByTicket(PDO $pdo,array $ticketIds):array
    {
        $ticketIds=array_values(array_unique(array_filter(array_map('intval',$ticketIds),static fn(int $id):bool=>$id>0)));if(!$ticketIds)return[];
        $ph=implode(',',array_fill(0,count($ticketIds),'?'));
        $q=$pdo->prepare("SELECT te.ticket_id,te.event_type,te.actor_type,te.old_value,te.new_value,te.created_at,u.full_name actor_name FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id IN ({$ph}) ORDER BY te.ticket_id,te.created_at,te.id");
        $q->execute($ticketIds);$g=[];foreach($q->fetchAll() as $e)$g[(int)$e['ticket_id']][]=$e;return$g;
    }

    private function lifecycle(array $ticket,array $events):array
    {
        $created=strtotime((string)$ticket['created_at'])?:time();$initial=null;
        foreach($events as $event){$new=$this->statusFromJson($event['new_value']??null);$old=$this->statusFromJson($event['old_value']??null);if(($event['event_type']??'')==='CREATED'&&$new){$initial=$new;break;}if($old){$initial=$old;break;}}
        if(!$initial)$initial=(string)($ticket['status']??'NEW');$current=$initial;$statusStarted=$created;$dur=[];$trans=[];
        foreach($events as $event){$new=$this->statusFromJson($event['new_value']??null);if(!$new||$new===$current)continue;$at=strtotime((string)$event['created_at']);if(!$at||$at<$statusStarted)continue;$mins=max(0,(int)round(($at-$statusStarted)/60));$dur[$current]=($dur[$current]??0)+$mins;$actor=trim((string)($event['actor_name']??''));if($actor==='')$actor=($event['actor_type']??'SYSTEM')==='PUBLIC'?'Solicitante':'Sistema';$trans[]=['from_label'=>self::STATUS_LABELS[$current]??$current,'to_label'=>self::STATUS_LABELS[$new]??$new,'at'=>(string)$event['created_at'],'actor'=>$actor];$current=$new;$statusStarted=$at;}
        $closedTs=!empty($ticket['closed_at'])?(strtotime((string)$ticket['closed_at'])?:null):null;$end=$closedTs?:time();if($end<$statusStarted)$end=$statusStarted;$dur[$current]=($dur[$current]??0)+max(0,(int)round(($end-$statusStarted)/60));
        $between=static function($a,$b):?int{if(empty($a)||empty($b))return null;$x=strtotime((string)$a);$y=strtotime((string)$b);return($x&&$y&&$y>=$x)?(int)round(($y-$x)/60):null;};
        return['transitions'=>$trans,'queue_minutes'=>(int)(($dur['NEW']??0)+($dur['AVAILABLE']??0)),'work_minutes'=>(int)(($dur['IN_PROGRESS']??0)+($dur['REOPENED']??0)),'pending_minutes'=>(int)($dur['PENDING']??0),'resolved_wait_minutes'=>(int)($dur['RESOLVED']??0),'assignment_minutes'=>$between($ticket['created_at']??null,$ticket['assigned_at']??null),'first_response_minutes'=>$between($ticket['created_at']??null,$ticket['first_response_at']??null),'resolution_minutes'=>$between($ticket['created_at']??null,$ticket['resolved_at']??null),'total_minutes'=>$between($ticket['created_at']??null,$ticket['closed_at']??date('Y-m-d H:i:s'))];
    }

    private function statusFromJson($json):?string
    {
        if(!is_string($json)||trim($json)==='')return null;$x=json_decode($json,true);if(!is_array($x))return null;$s=strtoupper(trim((string)($x['status']??'')));return array_key_exists($s,self::STATUS_LABELS)?$s:null;
    }
    private function date($value):string{$ts=empty($value)?false:strtotime((string)$value);return$ts?date('d/m/Y H:i',$ts):'';}
    private function filterDescription(PDO $pdo,array $f):string
    {
        $parts=['Período '.$f['from'].' a '.$f['to']];
        if($f['park_id']>0){$q=$pdo->prepare('SELECT name FROM parks WHERE id=? LIMIT 1');$q->execute([$f['park_id']]);$parts[]='Parque '.($q->fetchColumn()?:$f['park_id']);}
        if($f['category_id']>0){$q=$pdo->prepare('SELECT name FROM ticket_categories WHERE id=? LIMIT 1');$q->execute([$f['category_id']]);$parts[]='Categoría '.($q->fetchColumn()?:$f['category_id']);}
        if($f['assigned_to']>0){$q=$pdo->prepare('SELECT full_name FROM users WHERE id=? LIMIT 1');$q->execute([$f['assigned_to']]);$parts[]='Responsable '.($q->fetchColumn()?:$f['assigned_to']);}
        if($f['status']!=='')$parts[]='Estado '.(self::STATUS_LABELS[$f['status']]??$f['status']);
        if($f['priority']!=='')$parts[]='Prioridad '.(self::PRIORITY_LABELS[$f['priority']]??$f['priority']);
        return implode(' · ',$parts);
    }
}
