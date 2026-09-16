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

function j8(array $value):string
{
    return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$body=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($body!=='','Existe ProviderParticipationService');
ok(str_contains($body,'function rowsForTicket('),'Expone rowsForTicket');

if($body!==''){
    require_once $servicePath;
    if(class_exists('App\\Services\\ProviderParticipationService')){
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
                'id'=>101,
                'ticket_id'=>500,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j8(['external_user_id'=>10]),
                'metadata_json'=>j8(['can_comment'=>true,'can_upload'=>true]),
                'created_at'=>'2026-09-01 08:00:00',
                'ticket_number'=>'HD-500',
                'subject'=>'Caso identidad',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Luis',
            ],
            [
                'id'=>102,
                'ticket_id'=>500,
                'event_type'=>'EXTERNAL_REVOKED',
                'old_value'=>j8(['external_user_id'=>10]),
                'new_value'=>null,
                'metadata_json'=>'{}',
                'created_at'=>'2026-09-02 08:00:00',
                'ticket_number'=>'HD-500',
                'subject'=>'Caso identidad',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Luis',
            ],
            [
                'id'=>103,
                'ticket_id'=>500,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j8(['external_user_id'=>10]),
                'metadata_json'=>j8(['can_comment'=>true,'can_upload'=>true]),
                'created_at'=>'2026-09-03 08:00:00',
                'ticket_number'=>'HD-500',
                'subject'=>'Caso identidad',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Luis',
            ],
        ];

        $rows=\App\Services\ProviderParticipationService::buildCycles(
            $users,
            $events,
            [],
            [],
            [],
            strtotime('2026-09-05 08:00:00')
        );

        ok(count($rows)===2,'Dos ciclos siguen siendo independientes');
        ok((int)($rows[0]['grant_event_id']??0)===101,'Primer ciclo conserva grant_event_id');
        ok((int)($rows[0]['revoke_event_id']??0)===102,'Primer ciclo conserva revoke_event_id');
        ok((int)($rows[1]['grant_event_id']??0)===103,'Segundo ciclo usa otro grant_event_id');
        ok(array_key_exists('revoke_event_id',$rows[1])&&$rows[1]['revoke_event_id']===null,'Ciclo activo no inventa revoke_event_id');

        $duplicateGrantEvents=[
            [
                'id'=>201,
                'ticket_id'=>600,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j8(['external_user_id'=>10]),
                'metadata_json'=>'{}',
                'created_at'=>'2026-09-01 10:00:00',
                'ticket_number'=>'HD-600',
                'subject'=>'Caso grant duplicado',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Admin Uno',
            ],
            [
                'id'=>202,
                'ticket_id'=>600,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j8(['external_user_id'=>10]),
                'metadata_json'=>'{}',
                'created_at'=>'2026-09-02 10:00:00',
                'ticket_number'=>'HD-600',
                'subject'=>'Caso grant duplicado',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Admin Dos',
            ],
        ];

        $duplicateRows=\App\Services\ProviderParticipationService::buildCycles(
            $users,
            $duplicateGrantEvents,
            [],
            [],
            [],
            strtotime('2026-09-03 10:00:00')
        );
        ok((int)($duplicateRows[0]['grant_event_id']??0)===201,'Cierre implícito conserva grant_event_id original');
        ok(array_key_exists('revoke_event_id',$duplicateRows[0])&&$duplicateRows[0]['revoke_event_id']===null,'Cierre implícito no inventa EXTERNAL_REVOKED');
    }else{
        ok(false,'Clase ProviderParticipationService disponible');
    }
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Identidad exacta de ciclos de proveedor.'.PHP_EOL;
