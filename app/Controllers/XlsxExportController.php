<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database};
use App\Services\{ScopeService,TicketLifecycleService,TicketReportFilterService,XlsxExportService};
use PDO;

final class XlsxExportController
{
    private const RESOLUTION_LABELS=['CONFIGURATION'=>'Configuración','RESTART'=>'Reinicio / restablecimiento','REPLACEMENT'=>'Cambio / reemplazo','PROVIDER'=>'Gestión con proveedor','USER_GUIDANCE'=>'Orientación al usuario','SOFTWARE'=>'Software / aplicación','NETWORK'=>'Red / conectividad','HARDWARE'=>'Hardware / equipo','PERMISSION'=>'Acceso / permisos','MAINTENANCE'=>'Mantenimiento','OTHER'=>'Otro'];

    public function export(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();$reportFilters=new TicketReportFilterService();$filters=$reportFilters->filters($_GET);[$where,$params]=$reportFilters->where($filters);
        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,COALESCE(req.full_name,t.requester_name) requester_name,COALESCE(req.email,t.requester_email) requester_email,COALESCE(req.phone,t.requester_phone) requester_phone,
            COALESCE(p.name,'') park_name,COALESCE(a.name,'') area_name,COALESCE(c.name,'') category_name,
            t.subject,t.description,t.priority,t.status,t.pending_reason_code,t.pending_note,COALESCE(u.full_name,'') assigned_name,t.assigned_at,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at,
            COALESCE(tr.resolution_type,'') resolution_type,COALESCE(tr.root_cause,'') root_cause,
            COALESCE(tr.solution_applied,'') solution_applied,COALESCE(tr.preventive_action,'') preventive_action,
            COALESCE(ru.full_name,'') resolution_author,
            tf.nps_score,COALESCE(tf.comment,'') feedback_comment,tf.created_at feedback_at
            FROM tickets t
            LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN users ru ON ru.id=tr.resolved_by
            LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL
            LEFT JOIN ticket_feedback tf ON tf.ticket_id=t.id
            {$where} ORDER BY t.created_at DESC");
        $q->execute($params);$records=$q->fetchAll();
        $events=$this->eventsByTicket($pdo,array_map(static fn(array $r):int=>(int)$r['id'],$records));

        $headers=['Ticket','Creado','Solicitante','Correo','Teléfono','Parque','Área','Categoría','Asunto','Descripción','Prioridad','Estado actual','Motivo de espera','Detalle de espera','Responsable','Asignado','Primera respuesta','Resuelto','Cerrado','Calificación (0-10)','Comentario de servicio','Fecha de calificación','Límite primera respuesta','Límite resolución','Min. hasta asignación','Min. primera respuesta','Min. en cola','Min. trabajando','Min. en espera','Min. resuelto antes de cierre','Min. hasta resolución','Min. totales del caso','Cambios de estado','Historial de estados','Tipo de solución','Causa encontrada','Solución aplicada','Prevención / seguimiento','Documentado por'];
        $rows=[];$documented=0;$resolved=0;$totalFirst=0;$countFirst=0;$totalResolution=0;$countResolution=0;$totalWork=0;$countWork=0;$totalPending=0;$countPending=0;$statusChanges=0;$pendingReasonCounts=[];$pendingReasonMinutes=[];
        $npsResponses=0;$npsPromoters=0;$npsDetractors=0;$npsSum=0;

        foreach($records as $r){
            $life=TicketLifecycleService::analyze($r,$events[(int)$r['id']]??[]);$history=[];
            foreach($life['transitions'] as $t){$history[]=date('d/m/Y H:i',strtotime($t['at'])).' · '.($t['from_label']??'—').' → '.($t['to_label']??'—').(!empty($t['pending_reason'])?' · '.(WorkflowController::PENDING_REASONS[$t['pending_reason']]??$t['pending_reason']):'').(!empty($t['actor'])?' · '.$t['actor']:'');}
            foreach(($life['pending_reason_changes']??[]) as $change){$history[]=date('d/m/Y H:i',strtotime((string)$change['at'])).' · Motivo de espera: '.(WorkflowController::PENDING_REASONS[$change['from']??'']??($change['from']??'—')).' → '.(WorkflowController::PENDING_REASONS[$change['to']??'']??($change['to']??'—')).(!empty($change['actor'])?' · '.$change['actor']:'');}
            if(trim((string)$r['solution_applied'])!=='')$documented++;
            if(in_array((string)$r['status'],['RESOLVED','CLOSED'],true))$resolved++;
            if($life['first_response_minutes']!==null){$totalFirst+=(int)$life['first_response_minutes'];$countFirst++;}
            if($life['resolution_minutes']!==null){$totalResolution+=(int)$life['resolution_minutes'];$countResolution++;}
            if(isset($life['work_minutes'])){$totalWork+=(int)$life['work_minutes'];$countWork++;}
            if(isset($life['pending_minutes'])){$totalPending+=(int)$life['pending_minutes'];$countPending++;}
            $statusChanges+=count($life['transitions']);
            if(!empty($r['pending_reason_code']))$pendingReasonCounts[$r['pending_reason_code']]=($pendingReasonCounts[$r['pending_reason_code']]??0)+1;
            foreach(($life['pending_reason_minutes']??[]) as $code=>$minutes)$pendingReasonMinutes[$code]=($pendingReasonMinutes[$code]??0)+(int)$minutes;
            if($r['nps_score']!==null&&$r['nps_score']!==''){
                $score=(int)$r['nps_score'];$npsResponses++;$npsSum+=$score;
                if($score>=9)$npsPromoters++;elseif($score<=6)$npsDetractors++;
            }
            $rows[]=[
                $r['ticket_number'],$this->date($r['created_at']),$r['requester_name'],$r['requester_email'],$r['requester_phone'],$r['park_name'],$r['area_name'],$r['category_name'],$r['subject'],$r['description'],
                TicketReportFilterService::PRIORITY_LABELS[$r['priority']]??$r['priority'],TicketReportFilterService::STATUS_LABELS[$r['status']]??$r['status'],WorkflowController::PENDING_REASONS[$r['pending_reason_code']]??($r['pending_reason_code']?:''),$r['pending_note'],$r['assigned_name'],$this->date($r['assigned_at']),$this->date($r['first_response_at']),$this->date($r['resolved_at']),$this->date($r['closed_at']),
                $r['nps_score']??'',$r['feedback_comment'],$this->date($r['feedback_at']),$this->date($r['first_response_due_at']),$this->date($r['resolution_due_at']),
                $life['assignment_minutes']??'', $life['first_response_minutes']??'',(int)$life['queue_minutes'],(int)$life['work_minutes'],(int)$life['pending_minutes'],(int)$life['resolved_wait_minutes'],$life['resolution_minutes']??'', $life['total_minutes']??'',count($life['transitions']),implode("\n",$history),
                self::RESOLUTION_LABELS[$r['resolution_type']]??$r['resolution_type'],$r['root_cause'],$r['solution_applied'],$r['preventive_action'],$r['resolution_author']
            ];
        }

        $nps=$npsResponses>0?round((($npsPromoters-$npsDetractors)/$npsResponses)*100):'';
        $avgScore=$npsResponses>0?round($npsSum/$npsResponses,1):'';
        $filterText=$reportFilters->description($pdo,$filters).' · '.(new ScopeService())->scopeLabel();
        $documentedPct=count($records)>0?round(($documented/count($records))*100,1):0;
        $summaryRows=[
            ['Tickets exportados',count($records)],['Resueltos / cerrados',$resolved],['Con solución documentada',$documented],['Documentados (%)',$documentedPct],['Sin solución documentada',max(0,count($records)-$documented)],
            ['Respuestas de satisfacción',$npsResponses],['NPS',$nps],['Calificación promedio',$avgScore!==''?$avgScore.'/10':''],
            ['Primera respuesta promedio (min)',$countFirst?round($totalFirst/$countFirst,1):''],
            ['Hasta resolución promedio (min)',$countResolution?round($totalResolution/$countResolution,1):''],
            ['Trabajo efectivo promedio (min)',$countWork?round($totalWork/$countWork,1):''],
            ['En espera promedio (min)',$countPending?round($totalPending/$countPending,1):''],
            ['Cambios de estado registrados',$statusChanges],['Casos actualmente en espera',array_sum($pendingReasonCounts)],['Filtros aplicados',$filterText],['Generado',date('d/m/Y H:i:s')]
        ];
        $pendingRows=[];
        foreach(WorkflowController::PENDING_REASONS as $code=>$label)$pendingRows[]=[$label,(int)($pendingReasonCounts[$code]??0),(int)($pendingReasonMinutes[$code]??0)];
        if(isset($pendingReasonMinutes['UNSPECIFIED']))$pendingRows[]=['Sin motivo registrado',0,(int)$pendingReasonMinutes['UNSPECIFIED']];
        $satisfactionRows=[['Respuestas',$npsResponses],['Promotores (9-10)',$npsPromoters],['Pasivos (7-8)',max(0,$npsResponses-$npsPromoters-$npsDetractors)],['Detractores (0-6)',$npsDetractors],['NPS',$nps],['Promedio',$avgScore!==''?$avgScore.'/10':'']];

        Audit::log('REPORT_EXPORTED_XLSX','report',null,null,null,['filters'=>$filters,'scope'=>(new ScopeService())->scopeLabel(),'rows'=>count($records),'nps_responses'=>$npsResponses]);
        XlsxExportService::download('helpdesk_informe_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Resumen del informe','subtitle'=>$filterText,'headers'=>['Indicador','Valor'],'rows'=>$summaryRows],
            ['name'=>'Tickets','title'=>'Helpdesk Carrousel · Detalle de tickets','subtitle'=>$filterText,'headers'=>$headers,'rows'=>$rows],
            ['name'=>'Satisfacción','title'=>'Helpdesk Carrousel · Satisfacción del servicio','subtitle'=>$filterText,'headers'=>['Indicador','Valor'],'rows'=>$satisfactionRows],
            ['name'=>'Esperas','title'=>'Helpdesk Carrousel · Tiempos de espera por motivo','subtitle'=>$filterText,'headers'=>['Motivo','Casos activos','Minutos históricos'],'rows'=>$pendingRows],
        ]);
    }

    private function requireManagement():void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('reports.view')&&!Auth::can('management.view')){header('Location: '.APP_BASE_URL.'/dashboard');exit;}
    }

    private function eventsByTicket(PDO $pdo,array $ticketIds):array
    {
        $ticketIds=array_values(array_unique(array_filter(array_map('intval',$ticketIds),static fn(int $id):bool=>$id>0)));if(!$ticketIds)return[];
        $ph=implode(',',array_fill(0,count($ticketIds),'?'));
        $q=$pdo->prepare("SELECT te.ticket_id,te.event_type,te.actor_type,te.old_value,te.new_value,te.created_at,u.full_name actor_name FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id IN ({$ph}) ORDER BY te.ticket_id,te.created_at,te.id");
        $q->execute($ticketIds);$g=[];foreach($q->fetchAll() as $e)$g[(int)$e['ticket_id']][]=$e;return$g;
    }

    private function date($value):string{$ts=empty($value)?false:strtotime((string)$value);return$ts?date('d/m/Y H:i',$ts):'';}

}
