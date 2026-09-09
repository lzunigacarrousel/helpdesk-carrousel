<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\NotificationService;

final class WorkflowController
{
    private const STATUS_LABELS=[
        'NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera',
        'RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'
    ];

    public const PENDING_REASONS=[
        'WAITING_USER'=>'Esperando información del usuario',
        'WAITING_PROVIDER'=>'Esperando proveedor',
        'WAITING_PURCHASE'=>'Esperando compra o repuesto',
        'WAITING_VISIT'=>'Esperando visita o intervención en sitio',
        'WAITING_APPROVAL'=>'Esperando aprobación',
        'WAITING_THIRD_PARTY'=>'Esperando a un tercero',
        'OTHER'=>'Otro motivo de espera',
    ];

    public function changeStatus(): void
    {
        Auth::requirePermission('tickets.change_status');
        Csrf::verify($_POST['_csrf']??null);

        $id=(int)Http::post('ticket_id');
        $status=strtoupper(trim(Http::post('status')));
        $allowed=['IN_PROGRESS','PENDING','RESOLVED','CLOSED','REOPENED','CANCELLED'];
        if($id<=0||!in_array($status,$allowed,true))throw new \RuntimeException('Estado no válido.');

        $pendingReason=strtoupper(trim(Http::post('pending_reason_code')));
        $pendingNote=trim(Http::post('pending_note'));
        if($status==='PENDING'&&!isset(self::PENDING_REASONS[$pendingReason]))throw new \RuntimeException('Selecciona por qué el caso quedará en espera.');
        if(mb_strlen($pendingNote)>500)throw new \RuntimeException('La nota de espera es demasiado larga.');
        if($status!=='PENDING'){$pendingReason='';$pendingNote='';}

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT id,ticket_number,subject,status,assigned_to,requester_email,requester_name,pending_reason_code,pending_note FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1");
        $q->execute([$id]);$ticket=$q->fetch();
        if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');

        if(!Auth::can('tickets.reassign')&&(int)($ticket['assigned_to']??0)!==(int)Auth::id()){
            Flash::set('Solo la persona responsable puede actualizar este caso.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
        }

        $oldStatus=(string)$ticket['status'];
        $oldReason=(string)($ticket['pending_reason_code']??'');
        $oldNote=(string)($ticket['pending_note']??'');
        if($oldStatus===$status&&$oldReason===$pendingReason&&$oldNote===$pendingNote){
            Flash::set('El caso ya tiene ese estado.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
        }

        $resolvedSql=$status==='RESOLVED'?'resolved_at=NOW(),':($status==='REOPENED'?'resolved_at=NULL,':'');
        $closedSql=in_array($status,['CLOSED','CANCELLED'],true)?'closed_at=NOW(),':($status==='REOPENED'?'closed_at=NULL,':'');
        $pdo->prepare("UPDATE tickets SET status=?,pending_reason_code=?,pending_note=?,{$resolvedSql}{$closedSql}updated_at=NOW() WHERE id=?")
            ->execute([$status,$pendingReason!==''?$pendingReason:null,$pendingNote!==''?$pendingNote:null,$id]);

        $before=['status'=>$oldStatus,'pending_reason_code'=>$oldReason?:null,'pending_note'=>$oldNote?:null];
        $after=['status'=>$status,'pending_reason_code'=>$pendingReason?:null,'pending_note'=>$pendingNote?:null];
        $eventType=$oldStatus===$status?'PENDING_REASON_CHANGED':'STATUS_CHANGED';
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at) VALUES(?,?,?,'USER',?,?,?,NOW())")
            ->execute([
                $id,$eventType,(int)Auth::id(),
                json_encode($before,JSON_UNESCAPED_UNICODE),
                json_encode($after,JSON_UNESCAPED_UNICODE),
                json_encode(['pending_reason_label'=>$pendingReason!==''?(self::PENDING_REASONS[$pendingReason]??$pendingReason):null],JSON_UNESCAPED_UNICODE),
            ]);

        Audit::log('TICKET_STATUS_CHANGED','ticket',$id,$before,$after);

        $label=self::STATUS_LABELS[$status]??$status;
        $message='El caso '.$ticket['ticket_number'].' cambió de '.(self::STATUS_LABELS[$oldStatus]??$oldStatus).' a '.$label.'.';
        if($status==='PENDING'){
            $message.=' Motivo: '.self::PENDING_REASONS[$pendingReason].'.';
            if($pendingNote!=='')$message.=' Detalle: '.$pendingNote;
        }
        $audiences=['requester','assignee','externals','admins'];
        if($status==='REOPENED'){$audiences[]='support';$audiences[]='support_group';}
        try{
            (new NotificationService())->publishTicket(
                $id,
                $eventType,
                'Estado actualizado · '.$ticket['ticket_number'],
                $message,
                $audiences,
                APP_BASE_URL.'/tickets/view?id='.$id,
                ['old_status'=>$oldStatus,'new_status'=>$status,'pending_reason'=>$pendingReason?:null]
            );
        }catch(\Throwable $e){Logger::error($e);}

        Flash::set($status==='PENDING'?'Caso puesto en espera: '.self::PENDING_REASONS[$pendingReason].'.':'Estado actualizado a '.$label.'.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }
}
