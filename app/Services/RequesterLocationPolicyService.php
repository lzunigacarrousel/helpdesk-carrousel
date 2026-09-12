<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use PDO;

final class RequesterLocationPolicyService
{
    public const ENTITY_PARK='PARK';
    public const ENTITY_PERSON='PERSON';
    public const ENTITY_DEPARTMENT='DEPARTMENT';

    public const MODE_FIXED_PARK='FIXED_PARK';
    public const MODE_ASSIGNED_PARKS='ASSIGNED_PARKS';
    public const MODE_ANY_PARK='ANY_PARK';

    public function forUser(?array $sessionUser): array
    {
        $pdo=Database::pdo();
        $allParks=$this->activeParks($pdo);

        if(!$sessionUser || (int)($sessionUser['id']??0)<=0){
            return $this->context(self::MODE_ANY_PARK,self::ENTITY_PERSON,$allParks,null,false,'Puedes indicar el parque si aplica.');
        }

        if(($sessionUser['access_type']??'INTERNAL')==='EXTERNAL'){
            return $this->context(self::MODE_ANY_PARK,self::ENTITY_PERSON,$allParks,null,false,'Ubicación opcional.');
        }

        $uid=(int)$sessionUser['id'];
        $q=$pdo->prepare(
            "SELECT u.requester_entity_type,r.code role_code
             FROM users u
             JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $q->execute([$uid]);
        $account=$q->fetch()?:[];
        $entity=strtoupper((string)($account['requester_entity_type']??self::ENTITY_PERSON));
        $role=strtoupper((string)($account['role_code']??'REQUESTER'));

        $assignment=$this->activeAssignment($pdo,$uid);

        if($entity===self::ENTITY_PARK){
            $parkId=(int)($assignment['park_id']??0);
            if($parkId<=0){
                return $this->context(self::MODE_FIXED_PARK,$entity,[],null,true,'Esta cuenta de parque todavía no tiene un parque configurado.');
            }
            $parks=array_values(array_filter($allParks,static fn(array $p):bool=>(int)$p['id']===$parkId));
            return $this->context(
                self::MODE_FIXED_PARK,
                $entity,
                $parks,
                $parkId,
                true,
                'Esta cuenta representa un parque fijo. La ubicación se asigna automáticamente.'
            );
        }

        if($role==='SUPERVISOR'){
            $parks=[];
            $regionId=(int)($assignment['region_id']??0);
            $parkId=(int)($assignment['park_id']??0);

            if($regionId>0){
                $parks=array_values(array_filter($allParks,static fn(array $p):bool=>(int)($p['region_id']??0)===$regionId));
            }elseif($parkId>0){
                $parks=array_values(array_filter($allParks,static fn(array $p):bool=>(int)$p['id']===$parkId));
            }

            return $this->context(
                self::MODE_ASSIGNED_PARKS,
                $entity,
                $parks,
                count($parks)===1?(int)$parks[0]['id']:null,
                true,
                'Supervisión solo puede reportar casos de los parques que tiene asignados.'
            );
        }

        return $this->context(
            self::MODE_ANY_PARK,
            $entity,
            $allParks,
            null,
            false,
            'Puedes indicar cualquier parque si el caso corresponde a una ubicación específica.'
        );
    }

    public function resolveParkId(?array $sessionUser,int $submittedParkId): int
    {
        $context=$this->forUser($sessionUser);
        $mode=(string)$context['mode'];

        if($mode===self::MODE_FIXED_PARK){
            $fixed=(int)($context['fixed_park_id']??0);
            if($fixed<=0){
                throw new \RuntimeException('Esta cuenta de parque no tiene un parque configurado. Solicita a Administración que complete la cuenta.');
            }
            if($submittedParkId>0 && $submittedParkId!==$fixed){
                throw new \RuntimeException('Esta cuenta solo puede reportar solicitudes de su parque asignado.');
            }
            return $fixed;
        }

        if($mode===self::MODE_ASSIGNED_PARKS){
            if($submittedParkId<=0){
                throw new \RuntimeException('Selecciona uno de los parques que tienes asignados.');
            }
            $allowed=array_map(static fn(array $p):int=>(int)$p['id'],$context['parks']);
            if(!in_array($submittedParkId,$allowed,true)){
                throw new \RuntimeException('No puedes reportar solicitudes de un parque fuera de tu alcance.');
            }
            return $submittedParkId;
        }

        if($submittedParkId<=0)return 0;

        foreach($context['parks'] as $park){
            if((int)$park['id']===$submittedParkId)return $submittedParkId;
        }
        throw new \RuntimeException('La ubicación seleccionada no está disponible.');
    }

    public function accountLabels(): array
    {
        return [
            self::ENTITY_PARK=>'Parque',
            self::ENTITY_PERSON=>'Persona',
            self::ENTITY_DEPARTMENT=>'Área / departamento',
        ];
    }

    private function context(string $mode,string $entity,array $parks,?int $fixedParkId,bool $parkRequired,string $help): array
    {
        return [
            'mode'=>$mode,
            'entity_type'=>$entity,
            'parks'=>$parks,
            'fixed_park_id'=>$fixedParkId,
            'park_required'=>$parkRequired,
            'help'=>$help,
        ];
    }

    private function activeParks(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT id,name,region_id
             FROM parks
             WHERE is_active=1
             ORDER BY name"
        )->fetchAll()?:[];
    }

    private function activeAssignment(PDO $pdo,int $uid): ?array
    {
        $q=$pdo->prepare(
            "SELECT ua.region_id,ua.park_id,ua.area_id,ua.assignment_type,
                    rg.name region_name,p.name park_name,a.name area_name
             FROM user_assignments ua
             LEFT JOIN regions rg ON rg.id=ua.region_id
             LEFT JOIN parks p ON p.id=ua.park_id
             LEFT JOIN areas a ON a.id=ua.area_id
             WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             ORDER BY ua.id DESC
             LIMIT 1"
        );
        $q->execute([$uid]);
        return $q->fetch()?:null;
    }
}
