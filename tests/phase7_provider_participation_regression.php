<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$viewPath=$root.'/app/Views/management/external_report.php';
$workReportPath=$root.'/app/Controllers/WorkReportController.php';
$ciPath=$root.'/.github/workflows/helpdesk-ci.yml';
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

$users=[
    10=>[
        'id'=>10,
        'full_name'=>'Proveedor Uno',
        'email'=>'proveedor1@example.com',
        'organization_name'=>'Proveedor Uno, S.A.',
    ],
    11=>[
        'id'=>11,
        'full_name'=>'Proveedor Dos',
        'email'=>'proveedor2@example.com',
        'organization_name'=>'Proveedor Dos, S.A.',
    ],
];

$events=[
    [
        'id'=>1,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_GRANTED',
        'old_value'=>null,
        'new_value'=>j(['external_user_id'=>10]),
        'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),
        'created_at'=>'2026-09-01 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>2,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_REVOKED',
        'old_value'=>j(['external_user_id'=>10]),
        'new_value'=>null,
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-03 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>3,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_GRANTED',
        'old_value'=>null,
        'new_value'=>j(['external_user_id'=>10]),
        'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),
        'created_at'=>'2026-09-04 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
];

$serviceBody=is_file($servicePath)?(string)file_get_contents($servicePath):'';

ok($serviceBody!=='','Existe ProviderParticipationService');
ok(str_contains($serviceBody,'function buildCycles('),'Expone buildCycles');
ok(str_contains($serviceBody,'function rows('),'Expone rows');
ok(!preg_match('/\b(INSERT|UPDATE|DELETE|ALTER|CREATE TABLE)\b/i',$serviceBody),'Servicio no escribe ni altera BD');

if($serviceBody!==''){
    require_once $servicePath;
    if(class_exists('App\\Services\\ProviderParticipationService')){
        $rows=\App\Services\ProviderParticipationService::buildCycles(
            $users,
            $events,
            [],
            [],
            [],
            strtotime('2026-09-05 08:00:00')
        );
        ok(count($rows)===2,'Dos asignaciones del mismo proveedor/ticket forman ciclos independientes');
    }else{
        ok(false,'Clase ProviderParticipationService disponible');
    }
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contrato base de participación de proveedores.'.PHP_EOL;
