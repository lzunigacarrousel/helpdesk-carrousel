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
     * support y support_group se resuelven contra los integrantes activos del equipo del ticket.
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
    ): array {
        if($ticketId<=0 || trim($eventKey)==='' || trim($title)==='') return ['event_id'=>null,'emails'=>[]];
        $pdo=Database::pdo();
        $actorId=(int)(Auth::id()??0);
        $actionUrl=$this->normalizeActionUrl($actionUrl ?: APP_BASE_URL.'/tickets/view?id='.$ticketId);
        $recipients=$this->resolveTicketRecipients($pdo,$ticketId,$audiences,$actorId);
        if(!$recipients) return ['event_id'=>null,'emails'=>[]];

        $eventId=$this->insertEvent($pdo,$ticketId,$eventKey,$actorId,$payload);
        $globalEmail=array_key_exists('email',$options)?(bool)$options['email']:true;
        $globalInApp=array_key_exists('in_app',$options)?(bool)$options['in_app']:true;
        $mail=new MailService();$emailResults=[];

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
                $emailResults[]=$this->deliverEmail(
                    $pdo,$eventId,$userId?:null,$email,$title,$message,$actionUrl,
                    fn()=> $mail->sendTicketNotification($email,$title,$title,$message,$actionUrl,'Abrir en Helpdesk')
                );
            }
        }
        return ['event_id'=>$eventId,'emails'=>$emailResults];
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
    ): array {
        $pdo=Database::pdo();
        $actorId=(int)(Auth::id()??0);
        $eventId=$this->insertEvent($pdo,$ticketId,$eventKey,$actorId,[]);
        $email=strtolower(trim($email));
        $actionUrl=$this->normalizeActionUrl($actionUrl);
        $result=['event_id'=>$eventId,'email_status'=>null,'email_delivery_id'=>null];

        if($inApp && ($userId??0)>0 && (int)$userId!==$actorId){
            $pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,sent_at,created_at,updated_at) VALUES(?,'IN_APP',?,?,?,?,?,'SENT',NOW(),NOW(),NOW())")
                ->execute([$eventId,(int)$userId,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
        }
        if($emailChannel && filter_var($email,FILTER_VALIDATE_EMAIL)){
            $delivery=$this->deliverEmail(
                $pdo,$eventId,($userId??0)>0?(int)$userId:null,$email,$title,$message,$actionUrl,
                fn()=> (new MailService())->sendTicketNotification($email,$title,$title,$message,$actionUrl,'Abrir en Helpdesk')
            );
            $result['email_status']=$delivery['status'];$result['email_delivery_id']=$delivery['id'];
        }
        return $result;
    }

    /** Registra y envía OTP sin guardar el código en la trazabilidad. */
    public function sendOtpDelivery(int $userId,string $email,string $code,int $minutes): array
    {
        $pdo=Database::pdo();$email=strtolower(trim($email));
        if($userId<=0||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('No fue posible preparar el código de acceso.');
        $eventId=$this->insertEvent($pdo,null,'OTP_REQUESTED',0,['recipient_user_id'=>$userId,'expires_minutes'=>$minutes]);
        $message='Código temporal solicitado. Vence en '.$minutes.' minutos.';
        $delivery=$this->deliverEmail(
            $pdo,$eventId,$userId,$email,'Código de acceso',$message,null,
            fn()=> (new MailService())->sendOtp($email,$code,$minutes),true
        );
        return ['event_id'=>$eventId,'email_status'=>$delivery['status'],'email_delivery_id'=>$delivery['id']];
    }

    /** Reintenta solo entregas normales; un OTP vencido siempre debe solicitarse de nuevo. */
    public function retryEmailDelivery(int $deliveryId): string
    {
        if($deliveryId<=0)throw new \RuntimeException('No encontramos el correo que deseas reintentar.');
        if(MailService::mode()!=='smtp')throw new \RuntimeException('El Helpdesk está en modo de prueba; activa SMTP antes de reintentar correos.');
        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT d.*,e.event_key FROM notification_deliveries d JOIN notification_events e ON e.id=d.event_id WHERE d.id=? AND d.channel='EMAIL' LIMIT 1");
        $q->execute([$deliveryId]);$delivery=$q->fetch();
        if(!$delivery)throw new \RuntimeException('No encontramos el correo que deseas reintentar.');
        if((string)$delivery['event_key']==='OTP_REQUESTED')throw new \RuntimeException('Los códigos de acceso no se reenvían. Solicita un código nuevo.');
        if(!in_array((string)$delivery['status'],['FAILED','PENDING'],true))throw new \RuntimeException('Ese correo ya no necesita reintento.');
        $email=strtolower(trim((string)$delivery['recipient_email']));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('El destinatario del correo no es válido.');

        try{
            $result=(new MailService())->sendTicketNotification(
                $email,(string)($delivery['title']?:APP_NAME),(string)($delivery['title']?:'Actualización'),
                (string)($delivery['message']?:'Hay una actualización disponible.'),
                $delivery['action_url']? $this->normalizeActionUrl((string)$delivery['action_url']):null,
                'Abrir en Helpdesk'
            );
            if($result===MailService::RESULT_SENT){
                $pdo->prepare("UPDATE notification_deliveries SET status='SENT',attempts=attempts+1,sent_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
                return 'SENT';
            }
            $pdo->prepare("UPDATE notification_deliveries SET status='SKIPPED',last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
            return 'SKIPPED';
        }catch(\Throwable $e){
            Logger::error($e);
            $pdo->prepare("UPDATE notification_deliveries SET status='FAILED',attempts=attempts+1,last_error=?,updated_at=NOW() WHERE id=?")
                ->execute([mb_strimwidth($e->getMessage(),0,1000,'…'),$deliveryId]);
            throw new \RuntimeException('El correo no pudo enviarse. Revisa la configuración o intenta más tarde.');
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

    private function insertEvent(PDO $pdo,?int $ticketId,string $eventKey,int $actorId,array $payload): int
    {
        $event=$pdo->prepare("INSERT INTO notification_events(event_key,ticket_id,actor_user_id,payload_json,created_at) VALUES(?,?,?,?,NOW())");
        $event->execute([
            strtoupper(trim($eventKey)),
            ($ticketId??0)>0?$ticketId:null,
            $actorId>0?$actorId:null,
            json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
        return (int)$pdo->lastInsertId();
    }

    private function deliverEmail(
        PDO $pdo,int $eventId,?int $userId,string $email,string $title,string $message,?string $actionUrl,callable $sender,bool $throwOnFailure=false
    ): array {
        $actionUrl=$this->normalizeActionUrl($actionUrl);
        $q=$pdo->prepare("INSERT INTO notification_deliveries(event_id,channel,recipient_user_id,recipient_email,title,message,action_url,status,attempts,created_at,updated_at) VALUES(?,'EMAIL',?,?,?,?,?,'PENDING',0,NOW(),NOW())");
        $q->execute([$eventId,$userId,$email,$title,mb_strimwidth($message,0,500,'…'),$actionUrl]);
        $deliveryId=(int)$pdo->lastInsertId();
        try{
            $result=$sender();
            if($result===MailService::RESULT_LOGGED){
                $pdo->prepare("UPDATE notification_deliveries SET status='SKIPPED',attempts=0,sent_at=NULL,last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
                return ['id'=>$deliveryId,'status'=>'SKIPPED'];
            }
            $pdo->prepare("UPDATE notification_deliveries SET status='SENT',attempts=attempts+1,sent_at=NOW(),last_error=NULL,updated_at=NOW() WHERE id=?")->execute([$deliveryId]);
            return ['id'=>$deliveryId,'status'=>'SENT'];
        }catch(\Throwable $e){
            Logger::error($e);
            $pdo->prepare("UPDATE notification_deliveries SET status='FAILED',attempts=attempts+1,last_error=?,updated_at=NOW() WHERE id=?")
                ->execute([mb_strimwidth($e->getMessage(),0,1000,'…'),$deliveryId]);
            if($throwOnFailure)throw $e;
            return ['id'=>$deliveryId,'status'=>'FAILED'];
        }
    }

    private function normalizeActionUrl(?string $url): ?string
    {
        $url=trim((string)$url);if($url==='')return null;
        if(APP_CANONICAL_URL===APP_BASE_URL)return $url;
        if(str_starts_with($url,APP_BASE_URL))return APP_CANONICAL_URL.substr($url,strlen(APP_BASE_URL));
        if(str_starts_with($url,'/'))return APP_CANONICAL_URL.$url;
        return $url;
    }

    private function resolveTicketRecipients(PDO $pdo,int $ticketId,array $audiences,int $actorId): array
    {
        $audiences=array_values(array_unique(array_map('strtolower',$audiences)));
        $q=$pdo->prepare("SELECT t.requester_user_id,t.requester_email,t.requester_name,t.assigned_to,t.support_team_id,u.email assigned_email,u.full_name assigned_name FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");
        $q->execute([$ticketId]);$ticket=$q->fetch();if(!$ticket)return [];
        $recipients=[];
        $add=function(?int $userId,?string $email,?string $name,bool $emailChannel,bool $inApp)use(&$recipients,$actorId):void{
            $userId=(int)($userId??0);$email=strtolower(trim((string)$email));
            if($userId>0&&$userId===$actorId)return;
            if($userId<=0&&!filter_var($email,FILTER_VALIDATE_EMAIL))return;
            $key=$email!==''?'e:'.$email:'u:'.$userId;
            if(isset($recipients[$key])){
                if(!$recipients[$key]['user_id']&&$userId>0)$recipients[$key]['user_id']=$userId;
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
        if(in_array('support',$audiences,true)||in_array('support_group',$audiences,true)){
            $teamId=(int)($ticket['support_team_id']??0);
            if($teamId<=0){
                $team=$pdo->prepare("SELECT id FROM support_teams WHERE code='IT' AND is_active=1 LIMIT 1");$team->execute();$teamId=(int)($team->fetchColumn()?:0);
            }
            if($teamId>0){
                $s=$pdo->prepare("SELECT u.id,u.email,u.full_name FROM support_team_members stm JOIN support_teams st ON st.id=stm.team_id AND st.is_active=1 JOIN users u ON u.id=stm.user_id WHERE stm.team_id=? AND stm.is_active=1 AND stm.ended_at IS NULL AND u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL ORDER BY u.full_name");
                $s->execute([$teamId]);foreach($s->fetchAll() as $row)$add((int)$row['id'],(string)$row['email'],(string)$row['full_name'],true,true);
            }
        }
        if(in_array('admins',$audiences,true)){
            $s=$pdo->query("SELECT u.id,u.email,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN')");
            foreach($s->fetchAll() as $row)$add((int)$row['id'],(string)$row['email'],(string)$row['full_name'],false,true);
        }
        return array_values($recipients);
    }
}
