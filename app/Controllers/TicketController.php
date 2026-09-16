<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger,View};
use App\Services\{NotificationService,ScopeService,SlaPresentationService,TicketClassificationService,RequesterLocationPolicyService,TicketActivityService,ProviderParticipationService,ProviderRatingService};
use PDO;

final class TicketController
{
    private const STATUS_LABELS=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
    private const PRIORITY_LABELS=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];

    public function publicHome():void{View::render('tickets/public_home',['user'=>Auth::user(),'flash'=>Flash::pull()]);}

    public function publicCreate():void{

        $pdo=Database::pdo();$user=Auth::user();

        $categories=$pdo->query("SELECT c.id,c.code,TRIM(c.name) name,c.parent_id,c.sort_order,TRIM(p.name) parent_name,p.code parent_code,p.sort_order parent_sort_order FROM ticket_categories c LEFT JOIN ticket_categories p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name")->fetchAll();

        $assignment=$user?$this->singleActiveAssignment($pdo,(int)$user['id']):null;

        $locationPolicy=(new RequesterLocationPolicyService())->forUser($user);

        $defaultParkId=(int)($locationPolicy['fixed_park_id']??($assignment['park_id']??0));

        View::render('tickets/public_create',[

            'user'=>$user,

            'categories'=>$categories,

            'parks'=>$locationPolicy['parks'],

            'areas'=>$pdo->query("SELECT MIN(id) id,TRIM(name) name FROM areas WHERE is_active=1 GROUP BY LOWER(TRIM(name)) ORDER BY name")->fetchAll(),

            'assignment'=>$assignment,

            'locationPolicy'=>$locationPolicy,

            'defaultParkId'=>$defaultParkId,

            'defaultAreaId'=>(int)($assignment['area_id']??0),

            'flash'=>Flash::pull(),

        ]);

    }

    public function publicStore():void{
        Csrf::verify($_POST['_csrf']??null);
        $authUser=Auth::user();$requesterUserId=null;
        if($authUser){
            $name=(string)$authUser['full_name'];$email=strtolower((string)$authUser['email']);$knownPhone=trim((string)($authUser['phone']??''));$phone=$knownPhone!==''?$knownPhone:trim(Http::post('phone'));$requesterUserId=(int)$authUser['id'];
        }else{
            $name=trim(Http::post('name'));$email=strtolower(trim(Http::post('email')));$phone=trim(Http::post('phone'));
        }
        $parkId=(int)Http::post('park_id');$areaId=(int)Http::post('area_id');$categoryId=(int)Http::post('category_id');
        $subject=trim(Http::post('subject'));$description=trim(Http::post('description'));
        if(mb_strlen($name)<3)throw new \RuntimeException('Ingresa tu nombre completo.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Ingresa un correo válido.');
        if($categoryId<=0)throw new \RuntimeException('Selecciona en qué necesitas ayuda.');
        if(mb_strlen($subject)<5)throw new \RuntimeException('Escribe un resumen un poco más claro.');
        if(mb_strlen($description)<10)throw new \RuntimeException('Cuéntanos un poco más sobre lo que está pasando.');
        $pdo=Database::pdo();$this->rateLimit($email);
        $parkId=(new RequesterLocationPolicyService())->resolveParkId($authUser,$parkId);
        $c=$pdo->prepare('SELECT c.id,c.code,p.code parent_code FROM ticket_categories c LEFT JOIN ticket_categories p ON p.id=c.parent_id WHERE c.id=? AND c.is_active=1 LIMIT 1');$c->execute([$categoryId]);$category=$c->fetch();
        if(!$category)throw new \RuntimeException('La opción seleccionada no está disponible.');
        if($parkId>0&&!$this->activeExists('parks',$parkId))throw new \RuntimeException('La ubicación seleccionada no está disponible.');
        if($areaId>0&&!$this->activeExists('areas',$areaId))throw new \RuntimeException('El área seleccionada no está disponible.');
        $classification=TicketClassificationService::inferInitialClassification($category,$subject,$description);
        $requestType=(string)$classification['request_type'];$impact=(string)$classification['impact'];$urgency=(string)$classification['urgency'];$priority=(string)$classification['priority'];
        if($requesterUserId===null){$u=$pdo->prepare('SELECT id FROM users WHERE email=? AND email_verified_at IS NOT NULL AND deleted_at IS NULL LIMIT 1');$u->execute([$email]);$found=$u->fetchColumn();if($found)$requesterUserId=(int)$found;}
        $teamId=$pdo->query("SELECT id FROM support_teams WHERE code='IT' AND is_active=1 LIMIT 1")->fetchColumn()?:null;
        [$slaId,$firstDue,$resolutionDue]=$this->sla($categoryId,$priority);
        $ticket=Database::transaction(function(PDO $pdo)use($requesterUserId,$email,$name,$phone,$parkId,$areaId,$categoryId,$requestType,$impact,$urgency,$teamId,$subject,$description,$priority,$slaId,$firstDue,$resolutionDue){
            $s=$pdo->prepare("INSERT INTO tickets(ticket_number,case_type,request_type,visibility_mode,origin,requester_user_id,requester_email,requester_name,requester_phone,park_id,area_id,category_id,impact,urgency,support_team_id,subject,description,priority,priority_source,status,sla_policy_id,first_response_due_at,resolution_due_at,source_ip,created_at,updated_at) VALUES('', 'NORMAL',?,'INTERNAL','PUBLIC_WEB',?,?,?,?,?,?,?,?,?,?,?,?,?,'CALCULATED','AVAILABLE',?,?,?,?,NOW(),NOW())");
            $s->execute([$requestType,$requesterUserId,$email,$name,$phone?:null,$parkId?:null,$areaId?:null,$categoryId,$impact,$urgency,$teamId?:null,$subject,$description,$priority,$slaId,$firstDue,$resolutionDue,Http::ip()]);
            $id=(int)$pdo->lastInsertId();$number='HD-'.date('Y').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);
            $pdo->prepare('UPDATE tickets SET ticket_number=? WHERE id=?')->execute([$number,$id]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at) VALUES(?,'CREATED',NULL,'PUBLIC',?,?,NOW())")->execute([$id,json_encode(['status'=>'AVAILABLE','request_type'=>$requestType,'impact'=>$impact,'urgency'=>$urgency,'priority'=>$priority,'priority_source'=>'CALCULATED'],JSON_UNESCAPED_UNICODE),json_encode(['origin'=>'PUBLIC_WEB'],JSON_UNESCAPED_UNICODE)]);
            return['id'=>$id,'number'=>$number];
        });
        Audit::log('TICKET_CREATED_PUBLIC','ticket',(int)$ticket['id'],null,['ticket_number'=>$ticket['number'],'email'=>$email,'request_type'=>$requestType,'impact'=>$impact,'urgency'=>$urgency,'priority'=>$priority,'priority_source'=>'CALCULATED'],[],'PUBLIC_WEB');
        $this->notifyCreated((int)$ticket['id']);
        $_SESSION['public_ticket_created']=$ticket['number'];header('Location: '.APP_BASE_URL.'/ticket-enviado');exit;
    }

    public function publicDone():void{$number=(string)($_SESSION['public_ticket_created']??'');unset($_SESSION['public_ticket_created']);View::render('tickets/public_done',['ticketNumber'=>$number,'user'=>Auth::user()]);}

    public function index():void{
        Auth::requireLogin();$pdo=Database::pdo();$user=Auth::user();$uid=(int)Auth::id();$email=strtolower((string)$user['email']);
        if(($user['access_type']??'INTERNAL')==='EXTERNAL'){
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED' AND t.deleted_at IS NULL ORDER BY t.created_at DESC");$s->execute([$uid]);
        }else{
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE t.deleted_at IS NULL AND (t.requester_user_id=? OR LOWER(t.requester_email)=?) ORDER BY t.created_at DESC");$s->execute([$uid,$email]);
        }
        View::render('tickets/index',['user'=>$user,'tickets'=>$s->fetchAll(),'flash'=>Flash::pull()]);
    }

    public function queue():void{
        Auth::requirePermission('tickets.view_queue');$pdo=Database::pdo();
        [$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
        $q=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name,COALESCE(req.full_name,t.requester_name) current_requester_name,COALESCE(req.email,t.requester_email) current_requester_email
            FROM tickets t
            LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN parks p ON p.id=t.park_id
            LEFT JOIN areas a ON a.id=t.area_id
            LEFT JOIN users u ON u.id=t.assigned_to
            LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL
            WHERE t.deleted_at IS NULL
              AND t.status IN('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')
              AND ({$scopeSql})
            ORDER BY FIELD(t.priority,'CRITICAL','HIGH','MEDIUM','LOW'),COALESCE(t.resolution_due_at,'9999-12-31 23:59:59'),t.updated_at DESC");
        $q->execute($scopeParams);$rows=$q->fetchAll();
        foreach($rows as &$row)$row['sla_summary']=SlaPresentationService::summary($row);
        unset($row);
        View::render('tickets/queue',['user'=>Auth::user(),'tickets'=>$rows,'myTickets'=>[],'flash'=>Flash::pull(),'priorityLabels'=>self::PRIORITY_LABELS,'statusLabels'=>self::STATUS_LABELS]);
    }

    public function claim():void{
        Auth::requirePermission('tickets.claim');Csrf::verify($_POST['_csrf']??null);$id=(int)Http::post('ticket_id');if($id<=0)throw new \RuntimeException('Ticket no válido.');
        $pdo=Database::pdo();[$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
        $allowed=$pdo->prepare("SELECT COUNT(*) FROM tickets t WHERE t.id=? AND t.deleted_at IS NULL AND t.assigned_to IS NULL AND t.status IN('NEW','AVAILABLE','REOPENED') AND ({$scopeSql})");
        $allowed->execute(array_merge([$id],$scopeParams));
        if((int)$allowed->fetchColumn()!==1)throw new \RuntimeException('Ese caso no está disponible dentro de tu alcance.');
        $s=$pdo->prepare("UPDATE tickets SET assigned_to=?,assigned_at=NOW(),status='IN_PROGRESS',updated_at=NOW() WHERE id=? AND assigned_to IS NULL AND status IN('NEW','AVAILABLE','REOPENED') AND deleted_at IS NULL");$s->execute([(int)Auth::id(),$id]);
        if($s->rowCount()!==1){Flash::set('Ese caso ya fue tomado por otra persona.','info');header('Location: '.APP_BASE_URL.'/tickets/queue');exit;}
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'CLAIMED',?,'USER',?,NOW())")->execute([$id,(int)Auth::id(),json_encode(['assigned_to'=>(int)Auth::id(),'status'=>'IN_PROGRESS'],JSON_UNESCAPED_UNICODE)]);
        Audit::log('TICKET_CLAIMED','ticket',$id,null,['assigned_to'=>(int)Auth::id(),'status'=>'IN_PROGRESS']);
        $this->publish($id,'TICKET_CLAIMED','Tu solicitud ya está siendo atendida','El equipo de soporte comenzó a trabajar en tu solicitud.',['requester','admins']);
        Flash::set('Caso tomado correctamente.','success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function assign():void{
        Auth::requirePermission('tickets.reassign');Csrf::verify($_POST['_csrf']??null);$id=(int)Http::post('ticket_id');$userId=(int)Http::post('assigned_to');if($id<=0||$userId<=0)throw new \RuntimeException('Selecciona un responsable válido.');
        $pdo=Database::pdo();$candidate=$pdo->prepare("SELECT u.id,u.email,u.full_name,r.code role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') LIMIT 1");$candidate->execute([$userId]);$target=$candidate->fetch();if(!$target)throw new \RuntimeException('El responsable seleccionado no está disponible para soporte.');
        $old=$pdo->prepare('SELECT assigned_to,status FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$old->execute([$id]);$before=$old->fetch();if(!$before)throw new \RuntimeException('Ticket no encontrado.');if(in_array((string)$before['status'],['CLOSED','CANCELLED'],true))throw new \RuntimeException('No puedes reasignar un caso cerrado o cancelado.');
        $pdo->prepare("UPDATE tickets SET assigned_to=?,assigned_at=NOW(),status='IN_PROGRESS',updated_at=NOW() WHERE id=?")->execute([$userId,$id]);
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,created_at) VALUES(?,'REASSIGNED',?,'USER',?,?,NOW())")->execute([$id,(int)Auth::id(),json_encode(['assigned_to'=>$before['assigned_to'],'status'=>$before['status']],JSON_UNESCAPED_UNICODE),json_encode(['assigned_to'=>$userId,'status'=>'IN_PROGRESS','assigned_name'=>$target['full_name']],JSON_UNESCAPED_UNICODE)]);
        Audit::log('TICKET_REASSIGNED','ticket',$id,['assigned_to'=>$before['assigned_to']],['assigned_to'=>$userId]);
        $this->publish($id,'TICKET_REASSIGNED','Responsable actualizado','El caso fue asignado a '.$target['full_name'].' y quedó en proceso.',['requester','assignee','admins']);
        Flash::set('Caso asignado a '.$target['full_name'].'.','success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function release():void{
        Auth::requireLogin();Csrf::verify($_POST['_csrf']??null);$id=(int)Http::post('ticket_id');if($id<=0)throw new \RuntimeException('Ticket no válido.');$pdo=Database::pdo();
        $q=$pdo->prepare('SELECT assigned_to,status FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$q->execute([$id]);$ticket=$q->fetch();if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');
        if((int)($ticket['assigned_to']??0)!==(int)Auth::id()&&!Auth::can('tickets.reassign')){Flash::set('No puedes devolver este caso a la cola.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;}
        if(in_array((string)$ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true))throw new \RuntimeException('Este caso ya no puede volver a la cola desde su estado actual.');
        $pdo->prepare("UPDATE tickets SET assigned_to=NULL,assigned_at=NULL,status='AVAILABLE',updated_at=NOW() WHERE id=?")->execute([$id]);
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,created_at) VALUES(?,'RELEASED',?,'USER',?,?,NOW())")->execute([$id,(int)Auth::id(),json_encode(['assigned_to'=>$ticket['assigned_to'],'status'=>$ticket['status']],JSON_UNESCAPED_UNICODE),json_encode(['assigned_to'=>null,'status'=>'AVAILABLE'],JSON_UNESCAPED_UNICODE)]);
        Audit::log('TICKET_RELEASED','ticket',$id,['assigned_to'=>$ticket['assigned_to']],['assigned_to'=>null,'status'=>'AVAILABLE']);
        $this->publish($id,'TICKET_RELEASED','Caso disponible nuevamente','El caso volvió a la cola y está pendiente de un nuevo responsable.',['requester','support','support_group']);
        Flash::set('El caso volvió a la cola de soporte.','success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function changeStatus():void{
        Auth::requirePermission('tickets.change_status');Csrf::verify($_POST['_csrf']??null);$id=(int)Http::post('ticket_id');$status=strtoupper(trim(Http::post('status')));$allowed=['IN_PROGRESS','PENDING','RESOLVED','CLOSED','REOPENED','CANCELLED'];if($id<=0||!in_array($status,$allowed,true))throw new \RuntimeException('Estado no válido.');
        $pdo=Database::pdo();$q=$pdo->prepare('SELECT status,assigned_to FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$q->execute([$id]);$ticket=$q->fetch();if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');
        if(!Auth::can('tickets.reassign')&&(int)($ticket['assigned_to']??0)!==(int)Auth::id()){Flash::set('Solo la persona responsable puede actualizar este caso.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;}
        $oldStatus=(string)$ticket['status'];if($oldStatus===$status){Flash::set('El caso ya tiene ese estado.','info');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;}
        $resolvedSql=$status==='RESOLVED'?'resolved_at=NOW(),':($status==='REOPENED'?'resolved_at=NULL,':'');$closedSql=in_array($status,['CLOSED','CANCELLED'],true)?'closed_at=NOW(),':($status==='REOPENED'?'closed_at=NULL,':'');$pdo->prepare("UPDATE tickets SET status=?,{$resolvedSql}{$closedSql}updated_at=NOW() WHERE id=?")->execute([$status,$id]);
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,created_at) VALUES(?,'STATUS_CHANGED',?,'USER',?,?,NOW())")->execute([$id,(int)Auth::id(),json_encode(['status'=>$oldStatus],JSON_UNESCAPED_UNICODE),json_encode(['status'=>$status],JSON_UNESCAPED_UNICODE)]);
        Audit::log('TICKET_STATUS_CHANGED','ticket',$id,['status'=>$oldStatus],['status'=>$status]);
        $label=self::STATUS_LABELS[$status]??$status;
        $this->publish($id,'STATUS_CHANGED','Estado actualizado','El caso cambió de '.(self::STATUS_LABELS[$oldStatus]??$oldStatus).' a '.$label.'.',['requester','assignee','externals','admins']);
        Flash::set('Estado actualizado a '.$label.'.','success');header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);exit;
    }

    public function show():void{
        Auth::requireLogin();$id=(int)($_GET['id']??0);if($id<=0)throw new \RuntimeException('Ticket no válido.');$pdo=Database::pdo();
        $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name,u.email assigned_email,COALESCE(req.full_name,t.requester_name) current_requester_name,COALESCE(req.email,t.requester_email) current_requester_email,COALESCE(req.phone,t.requester_phone) current_requester_phone,rp.name current_requester_park_name,ra.name current_requester_area_name FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL LEFT JOIN user_assignments rqa ON rqa.id=(SELECT MAX(x.id) FROM user_assignments x WHERE x.user_id=t.requester_user_id AND x.status='ACTIVE' AND x.ends_at IS NULL) LEFT JOIN parks rp ON rp.id=rqa.park_id LEFT JOIN areas ra ON ra.id=rqa.area_id WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");$s->execute([$id]);$ticket=$s->fetch();if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');$this->visible($ticket);
        $e=$pdo->prepare("SELECT te.*,u.full_name actor_name FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id=? ORDER BY te.created_at,te.id");$e->execute([$id]);
        $ticket['sla_summary']=SlaPresentationService::summary($ticket);
        $canEditLocation=Auth::can('tickets.classify');
        $locationParks=$canEditLocation?$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll():[];
        $locationAreas=$canEditLocation?$pdo->query("SELECT id,name FROM areas WHERE is_active=1 ORDER BY name")->fetchAll():[];
        $isSupport=Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.reassign')||Auth::can('tickets.view_all');$supportUsers=[];if(Auth::can('tickets.reassign'))$supportUsers=$pdo->query("SELECT u.id,u.full_name,u.email,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll();

        $events=$e->fetchAll();
        if(!$isSupport){
            $requesterEventTypes=['CREATED','STATUS_CHANGED','RESOLVED','CLOSED','REOPENED'];
            $events=array_values(array_filter($events,static fn(array $event):bool=>in_array((string)$event['event_type'],$requesterEventTypes,true)));
            foreach($events as &$event)$event['actor_name']=null;
            unset($event);
        }
        $activityService=new TicketActivityService();
        $activities=$isSupport?$activityService->listForTicket($id):[];
        $requesterActivities=!$isSupport?$activityService->requesterVisibleForTicket($id):[];
        $canCreateActivities=$isSupport&&Auth::can('activities.create');
        $canManageActivities=$isSupport&&Auth::can('activities.manage');
        $canCancelActivities=$isSupport&&Auth::can('activities.cancel');
        $activityTypes=$isSupport?TicketActivityService::types():[];
        $activityResults=$isSupport?TicketActivityService::results():[];
        $activityResponsibleUsers=$canCreateActivities?$activityService->responsibleOptionsForTicket($id):[];
        $activityProviderUsers=$canCreateActivities?$activityService->providerOptionsForTicket($id):[];
        $activityParks=$isSupport?$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll():[];

        $providerCycles=[];
        $providerRatingLabels=ProviderRatingService::SCORE_LABELS;
        $canRateProviders=$isSupport
            &&in_array((string)Auth::role(),['ADMIN','SEMIADMIN','TECHNICIAN'],true)
            &&(new ScopeService())->userCanAccessTicket((int)Auth::id(),$id);
        if($isSupport){
            $participationService=new ProviderParticipationService($pdo);
            $ratingService=new ProviderRatingService($pdo);
            $providerCycles=$ratingService->enrichRows($participationService->rowsForTicket($id));
        }

        View::render('tickets/show',[
            'user'=>Auth::user(),
            'ticket'=>$ticket,
            'events'=>$events,
            'flash'=>Flash::pull(),
            'isSupport'=>$isSupport,
            'supportUsers'=>$supportUsers,
            'canClaim'=>Auth::can('tickets.claim')&&empty($ticket['assigned_to'])&&in_array($ticket['status'],['NEW','AVAILABLE','REOPENED'],true),
            'canReassign'=>Auth::can('tickets.reassign'),
            'canRelease'=>!empty($ticket['assigned_to'])&&((int)$ticket['assigned_to']===(int)Auth::id()||Auth::can('tickets.reassign'))&&!in_array($ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),
            'canChangeStatus'=>Auth::can('tickets.change_status')&&((int)($ticket['assigned_to']??0)===(int)Auth::id()||Auth::can('tickets.reassign')),
            'canClassify'=>Auth::can('tickets.classify')&&!in_array((string)$ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),
            'statusLabels'=>self::STATUS_LABELS,
            'priorityLabels'=>self::PRIORITY_LABELS,
            'canEditLocation'=>$canEditLocation,
            'locationParks'=>$locationParks,
            'locationAreas'=>$locationAreas,
            'activities'=>$activities,
            'requesterActivities'=>$requesterActivities,
            'activityTypes'=>$activityTypes,
            'activityResults'=>$activityResults,
            'activityResponsibleUsers'=>$activityResponsibleUsers,
            'activityProviderUsers'=>$activityProviderUsers,
            'activityParks'=>$activityParks,
            'canCreateActivities'=>$canCreateActivities,
            'canManageActivities'=>$canManageActivities,
            'canCancelActivities'=>$canCancelActivities,
            'providerCycles'=>$providerCycles,
            'providerRatingLabels'=>$providerRatingLabels,
            'canRateProviders'=>$canRateProviders,
        ]);
    }

    private function visible(array $t):void{
        if(Auth::can('tickets.view_all'))return;
        $uid=(int)Auth::id();$current=Auth::user();$email=strtolower((string)($current['email']??''));
        if(($current['access_type']??'')==='EXTERNAL'){
            $s=Database::pdo()->prepare('SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL');
            $s->execute([(int)$t['id'],$uid]);
            if((int)$s->fetchColumn()>0&&$t['case_type']==='SPECIAL'&&$t['visibility_mode']==='EXTERNAL_ALLOWED')return;
            Flash::set('Ese caso no está habilitado para tu cuenta.','info');header('Location: '.APP_BASE_URL.'/dashboard');exit;
        }
        if(Auth::can('tickets.view_queue')||Auth::can('management.view')||Auth::can('reports.view')){
            [$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
            $q=Database::pdo()->prepare("SELECT COUNT(*) FROM tickets t WHERE t.id=? AND t.deleted_at IS NULL AND ({$scopeSql})");
            $q->execute(array_merge([(int)$t['id']],$scopeParams));
            if((int)$q->fetchColumn()>0)return;
        }
        if((int)($t['requester_user_id']??0)===$uid||strtolower((string)$t['requester_email'])===$email||(int)($t['assigned_to']??0)===$uid)return;
        Flash::set('No tienes acceso a ese caso.','info');header('Location: '.APP_BASE_URL.'/dashboard');exit;
    }

    private function singleActiveAssignment(PDO $pdo,int $userId):?array
    {
        if($userId<=0)return null;
        $s=$pdo->prepare("SELECT ua.id,ua.assignment_type,ua.region_id,ua.park_id,ua.area_id,r.name region_name,p.name park_name,a.name area_name
            FROM user_assignments ua
            LEFT JOIN regions r ON r.id=ua.region_id
            LEFT JOIN parks p ON p.id=ua.park_id
            LEFT JOIN areas a ON a.id=ua.area_id
            WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.starts_at<=NOW() AND (ua.ends_at IS NULL OR ua.ends_at>=NOW())
              AND (ua.region_id IS NULL OR r.is_active=1)
              AND (ua.park_id IS NULL OR p.is_active=1)
              AND (ua.area_id IS NULL OR a.is_active=1)
            ORDER BY ua.id DESC LIMIT 2");
        $s->execute([$userId]);$rows=$s->fetchAll();
        return count($rows)===1?$rows[0]:null;
    }

    private function activeExists(string $table,int $id):bool{if(!in_array($table,['parks','areas'],true))return false;$s=Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE id=? AND is_active=1");$s->execute([$id]);return(int)$s->fetchColumn()>0;}
    private function sla(int $categoryId,string $priority):array{$s=Database::pdo()->prepare("SELECT id,first_response_minutes,resolution_minutes FROM sla_policies WHERE is_active=1 AND priority=? AND (category_id=? OR category_id IS NULL) ORDER BY category_id IS NULL ASC,id ASC LIMIT 1");$s->execute([$priority,$categoryId]);$r=$s->fetch();if(!$r)return[null,null,null];return[(int)$r['id'],date('Y-m-d H:i:s',time()+(int)$r['first_response_minutes']*60),date('Y-m-d H:i:s',time()+(int)$r['resolution_minutes']*60)];}
    private function rateLimit(string $email):void{$s=Database::pdo()->prepare("SELECT COUNT(*) FROM tickets WHERE requester_email=? AND source_ip=? AND created_at>=DATE_SUB(NOW(),INTERVAL 10 MINUTE)");$s->execute([$email,Http::ip()]);if((int)$s->fetchColumn()>=5)throw new \RuntimeException('Has enviado varias solicitudes recientemente. Espera unos minutos e intenta nuevamente.');}
    private function ticketForMail(int $id):?array{$s=Database::pdo()->prepare("SELECT t.ticket_number,t.requester_email,t.requester_name,t.subject,t.description,t.priority,t.status,p.name park_name,a.name area_name,c.name category_name FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id WHERE t.id=? LIMIT 1");$s->execute([$id]);return$s->fetch()?:null;}

    private function notifyCreated(int $id):void
    {
        try{
            $t=$this->ticketForMail($id);if(!$t)return;
            $service=new NotificationService();
            $service->publishTicket(
                $id,'TICKET_CREATED_REQUESTER','Solicitud recibida · '.$t['ticket_number'],
                'Recibimos tu solicitud “'.$t['subject'].'”. El caso quedó registrado y te avisaremos cuando exista una actualización.',
                ['requester'],APP_BASE_URL.'/tickets/view?id='.$id,
                ['priority'=>$t['priority'],'status'=>$t['status']]
            );
            $supportMessage='Nueva solicitud: '.$t['subject'].'. Solicitante: '.$t['requester_name'].'. Ubicación: '.($t['park_name']?:'No especificada').'. Prioridad: '.(self::PRIORITY_LABELS[$t['priority']]??$t['priority']).'.';
            $service->publishTicket($id,'TICKET_CREATED_SUPPORT','Nuevo ticket · '.$t['ticket_number'],$supportMessage,['support','support_group'],APP_BASE_URL.'/tickets/view?id='.$id);
        }catch(\Throwable $e){Logger::error($e);}
    }

    private function publish(int $id,string $eventKey,string $title,string $message,array $audiences,array $options=[]):void
    {
        try{(new NotificationService())->publishTicket($id,$eventKey,$title,$message,$audiences,APP_BASE_URL.'/tickets/view?id='.$id,[],$options);}catch(\Throwable $e){Logger::error($e);}
    }
}
