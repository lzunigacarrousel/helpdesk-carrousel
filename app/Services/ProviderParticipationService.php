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

    public function rows(?int $nowTs = null): array
    {
        $pdo=$this->pdo();
        $users=[];
        foreach($pdo->query("SELECT u.id,u.full_name,u.email,COALESCE(ep.organization_name,u.full_name) organization_name FROM users u LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE u.access_type='EXTERNAL'")->fetchAll() as $user){
            $users[(int)$user['id']]=$user;
        }

        $events=$pdo->query("SELECT te.id,te.ticket_id,te.event_type,te.old_value,te.new_value,te.metadata_json,te.created_at,
                t.ticket_number,t.subject,t.status ticket_status,actor.full_name actor_name
            FROM ticket_events te
            JOIN tickets t ON t.id=te.ticket_id
            LEFT JOIN users actor ON actor.id=te.actor_user_id
            WHERE te.event_type IN('EXTERNAL_GRANTED','EXTERNAL_REVOKED','STATUS_CHANGED')
              AND t.deleted_at IS NULL
            ORDER BY te.created_at,te.id")->fetchAll();

        $comments=$pdo->query("SELECT tc.id,tc.ticket_id,tc.author_user_id,tc.visibility,tc.created_at
            FROM ticket_comments tc
            JOIN users u ON u.id=tc.author_user_id
            WHERE tc.deleted_at IS NULL AND tc.visibility='EXTERNAL' AND u.access_type='EXTERNAL'
            ORDER BY tc.created_at,tc.id")->fetchAll();

        $attachments=$pdo->query("SELECT ta.id,ta.ticket_id,ta.uploaded_by_user_id,ta.visibility,ta.created_at
            FROM ticket_attachments ta
            JOIN users u ON u.id=ta.uploaded_by_user_id
            WHERE ta.visibility='EXTERNAL' AND u.access_type='EXTERNAL'
            ORDER BY ta.created_at,ta.id")->fetchAll();

        $reports=$pdo->query("SELECT twr.id,twr.ticket_id,twr.author_user_id,twr.comment_id,twr.work_status,
                twr.time_spent_minutes,twr.ready_for_review,twr.created_at
            FROM ticket_work_reports twr
            WHERE twr.author_access_type='EXTERNAL'
            ORDER BY twr.created_at,twr.id")->fetchAll();

        $rows=self::buildCycles($users,$events,$comments,$attachments,$reports,$nowTs);
        usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['granted_at'],(string)$a['granted_at']));
        return $rows;
    }

    public function rowsForTicket(int $ticketId, ?int $nowTs = null): array
    {
        if($ticketId<=0)return [];
        return array_values(array_filter(
            $this->rows($nowTs),
            static fn(array $row):bool=>(int)($row['ticket_id']??0)===$ticketId
        ));
    }

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
                $meta=json_decode((string)($event['metadata_json']??''),true);
                if(!is_array($meta))$meta=[];
                $rows[]=[
                    'ticket_id'=>$ticketId,'user_id'=>$uid,
                    'grant_event_id'=>(int)($event['id']??0),'revoke_event_id'=>null,
                    'organization'=>(string)($u['organization_name']??$u['full_name']??''),
                    'contact'=>(string)($u['full_name']??''),'email'=>(string)($u['email']??''),
                    'ticket_number'=>(string)($event['ticket_number']??''),'subject'=>(string)($event['subject']??''),
                    'ticket_status'=>(string)($event['ticket_status']??''),'granted_at'=>(string)($event['created_at']??''),
                    'granted_by'=>(string)(($event['actor_name']??'')?:'Sistema'),'revoked_at'=>null,'revoked_by'=>null,
                    'can_comment'=>(bool)($meta['can_comment']??true),'can_upload'=>(bool)($meta['can_upload']??true),
                    'duration_minutes'=>0,'responses'=>0,'attachments'=>0,'reports'=>0,'declared_minutes'=>0,
                    'first_response_at'=>null,'first_response_minutes'=>null,'first_response_origin'=>null,
                    'last_activity_at'=>null,'work_status'=>null,'activity_label'=>'Sin actualización','deliveries'=>0,
                    'returns'=>0,
                ];
                $open[$key]=array_key_last($rows);
            }elseif(isset($open[$key])){
                $i=$open[$key];
                $rows[$i]['revoked_at']=(string)($event['created_at']??'');
                $rows[$i]['revoked_by']=(string)(($event['actor_name']??'')?:'Sistema');
                $rows[$i]['revoke_event_id']=(int)($event['id']??0);
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

        $reportCommentIds=[];
        foreach($reports as $report){
            $commentId=(int)($report['comment_id']??0);
            if($commentId>0)$reportCommentIds[$commentId]=true;
        }

        usort($reports,static function(array $a,array $b):int{
            $cmp=strcmp((string)($a['created_at']??''),(string)($b['created_at']??''));
            return $cmp!==0?$cmp:((int)($a['id']??0)<=> (int)($b['id']??0));
        });
        $deliveryTimes=[];
        foreach($reports as $report){
            $ticketId=(int)($report['ticket_id']??0);
            $userId=(int)($report['author_user_id']??0);
            $createdAt=(string)($report['created_at']??'');
            $index=self::rowIndexFor($rows,$ticketId,$userId,$createdAt);
            if($index===null)continue;

            $rows[$index]['reports']++;
            $rows[$index]['declared_minutes']+=max(0,(int)($report['time_spent_minutes']??0));
            $status=(string)($report['work_status']??'');
            $rows[$index]['work_status']=$status!==''?$status:null;
            $rows[$index]['activity_label']=$status!==''?(self::WORK_STATUS_LABELS[$status]??$status):'Sin actualización';
            $rows[$index]['last_activity_at']=$createdAt!==''?$createdAt:null;
            if($status==='READY_FOR_REVIEW'||(int)($report['ready_for_review']??0)===1){
                $rows[$index]['deliveries']++;
                $at=strtotime($createdAt);
                if($at!==false)$deliveryTimes[$index][]=$at;
            }
            self::considerFirstResponse($rows[$index],$createdAt,'Informe técnico');
        }

        foreach($comments as $comment){
            if((string)($comment['visibility']??'')!=='EXTERNAL')continue;
            $commentId=(int)($comment['id']??0);
            if($commentId>0&&isset($reportCommentIds[$commentId]))continue;
            $ticketId=(int)($comment['ticket_id']??0);
            $userId=(int)($comment['author_user_id']??0);
            $createdAt=(string)($comment['created_at']??'');
            $index=self::rowIndexFor($rows,$ticketId,$userId,$createdAt);
            if($index===null)continue;
            $rows[$index]['responses']++;
            self::considerFirstResponse($rows[$index],$createdAt,'Mensaje');
        }

        foreach($attachments as $attachment){
            if((string)($attachment['visibility']??'')!=='EXTERNAL')continue;
            $ticketId=(int)($attachment['ticket_id']??0);
            $userId=(int)($attachment['uploaded_by_user_id']??0);
            $createdAt=(string)($attachment['created_at']??'');
            $index=self::rowIndexFor($rows,$ticketId,$userId,$createdAt);
            if($index!==null)$rows[$index]['attachments']++;
        }

        $returnTimes=[];
        foreach($events as $event){
            if((string)($event['event_type']??'')!=='STATUS_CHANGED')continue;
            $payload=json_decode((string)($event['new_value']??''),true);
            if(!is_array($payload))continue;
            $status=(string)($payload['status']??'');
            if(!in_array($status,['REOPENED','IN_PROGRESS'],true))continue;
            $ticketId=(int)($event['ticket_id']??0);
            $createdAt=(string)($event['created_at']??'');
            $at=strtotime($createdAt);
            if($at===false)continue;
            foreach($rows as $index=>$row){
                if((int)$row['ticket_id']!==$ticketId)continue;
                if(!self::timestampWithinRow($row,$at))continue;
                $returnTimes[$index][]=$at;
            }
        }

        foreach($rows as $index=>&$row){
            $deliveriesForRow=$deliveryTimes[$index]??[];
            $returnsForRow=$returnTimes[$index]??[];
            sort($deliveriesForRow,SORT_NUMERIC);
            sort($returnsForRow,SORT_NUMERIC);
            $returns=0;$lastReturnAt=0;
            foreach($returnsForRow as $returnAt){
                $hasDelivery=false;
                foreach($deliveriesForRow as $deliveryAt){
                    if($deliveryAt>$lastReturnAt&&$deliveryAt<$returnAt){$hasDelivery=true;break;}
                }
                if($hasDelivery){$returns++;$lastReturnAt=$returnAt;}
            }
            $row['returns']=$returns;
        }
        unset($row);

        foreach($rows as &$row){
            if($row['first_response_at']===null)continue;
            $start=strtotime((string)$row['granted_at']);
            $response=strtotime((string)$row['first_response_at']);
            if($start!==false&&$response!==false)$row['first_response_minutes']=max(0,(int)round(($response-$start)/60));
        }
        unset($row);

        return $rows;
    }

    public static function applyFilters(array $rows,array $filters): array
    {
        $q=mb_strtolower(trim((string)($filters['q']??'')));
        $provider=(int)($filters['provider']??0);
        $state=(string)($filters['state']??'');
        $activity=(string)($filters['activity']??'');
        $from=(string)($filters['from']??'');
        $to=(string)($filters['to']??'');

        return array_values(array_filter($rows,static function(array $row)use($q,$provider,$state,$activity,$from,$to):bool{
            if($provider>0&&(int)($row['user_id']??0)!==$provider)return false;
            if($state==='active'&&($row['revoked_at']??null)!==null)return false;
            if($state==='closed'&&($row['revoked_at']??null)===null)return false;
            if($activity==='NONE'&&($row['work_status']??null)!==null)return false;
            if($activity!==''&&$activity!=='NONE'&&(string)($row['work_status']??'')!==$activity)return false;

            $day=substr((string)($row['granted_at']??''),0,10);
            if($from!==''&&$day<$from)return false;
            if($to!==''&&$day>$to)return false;

            if($q!==''){
                $haystack=mb_strtolower(implode(' ',[
                    (string)($row['organization']??''),
                    (string)($row['contact']??''),
                    (string)($row['email']??''),
                    (string)($row['ticket_number']??''),
                    (string)($row['subject']??''),
                ]));
                if(!str_contains($haystack,$q))return false;
            }
            return true;
        }));
    }

    public static function summary(array $rows): array
    {
        $summary=[
            'participations'=>count($rows),
            'active'=>0,
            'no_response'=>0,
            'avg_first_response_minutes'=>null,
            'returns'=>0,
            'closed'=>0,
            'providers'=>0,
            'responses'=>0,
            'attachments'=>0,
        ];
        $responseMinutes=[];$providerIds=[];
        foreach($rows as $row){
            if(($row['revoked_at']??null)===null)$summary['active']++;else $summary['closed']++;
            if(($row['first_response_minutes']??null)===null){
                $summary['no_response']++;
            }else{
                $responseMinutes[]=(int)$row['first_response_minutes'];
            }
            $summary['returns']+=(int)($row['returns']??0);
            $summary['responses']+=(int)($row['responses']??0);
            $summary['attachments']+=(int)($row['attachments']??0);
            $userId=(int)($row['user_id']??0);
            if($userId>0)$providerIds[$userId]=true;
        }
        $summary['providers']=count($providerIds);
        if($responseMinutes!==[]){
            $summary['avg_first_response_minutes']=(int)round(array_sum($responseMinutes)/count($responseMinutes));
        }
        return $summary;
    }

    public function registeredProviders(): array
    {
        $providers=[];
        $rows=$this->pdo()->query(
            "SELECT u.id,COALESCE(NULLIF(ep.organization_name,''),u.full_name) organization_name
             FROM users u
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL'
             ORDER BY COALESCE(NULLIF(ep.organization_name,''),u.full_name),u.full_name"
        )->fetchAll();
        foreach($rows as $row){
            $userId=(int)($row['id']??0);
            if($userId<=0)continue;
            $providers[$userId]=(string)($row['organization_name']??'');
        }
        return $providers;
    }
    public static function providers(array $rows): array
    {
        $providers=[];
        foreach($rows as $row){
            $userId=(int)($row['user_id']??0);
            if($userId<=0)continue;
            $providers[$userId]=(string)($row['organization']??$row['contact']??'');
        }
        asort($providers,SORT_NATURAL|SORT_FLAG_CASE);
        return $providers;
    }

    public static function activityOptions(): array
    {
        return ['NONE'=>'Sin actualización']+self::WORK_STATUS_LABELS;
    }

    private static function rowIndexFor(array $rows,int $ticketId,int $userId,string $createdAt): ?int
    {
        $at=strtotime($createdAt);
        if($at===false)return null;
        $best=null;$bestStart=PHP_INT_MIN;
        foreach($rows as $index=>$row){
            if((int)($row['ticket_id']??0)!==$ticketId||(int)($row['user_id']??0)!==$userId)continue;
            $from=strtotime((string)($row['granted_at']??''));
            if($from===false||$at<$from)continue;
            $revokedAt=$row['revoked_at']??null;
            $to=$revokedAt!==null?strtotime((string)$revokedAt):null;
            if($to!==null&&$to!==false&&$at>$to)continue;
            if($from>=$bestStart){$best=(int)$index;$bestStart=$from;}
        }
        return $best;
    }

    private static function timestampWithinRow(array $row,int $at): bool
    {
        $from=strtotime((string)($row['granted_at']??''));
        if($from===false||$at<$from)return false;
        $revokedAt=$row['revoked_at']??null;
        if($revokedAt===null)return true;
        $to=strtotime((string)$revokedAt);
        return $to===false||$at<=$to;
    }

    private static function considerFirstResponse(array &$row,string $createdAt,string $origin): void
    {
        $candidate=strtotime($createdAt);
        if($candidate===false)return;
        $current=$row['first_response_at']!==null?strtotime((string)$row['first_response_at']):false;
        if($current===false||$candidate<$current||($candidate===$current&&$origin==='Informe técnico')){
            $row['first_response_at']=$createdAt;
            $row['first_response_origin']=$origin;
        }
    }
}
