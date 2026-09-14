<?php
declare(strict_types=1);

namespace App\Services;

final class AgendaService
{
    public const ACTIVE_STATUSES=['PROGRAMADA','EN_CURSO'];
    public const ALL_STATUSES=['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'];
    public const TYPES=['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'];

    public function activities(array $filters):array{return[];}
    public function overdueBefore(string $from,array $filters):array{return[];}
    public function filterOptions(array $filters):array{return['responsibles'=>[],'parks'=>[]];}
    public function searchTickets(string $query,int $limit=10):array{return[];}

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
                if(($left['responsible_user_id']??null)!==($right['responsible_user_id']??null))continue;
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
}
