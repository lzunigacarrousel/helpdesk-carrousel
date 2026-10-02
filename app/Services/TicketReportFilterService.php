<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class TicketReportFilterService
{
    public const STATUS_LABELS=[
        'NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera',
        'RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'
    ];

    public const PRIORITY_LABELS=[
        'LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'
    ];

    public function filters(array $input,?string $defaultFrom=null): array
    {
        $today=date('Y-m-d');
        $monthStart=date('Y-m-01');
        $periodStart=$defaultFrom!==null&&$this->validDate($defaultFrom)?$defaultFrom:$monthStart;

        $from=trim((string)($input['from']??$periodStart));
        $to=trim((string)($input['to']??$today));

        if(!$this->validDate($from))$from=$periodStart;
        if(!$this->validDate($to))$to=$today;
        if($from>$to){$from=$periodStart;$to=$today;}

        $status=strtoupper(trim((string)($input['status']??'')));
        if(!array_key_exists($status,self::STATUS_LABELS))$status='';

        $priority=strtoupper(trim((string)($input['priority']??'')));
        if(!array_key_exists($priority,self::PRIORITY_LABELS))$priority='';

        return[
            'from'=>$from,
            'to'=>$to,
            'park_id'=>max(0,(int)($input['park_id']??0)),
            'category_id'=>max(0,(int)($input['category_id']??0)),
            'assigned_to'=>max(0,(int)($input['assigned_to']??0)),
            'status'=>$status,
            'priority'=>$priority,
        ];
    }

    public function where(array $f,string $alias='t'): array
    {
        $t=preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$alias)?$alias:'t';
        $w=["{$t}.deleted_at IS NULL","{$t}.created_at>=?","{$t}.created_at<DATE_ADD(?,INTERVAL 1 DAY)"];
        $p=[$f['from'],$f['to']];

        [$scopeSql,$scopeParams]=(new ScopeService())->ticketConstraint($t);
        if($scopeSql!=='1=1'){$w[]=$scopeSql;array_push($p,...$scopeParams);}

        if((int)$f['park_id']>0){$w[]="{$t}.park_id=?";$p[]=(int)$f['park_id'];}
        if((int)$f['category_id']>0){
            $w[]="{$t}.category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)";
            $p[]=(int)$f['category_id'];
            $p[]=(int)$f['category_id'];
        }
        if((int)$f['assigned_to']>0){$w[]="{$t}.assigned_to=?";$p[]=(int)$f['assigned_to'];}
        if((string)$f['status']!==''){$w[]="{$t}.status=?";$p[]=(string)$f['status'];}
        if((string)$f['priority']!==''){$w[]="{$t}.priority=?";$p[]=(string)$f['priority'];}

        return[' WHERE '.implode(' AND ',$w),$p];
    }

    public function description(PDO $pdo,array $f): string
    {
        $parts=['Período '.$f['from'].' a '.$f['to']];

        if((int)$f['park_id']>0){
            $q=$pdo->prepare('SELECT name FROM parks WHERE id=? LIMIT 1');
            $q->execute([(int)$f['park_id']]);
            $parts[]='Parque '.($q->fetchColumn()?:$f['park_id']);
        }

        if((int)$f['category_id']>0){
            $q=$pdo->prepare('SELECT name FROM ticket_categories WHERE id=? LIMIT 1');
            $q->execute([(int)$f['category_id']]);
            $parts[]='Categoría '.($q->fetchColumn()?:$f['category_id']);
        }

        if((int)$f['assigned_to']>0){
            $q=$pdo->prepare('SELECT full_name FROM users WHERE id=? LIMIT 1');
            $q->execute([(int)$f['assigned_to']]);
            $parts[]='Responsable '.($q->fetchColumn()?:$f['assigned_to']);
        }

        if((string)$f['status']!=='')$parts[]='Estado '.(self::STATUS_LABELS[$f['status']]??$f['status']);
        if((string)$f['priority']!=='')$parts[]='Prioridad '.(self::PRIORITY_LABELS[$f['priority']]??$f['priority']);

        return implode(' · ',$parts);
    }

    private function validDate(string $value): bool
    {
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$value))return false;
        $dt=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        return $dt!==false&&$dt->format('Y-m-d')===$value;
    }
}
