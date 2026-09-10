<?php
declare(strict_types=1);

namespace App\Services;

final class TicketLifecycleService
{
    private const STATUS_LABELS=[
        'NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera',
        'RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado',
    ];

    public static function analyze(array $ticket,array $events,?int $now=null):array
    {
        $now=$now??time();
        $created=self::timestamp($ticket['created_at']??null)??$now;
        [$initial,$initialReason]=self::initialState($ticket,$events);

        $current=$initial;
        $currentReason=$initialReason;
        $statusStarted=$created;
        $reasonStarted=$current==='PENDING'?$created:null;
        $durations=[];$segments=[];$transitions=[];$pendingReasonMinutes=[];$pendingReasonChanges=[];

        foreach($events as $event){
            $at=self::timestamp($event['created_at']??null);
            if($at===null||$at<$statusStarted)continue;
            $newData=self::json((string)($event['new_value']??''));
            $new=self::status($newData['status']??null);
            $eventType=(string)($event['event_type']??'');

            if($current==='PENDING'&&$new==='PENDING'&&$eventType==='PENDING_REASON_CHANGED'){
                if($reasonStarted!==null&&$at>=$reasonStarted){
                    self::accumulateReason($pendingReasonMinutes,$currentReason,(int)round(($at-$reasonStarted)/60));
                }
                $nextReason=self::reason($newData['pending_reason_code']??null);
                $pendingReasonChanges[]=[
                    'from'=>$currentReason,'to'=>$nextReason,'at'=>(string)($event['created_at']??''),
                    'actor'=>self::actor($event),
                ];
                $currentReason=$nextReason;$reasonStarted=$at;
                continue;
            }

            if($new===null||$new===$current)continue;

            $minutes=max(0,(int)round(($at-$statusStarted)/60));
            $durations[$current]=($durations[$current]??0)+$minutes;
            $segments[]=[
                'status'=>$current,'label'=>self::STATUS_LABELS[$current]??$current,
                'from'=>date('Y-m-d H:i:s',$statusStarted),'to'=>(string)($event['created_at']??''),'minutes'=>$minutes,
                'pending_reason'=>$current==='PENDING'?$currentReason:null,
            ];
            if($current==='PENDING'&&$reasonStarted!==null){
                self::accumulateReason($pendingReasonMinutes,$currentReason,(int)round(($at-$reasonStarted)/60));
            }

            $transitions[]=[
                'from'=>$current,'to'=>$new,
                'from_label'=>self::STATUS_LABELS[$current]??$current,'to_label'=>self::STATUS_LABELS[$new]??$new,
                'at'=>(string)($event['created_at']??''),'actor'=>self::actor($event),'event_type'=>$eventType,
                'pending_reason'=>$new==='PENDING'?self::reason($newData['pending_reason_code']??null):null,
                'pending_note'=>(string)($newData['pending_note']??''),
            ];

            $current=$new;$statusStarted=$at;
            if($current==='PENDING'){
                $currentReason=self::reason($newData['pending_reason_code']??null);$reasonStarted=$at;
            }else{
                $currentReason=null;$reasonStarted=null;
            }
        }

        $closedTs=self::timestamp($ticket['closed_at']??null);
        $end=$closedTs??$now;if($end<$statusStarted)$end=$statusStarted;
        $finalMinutes=max(0,(int)round(($end-$statusStarted)/60));
        $durations[$current]=($durations[$current]??0)+$finalMinutes;
        $segments[]=[
            'status'=>$current,'label'=>self::STATUS_LABELS[$current]??$current,'from'=>date('Y-m-d H:i:s',$statusStarted),
            'to'=>$closedTs?(string)$ticket['closed_at']:null,'minutes'=>$finalMinutes,'pending_reason'=>$current==='PENDING'?$currentReason:null,
        ];
        if($current==='PENDING'&&$reasonStarted!==null){
            self::accumulateReason($pendingReasonMinutes,$currentReason,(int)round(($end-$reasonStarted)/60));
        }

        return[
            'initial_status'=>$initial,'current_status'=>$current,'durations'=>$durations,'segments'=>$segments,
            'transitions'=>$transitions,'pending_reason_changes'=>$pendingReasonChanges,'pending_reason_minutes'=>$pendingReasonMinutes,
            'queue_minutes'=>(int)(($durations['NEW']??0)+($durations['AVAILABLE']??0)),
            'work_minutes'=>(int)(($durations['IN_PROGRESS']??0)+($durations['REOPENED']??0)),
            'pending_minutes'=>(int)($durations['PENDING']??0),'resolved_wait_minutes'=>(int)($durations['RESOLVED']??0),
            'assignment_minutes'=>self::between($ticket['created_at']??null,$ticket['assigned_at']??null),
            'first_response_minutes'=>self::between($ticket['created_at']??null,$ticket['first_response_at']??null),
            'resolution_minutes'=>self::between($ticket['created_at']??null,$ticket['resolved_at']??null),
            'total_minutes'=>self::between($ticket['created_at']??null,$ticket['closed_at']??date('Y-m-d H:i:s',$now)),
        ];
    }

    private static function initialState(array $ticket,array $events):array
    {
        foreach($events as $event){
            $new=self::json((string)($event['new_value']??''));
            $old=self::json((string)($event['old_value']??''));
            if(($event['event_type']??'')==='CREATED'){
                $status=self::status($new['status']??null);
                if($status!==null)return[$status,$status==='PENDING'?self::reason($new['pending_reason_code']??null):null];
            }
            $status=self::status($old['status']??null);
            if($status!==null)return[$status,$status==='PENDING'?self::reason($old['pending_reason_code']??null):null];
        }
        $status=self::status($ticket['status']??null)??'NEW';
        return[$status,$status==='PENDING'?self::reason($ticket['pending_reason_code']??null):null];
    }

    private static function accumulateReason(array &$totals,?string $reason,int $minutes):void
    {
        $key=$reason?:'UNSPECIFIED';$totals[$key]=($totals[$key]??0)+max(0,$minutes);
    }

    private static function actor(array $event):string
    {
        $actor=trim((string)($event['actor_name']??''));
        if($actor!=='')return $actor;
        return (($event['actor_type']??'SYSTEM')==='PUBLIC')?'Solicitante':'Sistema';
    }

    private static function status(mixed $value):?string
    {
        $status=strtoupper(trim((string)$value));
        return isset(self::STATUS_LABELS[$status])?$status:null;
    }

    private static function reason(mixed $value):?string
    {
        $reason=strtoupper(trim((string)$value));
        return $reason!==''?$reason:null;
    }

    private static function json(string $json):array
    {
        if(trim($json)==='')return[];
        $decoded=json_decode($json,true);
        return is_array($decoded)?$decoded:[];
    }

    private static function timestamp(mixed $value):?int
    {
        if($value===null||$value==='')return null;
        $ts=strtotime((string)$value);
        return $ts===false?null:$ts;
    }

    private static function between(mixed $from,mixed $to):?int
    {
        $a=self::timestamp($from);$b=self::timestamp($to);
        return($a!==null&&$b!==null&&$b>=$a)?(int)round(($b-$a)/60):null;
    }
}
