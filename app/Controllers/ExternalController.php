<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger,View};
use App\Services\NotificationService;
use PDO;

final class ExternalController
{
    public function index(): void
    {
        Auth::requirePermission('external.manage');
        $pdo=Database::pdo();
        $users=$pdo->query("SELECT u.id,u.full_name,u.email,u.phone,u.status,ep.organization_name,ep.external_type,ep.notes,COUNT(eta.ticket_id) active_cases FROM users u JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL' LEFT JOIN external_profiles ep ON ep.user_id=u.id LEFT JOIN external_ticket_access eta ON eta.user_id=u.id AND eta.revoked_at IS NULL WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL' GROUP BY u.id,u.full_name,u.email,u.phone,u.status,ep.organization_name,ep.external_type,ep.notes ORDER BY COALESCE(ep.organization_name,u.full_name),u.full_name")->fetchAll();
        $tickets=$pdo->query("SELECT t.id,t.ticket_number,t.subject,t.status,t.case_type,t.visibility_mode,p.name park_name FROM tickets t LEFT JOIN parks p ON p.id=t.park_id WHERE t.deleted_at IS NULL AND t.status NOT IN('CLOSED','CANCELLED') ORDER BY t.created_at DESC LIMIT 150")->fetchAll();
        $access=$pdo->query("SELECT eta.ticket_id,eta.user_id,eta.can_comment,eta.can_upload,eta.granted_at,t.ticket_number,t.subject,u.full_name,ep.organization_name FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id JOIN users u ON u.id=eta.user_id LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE eta.revoked_at IS NULL ORDER BY eta.granted_at DESC")->fetchAll();
        View::render('admin/externals',['user'=>Auth::user(),'users'=>$users,'tickets'=>$tickets,'access'=>$access,'flash'=>Flash::pull()]);
    }

    public function createUser(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $email=strtolower(trim(Http::post('email')));$name=trim(Http::post('name'));$phone=trim(Http::post('phone'));$organization=trim(Http::post('organization_name'));$type=strtoupper(trim(Http::post('external_type')));$notes=trim(Http::post('notes'));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Ingresa un correo válido.');
        if(mb_strlen($name)<3)throw new \RuntimeException('Ingresa el nombre del contacto.');
        if(mb_strlen($organization)<2)throw new \RuntimeException('Ingresa el proveedor u organización.');
        if(!in_array($type,['PROVIDER','PARTNER','OTHER'],true))$type='PROVIDER';
        $pdo=Database::pdo();$exists=$pdo->prepare('SELECT id FROM users WHERE LOWER(email)=? AND deleted_at IS NULL LIMIT 1');$exists->execute([$email]);if($exists->fetchColumn())throw new \RuntimeException('Ese correo ya existe en Helpdesk.');
        $roleId=(int)$pdo->query("SELECT id FROM roles WHERE code='EXTERNAL' AND is_active=1 LIMIT 1")->fetchColumn();if($roleId<=0)throw new \RuntimeException('No fue posible habilitar este acceso.');
        $uid=Database::transaction(function(PDO $pdo)use($roleId,$email,$name,$phone,$organization,$type,$notes):int{
            $q=$pdo->prepare("INSERT INTO users(role_id,access_type,email,full_name,phone,status,created_at,updated_at) VALUES(?,'EXTERNAL',?,?,?,'ACTIVE',NOW(),NOW())");$q->execute([$roleId,$email,$name,$phone?:null]);$uid=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO external_profiles(user_id,organization_name,external_type,notes,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())")->execute([$uid,$organization,$type,$notes?:null]);return $uid;
        });
        Audit::log('EXTERNAL_USER_CREATED','user',$uid,null,['email'=>$email,'organization_name'=>$organization,'external_type'=>$type]);
        $result=['email_status'=>null];
        try{
            $result=(new NotificationService())->notifyUser(
                $uid,$email,null,'EXTERNAL_USER_CREATED','Tu acceso al Helpdesk está listo',
                'Hola '.$name.'. Tu acceso quedó habilitado para colaborar en los casos que se asignen a tu cuenta. Para ingresar usa este correo y el código temporal que recibirás al iniciar sesión.',
                APP_BASE_URL.'/login',true,true
            );
        }catch(\Throwable $e){Logger::error($e);$result['email_status']='FAILED';}
        $status=$result['email_status']??null;
        Flash::set($this->deliveryMessage('Usuario externo creado.',$status),$this->deliveryFlashType($status));header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }

    public function grant(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $ticketId=(int)Http::post('ticket_id');$userId=(int)Http::post('user_id');$canComment=Http::post('can_comment','0')==='1'?1:0;$canUpload=Http::post('can_upload','0')==='1'?1:0;
        if($ticketId<=0||$userId<=0)throw new \RuntimeException('Selecciona el caso y el proveedor.');
        $pdo=Database::pdo();
        $u=$pdo->prepare("SELECT u.id,u.email,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.access_type='EXTERNAL' AND r.code='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL LIMIT 1");$u->execute([$userId]);$external=$u->fetch();if(!$external)throw new \RuntimeException('Ese usuario no está disponible.');
        $t=$pdo->prepare('SELECT id,ticket_number,subject FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$t->execute([$ticketId]);$ticket=$t->fetch();if(!$ticket)throw new \RuntimeException('No encontramos ese caso.');
        $active=$pdo->prepare('SELECT 1 FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1');$active->execute([$ticketId,$userId]);if($active->fetchColumn())throw new \RuntimeException('Ese proveedor ya tiene acceso vigente a este caso.');
        Database::transaction(function(PDO $pdo)use($ticketId,$userId,$canComment,$canUpload):void{
            $pdo->prepare("UPDATE tickets SET case_type='SPECIAL',visibility_mode='EXTERNAL_ALLOWED',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            $pdo->prepare("INSERT INTO external_ticket_access(ticket_id,user_id,can_comment,can_upload,granted_by,granted_at,revoked_at) VALUES(?,?,?,?,?,NOW(),NULL) ON DUPLICATE KEY UPDATE can_comment=VALUES(can_comment),can_upload=VALUES(can_upload),granted_by=VALUES(granted_by),granted_at=NOW(),revoked_at=NULL")
                ->execute([$ticketId,$userId,$canComment,$canUpload,Auth::id()]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at) VALUES(?,'EXTERNAL_GRANTED',?,'USER',?,?,NOW())")
                ->execute([$ticketId,Auth::id(),json_encode(['external_user_id'=>$userId],JSON_UNESCAPED_UNICODE),json_encode(['can_comment'=>$canComment,'can_upload'=>$canUpload],JSON_UNESCAPED_UNICODE)]);
        });
        Audit::log('EXTERNAL_TICKET_GRANTED','ticket',$ticketId,null,['external_user_id'=>$userId,'can_comment'=>$canComment,'can_upload'=>$canUpload]);
        $result=['email_status'=>null];
        try{
            $result=(new NotificationService())->notifyUser(
                (int)$external['id'],(string)$external['email'],$ticketId,'EXTERNAL_GRANTED',
                'Nuevo caso compartido · '.$ticket['ticket_number'],
                'Necesitamos tu apoyo en “'.$ticket['subject'].'”. Abre el caso para revisar el contexto, responder o adjuntar evidencia según lo que esté habilitado.',
                APP_BASE_URL.'/tickets/view?id='.$ticketId,true,true
            );
        }catch(\Throwable $e){Logger::error($e);$result['email_status']='FAILED';}
        $status=$result['email_status']??null;
        Flash::set($this->deliveryMessage('Caso compartido.',$status),$this->deliveryFlashType($status));header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }

    public function revoke(): void
    {
        Auth::requirePermission('external.manage');Csrf::verify($_POST['_csrf']??null);
        $ticketId=(int)Http::post('ticket_id');$userId=(int)Http::post('user_id');if($ticketId<=0||$userId<=0)throw new \RuntimeException('No fue posible completar la acción.');
        $pdo=Database::pdo();
        $info=$pdo->prepare("SELECT u.id,u.email,u.full_name,t.ticket_number,t.subject FROM external_ticket_access eta JOIN users u ON u.id=eta.user_id JOIN tickets t ON t.id=eta.ticket_id WHERE eta.ticket_id=? AND eta.user_id=? AND eta.revoked_at IS NULL LIMIT 1");$info->execute([$ticketId,$userId]);$current=$info->fetch();
        Database::transaction(function(PDO $pdo)use($ticketId,$userId):void{
            $pdo->prepare('UPDATE external_ticket_access SET revoked_at=NOW() WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL')->execute([$ticketId,$userId]);
            $left=$pdo->prepare('SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND revoked_at IS NULL');$left->execute([$ticketId]);if((int)$left->fetchColumn()===0)$pdo->prepare("UPDATE tickets SET visibility_mode='INTERNAL',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,created_at) VALUES(?,'EXTERNAL_REVOKED',?,'USER',?,NOW())")
                ->execute([$ticketId,Auth::id(),json_encode(['external_user_id'=>$userId],JSON_UNESCAPED_UNICODE)]);
        });
        Audit::log('EXTERNAL_TICKET_REVOKED','ticket',$ticketId,['external_user_id'=>$userId],null);
        if($current){
            $result=['email_status'=>null];
            try{
                $result=(new NotificationService())->notifyUser(
                    (int)$current['id'],(string)$current['email'],$ticketId,'EXTERNAL_REVOKED',
                    'Participación finalizada · '.$current['ticket_number'],
                    'Tu participación en “'.$current['subject'].'” finalizó. El caso ya no aparece entre tus casos asignados.',
                    APP_BASE_URL.'/mis-tickets',true,true
                );
            }catch(\Throwable $e){Logger::error($e);$result['email_status']='FAILED';}
            $status=$result['email_status']??null;
            Flash::set($this->deliveryMessage('Acceso retirado.',$status),$this->deliveryFlashType($status));
        }else Flash::set('Acceso retirado.','success');
        header('Location: '.APP_BASE_URL.'/admin/externos');exit;
    }

    private function deliveryMessage(string $base,?string $status): string
    {
        return match($status){
            'SENT'=>$base.' Correo enviado correctamente.',
            'SKIPPED'=>$base.' En PC TEST el correo quedó registrado en modo de prueba.',
            'FAILED'=>$base.' El correo no pudo enviarse; revisa Correo y notificaciones.',
            default=>$base,
        };
    }

    private function deliveryFlashType(?string $status): string
    {
        return match($status){
            'FAILED'=>'danger',
            'SKIPPED'=>'info',
            default=>'success',
        };
    }
}
