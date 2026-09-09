<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\MailService;

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
        if($status==='PENDING'&&!isset(self::PENDING_REASONS[$pendingReason])){
            throw new \RuntimeException('Selecciona por qué el caso quedará en espera.');
        }
        if(mb_strlen($pendingNote)>500)throw new \RuntimeException('La nota de espera es demasiado larga.');
        if($status!=='PENDING'){$pendingReason='';$pendingNote='';}

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT id,ticket_number,subject,status,assigned_to,requester_email,requester_name,pending_reason_code,pending_note
            FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1");
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
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
            VALUES(?,?,'".((int)Auth::id())."','USER',?,?,?,NOW())")
            ->execute([$id,$eventType,json_encode($before,JSON_UNESCAPED_UNICODE),json_encode($after,JSON_UNESCAPED_UNICODE),json_encode(['pending_reason_label'=>$pendingReason!==''?(self::PENDING_REASONS[$pendingReason]??$pendingReason):null],JSON_UNESCAPED_UNICODE)]);

        Audit::log('TICKET_STATUS_CHANGED','ticket',$id,$before,$after);

        $label=self::STATUS_LABELS[$status]??$status;
        $message='El estado de tu solicitud cambió a: '.$label.'.';
        if($status==='PENDING'){
            $message.=' Motivo: '.self::PENDING_REASONS[$pendingReason].'.';
            if($pendingNote!=='')$message.=' '.$pendingNote;
        }
        $this->notifyRequester($ticket,$id,'Actualización de tu solicitud',$message);
        $this->notifyExternalParticipants($id,'Actualización del caso',$message);
        if($status==='REOPENED')$this->notifySupportGroup($ticket,$id,'Caso reabierto','Un caso fue reabierto y requiere seguimiento.');

        Flash::set($status==='PENDING'?'Caso puesto en espera: '.self::PENDING_REASONS[$pendingReason].'.':'Estado actualizado a '.$label.'.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    private function notifyRequester(array $ticket,int $id,string $title,string $message): void
    {
        $email=(string)($ticket['requester_email']??'');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))return;
        try{
            (new MailService())->sendTicketNotification($email,$title.' · '.$ticket['ticket_number'],$title,$message.PHP_EOL.PHP_EOL.'Caso: '.$ticket['ticket_number'],APP_BASE_URL.'/tickets/view?id='.$id);
        }catch(\Throwable $e){Logger::error($e);}
    }

    private function notifySupportGroup(array $ticket,int $id,string $title,string $message): void
    {
        if(!filter_var(SUPPORT_GROUP_EMAIL,FILTER_VALIDATE_EMAIL))return;
        try{
            (new MailService())->sendTicketNotification(SUPPORT_GROUP_EMAIL,$title.' · '.$ticket['ticket_number'],$title,$message,APP_BASE_URL.'/tickets/view?id='.$id);
        }catch(\Throwable $e){Logger::error($e);}
    }

    private function notifyExternalParticipants(int $id,string $title,string $message): void
    {
        try{
            $q=Database::pdo()->prepare("SELECT u.email,t.ticket_number FROM external_ticket_access eta JOIN users u ON u.id=eta.user_id JOIN tickets t ON t.id=eta.ticket_id
                WHERE eta.ticket_id=? AND eta.revoked_at IS NULL AND u.access_type='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL");
            $q->execute([$id]);
            $mail=new MailService();
            foreach($q->fetchAll() as $row){
                $email=(string)$row['email'];if(!filter_var($email,FILTER_VALIDATE_EMAIL))continue;
                $mail->sendTicketNotification($email,$title.' · '.$row['ticket_number'],$title,$message,APP_BASE_URL.'/tickets/view?id='.$id);
            }
        }catch(\Throwable $e){Logger::error($e);}
    }
}
