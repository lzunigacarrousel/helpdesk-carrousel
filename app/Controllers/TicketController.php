<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger,View};
use App\Services\MailService;
use PDO;

final class TicketController
{
    public function publicHome(): void
    {
        View::render('tickets/public_home',['user'=>Auth::user(),'flash'=>Flash::pull()]);
    }

    public function publicCreate(): void
    {
        $pdo=Database::pdo();
        View::render('tickets/public_create',[
            'user'=>Auth::user(),
            'categories'=>$pdo->query("SELECT id,name,default_priority FROM ticket_categories WHERE is_active=1 ORDER BY name")->fetchAll(),
            'parks'=>$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll(),
            'areas'=>$pdo->query("SELECT id,name FROM areas WHERE is_active=1 ORDER BY name")->fetchAll(),
            'flash'=>Flash::pull(),
        ]);
    }

    public function publicStore(): void
    {
        Csrf::verify($_POST['_csrf']??null);
        $name=trim(Http::post('name'));
        $email=strtolower(trim(Http::post('email')));
        $phone=trim(Http::post('phone'));
        $parkId=(int)Http::post('park_id');
        $areaId=(int)Http::post('area_id');
        $categoryId=(int)Http::post('category_id');
        $subject=trim(Http::post('subject'));
        $description=trim(Http::post('description'));

        if(mb_strlen($name)<3)throw new \RuntimeException('Ingresa tu nombre completo.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Ingresa un correo válido.');
        if($categoryId<=0)throw new \RuntimeException('Selecciona una categoría.');
        if(mb_strlen($subject)<5)throw new \RuntimeException('El asunto debe tener al menos 5 caracteres.');
        if(mb_strlen($description)<10)throw new \RuntimeException('Describe el problema con más detalle.');

        $pdo=Database::pdo();
        $this->rateLimit($email);
        $c=$pdo->prepare('SELECT id,default_priority FROM ticket_categories WHERE id=? AND is_active=1 LIMIT 1');
        $c->execute([$categoryId]);
        $category=$c->fetch();
        if(!$category)throw new \RuntimeException('La categoría seleccionada no está disponible.');
        if($parkId>0&&!$this->activeExists('parks',$parkId))throw new \RuntimeException('El parque seleccionado no está disponible.');
        if($areaId>0&&!$this->activeExists('areas',$areaId))throw new \RuntimeException('El área seleccionada no está disponible.');

        $priority=(string)$category['default_priority'];
        $requesterUserId=null;
        $u=$pdo->prepare('SELECT id FROM users WHERE email=? AND email_verified_at IS NOT NULL AND deleted_at IS NULL LIMIT 1');
        $u->execute([$email]);
        $found=$u->fetchColumn();
        if($found)$requesterUserId=(int)$found;
        $teamId=$pdo->query("SELECT id FROM support_teams WHERE code='IT' AND is_active=1 LIMIT 1")->fetchColumn()?:null;
        [$slaId,$firstDue,$resolutionDue]=$this->sla($categoryId,$priority);

        $ticket=Database::transaction(function(PDO $pdo)use($requesterUserId,$email,$name,$phone,$parkId,$areaId,$categoryId,$teamId,$subject,$description,$priority,$slaId,$firstDue,$resolutionDue){
            $s=$pdo->prepare("INSERT INTO tickets(ticket_number,case_type,visibility_mode,origin,requester_user_id,requester_email,requester_name,requester_phone,park_id,area_id,category_id,support_team_id,subject,description,priority,status,sla_policy_id,first_response_due_at,resolution_due_at,source_ip,created_at,updated_at) VALUES('', 'NORMAL','INTERNAL','PUBLIC_WEB',?,?,?,?,?,?,?,?,?,?,?,'AVAILABLE',?,?,?,?,NOW(),NOW())");
            $s->execute([$requesterUserId,$email,$name,$phone?:null,$parkId?:null,$areaId?:null,$categoryId,$teamId?:null,$subject,$description,$priority,$slaId,$firstDue,$resolutionDue,Http::ip()]);
            $id=(int)$pdo->lastInsertId();
            $number='HD-'.date('Y').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);
            $pdo->prepare('UPDATE tickets SET ticket_number=? WHERE id=?')->execute([$number,$id]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at) VALUES(?,'CREATED',NULL,'PUBLIC',?,?,NOW())")
                ->execute([$id,json_encode(['status'=>'AVAILABLE','priority'=>$priority],JSON_UNESCAPED_UNICODE),json_encode(['origin'=>'PUBLIC_WEB'],JSON_UNESCAPED_UNICODE)]);
            return ['id'=>$id,'number'=>$number];
        });

        Audit::log('TICKET_CREATED_PUBLIC','ticket',(int)$ticket['id'],null,['ticket_number'=>$ticket['number'],'email'=>$email],[],'PUBLIC_WEB');
        $this->notifyCreated((int)$ticket['id']);
        $_SESSION['public_ticket_created']=$ticket['number'];
        header('Location: '.APP_BASE_URL.'/ticket-enviado');exit;
    }

    public function publicDone(): void
    {
        $number=(string)($_SESSION['public_ticket_created']??'');
        unset($_SESSION['public_ticket_created']);
        View::render('tickets/public_done',['ticketNumber'=>$number,'user'=>Auth::user()]);
    }

    public function index(): void
    {
        Auth::requireLogin();
        $pdo=Database::pdo();$user=Auth::user();$uid=(int)Auth::id();$email=strtolower((string)$user['email']);
        if(($user['access_type']??'INTERNAL')==='EXTERNAL'){
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED' AND t.deleted_at IS NULL ORDER BY t.created_at DESC");
            $s->execute([$uid]);
        }else{
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE t.deleted_at IS NULL AND (t.requester_user_id=? OR LOWER(t.requester_email)=?) ORDER BY t.created_at DESC");
            $s->execute([$uid,$email]);
        }
        View::render('tickets/index',['user'=>$user,'tickets'=>$s->fetchAll(),'flash'=>Flash::pull()]);
    }

    public function queue(): void
    {
        Auth::requirePermission('tickets.view_queue');
        $s=Database::pdo()->query("SELECT t.*,c.name category_name,p.name park_name,a.name area_name FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id WHERE t.deleted_at IS NULL AND t.assigned_to IS NULL AND t.status IN('NEW','AVAILABLE','REOPENED') ORDER BY FIELD(t.priority,'CRITICAL','HIGH','MEDIUM','LOW'),t.created_at ASC");
        View::render('tickets/queue',['user'=>Auth::user(),'tickets'=>$s->fetchAll(),'flash'=>Flash::pull()]);
    }

    public function claim(): void
    {
        Auth::requirePermission('tickets.claim');Csrf::verify($_POST['_csrf']??null);
        $id=(int)Http::post('ticket_id');if($id<=0)throw new \RuntimeException('Ticket no válido.');
        $pdo=Database::pdo();
        $s=$pdo->prepare("UPDATE tickets SET assigned_to=?,assigned_at=NOW(),status='IN_PROGRESS',updated_at=NOW() WHERE id=? AND assigned_to IS NULL AND status IN('NEW','AVAILABLE','REOPENED') AND deleted_at IS NULL");
        $s->execute([(int)Auth::id(),$id]);
        if($s->rowCount()!==1){Flash::set('Ese caso ya fue tomado por otro usuario.','info');header('Location: '.APP_BASE_URL.'/tickets/queue');exit;}
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'CLAIMED',?,'USER',?,NOW())")
            ->execute([$id,(int)Auth::id(),json_encode(['assigned_to'=>(int)Auth::id(),'status'=>'IN_PROGRESS'],JSON_UNESCAPED_UNICODE)]);
        Audit::log('TICKET_CLAIMED','ticket',$id,null,['assigned_to'=>(int)Auth::id(),'status'=>'IN_PROGRESS']);
        $this->notifyRequester($id,'Tu solicitud está siendo atendida','Tu solicitud fue tomada por el equipo de soporte y cambió a EN PROCESO.');
        Flash::set('Caso tomado correctamente.','success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function show(): void
    {
        Auth::requireLogin();$id=(int)($_GET['id']??0);if($id<=0)throw new \RuntimeException('Ticket no válido.');
        $pdo=Database::pdo();
        $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");
        $s->execute([$id]);$ticket=$s->fetch();if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');$this->visible($ticket);
        $e=$pdo->prepare("SELECT te.*,u.full_name actor_name FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id=? ORDER BY te.created_at,te.id");$e->execute([$id]);
        View::render('tickets/show',['user'=>Auth::user(),'ticket'=>$ticket,'events'=>$e->fetchAll(),'flash'=>Flash::pull()]);
    }

    private function visible(array $t): void
    {
        if(Auth::can('tickets.view_all'))return;$uid=(int)Auth::id();$email=strtolower((string)(Auth::user()['email']??''));
        if((int)($t['requester_user_id']??0)===$uid||strtolower((string)$t['requester_email'])===$email||(int)($t['assigned_to']??0)===$uid)return;
        if((Auth::user()['access_type']??'')==='EXTERNAL'){$s=Database::pdo()->prepare('SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL');$s->execute([(int)$t['id'],$uid]);if((int)$s->fetchColumn()>0&&$t['case_type']==='SPECIAL'&&$t['visibility_mode']==='EXTERNAL_ALLOWED')return;}
        http_response_code(403);exit('403 - Sin acceso a este ticket');
    }

    private function activeExists(string $table,int $id): bool
    {
        if(!in_array($table,['parks','areas'],true))return false;$s=Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE id=? AND is_active=1");$s->execute([$id]);return (int)$s->fetchColumn()>0;
    }

    private function sla(int $categoryId,string $priority): array
    {
        $s=Database::pdo()->prepare("SELECT id,first_response_minutes,resolution_minutes FROM sla_policies WHERE is_active=1 AND priority=? AND (category_id=? OR category_id IS NULL) ORDER BY category_id IS NULL ASC,id ASC LIMIT 1");$s->execute([$priority,$categoryId]);$r=$s->fetch();if(!$r)return[null,null,null];return[(int)$r['id'],date('Y-m-d H:i:s',time()+(int)$r['first_response_minutes']*60),date('Y-m-d H:i:s',time()+(int)$r['resolution_minutes']*60)];
    }

    private function rateLimit(string $email): void
    {
        $pdo=Database::pdo();$s=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE requester_email=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)");$s->execute([$email]);$i=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE source_ip=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)");$i->execute([Http::ip()]);if((int)$s->fetchColumn()>=5||(int)$i->fetchColumn()>=10)throw new \RuntimeException('Se alcanzó el límite temporal de solicitudes. Intenta más tarde.');
    }

    private function notifyCreated(int $id): void
    {
        $pdo=Database::pdo();$s=$pdo->prepare('SELECT ticket_number,requester_email,subject,priority FROM tickets WHERE id=?');$s->execute([$id]);$t=$s->fetch();if(!$t)return;
        try{(new MailService())->sendTicketNotification((string)$t['requester_email'],'Solicitud '.$t['ticket_number'].' recibida | '.APP_NAME,'Solicitud recibida','Recibimos tu solicitud '.$t['ticket_number'].' · '.$t['subject'].'. Puedes consultar su seguimiento ingresando a Mis tickets con tu correo y OTP.',APP_BASE_URL.'/mis-tickets');}catch(\Throwable $e){Logger::error($e);}
        $recipients=$pdo->query("SELECT DISTINCT u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')")->fetchAll(PDO::FETCH_COLUMN);
        foreach($recipients as $to){if(strtolower((string)$to)===strtolower((string)$t['requester_email']))continue;try{(new MailService())->sendTicketNotification((string)$to,'Nueva solicitud '.$t['ticket_number'].' | '.APP_NAME,'Nueva solicitud disponible',$t['ticket_number'].' · '.$t['subject'].' · Prioridad '.$t['priority'],APP_BASE_URL.'/tickets/queue');}catch(\Throwable $e){Logger::error($e);}}
    }

    private function notifyRequester(int $id,string $title,string $message): void
    {
        $s=Database::pdo()->prepare('SELECT ticket_number,requester_email FROM tickets WHERE id=?');$s->execute([$id]);$t=$s->fetch();if(!$t||empty($t['requester_email']))return;try{(new MailService())->sendTicketNotification((string)$t['requester_email'],$t['ticket_number'].' | '.APP_NAME,$title,$message,APP_BASE_URL.'/mis-tickets');}catch(\Throwable $e){Logger::error($e);}
    }
}