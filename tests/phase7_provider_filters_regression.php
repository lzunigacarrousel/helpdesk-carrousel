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

require_once $servicePath;

$service='App\\Services\\ProviderParticipationService';
$body=(string)file_get_contents($servicePath);

ok(str_contains($body,'function applyFilters('),'Expone applyFilters');
ok(str_contains($body,'function summary('),'Expone summary');
ok(str_contains($body,'function providers('),'Expone providers');
ok(str_contains($body,'function activityOptions('),'Expone activityOptions');

$rows=[
    [
        'user_id'=>10,'organization'=>'Proveedor Uno, S.A.','contact'=>'Ana Uno','email'=>'ana@example.com',
        'ticket_number'=>'HD-100','subject'=>'Impresora','granted_at'=>'2026-09-01 08:00:00','revoked_at'=>null,
        'work_status'=>null,'first_response_minutes'=>60,'returns'=>0,
    ],
    [
        'user_id'=>10,'organization'=>'Proveedor Uno, S.A.','contact'=>'Ana Uno','email'=>'ana@example.com',
        'ticket_number'=>'HD-101','subject'=>'Router','granted_at'=>'2026-09-05 09:00:00','revoked_at'=>'2026-09-06 09:00:00',
        'work_status'=>'ANALYSIS','first_response_minutes'=>120,'returns'=>1,
    ],
    [
        'user_id'=>11,'organization'=>'Proveedor Dos, S.A.','contact'=>'Bruno Dos','email'=>'bruno@example.com',
        'ticket_number'=>'HD-102','subject'=>'Servidor','granted_at'=>'2026-09-10 10:00:00','revoked_at'=>null,
        'work_status'=>'READY_FOR_REVIEW','first_response_minutes'=>null,'returns'=>2,
    ],
];

if(method_exists($service,'applyFilters')){
    $filtered=$service::applyFilters($rows,['q'=>'router','provider'=>0,'state'=>'','activity'=>'','from'=>'','to'=>'']);
    ok(count($filtered)===1&&($filtered[0]['ticket_number']??'')==='HD-101','Buscar filtra proveedor/ticket/asunto');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>10,'state'=>'','activity'=>'','from'=>'','to'=>'']);
    ok(count($filtered)===2,'Filtro proveedor restringe por user_id');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>0,'state'=>'active','activity'=>'','from'=>'','to'=>'']);
    ok(count($filtered)===2,'Filtro Activas usa revoked_at null');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>0,'state'=>'closed','activity'=>'','from'=>'','to'=>'']);
    ok(count($filtered)===1&&($filtered[0]['ticket_number']??'')==='HD-101','Filtro Finalizadas usa revoked_at');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>0,'state'=>'','activity'=>'NONE','from'=>'','to'=>'']);
    ok(count($filtered)===1&&($filtered[0]['ticket_number']??'')==='HD-100','Actividad NONE selecciona Sin actualización');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>0,'state'=>'','activity'=>'ANALYSIS','from'=>'','to'=>'']);
    ok(count($filtered)===1&&($filtered[0]['ticket_number']??'')==='HD-101','Filtro actividad selecciona work_status exacto');

    $filtered=$service::applyFilters($rows,['q'=>'','provider'=>0,'state'=>'','activity'=>'','from'=>'2026-09-05','to'=>'2026-09-10']);
    ok(count($filtered)===2,'Rango Desde/Hasta filtra por granted_at inclusivo');
}else{
    ok(false,'Filtros ejecutables');
}

if(method_exists($service,'summary')){
    $summary=$service::summary($rows);
    ok((int)($summary['participations']??-1)===3,'Resumen cuenta participaciones');
    ok((int)($summary['active']??-1)===2,'Resumen cuenta activas');
    ok((int)($summary['no_response']??-1)===1,'Resumen cuenta sin respuesta');
    ok((int)($summary['avg_first_response_minutes']??-1)===90,'Promedio primera respuesta ignora null y redondea');
    ok((int)($summary['returns']??-1)===3,'Resumen suma devoluciones');
}else{
    ok(false,'Resumen ejecutable');
}

if(method_exists($service,'providers')){
    $providers=$service::providers($rows);
    ok(count($providers)===2,'Opciones de proveedor eliminan duplicados');
    ok(($providers[10]??null)==='Proveedor Uno, S.A.','Proveedor usa organización como etiqueta');
}else{
    ok(false,'Opciones de proveedor ejecutables');
}

if(method_exists($service,'activityOptions')){
    $activities=$service::activityOptions();
    ok(($activities['NONE']??null)==='Sin actualización','Opciones incluyen Sin actualización');
    ok(($activities['ANALYSIS']??null)==='En análisis / diagnóstico','Opciones incluyen análisis');
    ok(($activities['READY_FOR_REVIEW']??null)==='Listo para revisión de Carrousel','Opciones incluyen listo para revisión');
}else{
    ok(false,'Opciones de actividad ejecutables');
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Filtros y resumen de proveedores.'.PHP_EOL;
