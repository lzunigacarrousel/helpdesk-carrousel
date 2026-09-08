<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use App\Services\MailService;
use PDO;

final class ExternalController
{
    public function index(): void
    {
        Auth::requirePermission('external.manage');
        $pdo=Database::pdo();
        $users=$pdo->query("SELECT u.id,u.full_name,u.email,u.phone,u.status,ep.organization_name,ep.external_type,ep.notes,
            COUNT(eta.ticket_id) active_cases
            FROM users u JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
            LEFT JOIN external_profiles ep ON ep.user_id=u.id
            LEFT JOIN external_ticket_access eta ON eta.user_id=u.id AND eta.revoked_at IS NULL
            WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL'
            GROUP BY u.id,u.full_name,u.email,u.phone,u.status,ep.organization_name,ep.external_type,ep.notes
            ORDER BY COALESCE(ep.organization_name,u.full_name),u.full_name")->fetchAll();
        $tickets=$pdo->query("SELECT t.id,t.ticket_number,t.subject,t.status,t.case_type,t.visibility_mode,p.name park_name
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id
            WHERE t.deleted_at IS NULL AND t.status NOT IN('CLOSED','CANCELLED')
            ORDER BY t.created_at DESC LIMIT 150")->fetchAll();
        $access=$pdo->query("SELECT eta.ticket_id,eta.user_id,eta.can_comment,eta.can_upload,eta.granted_at,
            t.ticket_number,t.subject,u.full_name,ep.organization_name
            FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id JOIN users u ON u.id=eta.user_id
            LEFT JOIN external_profiles ep ON ep.user_id=u.id
            WHERE eta.revoked_at IS NULL ORDER BY eta.granted_at DESC")->fetchAll();
        View::render('admin/externals',['user'=>Auth::user(),'users'=>$users,'tickets'=>$tickets,'access'=>$access,'flash'=>Flash::pull()]);
    }

    public function createUser(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $email=strtolower(trim(Http::post('email')));$name=trim(Http::post('name'));$phone=trim(Http::post('phone'));
        $organization=trim(Http::post('organization_name'));$type=strtoupper(trim(Http::post('external_type')));$notes=trim(Http::post('notes'));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Correo externo no válido.');
        if(mb_strlen($name)<3)throw new \RuntimeException('Ingresa el nombre del contacto.');
        if(mb_strlen($organization)<2)throw new \RuntimeException('Ingresa el proveedor u organización.');
        if(!in_array($type,['PROVIDER','PARTNER','OTHER'],true))$type='PROVIDER';
        $pdo=Database::pdo();
        $exists=$pdo->prepare('SELECT id FROM users WHERE LOWER(email)=? AND deleted_at IS NULL LIMIT 1');$exists->execute([$email]);if($exists->fetchColumn())throw new \RuntimeException('Ese correo ya existe en Helpdesk.');
        $roleId=(int)$pdo->query("SELECT id FROM roles WHERE code='EXTERNAL' AND is_active=1 LIMIT 1")->fetchColumn();if($roleId<=0)throw new \RuntimeException('No existe el rol Externo.');
        $uid=Database::transaction(function(PDO $pdo)use($roleId,$email,$name,$phone,$organization,$type,$notes):int{
            $q=$pdo->prepare("INSERT INTO users(role_id,access_type,email,full_name,phone,status,created_at,updated_at) VALUES(?,'EXTERNAL',?,?,?,'ACTIVE',NOW(),NOW())");
            $q->execute([$roleId,$email,$name,$phone?:null]);$uid=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO external_profiles(user_id,organization_name,external_type,notes,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())")
                ->execute([$uid,$organization,$type,$notes?:null]);
            return $uid;
        });
        Audit::log('EXTERNAL_USER_CREATED','user',$uid,null,['email'=>$email,'organization_name'=>$organization,'external_type'=>$type]);
        try{(new MailService())->sendTicketNotification($email,'Acceso a Helpdesk Carrousel','Tu acceso fue creado','Hola '.$name.'. Se creó tu acceso externo a Helpdesk Carrousel. Cuando tengas un caso asignado podrás verlo ingresando con este correo y un código OTP.',APP_BASE_URL.'/login');}catch(\Throwable $e){}
        Flash::set('Usuario externo creado.','success');header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }

    public function grant(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $ticketId=(int)Http::post('ticket_id');$userId=(int)Http::post('user_id');
        $canComment=Http::post('can_comment','0')==='1'?1:0;$canUpload=Http::post('can_upload','0')==='1'?1:0;
        if($ticketId<=0||$userId<=0)throw new \RuntimeException('Selecciona ticket y usuario externo.');
        $pdo=Database::pdo();
        $u=$pdo->prepare("SELECT u.id,u.email,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.access_type='EXTERNAL' AND r.code='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL LIMIT 1");$u->execute([$userId]);$external=$u->fetch();if(!$external)throw new \RuntimeException('Usuario externo no disponible.');
        $t=$pdo->prepare('SELECT id,ticket_number,subject FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$t->execute([$ticketId]);$ticket=$t->fetch();if(!$ticket)throw new \RuntimeException('Ticket no encontrado.');
        Database::transaction(function(PDO $pdo)use($ticketId,$userId,$canComment,$canUpload):void{
            $pdo->prepare("UPDATE tickets SET case_type='SPECIAL',visibility_mode='EXTERNAL_ALLOWED',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            $pdo->prepare("INSERT INTO external_ticket_access(ticket_id,user_id,can_comment,can_upload,granted_by,granted_at,revoked_at)
                VALUES(?,?,?,?,?,NOW(),NULL) ON DUPLICATE KEY UPDATE can_comment=VALUES(can_comment),can_upload=VALUES(can_upload),granted_by=VALUES(granted_by),granted_at=NOW(),revoked_at=NULL")
                ->execute([$ticketId,$userId,$canComment,$canUpload,Auth::id()]);
        });
        Audit::log('EXTERNAL_TICKET_GRANTED','ticket',$ticketId,null,['external_user_id'=>$userId,'can_comment'=>$canComment,'can_upload'=>$canUpload]);
        try{(new MailService())->sendTicketNotification((string)$external['email'],'Caso asignado · '.$ticket['ticket_number'],'Tienes un caso asignado','Se te habilitó acceso al caso '.$ticket['ticket_number'].': '.$ticket['subject'].'. Solo podrás consultar los casos que Carrousel te asigne.',APP_BASE_URL.'/tickets/view?id='.$ticketId);}catch(\Throwable $e){}
        Flash::set('Caso compartido con el usuario externo.','success');header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }

    public function revoke(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $ticketId=(int)Http::post('ticket_id');$userId=(int)Http::post('user_id');if($ticketId<=0||$userId<=0)throw new \RuntimeException('Datos no válidos.');
        $pdo=Database::pdo();$pdo->prepare('UPDATE external_ticket_access SET revoked_at=NOW() WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL')->execute([$ticketId,$userId]);
        $left=$pdo->prepare('SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND revoked_at IS NULL');$left->execute([$ticketId]);if((int)$left->fetchColumn()===0)$pdo->prepare("UPDATE tickets SET visibility_mode='INTERNAL',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
        Audit::log('EXTERNAL_TICKET_REVOKED','ticket',$ticketId,['external_user_id'=>$userId],null);
        Flash::set('Acceso externo revocado.','success');header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }
}
