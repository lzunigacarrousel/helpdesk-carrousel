<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

function j(array $value):string
{
    return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

require_once $servicePath;

$users=[
    10=>[
        'id'=>10,
        'full_name'=>'Proveedor Uno',
        'email'=>'proveedor1@example.com',
        'organization_name'=>'Proveedor Uno, S.A.',
    ],
];

$events=[
    [
        'id'=>1,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_GRANTED',
        'old_value'=>null,
        'new_value'=>j(['external_user_id'=>10]),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-01 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'IN_PROGRESS',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>2,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'IN_PROGRESS']),
        'new_value'=>j(['status'=>'REOPENED']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-01 08:30:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>3,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'PENDING']),
        'new_value'=>j(['status'=>'IN_PROGRESS']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-01 12:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'IN_PROGRESS',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>4,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'PENDING']),
        'new_value'=>j(['status'=>'IN_PROGRESS']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-02 14:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'IN_PROGRESS',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>5,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'IN_PROGRESS']),
        'new_value'=>j(['status'=>'PENDING']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-02 15:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'PENDING',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>6,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'PENDING']),
        'new_value'=>j(['status'=>'REOPENED']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-03 10:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>7,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_REVOKED',
        'old_value'=>j(['external_user_id'=>10]),
        'new_value'=>null,
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-04 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>8,
        'ticket_id'=>100,
        'event_type'=>'STATUS_CHANGED',
        'old_value'=>j(['status'=>'PENDING']),
        'new_value'=>j(['status'=>'IN_PROGRESS']),
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-05 10:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'IN_PROGRESS',
        'actor_name'=>'Luis',
    ],
];

$reports=[
    [
        'id'=>601,
        'ticket_id'=>100,
        'author_user_id'=>10,
        'comment_id'=>null,
        'work_status'=>'READY_FOR_REVIEW',
        'time_spent_minutes'=>30,
        'ready_for_review'=>1,
        'created_at'=>'2026-09-01 10:00:00',
    ],
    [
        'id'=>602,
        'ticket_id'=>100,
        'author_user_id'=>10,
        'comment_id'=>null,
        'work_status'=>'READY_FOR_REVIEW',
        'time_spent_minutes'=>20,
        'ready_for_review'=>1,
        'created_at'=>'2026-09-01 11:00:00',
    ],
    [
        'id'=>603,
        'ticket_id'=>100,
        'author_user_id'=>10,
        'comment_id'=>null,
        'work_status'=>'READY_FOR_REVIEW',
        'time_spent_minutes'=>15,
        'ready_for_review'=>1,
        'created_at'=>'2026-09-02 16:00:00',
    ],
];

$rows=\App\Services\ProviderParticipationService::buildCycles(
    $users,
    $events,
    [],
    [],
    $reports,
    strtotime('2026-09-05 12:00:00')
);

$cycle=$rows[0]??[];

ok((int)($cycle['deliveries']??-1)===3,'Cuenta las tres entregas READY_FOR_REVIEW del ciclo');
ok(array_key_exists('returns',$cycle),'Ciclo expone métrica de devoluciones');
ok((int)($cycle['returns']??-1)===2,'Dos retornos posteriores a entregas cuentan como dos devoluciones');
ok((int)($cycle['returns']??-1)!==3,'Dos entregas antes del mismo retorno no duplican la devolución');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Devoluciones de proveedores posteriores a entrega.'.PHP_EOL;
