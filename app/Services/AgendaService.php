<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Auth,Database};

final class AgendaService
{
    public const ACTIVE_STATUSES=['PROGRAMADA','EN_CURSO'];
    public const ALL_STATUSES=['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'];
    public const TYPES=['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'];

    public function activities(array $filters): array
    {
        $states=$this->effectiveStates($filters);
        [$where,$params]=$this->scopedWhere($filters);
        $where[]='a.status IN('.implode(',',array_fill(0,count($states),'?')).')';
        array_push($params,...$states);
        $where[]='a.scheduled_start_at < DATE_ADD(?,INTERVAL 1 DAY)';
        $where[]='a.scheduled_end_at >= ?';
        $params[]=(string)($filters['to']??'');
        $params[]=(string)($filters['from']??'');

        $query=Database::pdo()->prepare($this->activitySelect().' WHERE '.implode(' AND ',$where).' ORDER BY a.scheduled_start_at,a.id');
        $query->execute($params);
        return $this->normalizeRows($query->fetchAll());
    }

    public function overdueBefore(string $from,array $filters): array
    {
        $status=(string)($filters['status']??'active');
        if(in_array($status,self::ALL_STATUSES,true)&&$status!=='PROGRAMADA')return[];
        if(!in_array('PROGRAMADA',$this->effectiveStates($filters),true))return[];

        [$where,$params]=$this->scopedWhere($filters);
        $where[]="a.status='PROGRAMADA'";
        $where[]='a.scheduled_end_at < ?';
        $params[]=$from.' 00:00:00';

        $query=Database::pdo()->prepare($this->activitySelect().' WHERE '.implode(' AND ',$where).' ORDER BY a.scheduled_end_at,a.id');
        $query->execute($params);
        return $this->normalizeRows($query->fetchAll());
    }

    public function filterOptions(array $filters): array
    {
        [$where,$params]=$this->scopedWhere($filters,false);
        $condition=implode(' AND ',$where);
        $pdo=Database::pdo();

        $responsibles=$pdo->prepare(
            'SELECT DISTINCT u.id,u.full_name
             FROM ticket_activities a
             JOIN tickets t ON t.id=a.ticket_id
             JOIN users u ON u.id=a.responsible_user_id
             WHERE '.$condition.' AND u.status=\'ACTIVE\' AND u.deleted_at IS NULL
             ORDER BY u.full_name,u.id'
        );
        $responsibles->execute($params);

        $parks=$pdo->prepare(
            'SELECT DISTINCT p.id,p.name
             FROM ticket_activities a
             JOIN tickets t ON t.id=a.ticket_id
             JOIN parks p ON p.id=a.park_id
             WHERE '.$condition.'
             ORDER BY p.name,p.id'
        );
        $parks->execute($params);

        return['responsibles'=>$responsibles->fetchAll(),'parks'=>$parks->fetchAll()];
    }

    public function searchTickets(string $query,int $limit=10): array
    {
        $query=trim($query);if(mb_strlen($query)<2)return[];
        $limit=max(1,min(10,$limit));
        [$where,$params]=$this->scopedWhere([]);
        $where[]='(t.ticket_number LIKE ? OR t.subject LIKE ?)';
        $term='%'.$query.'%';
        $params[]=$term;
        $params[]=$term;

        $search=Database::pdo()->prepare(
            'SELECT t.id ticket_id,t.ticket_number ticket_code,t.subject ticket_subject
             FROM tickets t
             WHERE '.implode(' AND ',$where).'
             ORDER BY t.ticket_number DESC,t.id DESC
             LIMIT '.$limit
        );
        $search->execute($params);
        $rows=$search->fetchAll();
        foreach($rows as &$row){
            $row['ticket_url']=APP_BASE_URL.'/tickets/view?id='.(int)$row['ticket_id'].'#actividades';
        }
        unset($row);
        return $rows;
    }

    public static function markConflicts(array $rows):array
    {
        foreach($rows as &$row){
            $row['has_conflict']=false;
        }
        unset($row);

        foreach($rows as $leftIndex=>$left){
            if(!in_array((string)($left['status']??''),self::ACTIVE_STATUSES,true))continue;
            foreach($rows as $rightIndex=>$right){
                if($rightIndex<=$leftIndex||!in_array((string)($right['status']??''),self::ACTIVE_STATUSES,true))continue;
                if((int)($left['responsible_user_id']??0)!==(int)($right['responsible_user_id']??0))continue;
                if((string)($left['scheduled_start_at']??'')<(string)($right['scheduled_end_at']??'')
                    && (string)($left['scheduled_end_at']??'')>(string)($right['scheduled_start_at']??'')){
                    $rows[$leftIndex]['has_conflict']=true;
                    $rows[$rightIndex]['has_conflict']=true;
                }
            }
        }

        return $rows;
    }

    public static function hourWindow(array $rows):array
    {
        $startHour=8;
        $endHour=18;

        foreach($rows as $row){
            if(preg_match('/^\d{4}-\d{2}-\d{2} (\d{2}):(\d{2}):\d{2}$/',(string)($row['scheduled_start_at']??''),$start)){
                $startHour=min($startHour,max(0,(int)$start[1]-1));
            }
            if(preg_match('/^\d{4}-\d{2}-\d{2} (\d{2}):(\d{2}):\d{2}$/',(string)($row['scheduled_end_at']??''),$end)){
                $endHour=max($endHour,min(24,(int)$end[1]+1+((int)$end[2]>0?1:0)));
            }
        }

        return['start_hour'=>$startHour,'end_hour'=>$endHour];
    }

    private function effectiveStates(array $filters): array
    {
        $history=!empty($filters['history']);
        $status=(string)($filters['status']??'active');
        $allowed=$history?self::ALL_STATUSES:self::ACTIVE_STATUSES;
        return $status==='all'&&$history
            ?self::ALL_STATUSES
            :($status==='active'||!in_array($status,$allowed,true)?self::ACTIVE_STATUSES:[$status]);
    }

    private function scopedWhere(array $filters,bool $includeNarrowing=true): array
    {
        [$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint('t');
        $where=['t.deleted_at IS NULL'];$params=[];
        if($scopeSql!=='1=1'){$where[]=$scopeSql;array_push($params,...$scopeParams);}

        if((string)Auth::role()==='TECHNICIAN'&&($filters['scope_mode']??'')==='mine'){
            $where[]='a.responsible_user_id=?';
            $params[]=(int)Auth::id();
        }
        if(!$includeNarrowing)return[$where,$params];

        $responsibleId=(int)($filters['responsible_user_id']??0);
        if($responsibleId>0){$where[]='a.responsible_user_id=?';$params[]=$responsibleId;}
        $parkId=(int)($filters['park_id']??0);
        if($parkId>0){$where[]='a.park_id=?';$params[]=$parkId;}
        $type=(string)($filters['activity_type']??'');
        if(in_array($type,self::TYPES,true)){$where[]='a.activity_type=?';$params[]=$type;}

        return[$where,$params];
    }

    private function activitySelect(): string
    {
        return 'SELECT a.id activity_id,a.ticket_id,t.ticket_number ticket_code,t.subject ticket_subject,
                       a.activity_type,a.status,a.scheduled_start_at,a.scheduled_end_at,
                       a.responsible_user_id,u.full_name responsible_name,
                       a.park_id,p.name park_name
                FROM ticket_activities a
                JOIN tickets t ON t.id=a.ticket_id
                JOIN users u ON u.id=a.responsible_user_id
                LEFT JOIN parks p ON p.id=a.park_id';
    }

    private function normalizeRows(array $rows): array
    {
        $now=time();
        foreach($rows as &$row){
            $row['is_overdue']=(string)$row['status']==='PROGRAMADA'&&strtotime((string)$row['scheduled_end_at'])<$now;
            $row['ticket_url']=APP_BASE_URL.'/tickets/view?id='.(int)$row['ticket_id'].'#actividades';
        }
        unset($row);
        return self::markConflicts($rows);
    }
}
