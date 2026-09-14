<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;

function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}

function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$install=body($root.'/database/INSTALAR.sql');
$migration=body($root.'/database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql');
$verify=body($root.'/database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql');
$ci=body($root.'/.github/workflows/helpdesk-ci.yml');

ok(str_contains($install,'CREATE TABLE ticket_activities'),'Instalación canónica crea ticket_activities');
ok(str_contains($install,'CREATE TABLE ticket_activity_participants'),'Instalación canónica crea participantes');
ok(str_contains($install,'activity_id BIGINT UNSIGNED NULL'),'Adjuntos admiten relación opcional a actividad');

foreach(['activities.view','activities.create','activities.manage','activities.cancel'] as $permission){
    ok(str_contains($install,$permission),'Permiso canónico '.$permission);
}

foreach(['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'] as $type){
    ok(str_contains($install,$type),'Tipo de actividad '.$type);
}

foreach(['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $status){
    ok(str_contains($install,$status),'Estado de actividad '.$status);
}

ok($migration!==''&&str_contains($migration,'2026-09-13-fase5-actividades'),'Existe migración incremental idempotente');
ok($verify!==''&&str_contains($verify,'ticket_activities'),'Existe verificador específico');
ok(str_contains($ci,'main')&&str_contains($ci,'fase'),'CI cubre main y ramas de fase');
ok(str_contains($ci,'phase5_activities_schema_regression.php'),'CI ejecuta gate Fase 5');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Gate de esquema Fase 5 completado.".PHP_EOL;
