<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProviderParticipationService
{
    public const WORK_STATUS_LABELS = [
        'ANALYSIS' => 'En análisis / diagnóstico',
        'WAITING_CARROUSEL' => 'Esperando información de Carrousel',
        'WAITING_THIRD_PARTY' => 'Esperando tercero / fabricante',
        'IN_PROGRESS' => 'En atención / trabajando',
        'VALIDATING' => 'En validación',
        'READY_FOR_REVIEW' => 'Listo para revisión de Carrousel',
    ];

    public function __construct(private ?object $pdo = null){}

    private function pdo(): object{return $this->pdo ?? Database::pdo();}

    public function rows(?int $nowTs = null): array{return [];}

    public static function buildCycles(array $users,array $events,array $comments,array $attachments,array $reports,?int $nowTs = null): array
    {
        $nowTs=$nowTs??time();
        usort($events,static function(array $a,array $b):int{
            $cmp=strcmp((string)($a['created_at']??''),(string)($b['created_at']??''));
            return $cmp!==0?$cmp:((int)($a['id']??0)<=> (int)($b['id']??0));
        });

        $rows=[];$open=[];
        foreach($events as $event){
            $type=(string)($event['event_type']??'');
            if(!in_array($type,['EXTERNAL_GRANTED','EXTERNAL_REVOKED'],true))continue;
            $raw=(string)($type==='EXTERNAL_GRANTED'?($event['new_value']??''):($event['old_value']??''));
            $payload=json_decode($raw,true);
            if(!is_array($payload))$payload=[];
            $uid=(int)($payload['external_user_id']??0);
            $ticketId=(int)($event['ticket_id']??0);
            if($uid<=0||$ticketId<=0||!isset($users[$uid]))continue;
            $key=$ticketId.':'.$uid;

            if($type==='EXTERNAL_GRANTED'){
                if(isset($open[$key])){
                    $i=$open[$key];
                    $rows[$i]['revoked_at']=(string)$event['created_at'];
                    $rows[$i]['revoked_by']='Nueva asignación';
                }
                $u=$users[$uid];
                $rows[]=[
                    'ticket_id'=>$ticketId,'user_id'=>$uid,
                    'organization'=>(string)($u['organization_name']??$u['full_name']??''),
                    'contact'=>(string)($u['full_name']??''),'email'=>(string)($u['email']??''),
                    'ticket_number'=>(string)($event['ticket_number']??''),'subject'=>(string)($event['subject']??''),
                    'ticket_status'=>(string)($event['ticket_status']??''),'granted_at'=>(string)($event['created_at']??''),
                    'granted_by'=>(string)(($event['actor_name']??'')?:'Sistema'),'revoked_at'=>null,'revoked_by'=>null,
                    'duration_minutes'=>0,'responses'=>0,'attachments'=>0,'reports'=>0,'declared_minutes'=>0,
                ];
                $open[$key]=array_key_last($rows);
            }elseif(isset($open[$key])){
                $i=$open[$key];
                $rows[$i]['revoked_at']=(string)($event['created_at']??'');
                $rows[$i]['revoked_by']=(string)(($event['actor_name']??'')?:'Sistema');
                unset($open[$key]);
            }
        }

        foreach($rows as &$row){
            $start=strtotime((string)$row['granted_at']);
            $end=$row['revoked_at']!==null?strtotime((string)$row['revoked_at']):$nowTs;
            $start=$start===false?$nowTs:$start;
            $end=$end===false?$nowTs:$end;
            $row['duration_minutes']=max(0,(int)round(($end-$start)/60));
        }
        unset($row);
        return $rows;
    }
}
