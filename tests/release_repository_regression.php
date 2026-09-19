<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$readme=(string)file_get_contents($root.'/README.md');
$main=(string)file_get_contents($root.'/MAIN.bat');
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');
$gitignore=(string)file_get_contents($root.'/.gitignore');
$prodConfigTool=(string)@file_get_contents($root.'/tools/PREPARAR_CONFIG_PRODUCCION.ps1');

ok(str_starts_with($readme,'# Helpdesk Carrousel'),'README usa identidad canónica');
ok(!str_contains($readme,'Helpdesk Carrousel 360'),'README no usa marca 360');
ok(!str_contains($main,'Helpdesk Carrousel 360'),'MAIN no usa marca 360');
ok(str_contains($readme,'PREPRODUCCIÓN TÉCNICA GREEN'),'README refleja estado actual');
ok(!str_contains($readme,'Roadmap funcional de 12 fases'),'README no conserva roadmap histórico');
ok(!str_contains($readme,'VALIDAR_FASE'),'README no expone gates históricos');
ok(str_contains($main,'Validar PREPRODUCCION'),'MAIN expone gate canónico');
ok(str_contains($main,'Preparar configuracion PRODUCCION'),'MAIN permite preparar configuración privada de producción');
ok($prodConfigTool!=='','Existe preparador privado de configuración de producción');
ok(str_contains($prodConfigTool,'dist\\production-config'),'Preparador escribe únicamente en dist/production-config');
ok(str_contains($gitignore,'/dist/'),'dist/ permanece ignorado por Git');
ok(!str_contains($main,'Historial Git'),'MAIN elimina utilidades no esenciales');
ok(!str_contains($main,'Mostrar URLs'),'MAIN elimina utilidades no esenciales');

$rootPhaseGates=glob($root.'/VALIDAR_FASE*.bat')?:[];
ok($rootPhaseGates===[],'No quedan gates históricos de fase en la raíz');

$rootBats=array_map('basename',glob($root.'/*.bat')?:[]);
sort($rootBats);
$expected=['INSTALAR_PC_TEST.bat','INSTALAR_PRODUCCION.bat','MAIN.bat','VALIDAR_PREPRODUCCION.bat'];
sort($expected);
ok($rootBats===$expected,'Raíz contiene únicamente BAT operativos canónicos');

ok(!str_contains($ci,"'fase*'"),'CI solo trabaja sobre main');
ok(str_contains($ci,'tests/release_repository_regression.php'),'CI valida contrato de release');
ok(str_contains($ci,'= "42"'),'CI espera 42 tablas canónicas');
ok(!str_contains($ci,'= "43"'),'CI no conserva conteo legacy de 43 tablas');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Repositorio de release limpio y canónico.'.PHP_EOL;
