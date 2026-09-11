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
        $users=$pdo->query(
            "SELECT u.id,u.full_name,u.email,u.phone,u.status,
                    ep.organization_name,ep.external_type,ep.notes,
                    (SELECT COUNT(*) FROM external_ticket_access eta
                     WHERE eta.user_id=u.id AND eta.revoked_at IS NULL) active_cases,
                    EXISTS(
                        SELECT 1 FROM audit_logs al
                        WHERE al.action='USER_CONVERTED_TO_EXTERNAL'
                          AND al.entity_type='user'
                          AND CAST(al.entity_id AS UNSIGNED)=u.id
                    ) converted_from_internal
             FROM users u
             JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL'
             ORDER BY FIELD(u.status,'ACTIVE','PENDING','BLOCKED','DISABLED'),
                      COALESCE(ep.organization_name,u.full_name),u.full_name"
        )->fetchAll();
        $shareUsers=array_values(array_filter($users,static fn(array $row):bool=>($row['status']??'')==='ACTIVE'));

        $tickets=$pdo->query(
            "SELECT t.id,t.ticket_number,t.subject,t.status,t.case_type,t.visibility_mode,p.name park_name
             FROM tickets t
             LEFT JOIN parks p ON p.id=t.park_id
             WHERE t.deleted_at IS NULL AND t.status NOT IN('CLOSED','CANCELLED')
             ORDER BY t.created_at DESC LIMIT 150"
        )->fetchAll();

        $access=$pdo->query(
            "SELECT eta.ticket_id,eta.user_id,eta.can_comment,eta.can_upload,eta.granted_at,
                    t.ticket_number,t.subject,u.full_name,ep.organization_name
             FROM external_ticket_access eta
             JOIN tickets t ON t.id=eta.ticket_id
             JOIN users u ON u.id=eta.user_id
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE eta.revoked_at IS NULL
             ORDER BY eta.granted_at DESC"
        )->fetchAll();

        $internalRoles=$pdo->query("SELECT id,code,name FROM roles WHERE is_active=1 AND code<>'EXTERNAL' ORDER BY FIELD(code,'REQUESTER','TECHNICIAN','SUPERVISOR','MANAGEMENT','SEMIADMIN','ADMIN'),name")->fetchAll();
        $regions=$pdo->query('SELECT id,name FROM regions WHERE is_active=1 ORDER BY name')->fetchAll();
        $parks=$pdo->query('SELECT id,name,region_id FROM parks WHERE is_active=1 ORDER BY name')->fetchAll();
        $areas=$pdo->query('SELECT id,name FROM areas WHERE is_active=1 ORDER BY name')->fetchAll();
        $positions=$pdo->query('SELECT id,code,name FROM positions WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
        $managers=$pdo->query(
            "SELECT DISTINCT u.id,u.full_name,pos.name position_name
             FROM users u
             JOIN user_assignments ua ON ua.user_id=u.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             JOIN positions pos ON pos.id=ua.position_id
             WHERE u.deleted_at IS NULL AND u.access_type='INTERNAL' AND u.status IN('ACTIVE','PENDING')
             ORDER BY FIELD(pos.code,'MANAGEMENT','REGIONAL_SUPERVISOR','PARK_MANAGER','TECHNOLOGY','ADMINISTRATION','OPERATIONS','MAINTENANCE','PARK_USER','OTHER'),u.full_name"
        )->fetchAll();
        View::render('admin/externals',[
            'user'=>Auth::user(),
            'users'=>$users,
            'shareUsers'=>$shareUsers,
            'tickets'=>$tickets,
            'access'=>$access,
            'internalRoles'=>$internalRoles,
            'regions'=>$regions,
            'parks'=>$parks,
            'areas'=>$areas,
            'positions'=>$positions,
            'managers'=>$managers,
            'flash'=>Flash::pull(),
        ]);
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

    public function convertInternal(): void
    {
        Auth::requirePermission('users.manage');
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);

        $uid=(int)Http::post('user_id');
        $organization=trim(Http::post('organization_name'));
        $type=strtoupper(trim(Http::post('external_type')));
        $notes=trim(Http::post('notes'));
        $confirmed=Http::post('confirm_convert')==='1';

        if($uid<=0)$this->rejectTo('Selecciona un usuario interno válido.','/admin/users');
        if($uid===(int)Auth::id())$this->rejectTo('No puedes convertir tu propia cuenta a proveedor externo.','/admin/users');
        if(!$confirmed)$this->rejectTo('Confirma que deseas convertir esta cuenta a proveedor externo.','/admin/users');
        if(mb_strlen($organization)<2)$this->rejectTo('Ingresa el proveedor u organización.','/admin/users');
        if(!in_array($type,['PROVIDER','PARTNER','OTHER'],true))$type='PROVIDER';

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT u.id,u.email,u.full_name,u.phone,u.status,u.role_id,u.access_type,
                    r.code role_code,r.name role_name
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL AND u.access_type='INTERNAL'
             LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before)$this->rejectTo('Ese usuario ya no está disponible como cuenta interna.','/admin/users');

        if(($before['role_code']??'')==='ADMIN'&&Auth::role()!=='ADMIN'){
            $this->rejectTo('Solo un Administrador puede convertir otra cuenta Administrador.','/admin/users');
        }
        if(($before['role_code']??'')==='ADMIN'){
            $admins=$pdo->prepare(
                "SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id
                 WHERE u.id<>? AND u.deleted_at IS NULL AND u.access_type='INTERNAL'
                   AND u.status IN('ACTIVE','PENDING') AND r.code='ADMIN'"
            );
            $admins->execute([$uid]);
            if((int)$admins->fetchColumn()===0){
                $this->rejectTo('Debe quedar al menos un Administrador interno activo.','/admin/users');
            }
        }

        $active=$pdo->prepare(
            "SELECT COUNT(*) FROM tickets
             WHERE assigned_to=? AND deleted_at IS NULL
               AND status NOT IN('RESOLVED','CLOSED','CANCELLED')"
        );
        $active->execute([$uid]);
        $activeTickets=(int)$active->fetchColumn();
        if($activeTickets>0){
            $this->rejectTo(
                'No se puede convertir todavía: esta persona tiene '.$activeTickets.' caso(s) activo(s) asignado(s). Reasígnalos primero.',
                '/admin/users'
            );
        }

        $externalRoleId=(int)$pdo->query(
            "SELECT id FROM roles WHERE code='EXTERNAL' AND is_active=1 LIMIT 1"
        )->fetchColumn();
        if($externalRoleId<=0)$this->rejectTo('El perfil de proveedor externo no está disponible.','/admin/users');

        $assignment=$pdo->prepare(
            "SELECT ua.assignment_type,ua.region_id,ua.park_id,ua.area_id,ua.position_id,ua.manager_user_id
             FROM user_assignments ua
             WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             ORDER BY ua.id DESC LIMIT 1"
        );
        $assignment->execute([$uid]);
        $before['assignment']=$assignment->fetch()?:null;

        Database::transaction(function(PDO $pdo)use($uid,$externalRoleId,$organization,$type,$notes):void{
            $pdo->prepare(
                "UPDATE support_team_members
                 SET is_active=0,ended_at=NOW()
                 WHERE user_id=? AND is_active=1"
            )->execute([$uid]);

            $pdo->prepare(
                "UPDATE user_assignments
                 SET status='ENDED',ends_at=NOW()
                 WHERE user_id=? AND status='ACTIVE' AND ends_at IS NULL"
            )->execute([$uid]);

            $pdo->prepare(
                "UPDATE user_assignments
                 SET manager_user_id=NULL
                 WHERE manager_user_id=? AND status='ACTIVE' AND ends_at IS NULL"
            )->execute([$uid]);

            $pdo->prepare("DELETE FROM user_permission_overrides WHERE user_id=?")->execute([$uid]);
            $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);

            $pdo->prepare(
                "UPDATE users
                 SET role_id=?,access_type='EXTERNAL',status='ACTIVE',updated_at=NOW()
                 WHERE id=? AND deleted_at IS NULL"
            )->execute([$externalRoleId,$uid]);

            $pdo->prepare(
                "INSERT INTO external_profiles(user_id,organization_name,external_type,notes,created_at,updated_at)
                 VALUES(?,?,?,?,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE
                    organization_name=VALUES(organization_name),
                    external_type=VALUES(external_type),
                    notes=VALUES(notes),
                    updated_at=NOW()"
            )->execute([$uid,$organization,$type,$notes?:null]);
        });

        Audit::log('USER_CONVERTED_TO_EXTERNAL','user',$uid,$before,[
            'access_type'=>'EXTERNAL',
            'role_code'=>'EXTERNAL',
            'status'=>'ACTIVE',
            'organization_name'=>$organization,
            'external_type'=>$type,
            'notes'=>$notes?:null,
        ],['permissions_reset'=>true,'sessions_revoked'=>true]);

        $result=['email_status'=>null];
        try{
            $result=(new NotificationService())->notifyUser(
                $uid,(string)$before['email'],null,'USER_CONVERTED_TO_EXTERNAL',
                'Tu acceso al Helpdesk cambió',
                'Hola '.$before['full_name'].'. Tu cuenta ahora funciona como proveedor externo. Solo podrás consultar los casos que el equipo de Carrousel comparta expresamente contigo.',
                APP_BASE_URL.'/login',true,true
            );
        }catch(\Throwable $e){
            Logger::error($e);
            $result['email_status']='FAILED';
        }

        $status=$result['email_status']??null;
        Flash::set(
            $this->deliveryMessage('Usuario convertido a proveedor externo.',$status),
            $this->deliveryFlashType($status)
        );
        header('Location: '.APP_BASE_URL.'/admin/externos');
        exit;
    }

    public function convertExternalToInternal(): void
    {
        Auth::requirePermission('users.manage');
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);

        $uid=(int)Http::post('user_id');
        if($uid<=0)$this->rejectTo('Selecciona un proveedor válido.','/admin/externos');
        if(Http::post('confirm_convert_internal')!=='1')$this->rejectTo('Confirma que deseas convertir esta cuenta a usuario interno.','/admin/externos');

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT u.id,u.email,u.full_name,u.phone,u.status,u.role_id,u.access_type,
                    ep.organization_name,ep.external_type,ep.notes
             FROM users u
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.id=? AND u.deleted_at IS NULL AND u.access_type='EXTERNAL'
             LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before)$this->rejectTo('Ese proveedor ya no está disponible como cuenta externa.','/admin/externos');

        $data=$this->readInternalConversionPayload($pdo,$uid);
        if($data['role_code']==='ADMIN'&&Auth::role()!=='ADMIN')$this->rejectTo('Solo un Administrador puede asignar el perfil Administrador.','/admin/externos');

        $tickets=$pdo->prepare('SELECT ticket_id FROM external_ticket_access WHERE user_id=? AND revoked_at IS NULL');
        $tickets->execute([$uid]);
        $ticketIds=array_map('intval',$tickets->fetchAll(PDO::FETCH_COLUMN)?:[]);

        Database::transaction(function(PDO $pdo)use($uid,$data,$ticketIds):void{
            $pdo->prepare("UPDATE external_ticket_access SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
            foreach($ticketIds as $ticketId){
                $left=$pdo->prepare('SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND revoked_at IS NULL');
                $left->execute([$ticketId]);
                if((int)$left->fetchColumn()===0)$pdo->prepare("UPDATE tickets SET visibility_mode='INTERNAL',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            }

            $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE user_assignments SET status='ENDED',ends_at=NOW() WHERE user_id=? AND status='ACTIVE' AND ends_at IS NULL")->execute([$uid]);

            $pdo->prepare(
                "UPDATE users SET role_id=?,access_type='INTERNAL',status=?,updated_at=NOW()
                 WHERE id=? AND deleted_at IS NULL AND access_type='EXTERNAL'"
            )->execute([$data['role_id'],$data['status'],$uid]);

            $pdo->prepare(
                "INSERT INTO user_assignments(user_id,region_id,park_id,area_id,position_id,assignment_type,manager_user_id,status,starts_at,reason,created_by,created_at)
                 VALUES(?,?,?,?,?,?,?,'ACTIVE',NOW(),?,?,NOW())"
            )->execute([
                $uid,$data['region_id'],$data['park_id'],$data['area_id'],$data['position_id'],
                $data['assignment_type'],$data['manager_user_id'],'Conversión de proveedor a usuario interno',Auth::id()
            ]);
            $this->syncInternalSupportMembership($pdo,$uid,$data['role_code'],$data['status']);
        });

        Audit::log('USER_CONVERTED_TO_INTERNAL','user',$uid,$before,[
            'access_type'=>'INTERNAL','role_id'=>$data['role_id'],'role_code'=>$data['role_code'],'status'=>$data['status'],
            'assignment_type'=>$data['assignment_type'],'region_id'=>$data['region_id'],'park_id'=>$data['park_id'],
            'area_id'=>$data['area_id'],'position_id'=>$data['position_id'],'manager_user_id'=>$data['manager_user_id'],
            'external_profile_preserved'=>true,'revoked_ticket_ids'=>$ticketIds,
        ],['sessions_revoked'=>true,'external_history_preserved'=>true]);

        Flash::set('Proveedor convertido a usuario interno. Su historial externo se conservó y deberá iniciar sesión nuevamente.','success');
        header('Location: '.APP_BASE_URL.'/admin/users');
        exit;
    }
    public function updateUser(): void
    {
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);

        $uid=(int)Http::post('user_id');
        $name=trim(Http::post('name'));
        $email=strtolower(trim(Http::post('email')));
        $phone=trim(Http::post('phone'));
        $organization=trim(Http::post('organization_name'));
        $type=strtoupper(trim(Http::post('external_type')));
        $notes=trim(Http::post('notes'));

        if($uid<=0)$this->rejectTo('Proveedor no válido.','/admin/externos');
        if(mb_strlen($name)<3)$this->rejectTo('Ingresa el nombre del contacto.','/admin/externos');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))$this->rejectTo('Ingresa un correo válido.','/admin/externos');
        if(mb_strlen($organization)<2)$this->rejectTo('Ingresa el proveedor u organización.','/admin/externos');
        if(!in_array($type,['PROVIDER','PARTNER','OTHER'],true))$type='PROVIDER';

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT u.id,u.full_name,u.email,u.phone,u.status,ep.organization_name,ep.external_type,ep.notes
             FROM users u
             JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.id=? AND u.access_type='EXTERNAL' AND u.deleted_at IS NULL LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before)$this->rejectTo('Ese proveedor ya no está disponible.','/admin/externos');

        $duplicate=$pdo->prepare('SELECT id FROM users WHERE LOWER(email)=? AND id<>? LIMIT 1');
        $duplicate->execute([$email,$uid]);
        if($duplicate->fetchColumn())$this->rejectTo('Ese correo ya pertenece a otra cuenta.','/admin/externos');

        Database::transaction(function(PDO $pdo)use($uid,$name,$email,$phone,$organization,$type,$notes,$before):void{
            $pdo->prepare(
                "UPDATE users SET full_name=?,email=?,phone=?,updated_at=NOW()
                 WHERE id=? AND access_type='EXTERNAL' AND deleted_at IS NULL"
            )->execute([$name,$email,$phone?:null,$uid]);

            $pdo->prepare(
                "INSERT INTO external_profiles(user_id,organization_name,external_type,notes,created_at,updated_at)
                 VALUES(?,?,?,?,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE
                    organization_name=VALUES(organization_name),
                    external_type=VALUES(external_type),
                    notes=VALUES(notes),
                    updated_at=NOW()"
            )->execute([$uid,$organization,$type,$notes?:null]);

            if(strtolower((string)$before['email'])!==$email){
                $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
                $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);
            }
        });

        Audit::log('EXTERNAL_USER_UPDATED','user',$uid,$before,[
            'full_name'=>$name,'email'=>$email,'phone'=>$phone?:null,
            'organization_name'=>$organization,'external_type'=>$type,'notes'=>$notes?:null
        ]);
        Flash::set('Proveedor actualizado correctamente.','success');
        header('Location: '.APP_BASE_URL.'/admin/externos');
        exit;
    }

    public function deactivateUser(): void
    {
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);
        $uid=(int)Http::post('user_id');
        if($uid<=0)$this->rejectTo('Proveedor no válido.','/admin/externos');

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT u.id,u.full_name,u.email,u.status,ep.organization_name
             FROM users u
             JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.id=? AND u.access_type='EXTERNAL' AND u.deleted_at IS NULL LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before)$this->rejectTo('Ese proveedor ya no está disponible.','/admin/externos');
        if(($before['status']??'')==='DISABLED'){
            Flash::set('Ese proveedor ya está desactivado.','info');
            header('Location: '.APP_BASE_URL.'/admin/externos');
            exit;
        }

        $tickets=$pdo->prepare(
            'SELECT ticket_id FROM external_ticket_access WHERE user_id=? AND revoked_at IS NULL'
        );
        $tickets->execute([$uid]);
        $ticketIds=array_map('intval',$tickets->fetchAll(PDO::FETCH_COLUMN)?:[]);

        Database::transaction(function(PDO $pdo)use($uid,$ticketIds):void{
            $pdo->prepare(
                "UPDATE external_ticket_access SET revoked_at=NOW()
                 WHERE user_id=? AND revoked_at IS NULL"
            )->execute([$uid]);

            foreach($ticketIds as $ticketId){
                $left=$pdo->prepare(
                    'SELECT COUNT(*) FROM external_ticket_access WHERE ticket_id=? AND revoked_at IS NULL'
                );
                $left->execute([$ticketId]);
                if((int)$left->fetchColumn()===0){
                    $pdo->prepare(
                        "UPDATE tickets SET visibility_mode='INTERNAL',updated_at=NOW() WHERE id=?"
                    )->execute([$ticketId]);
                }
            }

            $pdo->prepare(
                "UPDATE users SET status='DISABLED',updated_at=NOW()
                 WHERE id=? AND access_type='EXTERNAL' AND deleted_at IS NULL"
            )->execute([$uid]);
            $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);
        });

        Audit::log('EXTERNAL_USER_DISABLED','user',$uid,$before,[
            'status'=>'DISABLED',
            'revoked_ticket_ids'=>$ticketIds,
        ]);
        Flash::set('Proveedor desactivado. Sus accesos vigentes a casos fueron revocados.','success');
        header('Location: '.APP_BASE_URL.'/admin/externos');
        exit;
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

    private function readInternalConversionPayload(PDO $pdo,int $uid): array
    {
        $roleId=(int)Http::post('role_id');
        $status=strtoupper(trim(Http::post('status'))?:'ACTIVE');
        $type=strtoupper(trim(Http::post('assignment_type')));
        $region=(int)Http::post('region_id')?:null;
        $park=(int)Http::post('park_id')?:null;
        $area=(int)Http::post('area_id')?:null;
        $position=(int)Http::post('position_id')?:null;
        $manager=(int)Http::post('manager_user_id')?:null;

        if($roleId<=0)$this->rejectTo('Selecciona el perfil interno.','/admin/externos');
        if(!in_array($status,['PENDING','ACTIVE','BLOCKED','DISABLED'],true))$this->rejectTo('Estado de usuario no válido.','/admin/externos');
        if(!in_array($type,['PARK','CORPORATE','OTHER'],true))$this->rejectTo('Selecciona dónde trabajará esta persona.','/admin/externos');
        if(!$position)$this->rejectTo('Selecciona el puesto o función.','/admin/externos');
        if($manager===$uid)$this->rejectTo('Una persona no puede ser su propio responsable directo.','/admin/externos');

        $role=$pdo->prepare("SELECT code FROM roles WHERE id=? AND is_active=1 AND code<>'EXTERNAL' LIMIT 1");
        $role->execute([$roleId]);$roleCode=(string)$role->fetchColumn();
        if($roleCode==='')$this->rejectTo('El perfil interno seleccionado no está disponible.','/admin/externos');

        if($type==='PARK'){
            if(!$park)$this->rejectTo('Selecciona el parque.','/admin/externos');
            $q=$pdo->prepare('SELECT region_id FROM parks WHERE id=? AND is_active=1 LIMIT 1');$q->execute([$park]);
            $parkRegion=(int)$q->fetchColumn()?:null;if(!$parkRegion)$this->rejectTo('El parque seleccionado no está disponible.','/admin/externos');
            $region=$parkRegion;$area=null;
        }elseif($type==='CORPORATE'){
            if(!$area)$this->rejectTo('Selecciona el área corporativa.','/admin/externos');
            $q=$pdo->prepare('SELECT COUNT(*) FROM areas WHERE id=? AND is_active=1');$q->execute([$area]);
            if((int)$q->fetchColumn()!==1)$this->rejectTo('El área seleccionada no está disponible.','/admin/externos');
            $park=null;
        }else{
            $park=null;$area=null;
        }

        $q=$pdo->prepare('SELECT COUNT(*) FROM positions WHERE id=? AND is_active=1');$q->execute([$position]);
        if((int)$q->fetchColumn()!==1)$this->rejectTo('El puesto seleccionado no está disponible.','/admin/externos');
        if($manager){
            $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND deleted_at IS NULL AND access_type='INTERNAL' AND status IN('ACTIVE','PENDING')");
            $q->execute([$manager]);if((int)$q->fetchColumn()!==1)$this->rejectTo('El responsable directo seleccionado no está disponible.','/admin/externos');
        }
        return ['role_id'=>$roleId,'role_code'=>$roleCode,'status'=>$status,'assignment_type'=>$type,'region_id'=>$region,'park_id'=>$park,'area_id'=>$area,'position_id'=>$position,'manager_user_id'=>$manager];
    }

    private function syncInternalSupportMembership(PDO $pdo,int $uid,string $roleCode,string $status): void
    {
        $teamId=(int)$pdo->query("SELECT id FROM support_teams WHERE code='IT' AND is_active=1 LIMIT 1")->fetchColumn();
        if($teamId<=0)return;
        $eligible=in_array($roleCode,['ADMIN','SEMIADMIN','TECHNICIAN'],true)&&$status==='ACTIVE';
        if($eligible){
            $pdo->prepare("INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at) VALUES(?,?,1,NOW(),NULL) ON DUPLICATE KEY UPDATE is_active=1,ended_at=NULL")->execute([$teamId,$uid]);
        }else{
            $pdo->prepare("UPDATE support_team_members SET is_active=0,ended_at=NOW() WHERE team_id=? AND user_id=?")->execute([$teamId,$uid]);
        }
    }
    private function rejectTo(string $message,string $path): never
    {
        Flash::set($message,'danger');
        header('Location: '.APP_BASE_URL.$path);
        exit;
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
