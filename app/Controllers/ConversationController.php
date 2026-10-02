<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\{NotificationService,TicketAttachmentService};
use PDO;

final class ConversationController
{
    public function respond(): void
    {
        Auth::requireLogin();Csrf::verify($_POST['_csrf'] ?? null);
        $ticketId=(int)Http::post('ticket_id');$body=trim(Http::post('body'));$requestedVisibility=strtoupper(trim(Http::post('visibility','PUBLIC')));
        if($ticketId<=0)throw new \RuntimeException('No encontramos el caso.');
        $pdo=Database::pdo();[$ticket,$context]=$this->ticketContext($pdo,$ticketId);
        if(in_array((string)$ticket['status'],['CLOSED','CANCELLED'],true)){Flash::set('Este caso ya está finalizado.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);exit;}

        $isExternal=$context['is_external'];$isSupport=$context['is_support'];$canComment=$context['can_comment'];$canUpload=$context['can_upload'];
        $hasFile=isset($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        if($body===''&&!$hasFile)throw new \RuntimeException('Escribe una respuesta o adjunta un archivo.');
        if($body!==''&&!$canComment)throw new \RuntimeException('Tu acceso a este caso es solo de consulta.');
        if($hasFile&&!$canUpload)throw new \RuntimeException('Tu acceso no permite adjuntar archivos en este caso.');

        $visibility='PUBLIC';
        if($isExternal)$visibility='EXTERNAL';
        elseif($isSupport&&$requestedVisibility==='INTERNAL')$visibility='INTERNAL';
        elseif($isSupport&&$requestedVisibility==='EXTERNAL'){
            $x=$pdo->prepare("SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND revoked_at IS NULL");$x->execute([$ticketId]);
            if((int)$x->fetchColumn()<=0)throw new \RuntimeException('Este caso no tiene un proveedor participando.');
            $visibility='EXTERNAL';
        }

        $commentId=null;$attachmentId=null;
        try {
            Database::transaction(function(PDO $pdo)use($ticketId,$body,$visibility,$hasFile,$isSupport,&$commentId,&$attachmentId):void{
            if($body!==''){
                $u=Auth::user();$q=$pdo->prepare("INSERT INTO ticket_comments(ticket_id,author_user_id,author_name,author_email,visibility,body,created_at) VALUES(?,?,?,?,?,?,NOW())");
                $q->execute([$ticketId,Auth::id(),$u['full_name']??null,$u['email']??null,$visibility,$body]);$commentId=(int)$pdo->lastInsertId();
            }
            if($hasFile){
                $attachmentId=(new TicketAttachmentService())->storeUploadedFile(
                    $pdo,$ticketId,$commentId,null,$visibility,$_FILES['attachment']
                );
            }
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at) VALUES(?,'COMMENTED',?,'USER',?,?,NOW())")
                ->execute([$ticketId,Auth::id(),json_encode(['visibility'=>$visibility],JSON_UNESCAPED_UNICODE),json_encode(['comment_id'=>$commentId,'attachment_id'=>$attachmentId],JSON_UNESCAPED_UNICODE)]);
            if($isSupport&&$visibility==='PUBLIC'){
                $pdo->prepare('UPDATE tickets SET first_response_at=COALESCE(first_response_at,NOW()),updated_at=NOW() WHERE id=?')->execute([$ticketId]);
            }else{
                $pdo->prepare('UPDATE tickets SET updated_at=NOW() WHERE id=?')->execute([$ticketId]);
            }
            });
        } catch (\RuntimeException $e) {
            Logger::error($e);
            Flash::set($e->getMessage(),'error');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion');
            exit;
        }

        Audit::log('TICKET_RESPONSE_ADDED','ticket',$ticketId,null,['visibility'=>$visibility,'comment_id'=>$commentId,'attachment_id'=>$attachmentId]);
        $this->notifyConversation($ticketId,$ticket,$context,$visibility,$body,$hasFile);
        $message=match($visibility){'INTERNAL'=>'Conversación interna guardada.','EXTERNAL'=>$isSupport?'Actualización enviada al proveedor.':'Actualización enviada.','PUBLIC'=>'Respuesta enviada correctamente.',default=>'Actualización guardada.'};
        Flash::set($message,'success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion');exit;
    }

    public function download(): void
    {
        Auth::requireLogin();$id=(int)($_GET['id']??0);if($id<=0)throw new \RuntimeException('Archivo no disponible.');
        $pdo=Database::pdo();$q=$pdo->prepare("SELECT ta.*,t.requester_user_id,t.requester_email,t.assigned_to,t.case_type,t.visibility_mode,t.deleted_at FROM ticket_attachments ta JOIN tickets t ON t.id=ta.ticket_id WHERE ta.id=? LIMIT 1");$q->execute([$id]);$a=$q->fetch();if(!$a||$a['deleted_at']!==null)throw new \RuntimeException('Archivo no disponible.');
        [,$context]=$this->ticketContext($pdo,(int)$a['ticket_id']);$visibility=(string)$a['visibility'];
        if($visibility==='INTERNAL'&&!$context['is_support']){Flash::set('Ese archivo es de uso interno.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.(int)$a['ticket_id']);exit;}
        if($visibility==='EXTERNAL'&&!$context['is_support']&&!$context['is_external']){Flash::set('Ese archivo forma parte de la colaboración con el proveedor.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.(int)$a['ticket_id']);exit;}
        $path=APP_ROOT.'/'.ltrim((string)$a['storage_path'],'/\\');if(!is_file($path))throw new \RuntimeException('Archivo no disponible.');
        header('Content-Type: '.((string)$a['mime_type']?:'application/octet-stream'));header('Content-Length: '.(string)filesize($path));header('Content-Disposition: attachment; filename="'.rawurlencode((string)$a['original_name']).'"');header('X-Content-Type-Options: nosniff');readfile($path);exit;
    }

    private function ticketContext(PDO $pdo,int $ticketId): array
    {
        $q=$pdo->prepare("SELECT t.*,u.email assigned_email,u.full_name assigned_name FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");$q->execute([$ticketId]);$ticket=$q->fetch();if(!$ticket)throw new \RuntimeException('No encontramos el caso.');
        $user=Auth::user();$uid=(int)Auth::id();$email=strtolower((string)($user['email']??''));$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
        $isSupport=Auth::can('tickets.view_all')||Auth::can('tickets.change_status')||(int)($ticket['assigned_to']??0)===$uid;$isRequester=(int)($ticket['requester_user_id']??0)===$uid||strtolower((string)$ticket['requester_email'])===$email;$canComment=$isRequester||$isSupport;$canUpload=$isRequester||$isSupport;
        if($isExternal){
            $a=$pdo->prepare("SELECT can_comment,can_upload FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1");$a->execute([$ticketId,$uid]);$access=$a->fetch();
            if(!$access||$ticket['case_type']!=='SPECIAL'||$ticket['visibility_mode']!=='EXTERNAL_ALLOWED'){Flash::set('Ese caso no está habilitado para tu cuenta.','info');header('Location: '.APP_BASE_URL.'/mis-tickets');exit;}
            $canComment=(bool)$access['can_comment'];$canUpload=(bool)$access['can_upload'];$isSupport=false;$isRequester=false;
        }elseif(!$isRequester&&!$isSupport){Flash::set('No tienes acceso a ese caso.','info');header('Location: '.APP_BASE_URL.'/dashboard');exit;}
        return[$ticket,['is_external'=>$isExternal,'is_support'=>$isSupport,'is_requester'=>$isRequester,'can_comment'=>$canComment,'can_upload'=>$canUpload]];
    }

    private function notifyConversation(int $ticketId,array $ticket,array $context,string $visibility,string $body,bool $hasFile): void
    {
        try{
            $actor=Auth::user();$actorName=(string)($actor['full_name']??'Usuario');$summary=$body!==''?mb_strimwidth($body,0,360,'…'):'Se adjuntó un archivo al caso.';if($hasFile&&$body!=='')$summary.=' También se adjuntó un archivo.';$service=new NotificationService();
            if($visibility==='INTERNAL'){
                $service->publishTicket($ticketId,'INTERNAL_NOTE_ADDED','Nueva conversación interna · '.$ticket['ticket_number'],$actorName.' agregó un mensaje interno: '.$summary,['assignee','admins'],APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion',[],['email'=>false,'in_app'=>true]);return;
            }
            if($visibility==='EXTERNAL'){
                $audiences=$context['is_external']?['assignee','admins']:['externals','assignee','admins'];if($context['is_external']&&empty($ticket['assigned_to']))$audiences[]='support_group';
                $service->publishTicket($ticketId,'EXTERNAL_RESPONSE_ADDED','Actualización de proveedor · '.$ticket['ticket_number'],$actorName.' agregó una actualización: '.$summary,$audiences,APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion',['has_attachment'=>$hasFile]);return;
            }
            $audiences=['requester','assignee','externals','admins'];if(!$context['is_support']&&empty($ticket['assigned_to']))$audiences[]='support_group';
            $service->publishTicket($ticketId,'PUBLIC_RESPONSE_ADDED','Nueva actualización · '.$ticket['ticket_number'],$actorName.' agregó una respuesta: '.$summary,$audiences,APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion',['has_attachment'=>$hasFile]);
        }catch(\Throwable $e){Logger::error($e);}
    }
}
