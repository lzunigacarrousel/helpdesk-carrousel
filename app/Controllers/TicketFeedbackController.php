<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use App\Services\NotificationService;
use PDO;

final class TicketFeedbackController
{
    public function index(): void
    {
        Auth::requireLogin();
        $id=(int)($_GET['id']??0);
        if($id<=0){Flash::set('No encontramos esa solicitud.','info');$this->back();}

        $pdo=Database::pdo();
        $ticket=$this->requesterTicket($pdo,$id);
        if(!in_array((string)$ticket['status'],['RESOLVED','CLOSED'],true)){
            Flash::set('La solicitud todavía está en seguimiento.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
        }

        $q=$pdo->prepare("SELECT tf.* FROM ticket_feedback tf WHERE tf.ticket_id=? LIMIT 1");
        $q->execute([$id]);$feedback=$q->fetch()?:null;

        View::render('tickets/feedback',[
            'user'=>Auth::user(),
            'ticket'=>$ticket,
            'feedback'=>$feedback,
            'flash'=>Flash::pull(),
        ]);
    }

    public function submit(): void
    {
        Auth::requireLogin();Csrf::verify($_POST['_csrf']??null);
        $id=(int)Http::post('ticket_id');$score=(int)Http::post('nps_score');$comment=trim(Http::post('comment'));
        if($id<=0)throw new \RuntimeException('No encontramos esa solicitud.');
        if($score<0||$score>10)throw new \RuntimeException('Selecciona una calificación entre 0 y 10.');
        if(mb_strlen($comment)>1000)throw new \RuntimeException('Tu comentario es demasiado largo.');

        $pdo=Database::pdo();$ticket=$this->requesterTicket($pdo,$id);
        if((string)$ticket['status']==='CLOSED'){
            Flash::set('Esta solicitud ya está cerrada. Gracias por tu opinión.','success');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
        }
        if((string)$ticket['status']!=='RESOLVED')throw new \RuntimeException('Esta solicitud todavía no está lista para calificar.');

        $uid=(int)Auth::id();
        Database::transaction(function(PDO $pdo)use($id,$uid,$score,$comment):void{
            $pdo->prepare("INSERT INTO ticket_feedback(ticket_id,requester_user_id,nps_score,comment,created_at,updated_at)
                VALUES(?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE requester_user_id=VALUES(requester_user_id),nps_score=VALUES(nps_score),comment=VALUES(comment),updated_at=NOW()")
                ->execute([$id,$uid?:null,$score,$comment!==''?$comment:null]);
            $pdo->prepare("UPDATE tickets SET status='CLOSED',closed_at=NOW(),updated_at=NOW() WHERE id=? AND status='RESOLVED' AND deleted_at IS NULL")->execute([$id]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
                VALUES(?,'STATUS_CHANGED',?,'USER',?,?,?,NOW())")
                ->execute([$id,$uid,json_encode(['status'=>'RESOLVED'],JSON_UNESCAPED_UNICODE),json_encode(['status'=>'CLOSED'],JSON_UNESCAPED_UNICODE),json_encode(['source'=>'REQUESTER_CONFIRMATION','nps_score'=>$score],JSON_UNESCAPED_UNICODE)]);
        });

        Audit::log('TICKET_FEEDBACK_SUBMITTED','ticket',$id,['status'=>'RESOLVED'],['status'=>'CLOSED','nps_score'=>$score],['comment_provided'=>$comment!=='']);
        try{
            (new NotificationService())->publishTicket(
                $id,'TICKET_FEEDBACK_SUBMITTED','Solicitud confirmada y cerrada',
                'El solicitante confirmó la solución y calificó la atención con '.$score.'/10.',
                ['assignee','admins'],APP_BASE_URL.'/tickets/view?id='.$id,['nps_score'=>$score],['email'=>false,'in_app'=>true]
            );
        }catch(\Throwable $e){}
        Flash::set('Gracias. La solicitud quedó cerrada.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function reopen(): void
    {
        Auth::requireLogin();Csrf::verify($_POST['_csrf']??null);
        $id=(int)Http::post('ticket_id');$reason=trim(Http::post('reason'));
        if($id<=0)throw new \RuntimeException('No encontramos esa solicitud.');
        if(mb_strlen($reason)<5)throw new \RuntimeException('Cuéntanos brevemente qué falta o qué sigue ocurriendo.');
        if(mb_strlen($reason)>1000)throw new \RuntimeException('El comentario es demasiado largo.');

        $pdo=Database::pdo();$ticket=$this->requesterTicket($pdo,$id);
        if((string)$ticket['status']!=='RESOLVED')throw new \RuntimeException('Esta solicitud ya no puede devolverse desde su estado actual.');
        $uid=(int)Auth::id();$user=Auth::user();
        $visibleReason='Devuelto por el solicitante · Motivo de devolución: '.$reason;

        Database::transaction(function(PDO $pdo)use($id,$uid,$user,$reason,$visibleReason):void{
            $pdo->prepare("UPDATE tickets SET status='REOPENED',resolved_at=NULL,closed_at=NULL,updated_at=NOW() WHERE id=? AND status='RESOLVED' AND deleted_at IS NULL")->execute([$id]);
            $q=$pdo->prepare("INSERT INTO ticket_comments(ticket_id,author_user_id,author_name,author_email,visibility,body,created_at) VALUES(?,?,?,?, 'PUBLIC', ?,NOW())");
            $q->execute([$id,$uid?:null,$user['full_name']??null,$user['email']??null,$visibleReason]);$commentId=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
                VALUES(?,'REOPENED',?,'USER',?,?,?,NOW())")
                ->execute([$id,$uid,json_encode(['status'=>'RESOLVED'],JSON_UNESCAPED_UNICODE),json_encode(['status'=>'REOPENED'],JSON_UNESCAPED_UNICODE),json_encode(['source'=>'REQUESTER_RETURN','comment_id'=>$commentId],JSON_UNESCAPED_UNICODE)]);
        });

        Audit::log('TICKET_REOPENED_BY_REQUESTER','ticket',$id,['status'=>'RESOLVED'],['status'=>'REOPENED'],['reason'=>$reason]);
        $audiences=['assignee','admins'];if(empty($ticket['assigned_to']))$audiences[]='support_group';
        try{
            (new NotificationService())->publishTicket(
                $id,'TICKET_REOPENED','La solicitud volvió a soporte',
                ($user['full_name']??'El solicitante').' indicó que necesita más ayuda: '.mb_strimwidth($reason,0,350,'…'),
                $audiences,APP_BASE_URL.'/tickets/view?id='.$id.'#conversacion',['source'=>'REQUESTER_RETURN']
            );
        }catch(\Throwable $e){}
        Flash::set('Listo. La solicitud volvió al equipo de soporte y registramos el motivo.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    private function requesterTicket(PDO $pdo,int $id): array
    {
        $user=Auth::user();
        if(($user['access_type']??'INTERNAL')==='EXTERNAL'){
            Flash::set('Esta opción corresponde al solicitante del caso.','info');$this->back();
        }
        $uid=(int)Auth::id();$email=strtolower(trim((string)($user['email']??'')));
        $q=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,u.full_name assigned_name,tr.root_cause,tr.solution_applied,tr.preventive_action,tr.resolution_type,tr.resolved_by,ru.full_name resolved_by_name
            FROM tickets t
            LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN parks p ON p.id=t.park_id
            LEFT JOIN users u ON u.id=t.assigned_to
            LEFT JOIN ticket_resolutions tr ON tr.ticket_id=t.id
            LEFT JOIN users ru ON ru.id=tr.resolved_by
            WHERE t.id=? AND t.deleted_at IS NULL AND (t.requester_user_id=? OR LOWER(t.requester_email)=?) LIMIT 1");
        $q->execute([$id,$uid,$email]);$ticket=$q->fetch();
        if(!$ticket){Flash::set('No tienes acceso a esa solicitud.','info');$this->back();}
        return $ticket;
    }

    private function back(): never
    {
        header('Location: '.APP_BASE_URL.'/mis-tickets');exit;
    }
}
