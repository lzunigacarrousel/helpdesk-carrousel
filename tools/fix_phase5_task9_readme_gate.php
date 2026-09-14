<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/tests/phase5_activities_ui_regression.php';
$body=@file_get_contents($path);
if(!is_string($body)){
    fwrite(STDERR,"[ERROR] No se pudo leer tests/phase5_activities_ui_regression.php".PHP_EOL);
    exit(1);
}

$old="ok(str_contains(\$readme,'Fase 6 | Agenda | **Siguiente fase**'),'README marca Agenda como siguiente fase');";
$new="ok(str_contains(\$readme,'| 6 | Agenda | **Siguiente fase**; depende de `ticket_activities` |'),'README marca Agenda como siguiente fase');";

if(str_contains($body,$new)){
    echo "[OK] Gate README Task 9 ya estaba corregido.".PHP_EOL;
    exit(0);
}

$count=substr_count($body,$old);
if($count!==1){
    fwrite(STDERR,"[ERROR] Se esperaba una sola asercion antigua de Agenda y se encontraron {$count}.".PHP_EOL);
    exit(1);
}

$body=str_replace($old,$new,$body);
if(@file_put_contents($path,$body)===false){
    fwrite(STDERR,"[ERROR] No se pudo escribir el test.".PHP_EOL);
    exit(1);
}

echo "[OK] Gate README Task 9 corregido para validar la fila Markdown real de Fase 6.".PHP_EOL;
