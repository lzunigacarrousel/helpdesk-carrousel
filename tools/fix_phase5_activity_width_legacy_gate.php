<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$test=$root.'/tests/phase5_activities_ui_regression.php';

function fail(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}

$body=@file_get_contents($test);
if(!is_string($body)) fail('No se pudo leer '.$test);

$old="ok(str_contains(\$activityCss,'.ticket-activity-complete-details[open]'),'Finalizar abierto ocupa el ancho disponible');";
$new="ok(str_contains(\$activityCss,'.ticket-activity-complete-details[open]')||str_contains(\$activityCss,'.ticket-activity-transition-details[open]'),'Finalizar abierto ocupa el ancho disponible');";

$count=substr_count($body,$old);
if($count===0){
    if(str_contains($body,$new)){
        echo '[OK] Gate legacy de Finalizar ya estaba actualizado.'.PHP_EOL;
        exit(0);
    }
    fail('No se encontró el gate legacy esperado.');
}
if($count!==1) fail('Se esperaban 1 coincidencia y se encontraron '.$count.'.');

$updated=str_replace($old,$new,$body);
if(file_put_contents($test,$updated)===false) fail('No se pudo actualizar '.$test);

echo '[OK] Gate legacy de Finalizar actualizado para aceptar la regla generalizada.'.PHP_EOL;
