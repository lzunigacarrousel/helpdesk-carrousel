<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth,Database};

final class ScopeService
{
    public function ticketConstraint(string $alias='t', ?int $userId=null): array
    {
        $userId=$userId??(int)Auth::id();
        if($userId<=0)return['0=1',[]];

        $role=(string)Auth::role();
        $user=Auth::user();
        if(($user['access_type']??'INTERNAL')==='EXTERNAL')return['0=1',[]];

        if(in_array($role,['ADMIN','SEMIADMIN','MANAGEMENT'],true))return['1=1',[]];

        if($role==='SUPERVISOR'){
            $scope=$this->assignmentScope($userId);
            if(!$scope)return['0=1',[]];
            if(!empty($scope['region_id']))return["{$alias}.park_id IN (SELECT id FROM parks WHERE region_id=?)",[(int)$scope['region_id']]];
            if(!empty($scope['park_id']))return["{$alias}.park_id=?",[(int)$scope['park_id']]];
            if(!empty($scope['area_id']))return["{$alias}.area_id=?",[(int)$scope['area_id']]];
            return['0=1',[]];
        }

        if($role==='TECHNICIAN'){
            $scopes=$this->supportScopes($userId);
            if(!$scopes)return['1=1',[]]; // compatibilidad con el equipo IT actual
            foreach($scopes as $scope)if(($scope['scope_type']??'')==='GLOBAL')return['1=1',[]];
            $parts=[];$params=[];
            foreach($scopes as $scope){
                $type=(string)($scope['scope_type']??'');
                if($type==='PARK'&&!empty($scope['park_id'])){$parts[]="{$alias}.park_id=?";$params[]=(int)$scope['park_id'];}
                elseif($type==='AREA'&&!empty($scope['area_id'])){$parts[]="{$alias}.area_id=?";$params[]=(int)$scope['area_id'];}
                elseif($type==='PARK_AREA'&&!empty($scope['park_id'])&&!empty($scope['area_id'])){$parts[]="({$alias}.park_id=? AND {$alias}.area_id=?)";$params[]=(int)$scope['park_id'];$params[]=(int)$scope['area_id'];}
            }
            return $parts?['('.implode(' OR ',$parts).')',$params]:['1=1',[]];
        }

        $email=strtolower((string)($user['email']??''));
        return["({$alias}.requester_user_id=? OR LOWER({$alias}.requester_email)=?)",[$userId,$email]];
    }

    public function canAccessOrganization(?int $regionId,?int $parkId,?int $areaId): bool
    {
        $role=(string)Auth::role();
        if(in_array($role,['ADMIN','SEMIADMIN','MANAGEMENT'],true))return true;
        if($role==='SUPERVISOR'){
            $scope=$this->assignmentScope((int)Auth::id());
            if(!$scope)return false;
            if(!empty($scope['region_id']))return (int)$scope['region_id']===(int)$regionId;
            if(!empty($scope['park_id']))return (int)$scope['park_id']===(int)$parkId;
            if(!empty($scope['area_id']))return (int)$scope['area_id']===(int)$areaId;
        }
        return false;
    }

    public function scopeLabel(?int $userId=null): string
    {
        $userId=$userId??(int)Auth::id();$role=(string)Auth::role();
        if(in_array($role,['ADMIN','SEMIADMIN','MANAGEMENT'],true))return 'Alcance global';
        if($role==='TECHNICIAN'){
            $scopes=$this->supportScopes($userId);
            if(!$scopes)return 'Soporte · alcance global';
            if(array_filter($scopes,static fn(array $s):bool=>($s['scope_type']??'')==='GLOBAL'))return 'Soporte · alcance global';
            return 'Soporte · alcance configurado';
        }
        if($role==='SUPERVISOR'){
            $a=$this->assignmentScope($userId);
            if(!$a)return 'Alcance pendiente';
            if(!empty($a['region_name']))return 'Región · '.$a['region_name'];
            if(!empty($a['park_name']))return 'Parque · '.$a['park_name'];
            if(!empty($a['area_name']))return 'Área · '.$a['area_name'];
            return 'Alcance pendiente';
        }
        return 'Solo información propia';
    }

    public function scopesForUser(int $uid): array
    {
        $support=$this->supportScopes($uid);
        if($support)return $support;
        $assignment=$this->assignmentScope($uid);
        return $assignment?[$assignment]:[];
    }

    private function assignmentScope(int $uid): ?array
    {
        $q=Database::pdo()->prepare(
            "SELECT ua.region_id,ua.park_id,ua.area_id,ua.assignment_type,
                    rg.name region_name,p.name park_name,a.name area_name
             FROM user_assignments ua
             LEFT JOIN regions rg ON rg.id=ua.region_id
             LEFT JOIN parks p ON p.id=ua.park_id
             LEFT JOIN areas a ON a.id=ua.area_id
             WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             ORDER BY ua.id DESC LIMIT 1"
        );
        $q->execute([$uid]);
        return $q->fetch()?:null;
    }

    private function supportScopes(int $uid): array
    {
        try{
            $q=Database::pdo()->prepare(
                "SELECT ss.scope_type,ss.park_id,ss.area_id,p.name park_name,a.name area_name,st.name team_name
                 FROM support_team_members stm
                 JOIN support_teams st ON st.id=stm.team_id AND st.is_active=1
                 JOIN support_scopes ss ON ss.team_id=stm.team_id AND ss.is_active=1
                 LEFT JOIN parks p ON p.id=ss.park_id
                 LEFT JOIN areas a ON a.id=ss.area_id
                 WHERE stm.user_id=? AND stm.is_active=1 AND stm.ended_at IS NULL
                 ORDER BY ss.scope_type,p.name,a.name"
            );
            $q->execute([$uid]);
            return $q->fetchAll();
        }catch(\Throwable){return[];}
    }
}
