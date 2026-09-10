<?php
declare(strict_types=1);

namespace App\Services;

final class SlaPresentationService
{
    private const FINAL_STATUSES=['RESOLVED','CLOSED','CANCELLED'];

    public static function summary(array $ticket,?int $now=null):array
    {
        $now=$now??time();
        $status=strtoupper(trim((string)($ticket['status']??'')));
        $created=self::timestamp($ticket['created_at']??null);
        $due=self::timestamp($ticket['resolution_due_at']??null);

        if(in_array($status,self::FINAL_STATUSES,true)){
            return[
                'state'=>'completed','state_label'=>'Finalizado','tone'=>'neutral',
                'remaining_seconds'=>null,'remaining_label'=>'Finalizado','utilization_percent'=>null,
                'due_at'=>$ticket['resolution_due_at']??null,
            ];
        }

        if($created===null||$due===null||$due<=$created){
            return[
                'state'=>'none','state_label'=>'Sin SLA','tone'=>'neutral',
                'remaining_seconds'=>null,'remaining_label'=>'Sin SLA','utilization_percent'=>null,
                'due_at'=>$ticket['resolution_due_at']??null,
            ];
        }

        $total=max(1,$due-$created);
        $elapsed=max(0,$now-$created);
        $remaining=$due-$now;
        $percent=(int)round(($elapsed/$total)*100);
        $percent=max(0,$percent);

        if($remaining<0){
            $state='overdue';$label='Vencido';$tone='danger';
            $remainingLabel='Vencido hace '.self::duration(abs($remaining));
        }elseif($percent>=90||$remaining<=1800){
            $state='near_due';$label='Próximo a vencer';$tone='warning';
            $remainingLabel=self::duration($remaining).' restantes';
        }elseif($percent>=75){
            $state='attention';$label='Atención requerida';$tone='attention';
            $remainingLabel=self::duration($remaining).' restantes';
        }else{
            $state='within';$label='Dentro de objetivo';$tone='ok';
            $remainingLabel=self::duration($remaining).' restantes';
        }

        return[
            'state'=>$state,'state_label'=>$label,'tone'=>$tone,
            'remaining_seconds'=>$remaining,'remaining_label'=>$remainingLabel,
            'utilization_percent'=>$percent,'due_at'=>$ticket['resolution_due_at']??null,
        ];
    }

    public static function isOverdue(array $ticket,?int $now=null):bool
    {
        return (self::summary($ticket,$now)['state']??'')==='overdue';
    }

    public static function isNearDue(array $ticket,?int $now=null):bool
    {
        return (self::summary($ticket,$now)['state']??'')==='near_due';
    }

    private static function duration(int $seconds):string
    {
        $seconds=max(0,$seconds);
        $days=intdiv($seconds,86400);$hours=intdiv($seconds%86400,3600);$minutes=intdiv($seconds%3600,60);
        if($days>0)return $days.' d '.($hours>0?$hours.' h':'');
        if($hours>0)return $hours.' h '.($minutes>0?$minutes.' min':'');
        return max(1,$minutes).' min';
    }

    private static function timestamp(mixed $value):?int
    {
        if($value===null||$value==='')return null;
        $ts=strtotime((string)$value);
        return $ts===false?null:$ts;
    }
}
