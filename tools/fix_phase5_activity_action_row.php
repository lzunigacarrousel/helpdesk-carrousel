<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$test=$root.'/tests/phase5_activities_ui_regression.php';
$css=$root.'/public/assets/css/ticket-activities.css';

function fail(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}
function readStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function writeStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false) fail('No se pudo escribir '.$path);
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count!==1) fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return str_replace($search,$replace,$content);
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}

chdir($root) || fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0) fail('No se pudo consultar git status.');
if($status) fail('El working tree debe estar limpio antes de aplicar este ajuste.');

$testBody=readStrict($test);
$eol=str_contains($testBody,"\r\n")?"\r\n":"\n";
$oldGate="ok(str_contains(\$activityCss,'.ticket-activity-actions details.ticket-activity-transition-details[open]{flex:1 0 100%;width:100%}'),'Toda transición abierta ocupa ancho completo');";
$newGate="ok(str_contains(\$activityCss,'.ticket-activity-actions details.ticket-activity-transition-details[open]{display:contents}'),'Transición abierta conserva botones en la fila de acciones');".$eol
    ."ok(str_contains(\$activityCss,'.ticket-activity-actions details.ticket-activity-transition-details[open]>.ticket-activity-transition{order:20;flex:1 0 100%;width:100%}'),'Panel abierto usa ancho completo debajo de las acciones');";
$testBody=replaceOnce($testBody,$oldGate,$newGate,'Gate de fila de acciones');
writeStrict($test,$testBody);

if(run('"'.$php.'" -l "'.$test.'"')!==0) fail('El gate RED tiene error de sintaxis.');
echo "=== RED esperado: el panel abierto aún desplaza los botones ===".PHP_EOL;
$red=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($red===0) fail('El RED no falló; el nuevo gate no detectó el problema visual.');
echo '[OK] RED confirmado: el layout actual no mantiene la fila de acciones.'.PHP_EOL.PHP_EOL;

$cssBody=readStrict($css);
$cssEol=str_contains($cssBody,"\r\n")?"\r\n":"\n";
$oldCss='.ticket-activity-actions details.ticket-activity-transition-details[open]{flex:1 0 100%;width:100%}';
$newCss='.ticket-activity-actions details.ticket-activity-transition-details[open]{display:contents}'.$cssEol
    .'.ticket-activity-actions details.ticket-activity-transition-details[open]>.ticket-activity-transition{order:20;flex:1 0 100%;width:100%}';
$cssBody=replaceOnce($cssBody,$oldCss,$newCss,'Layout de transición abierta');
writeStrict($css,$cssBody);

echo "=== GREEN esperado: botones alineados y panel debajo ===".PHP_EOL;
$green=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($green!==0) fail('La regresión de Fase 5 no quedó en verde.');
if(run('git diff --check')!==0) fail('git diff --check encontró problemas.');

echo '[OK] Iniciar, Reprogramar/Finalizar y Cancelar conservan la fila de acciones.'.PHP_EOL;
echo '[OK] El formulario abierto salta debajo y conserva ancho completo.'.PHP_EOL;
echo '[OK] No se modificó lógica, base de datos ni estados del ticket.'.PHP_EOL;
