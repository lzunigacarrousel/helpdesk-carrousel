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
            "SELECT u.id,u.email,u.full_name,u.phone,u.status,u.role_id,u.access_type,
                    r.code role_code,r.name role_name,
                    ua.id assignment_id,ua.assignment_type,ua.region_id,ua.park_id,ua.area_id,
                    ua.position_id,ua.manager_user_id,
                    rg.name region_name,p.name park_name,a.name area_name,pos.name position_name,m.full_name manager_name
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
             WHERE u.deleted_at IS NULL
             ORDER BY FIELD(u.status,'PENDING','ACTIVE','BLOCKED','DISABLED'),u.full_name"
        )->fetchAll();

        $roles=$pdo->query("SELECT id,code,name FROM roles WHERE is_active=1 ORDER BY FIELD(code,'REQUESTER','TECHNICIAN','SEMIADMIN','ADMIN','EXTERNAL'),name")->fetchAll();
        $regions=$pdo->query('SELECT id,name FROM regions WHERE is_active=1 ORDER BY name')->fetchAll();
        $parks=$pdo->query('SELECT id,name,region_id FROM parks WHERE is_active=1 ORDER BY name')->fetchAll();
        $areas=$pdo->query('SELECT id,name FROM areas WHERE is_active=1 ORDER BY name')->fetchAll();
        $positions=$pdo->query('SELECT id,code,name FROM positions WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
        $managers=$pdo->query(
            "SELECT DISTINCT u.id,u.full_name,pos.name position_name
             FROM users u
             JOIN user_assignments ua ON ua.user_id=u.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             JOIN positions pos ON pos.id=ua.position_id
             WHERE u.deleted_at IS NULL AND u.status IN('ACTIVE','PENDING')
               AND pos.code IN('PARK_MANAGER','REGIONAL_SUPERVISOR','MANAGEMENT')
             ORDER BY u.full_name"
        )->fetchAll();

        View::render('admin/users',compact('users','roles','regions','parks','areas','positions','managers')+['user'=>Auth::user(),'flash'=>Flash::pull()]);
    }

    public function assign(): void
    {
        Auth::requirePermission('users.manage');
        Csrf::verify($_POST['_csrf']??null);
        $pdo=Database::pdo();

        $uid=(int)Http::post('user_id');
        $rid=(int)Http::post('role_id');
        $type=strtoupper(trim(Http::post('assignment_type')));
        $region=(int)Http::post('region_id') ?: null;
        $park=(int)Http::post('park_id') ?: null;
        $area=(int)Http::post('area_id') ?: null;
        $position=(int)Http::post('position_id') ?: null;
        $manager=(int)Http::post('manager_user_id') ?: null;

        if($uid<=0||$rid<=0||!in_array($type,['PARK','CORPORATE','OTHER'],true)) throw new \RuntimeException('Datos de asignación no válidos.');
        if($type==='PARK'&&!$park) throw new \RuntimeException('Selecciona el parque del usuario.');
        if($type==='CORPORATE'&&!$area) throw new \RuntimeException('Selecciona el área del usuario.');
        if(!$position) throw new \RuntimeException('Selecciona el puesto o función.');
        if($manager===$uid) throw new \RuntimeException('Una persona no puede ser su propio responsable.');

        $old=$pdo->prepare('SELECT role_id,status,access_type FROM users WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $old->execute([$uid]);
        $before=$old->fetch();
        if(!$before) throw new \RuntimeException('Usuario no encontrado.');

        $role=$pdo->prepare('SELECT code FROM roles WHERE id=? AND is_active=1 LIMIT 1');
        $role->execute([$rid]);
        $roleCode=(string)$role->fetchColumn();
        if($roleCode==='') throw new \RuntimeException('Rol no disponible.');

        if($park){
            $q=$pdo->prepare('SELECT region_id FROM parks WHERE id=? AND is_active=1 LIMIT 1');
            $q->execute([$park]);
            $parkRegion=(int)$q->fetchColumn() ?: null;
            if($parkRegion) $region=$parkRegion;
        }

        Database::transaction(function(PDO $pdo)use($uid,$rid,$roleCode,$type,$region,$park,$area,$position,$manager):void{
            $pdo->prepare("UPDATE user_assignments SET status='ENDED',ends_at=NOW() WHERE user_id=? AND status='ACTIVE' AND ends_at IS NULL")->execute([$uid]);
            $pdo->prepare(
                "INSERT INTO user_assignments(user_id,region_id,park_id,area_id,position_id,assignment_type,manager_user_id,status,starts_at,reason,created_by,created_at)
                 VALUES(?,?,?,?,?,?,?,'ACTIVE',NOW(),'Asignación administrativa Helpdesk',?,NOW())"
            )->execute([$uid,$region,$park,$area,$position,$type,$manager,Auth::id()]);

            $accessType=$roleCode==='EXTERNAL'?'EXTERNAL':'INTERNAL';
            $pdo->prepare("UPDATE users SET role_id=?,access_type=?,status='ACTIVE',updated_at=NOW() WHERE id=?")->execute([$rid,$accessType,$uid]);

            $teamId=(int)$pdo->query("SELECT id FROM support_teams WHERE code='IT' LIMIT 1")->fetchColumn();
            if($teamId>0){
                if(in_array($roleCode,['ADMIN','SEMIADMIN','TECHNICIAN'],true)){
                    $pdo->prepare("INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at) VALUES(?,?,1,NOW(),NULL) ON DUPLICATE KEY UPDATE is_active=1,ended_at=NULL")->execute([$teamId,$uid]);
                }else{
                    $pdo->prepare("UPDATE support_team_members SET is_active=0,ended_at=NOW() WHERE team_id=? AND user_id=?")->execute([$teamId,$uid]);
                }
            }
        });

        Audit::log('USER_ASSIGNMENT_UPDATED','user',$uid,$before,[
            'role_id'=>$rid,'role_code'=>$roleCode,'assignment_type'=>$type,
            'region_id'=>$region,'park_id'=>$park,'area_id'=>$area,'position_id'=>$position,'manager_user_id'=>$manager
        ]);
        Flash::set('Usuario y asignación actualizados.','success');
        header('Location: '.APP_BASE_URL.'/admin/users');
        exit;
    }
}
