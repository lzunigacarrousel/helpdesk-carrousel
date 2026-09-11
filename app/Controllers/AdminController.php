<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use PDO;

final class AdminController
{
    public function users(): void
    {
        Auth::requirePermission('users.manage');
        $pdo=Database::pdo();

        $users=$pdo->query(
            "SELECT u.id,u.email,u.full_name,u.phone,u.status,u.role_id,u.access_type,u.last_login_at,
                    r.code role_code,r.name role_name,
                    ua.id assignment_id,ua.assignment_type,ua.region_id,ua.park_id,ua.area_id,
                    ua.position_id,ua.manager_user_id,
                    rg.name region_name,p.name park_name,a.name area_name,pos.name position_name,m.full_name manager_name,
                    (SELECT COUNT(*) FROM tickets t
                     WHERE t.assigned_to=u.id AND t.deleted_at IS NULL
                       AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')) active_ticket_count
             FROM users u
             JOIN roles r ON r.id=u.role_id
             LEFT JOIN user_assignments ua ON ua.id=(
                SELECT MAX(x.id) FROM user_assignments x
                WHERE x.user_id=u.id AND x.status='ACTIVE' AND x.ends_at IS NULL
             )
             LEFT JOIN regions rg ON rg.id=ua.region_id
             LEFT JOIN parks p ON p.id=ua.park_id
             LEFT JOIN areas a ON a.id=ua.area_id
             LEFT JOIN positions pos ON pos.id=ua.position_id
             LEFT JOIN users m ON m.id=ua.manager_user_id
             WHERE u.deleted_at IS NULL AND u.access_type='INTERNAL'
             ORDER BY FIELD(u.status,'PENDING','ACTIVE','BLOCKED','DISABLED'),u.full_name"
        )->fetchAll();

        $roles=$pdo->query("SELECT id,code,name FROM roles WHERE is_active=1 AND code<>'EXTERNAL' ORDER BY FIELD(code,'REQUESTER','TECHNICIAN','SUPERVISOR','MANAGEMENT','SEMIADMIN','ADMIN'),name")->fetchAll();
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

        View::render('admin/users',compact('users','roles','regions','parks','areas','positions','managers')+['user'=>Auth::user(),'flash'=>Flash::pull()]);
    }

    public function create(): void
    {
        Auth::requirePermission('users.manage');
        Csrf::verify($_POST['_csrf']??null);
        $pdo=Database::pdo();

        try{
            $data=$this->readPayload($pdo,null,null);
            $this->assertEmailAvailable($pdo,$data['email'],null);
            if($data['role_code']==='ADMIN' && Auth::role()!=='ADMIN') throw new \RuntimeException('Solo un Administrador puede crear otra cuenta Administrador.');

            $uid=Database::transaction(function(PDO $pdo)use($data):int{
                $stmt=$pdo->prepare(
                    "INSERT INTO users(role_id,access_type,email,full_name,phone,status,created_at,updated_at)
                     VALUES(?,'INTERNAL',?,?,?, ?,NOW(),NOW())"
                );
                $stmt->execute([$data['role_id'],$data['email'],$data['full_name'],$data['phone'],$data['status']]);
                $uid=(int)$pdo->lastInsertId();
                $this->replaceAssignment($pdo,$uid,$data,'Creación administrativa Helpdesk');
                $this->syncSupportMembership($pdo,$uid,$data['role_code'],$data['status']);
                return $uid;
            });

            Audit::log('USER_CREATED','user',$uid,null,[
                'email'=>$data['email'],'full_name'=>$data['full_name'],'status'=>$data['status'],
                'role_id'=>$data['role_id'],'role_code'=>$data['role_code'],
                'assignment_type'=>$data['assignment_type'],'region_id'=>$data['region_id'],
                'park_id'=>$data['park_id'],'area_id'=>$data['area_id'],'position_id'=>$data['position_id'],
                'manager_user_id'=>$data['manager_user_id']
            ]);
            Flash::set('Usuario creado. Ya puede ingresar con su correo y código de acceso.','success');
            $this->redirectUsers();
        }catch(\RuntimeException $e){
            Flash::set($e->getMessage(),'danger');
            header('Location: '.APP_BASE_URL.'/admin/users?create=1');
            exit;
        }
    }
    public function assign(): void
    {
        Auth::requirePermission('users.manage');
        Csrf::verify($_POST['_csrf']??null);
        $pdo=Database::pdo();
        $uid=(int)Http::post('user_id');
        if($uid<=0) throw new \RuntimeException('Usuario no válido.');

        $q=$pdo->prepare(
            "SELECT u.id,u.email,u.full_name,u.phone,u.status,u.role_id,u.access_type,r.code role_code
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL AND u.access_type='INTERNAL' LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before) throw new \RuntimeException('Usuario interno no encontrado.');

        if(($before['role_code']??'')==='ADMIN' && Auth::role()!=='ADMIN') throw new \RuntimeException('Solo un Administrador puede modificar otra cuenta Administrador.');

        $data=$this->readPayload($pdo,$uid,$before);
        $this->assertEmailAvailable($pdo,$data['email'],$uid);
        if($data['role_code']==='ADMIN' && Auth::role()!=='ADMIN') throw new \RuntimeException('Solo un Administrador puede asignar el perfil Administrador.');

        if($uid===(int)Auth::id()){
            if(in_array($data['status'],['BLOCKED','DISABLED'],true)) throw new \RuntimeException('No puedes bloquear o deshabilitar tu propia cuenta.');
            if(($before['role_code']??'')==='ADMIN' && $data['role_code']!=='ADMIN') throw new \RuntimeException('No puedes quitarte a ti mismo el perfil Administrador.');
        }
        if(($before['role_code']??'')==='ADMIN' && $data['role_code']!=='ADMIN') $this->assertAnotherActiveAdmin($pdo,$uid);

        Database::transaction(function(PDO $pdo)use($uid,$data):void{
            $pdo->prepare(
                "UPDATE users
                 SET email=?,full_name=?,phone=?,status=?,role_id=?,access_type='INTERNAL',updated_at=NOW()
                 WHERE id=? AND deleted_at IS NULL"
            )->execute([$data['email'],$data['full_name'],$data['phone'],$data['status'],$data['role_id'],$uid]);

            $this->replaceAssignment($pdo,$uid,$data,'Actualización administrativa Helpdesk');
            $this->syncSupportMembership($pdo,$uid,$data['role_code'],$data['status']);

            if(in_array($data['status'],['BLOCKED','DISABLED'],true)){
                $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
                $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);
            }
        });

        Audit::log('USER_UPDATED','user',$uid,$before,[
            'email'=>$data['email'],'full_name'=>$data['full_name'],'phone'=>$data['phone'],
            'status'=>$data['status'],'role_id'=>$data['role_id'],'role_code'=>$data['role_code'],
            'assignment_type'=>$data['assignment_type'],'region_id'=>$data['region_id'],
            'park_id'=>$data['park_id'],'area_id'=>$data['area_id'],'position_id'=>$data['position_id'],
            'manager_user_id'=>$data['manager_user_id']
        ]);
        Flash::set('Usuario actualizado correctamente.','success');
        $this->redirectUsers();
    }

    public function delete(): void
    {
        Auth::requirePermission('users.manage');
        Csrf::verify($_POST['_csrf']??null);
        $pdo=Database::pdo();
        $uid=(int)Http::post('user_id');
        if($uid<=0) throw new \RuntimeException('Usuario no válido.');
        if(Http::post('confirm_delete')!=='1') throw new \RuntimeException('Confirma la eliminación del usuario.');
        if($uid===(int)Auth::id()) throw new \RuntimeException('No puedes eliminar tu propia cuenta.');

        $q=$pdo->prepare(
            "SELECT u.id,u.email,u.full_name,u.status,u.role_id,u.access_type,r.code role_code
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL AND u.access_type='INTERNAL' LIMIT 1"
        );
        $q->execute([$uid]);
        $before=$q->fetch();
        if(!$before) throw new \RuntimeException('Usuario interno no encontrado.');
        if(($before['role_code']??'')==='ADMIN' && Auth::role()!=='ADMIN') throw new \RuntimeException('Solo un Administrador puede eliminar otra cuenta Administrador.');
        if(($before['role_code']??'')==='ADMIN') $this->assertAnotherActiveAdmin($pdo,$uid);

        $tickets=$pdo->prepare(
            "SELECT COUNT(*) FROM tickets
             WHERE assigned_to=? AND deleted_at IS NULL AND status NOT IN('RESOLVED','CLOSED','CANCELLED')"
        );
        $tickets->execute([$uid]);
        $activeTickets=(int)$tickets->fetchColumn();
        if($activeTickets>0) throw new \RuntimeException('Este usuario todavía tiene '.$activeTickets.' caso(s) activo(s). Reasígnalos antes de eliminarlo.');

        Database::transaction(function(PDO $pdo)use($uid):void{
            $pdo->prepare("UPDATE users SET status='DISABLED',deleted_at=NOW(),updated_at=NOW() WHERE id=? AND deleted_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE user_assignments SET status='ENDED',ends_at=NOW() WHERE user_id=? AND status='ACTIVE' AND ends_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE user_assignments SET manager_user_id=NULL WHERE manager_user_id=? AND status='ACTIVE' AND ends_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE support_team_members SET is_active=0,ended_at=NOW() WHERE user_id=? AND is_active=1")->execute([$uid]);
            $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$uid]);
            $pdo->prepare("UPDATE otp_codes SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL")->execute([$uid]);
        });

        Audit::log('USER_DELETED','user',$uid,$before,['status'=>'DISABLED','deleted'=>true]);
        Flash::set('Usuario eliminado del acceso activo. Su historial y tickets se conservaron.','success');
        $this->redirectUsers();
    }

    private function readPayload(PDO $pdo, ?int $userId, ?array $fallback): array
    {
        $email=strtolower(trim(Http::post('email')) ?: (string)($fallback['email']??''));
        $fullName=trim(Http::post('full_name')) ?: trim((string)($fallback['full_name']??''));
        $phone=trim(Http::post('phone'));
        if($phone==='' && $fallback && !array_key_exists('phone',$_POST)) $phone=(string)($fallback['phone']??'');
        $phone=$phone!==''?$phone:null;
        $status=strtoupper(trim(Http::post('status')) ?: (string)($fallback['status']??'ACTIVE'));
        $roleId=(int)Http::post('role_id');
        $type=strtoupper(trim(Http::post('assignment_type')));
        $region=(int)Http::post('region_id') ?: null;
        $park=(int)Http::post('park_id') ?: null;
        $area=(int)Http::post('area_id') ?: null;
        $position=(int)Http::post('position_id') ?: null;
        $manager=(int)Http::post('manager_user_id') ?: null;

        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Ingresa un correo válido.');
        if(mb_strlen($fullName)<3) throw new \RuntimeException('Ingresa el nombre completo del usuario.');
        if(!in_array($status,['PENDING','ACTIVE','BLOCKED','DISABLED'],true)) throw new \RuntimeException('Estado de usuario no válido.');
        if($roleId<=0) throw new \RuntimeException('Selecciona el perfil del usuario.');
        if(!in_array($type,['PARK','CORPORATE','OTHER'],true)) throw new \RuntimeException('Selecciona el tipo de ubicación.');
        if($type==='PARK'&&!$park) throw new \RuntimeException('Selecciona el parque del usuario.');
        if($type==='CORPORATE'&&!$area) throw new \RuntimeException('Selecciona el área del usuario.');
        if(!$position) throw new \RuntimeException('Selecciona el puesto o función.');
        if($userId&&$manager===$userId) throw new \RuntimeException('Una persona no puede ser su propio responsable.');

        $role=$pdo->prepare('SELECT code FROM roles WHERE id=? AND is_active=1 LIMIT 1');
        $role->execute([$roleId]);
        $roleCode=(string)$role->fetchColumn();
        if($roleCode===''||$roleCode==='EXTERNAL') throw new \RuntimeException('Para proveedores utiliza la sección Proveedores externos.');

        if($park){
            $q=$pdo->prepare('SELECT region_id FROM parks WHERE id=? AND is_active=1 LIMIT 1');
            $q->execute([$park]);
            $parkRegion=(int)$q->fetchColumn() ?: null;
            if(!$parkRegion && $type==='PARK') throw new \RuntimeException('El parque seleccionado no está disponible.');
            if($parkRegion) $region=$parkRegion;
        }
        if($area){
            $q=$pdo->prepare('SELECT COUNT(*) FROM areas WHERE id=? AND is_active=1');
            $q->execute([$area]);
            if((int)$q->fetchColumn()!==1) throw new \RuntimeException('El área seleccionada no está disponible.');
        }
        $q=$pdo->prepare('SELECT COUNT(*) FROM positions WHERE id=? AND is_active=1');
        $q->execute([$position]);
        if((int)$q->fetchColumn()!==1) throw new \RuntimeException('El puesto seleccionado no está disponible.');

        if($manager){
            $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND deleted_at IS NULL AND access_type='INTERNAL' AND status IN('ACTIVE','PENDING')");
            $q->execute([$manager]);
            if((int)$q->fetchColumn()!==1) throw new \RuntimeException('El responsable seleccionado no está disponible.');
        }

        return [
            'email'=>$email,'full_name'=>$fullName,'phone'=>$phone,'status'=>$status,
            'role_id'=>$roleId,'role_code'=>$roleCode,'assignment_type'=>$type,
            'region_id'=>$region,'park_id'=>$park,'area_id'=>$area,'position_id'=>$position,
            'manager_user_id'=>$manager,
        ];
    }

    private function assertEmailAvailable(PDO $pdo, string $email, ?int $ignoreUserId): void
    {
        if($ignoreUserId){
            $q=$pdo->prepare('SELECT id,access_type,deleted_at FROM users WHERE LOWER(email)=? AND id<>? LIMIT 1');
            $q->execute([$email,$ignoreUserId]);
        }else{
            $q=$pdo->prepare('SELECT id,access_type,deleted_at FROM users WHERE LOWER(email)=? LIMIT 1');
            $q->execute([$email]);
        }
        $existing=$q->fetch();
        if(!$existing)return;
        if(!empty($existing['deleted_at'])) throw new \RuntimeException('Este correo pertenece a una cuenta histórica. Revisa el historial antes de crear una identidad duplicada.');
        if(($existing['access_type']??'INTERNAL')==='EXTERNAL') throw new \RuntimeException('Este correo ya pertenece a un proveedor externo. Convierte esa cuenta nuevamente en usuario interno desde Proveedores externos.');
        throw new \RuntimeException('Ese correo ya tiene una cuenta interna en Helpdesk.');
    }
    private function replaceAssignment(PDO $pdo, int $uid, array $data, string $reason): void
    {
        $pdo->prepare("UPDATE user_assignments SET status='ENDED',ends_at=NOW() WHERE user_id=? AND status='ACTIVE' AND ends_at IS NULL")->execute([$uid]);
        $pdo->prepare(
            "INSERT INTO user_assignments(user_id,region_id,park_id,area_id,position_id,assignment_type,manager_user_id,status,starts_at,reason,created_by,created_at)
             VALUES(?,?,?,?,?,?,?,'ACTIVE',NOW(),?,?,NOW())"
        )->execute([
            $uid,$data['region_id'],$data['park_id'],$data['area_id'],$data['position_id'],
            $data['assignment_type'],$data['manager_user_id'],$reason,Auth::id()
        ]);
    }

    private function syncSupportMembership(PDO $pdo, int $uid, string $roleCode, string $status): void
    {
        $teamId=(int)$pdo->query("SELECT id FROM support_teams WHERE code='IT' AND is_active=1 LIMIT 1")->fetchColumn();
        if($teamId<=0) return;
        $eligible=in_array($roleCode,['ADMIN','SEMIADMIN','TECHNICIAN'],true)&&$status==='ACTIVE';
        if($eligible){
            $pdo->prepare("INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at) VALUES(?,?,1,NOW(),NULL) ON DUPLICATE KEY UPDATE is_active=1,ended_at=NULL")
                ->execute([$teamId,$uid]);
        }else{
            $pdo->prepare("UPDATE support_team_members SET is_active=0,ended_at=NOW() WHERE team_id=? AND user_id=?")
                ->execute([$teamId,$uid]);
        }
    }

    private function assertAnotherActiveAdmin(PDO $pdo, int $excludeUserId): void
    {
        $q=$pdo->prepare(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id<>? AND u.deleted_at IS NULL AND u.access_type='INTERNAL'
               AND u.status IN('ACTIVE','PENDING') AND r.code='ADMIN'"
        );
        $q->execute([$excludeUserId]);
        if((int)$q->fetchColumn()===0) throw new \RuntimeException('Debe quedar al menos un Administrador activo.');
    }

    private function redirectUsers(): never
    {
        header('Location: '.APP_BASE_URL.'/admin/users');
        exit;
    }
}
