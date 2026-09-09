<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth,Database,Logger};
use PDO;

final class NotificationService
{
    /**
     * Publica una novedad de ticket y la entrega por correo y/o dentro del Helpdesk.
     * Audiencias soportadas: requester, assignee, externals, support, admins, support_group.
     */
    public function publishTicket(
        int $ticketId,
        string $eventKey,
        string $title,
        string $message,
        array $audiences,
        ?string $actionUrl=null,
        array $payload=[],
        array $options=[]
    ): void {
        if($ticketId<=0 || trim($eventKey)==='' || trim($title)==='') return;
        $pdo=Database::pdo();
        $actorId=(int)(Auth::id()??0);
        $actionUrl=$actionUrl ?: APP_BASE_URL.'/tickets/view?id='.$ticketId;
        $recipients=$this->resolveTicketRecipients($pdo,$ticketId,$audiences,$actorId);
        if(!$recipients) return;

        $event=$pdo->prepare("INSERT INTO notification_events(event_key,ticket_id,actor_user_id,payload_json,created_at) VALUES(?,?,?,?,NOW())");
        $event->execute([
            strtoupper(trim($eventKey)),
            $ticketId,
            $actorId>0?$actorId:null,
            json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        $eventId=(int)$pdo->lastInsertId();
        $globalEmail=array_key_exists('email',$options)?(bool)$options['email']:true;
        $globalInApp=array_key_exists('in_app',$options)?(bool)$options['in_app']:true;
        $mail=new MailService();

        foreach($recipients as $recipient){
            $userId=(int)($recipient['user_id']??0);
            $email=strtolower(trim((string)($recipient['email']??'')));
            $allowInApp=$globalInApp && !empty($recipient['in_app']) && $userId>0;
            $allowEmail=$globalEmail && !empty($recipient['email_channel']) && filter_var($email,FILTER_VALIDATE_EMAIL);

            if($allowInApp){
                $q=$pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,sent_at,created_at,updated_at) VALUES(?,'IN_APP',?,?,?,?,?,'SENT',NOW(),NOW(),NOW())");
                $q->execute([$eventId,$userId,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
            }

            if($allowEmail){
                $q=$pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,attempts,created_at,updated_at) VALUES(?,'EMAIL',?,?,?,?,?,'PENDING',0,NOW(),NOW())");
                $q->execute([$eventId,$userId>0?$userId:null,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
                $deliveryId=(int)$pdo->lastInsertId();
                try{
                    $mail->sendTicketNotification($email,$title,$title,$message,$actionUrl,'Abrir en Helpdesk');
                    $pdo->prepare("UPDATE notification_deliveries SET status='SENT',attempts=attempts+1,sent_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
                }catch(\Throwable $e){
                    Logger::error($e);
                    $pdo->prepare("UPDATE notification_deliveries SET status='FAILED',attempts=attempts+1,last_error=?,updated_at=NOW() WHERE id=?")->execute([mb_strimwidth($e->getMessage(),0,1000,'…'),$deliveryId]);
                }
            }
        }
    }

    public function notifyUser(
        ?int $userId,
        string $email,
        ?int $ticketId,
        string $eventKey,
        string $title,
        string $message,
        string $actionUrl,
        bool $emailChannel=true,
        bool $inApp=true
    ): void {
        $pdo=Database::pdo();
        $actorId=(int)(Auth::id()??0);
        $event=$pdo->prepare("INSERT INTO notification_events(event_key,ticket_id,actor_user_id,payload_json,created_at) VALUES(?,?,?,?,NOW())");
        $event->execute([strtoupper(trim($eventKey)),$ticketId,$actorId>0?$actorId:null,json_encode([],JSON_UNESCAPED_UNICODE)]);
        $eventId=(int)$pdo->lastInsertId();
        $email=strtolower(trim($email));
        if($inApp && ($userId??0)>0 && (int)$userId!==$actorId){
            $pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,sent_at,created_at,updated_at) VALUES(?,'IN_APP',?,?,?,?,?,'SENT',NOW(),NOW(),NOW())")
                ->execute([$eventId,(int)$userId,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
        }
        if($emailChannel && filter_var($email,FILTER_VALIDATE_EMAIL)){
            $pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,attempts,created_at,updated_at) VALUES(?,'EMAIL',?,?,?,?,?,'PENDING',0,NOW(),NOW())")
                ->execute([$eventId,($userId??0)>0?(int)$userId:null,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
            $deliveryId=(int)$pdo->lastInsertId();
            try{
                (new MailService())->sendTicketNotification($email,$title,$title,$message,$actionUrl,'Abrir en Helpdesk');
                $pdo->prepare("UPDATE notification_deliveries SET status='SENT',attempts=attempts+1,sent_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
            }catch(\Throwable $e){
                Logger::error($e);
                $pdo->prepare("UPDATE notification_deliveries SET status='FAILED',attempts=attempts+1,last_error=?,updated_at=NOW() WHERE id=?")->execute([mb_strimwidth($e->getMessage(),0,1000,'…'),$deliveryId]);
            }
        }
    }

    public function recentForUser(int $userId,int $limit=8): array
    {
        if($userId<=0) return [];
        $limit=max(1,min(20,$limit));
        $sql="SELECT d.id,d.title,d.message,d.action_url,d.read_at,d.created_at,e.event_key,e.ticket_id
              FROM notification_deliveries d
              JOIN notification_events e ON e.id=d.event_id
              WHERE d.channel='IN_APP' AND d.recipient_user_id=?
              ORDER BY d.created_at DESC,d.id DESC LIMIT {$limit}";
        $q=Database::pdo()->prepare($sql);$q->execute([$userId]);
        return $q->fetchAll()?:[];
    }

    public function unreadCount(int $userId): int
    {
        if($userId<=0) return 0;
        $q=Database::pdo()->prepare("SELECT COUNT(*) FROM notification_deliveries WHERE channel='IN_APP' AND recipient_user_id=? AND read_at IS NULL");
        $q->execute([$userId]);return (int)$q->fetchColumn();
    }

    public function markRead(int $deliveryId,int $userId): void
    {
        if($deliveryId<=0||$userId<=0)return;
        Database::pdo()->prepare("UPDATE notification_deliveries SET read_at=COALESCE(read_at,NOW()),updated_at=NOW() WHERE id=? AND channel='IN_APP' AND recipient_user_id=?")
            ->execute([$deliveryId,$userId]);
    }

    public function markAllRead(int $userId): void
    {
        if($userId<=0)return;
        Database::pdo()->prepare("UPDATE notification_deliveries SET read_at=COALESCE(read_at,NOW()),updated_at=NOW() WHERE channel='IN_APP' AND recipient_user_id=? AND read_at IS NULL")
            ->execute([$userId]);
    }

    private function resolveTicketRecipients(PDO $pdo,int $ticketId,array $audiences,int $actorId): array
    {
        $audiences=array_values(array_unique(array_map('strtolower',$audiences)));
        $q=$pdo->prepare("SELECT t.requester_user_id,t.requester_email,t.requester_name,t.assigned_to,u.email assigned_email,u.full_name assigned_name FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");
        $q->execute([$ticketId]);$ticket=$q->fetch();if(!$ticket)return [];
        $recipients=[];
        $add=function(?int $userId,?string $email,?string $name,bool $emailChannel,bool $inApp)use(&$recipients,$actorId):void{
            $userId=(int)($userId??0);$email=strtolower(trim((string)$email));
            if($userId>0&&$userId===$actorId)return;
            if($userId<=0&&!filter_var($email,FILTER_VALIDATE_EMAIL))return;
            $key=$userId>0?'u:'.$userId:'e:'.$email;
            if(isset($recipients[$key])){
                $recipients[$key]['email_channel']=$recipients[$key]['email_channel']||$emailChannel;
                $recipients[$key]['in_app']=$recipients[$key]['in_app']||$inApp;
                return;
            }
            $recipients[$key]=['user_id'=>$userId?:null,'email'=>$email,'name'=>$name?:'Usuario','email_channel'=>$emailChannel,'in_app'=>$inApp];
        };

        if(in_array('requester',$audiences,true)){
            $add((int)($ticket['requester_user_id']??0),(string)($ticket['requester_email']??''),(string)($ticket['requester_name']??''),true,true);
        }
        if(in_array('assignee',$audiences,true)){
            $add((int)($ticket['assigned_to']??0),(string)($ticket['assigned_email']??''),(string)($ticket['assigned_name']??''),true,true);
        }
        if(in_array('externals',$audiences,true)){
            $x=$pdo->prepare("SELECT u.id,u.email,u.full_name FROM external_ticket_access eta JOIN users u ON u.id=eta.user_id WHERE eta.ticket_id=? AND eta.revoked_at IS NULL AND u.access_type='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL");
            $x->execute([$ticketId]);foreach($x->fetchAll() as $row)$add((int)$row['id'],(string)$row['email'],(string)$row['full_name'],true,true);
        }
        if(in_array('support',$audiences,true)||in_array('admins',$audiences,true)){
            $roles=in_array('support',$audiences,true)?['ADMIN','SEMIADMIN','TECHNICIAN']:['ADMIN','SEMIADMIN'];
            $marks=implode(',',array_fill(0,count($roles),'?'));
            $s=$pdo->prepare("SELECT u.id,u.email,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN ({$marks})");
            $s->execute($roles);foreach($s->fetchAll() as $row)$add((int)$row['id'],(string)$row['email'],(string)$row['full_name'],false,true);
        }
        if(in_array('support_group',$audiences,true) && filter_var(SUPPORT_GROUP_EMAIL,FILTER_VALIDATE_EMAIL)){
            $add(null,SUPPORT_GROUP_EMAIL,'Equipo de Sistemas',true,false);
        }
        return array_values($recipients);
    }
}
