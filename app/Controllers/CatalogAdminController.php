<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use PDO;

final class CatalogAdminController
{
    public function index(): void
    {
        Auth::requirePermission('catalogs.manage');
        $pdo=Database::pdo();

        $regions=$pdo->query(
            "SELECT r.*,
                    COUNT(DISTINCT p.id) parks_total,
                    COALESCE(SUM(CASE WHEN p.is_active=1 THEN 1 ELSE 0 END),0) active_parks,
                    (SELECT COUNT(*) FROM user_assignments ua
                     WHERE ua.region_id=r.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL) active_assignments
             FROM regions r
             LEFT JOIN parks p ON p.region_id=r.id
             GROUP BY r.id
             ORDER BY r.is_active DESC,r.name"
        )->fetchAll();

        $parks=$pdo->query(
            "SELECT p.*,r.name region_name,r.is_active region_active,
                    (SELECT COUNT(*) FROM user_assignments ua
                     WHERE ua.park_id=p.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL) active_assignments,
                    (SELECT COUNT(*) FROM tickets t
                     WHERE t.park_id=p.id AND t.deleted_at IS NULL
                       AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')) open_tickets,
                    (SELECT COUNT(*) FROM ticket_activities ta
                     WHERE ta.park_id=p.id AND ta.status IN('PROGRAMADA','EN_CURSO')) active_activities
             FROM parks p
             LEFT JOIN regions r ON r.id=p.region_id
             ORDER BY p.is_active DESC,p.name"
        )->fetchAll();

        $areas=$pdo->query(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM user_assignments ua
                     WHERE ua.area_id=a.id AND ua.status='ACTIVE' AND ua.ends_at IS NULL) active_assignments,
                    (SELECT COUNT(*) FROM tickets t
                     WHERE t.area_id=a.id AND t.deleted_at IS NULL
                       AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')) open_tickets
             FROM areas a
             ORDER BY a.is_active DESC,a.name"
        )->fetchAll();

        View::render('admin/catalogs',[
            'user'=>Auth::user(),
            'flash'=>Flash::pull(),
            'regions'=>$regions,
            'parks'=>$parks,
            'areas'=>$areas,
        ]);
    }

    public function saveRegion(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $name=$this->cleanName(Http::post('name'),120,'región');

        $this->assertUniqueName($pdo,'regions',$name,$id);
        if($id>0){
            $before=$this->find($pdo,'regions',$id);
            $pdo->prepare('UPDATE regions SET name=?,updated_at=NOW() WHERE id=?')->execute([$name,$id]);
            Audit::log('REGION_UPDATED','region',$id,$before,['name'=>$name,'code'=>$before['code']??null]);
            Flash::set('Región actualizada correctamente.','success');
        }else{
            $code=$this->nextCode($pdo,'regions','REGION',$name);
            $pdo->prepare('INSERT INTO regions(code,name,is_active,created_at,updated_at) VALUES(?,?,1,NOW(),NOW())')->execute([$code,$name]);
            $id=(int)$pdo->lastInsertId();
            Audit::log('REGION_CREATED','region',$id,null,['name'=>$name,'code'=>$code,'is_active'=>1]);
            Flash::set('Región creada correctamente.','success');
        }
        $this->redirect('regiones');
    }

    public function toggleRegion(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $region=$this->find($pdo,'regions',$id);
        $activate=Http::post('activate')==='1';

        if(!$activate){
            $q=$pdo->prepare('SELECT COUNT(*) FROM parks WHERE region_id=? AND is_active=1');$q->execute([$id]);
            $activeParks=(int)$q->fetchColumn();
            $q=$pdo->prepare("SELECT COUNT(*) FROM user_assignments WHERE region_id=? AND status='ACTIVE' AND ends_at IS NULL");$q->execute([$id]);
            $assignments=(int)$q->fetchColumn();
            if($activeParks>0||$assignments>0){
                throw new \RuntimeException('No puedes desactivar esta región: todavía tiene parques activos o asignaciones vigentes.');
            }
        }

        $pdo->prepare('UPDATE regions SET is_active=?,updated_at=NOW() WHERE id=?')->execute([$activate?1:0,$id]);
        Audit::log($activate?'REGION_REACTIVATED':'REGION_DISABLED','region',$id,['is_active'=>(int)$region['is_active']],['is_active'=>$activate?1:0]);
        Flash::set($activate?'Región reactivada.':'Región desactivada.','success');
        $this->redirect('regiones');
    }

    public function savePark(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $name=$this->cleanName(Http::post('name'),160,'parque');
        $regionId=(int)Http::post('region_id');
        $costCenter=trim(Http::post('cost_center'));
        $address=trim(Http::post('address'));

        $region=$this->activeRegion($pdo,$regionId);
        $this->assertUniqueName($pdo,'parks',$name,$id);

        if($id>0){
            $before=$this->find($pdo,'parks',$id);
            Database::transaction(function(PDO $pdo)use($id,$name,$regionId,$costCenter,$address,$before):void{
                $pdo->prepare('UPDATE parks SET region_id=?,name=?,cost_center=?,address=?,updated_at=NOW() WHERE id=?')
                    ->execute([$regionId,$name,$costCenter!==''?$costCenter:null,$address!==''?$address:null,$id]);
                if((int)($before['region_id']??0)!==$regionId){
                    $pdo->prepare("UPDATE user_assignments SET region_id=? WHERE park_id=? AND status='ACTIVE' AND ends_at IS NULL")
                        ->execute([$regionId,$id]);
                }
            });
            Audit::log('PARK_UPDATED','park',$id,$before,[
                'name'=>$name,'region_id'=>$regionId,'region_name'=>$region['name'],
                'cost_center'=>$costCenter!==''?$costCenter:null,'address'=>$address!==''?$address:null,
                'code'=>$before['code']??null
            ]);
            Flash::set('Parque actualizado correctamente.','success');
        }else{
            $code=$this->nextCode($pdo,'parks','PARK',$name);
            $pdo->prepare('INSERT INTO parks(region_id,code,name,cost_center,address,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())')
                ->execute([$regionId,$code,$name,$costCenter!==''?$costCenter:null,$address!==''?$address:null]);
            $id=(int)$pdo->lastInsertId();
            Audit::log('PARK_CREATED','park',$id,null,[
                'name'=>$name,'region_id'=>$regionId,'region_name'=>$region['name'],
                'cost_center'=>$costCenter!==''?$costCenter:null,'address'=>$address!==''?$address:null,'code'=>$code,'is_active'=>1
            ]);
            Flash::set('Parque creado correctamente.','success');
        }
        $this->redirect('parques');
    }

    public function togglePark(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $park=$this->find($pdo,'parks',$id);
        $activate=Http::post('activate')==='1';

        if($activate){
            $this->activeRegion($pdo,(int)($park['region_id']??0));
        }else{
            $q=$pdo->prepare("SELECT COUNT(*) FROM user_assignments WHERE park_id=? AND status='ACTIVE' AND ends_at IS NULL");$q->execute([$id]);
            $assignments=(int)$q->fetchColumn();
            $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE park_id=? AND deleted_at IS NULL AND status NOT IN('RESOLVED','CLOSED','CANCELLED')");$q->execute([$id]);
            $openTickets=(int)$q->fetchColumn();
            $q=$pdo->prepare("SELECT COUNT(*) FROM ticket_activities WHERE park_id=? AND status IN('PROGRAMADA','EN_CURSO')");$q->execute([$id]);
            $activities=(int)$q->fetchColumn();
            if($assignments>0||$openTickets>0||$activities>0){
                throw new \RuntimeException('No puedes desactivar este parque: tiene asignaciones, tickets abiertos o actividades vigentes.');
            }
        }

        $pdo->prepare('UPDATE parks SET is_active=?,updated_at=NOW() WHERE id=?')->execute([$activate?1:0,$id]);
        Audit::log($activate?'PARK_REACTIVATED':'PARK_DISABLED','park',$id,['is_active'=>(int)$park['is_active']],['is_active'=>$activate?1:0]);
        Flash::set($activate?'Parque reactivado.':'Parque desactivado.','success');
        $this->redirect('parques');
    }

    public function saveArea(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $name=$this->cleanName(Http::post('name'),120,'área');
        $description=trim(Http::post('description'));

        $this->assertUniqueName($pdo,'areas',$name,$id);
        if($id>0){
            $before=$this->find($pdo,'areas',$id);
            $pdo->prepare('UPDATE areas SET name=?,description=?,updated_at=NOW() WHERE id=?')
                ->execute([$name,$description!==''?$description:null,$id]);
            Audit::log('AREA_UPDATED','area',$id,$before,['name'=>$name,'description'=>$description!==''?$description:null,'code'=>$before['code']??null]);
            Flash::set('Área actualizada correctamente.','success');
        }else{
            $code=$this->nextCode($pdo,'areas','AREA',$name);
            $pdo->prepare('INSERT INTO areas(code,name,description,is_active,created_at,updated_at) VALUES(?,?,?,1,NOW(),NOW())')
                ->execute([$code,$name,$description!==''?$description:null]);
            $id=(int)$pdo->lastInsertId();
            Audit::log('AREA_CREATED','area',$id,null,['name'=>$name,'description'=>$description!==''?$description:null,'code'=>$code,'is_active'=>1]);
            Flash::set('Área creada correctamente.','success');
        }
        $this->redirect('areas');
    }

    public function toggleArea(): void
    {
        $this->gate();
        $pdo=Database::pdo();
        $id=(int)Http::post('id');
        $area=$this->find($pdo,'areas',$id);
        $activate=Http::post('activate')==='1';

        if(!$activate){
            $q=$pdo->prepare("SELECT COUNT(*) FROM user_assignments WHERE area_id=? AND status='ACTIVE' AND ends_at IS NULL");$q->execute([$id]);
            $assignments=(int)$q->fetchColumn();
            $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE area_id=? AND deleted_at IS NULL AND status NOT IN('RESOLVED','CLOSED','CANCELLED')");$q->execute([$id]);
            $openTickets=(int)$q->fetchColumn();
            if($assignments>0||$openTickets>0){
                throw new \RuntimeException('No puedes desactivar esta área: tiene asignaciones o tickets abiertos.');
            }
        }

        $pdo->prepare('UPDATE areas SET is_active=?,updated_at=NOW() WHERE id=?')->execute([$activate?1:0,$id]);
        Audit::log($activate?'AREA_REACTIVATED':'AREA_DISABLED','area',$id,['is_active'=>(int)$area['is_active']],['is_active'=>$activate?1:0]);
        Flash::set($activate?'Área reactivada.':'Área desactivada.','success');
        $this->redirect('areas');
    }

    private function gate(): void
    {
        Auth::requirePermission('catalogs.manage');
        Csrf::verify($_POST['_csrf']??null);
    }

    private function activeRegion(PDO $pdo,int $id): array
    {
        if($id<=0)throw new \RuntimeException('Selecciona una región activa.');
        $q=$pdo->prepare('SELECT id,code,name FROM regions WHERE id=? AND is_active=1 LIMIT 1');
        $q->execute([$id]);
        $row=$q->fetch();
        if(!$row)throw new \RuntimeException('La región seleccionada no está disponible.');
        return $row;
    }

    private function cleanName(string $value,int $max,string $label): string
    {
        $value=trim((string)preg_replace('/\s+/u',' ',$value));
        if($value==='')throw new \RuntimeException('Ingresa el nombre de la '.$label.'.');
        if(mb_strlen($value)>$max)throw new \RuntimeException('El nombre de la '.$label.' es demasiado largo.');
        return $value;
    }

    private function assertUniqueName(PDO $pdo,string $table,string $name,int $excludeId): void
    {
        if(!in_array($table,['regions','parks','areas'],true))throw new \RuntimeException('Catálogo no válido.');
        $sql="SELECT COUNT(*) FROM {$table} WHERE LOWER(name)=LOWER(?)".($excludeId>0?' AND id<>?':'');
        $q=$pdo->prepare($sql);
        $params=[$name];if($excludeId>0)$params[]=$excludeId;
        $q->execute($params);
        if((int)$q->fetchColumn()>0)throw new \RuntimeException('Ya existe un registro con ese nombre.');
    }

    private function find(PDO $pdo,string $table,int $id): array
    {
        if($id<=0||!in_array($table,['regions','parks','areas'],true))throw new \RuntimeException('Registro no válido.');
        $q=$pdo->prepare("SELECT * FROM {$table} WHERE id=? LIMIT 1");$q->execute([$id]);
        $row=$q->fetch();
        if(!$row)throw new \RuntimeException('El registro ya no existe.');
        return $row;
    }

    private function nextCode(PDO $pdo,string $table,string $prefix,string $name): string
    {
        $fold=strtr($name,['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $slug=strtoupper(trim((string)preg_replace('/[^A-Za-z0-9]+/','_',$fold),'_'));
        $slug=substr($slug,0,30);
        if($slug==='')$slug='NUEVO';
        $base=$prefix.'_'.$slug;
        $candidate=$base;$n=2;
        while(true){
            $q=$pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE code=?");$q->execute([$candidate]);
            if((int)$q->fetchColumn()===0)return $candidate;
            $candidate=substr($base,0,44).'_'.$n++;
        }
    }

    private function redirect(string $anchor): never
    {
        header('Location: '.APP_BASE_URL.'/admin/catalogos#'.$anchor);
        exit;
    }
}
