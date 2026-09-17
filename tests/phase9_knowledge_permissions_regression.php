<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$install=(string)file_get_contents($root.'/database/INSTALAR.sql');
$migration=(string)file_get_contents($root.'/database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

foreach([
    'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
    'knowledge.publish_public','knowledge.history','knowledge.restore'
] as $permission){
    ok(str_contains($install,"'{$permission}'"),"INSTALAR contiene {$permission}");
    ok(str_contains($migration,"'{$permission}'"),"Migración contiene {$permission}");
}

$techStart=strpos($install,"WHERE r.code='TECHNICIAN'");
$managementStart=strpos($install,"-- Gerencia:",$techStart!==false?$techStart:0);
$techBlock=$techStart!==false
    ? substr($install,$techStart,($managementStart!==false?$managementStart:strlen($install))-$techStart)
    : '';

ok($techBlock!=='','Existe bloque de permisos TECHNICIAN');
ok(str_contains($techBlock,"'knowledge.view'"),'TECHNICIAN conserva knowledge.view');
ok(str_contains($techBlock,"'knowledge.draft_manage'"),'TECHNICIAN puede gestionar borradores');
foreach(['knowledge.review','knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'] as $forbidden){
    ok(!str_contains($techBlock,"'{$forbidden}'"),"TECHNICIAN no recibe {$forbidden}");
}

$semiStart=strpos($install,"WHERE r.code='SEMIADMIN'");
$techComment=strpos($install,"-- Tecnico:",$semiStart!==false?$semiStart:0);
$semiBlock=$semiStart!==false
    ? substr($install,$semiStart,($techComment!==false?$techComment:strlen($install))-$semiStart)
    : '';

ok($semiBlock!=='','Existe bloque de permisos SEMIADMIN');
foreach([
    'knowledge.draft_manage','knowledge.review','knowledge.publish_internal',
    'knowledge.publish_public','knowledge.history','knowledge.restore'
] as $permission){
    ok(str_contains($semiBlock,"'{$permission}'"),"SEMIADMIN recibe {$permission}");
}

ok(str_contains($migration,"WHERE r.code='ADMIN'"),'Migración enlaza permisos nuevos a ADMIN');
ok(str_contains($migration,"WHERE r.code='TECHNICIAN'"),'Migración enlaza permisos de técnico');
ok(str_contains($migration,"WHERE r.code='SEMIADMIN'"),'Migración enlaza permisos de semiadmin');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Gobierno editorial de conocimiento Fase 9.'.PHP_EOL;
