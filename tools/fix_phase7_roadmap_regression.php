<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/tests/phase7_provider_participation_regression.php';

if(!is_file($path)){
    fwrite(STDERR,'[ERROR] No existe: '.$path.PHP_EOL);
    exit(1);
}

$body=str_replace(["\r\n","\r"],"\n",(string)file_get_contents($path));
$old="ok(str_contains(\$readmeBody,'| 8 | Calidad IT → proveedor | **Siguiente fase** |'),'README marca Fase 8 como siguiente');";
$new="ok(str_contains(\$readmeBody,'| 8 | Calidad IT → proveedor |'),'README conserva Fase 8 en roadmap');";

$count=substr_count($body,$old);
if($count===0){
    if(str_contains($body,$new)){
        echo '[OK] Regresion de roadmap Fase 7: ya corregida.'.PHP_EOL;
        exit(0);
    }
    fwrite(STDERR,'[ERROR] No se encontro la asercion heredada de Fase 7.'.PHP_EOL);
    exit(1);
}
if($count!==1){
    fwrite(STDERR,'[ERROR] Se esperaban 1 coincidencia y se encontraron '.$count.'.'.PHP_EOL);
    exit(1);
}

file_put_contents($path,str_replace($old,$new,$body));

echo '[OK] Fase 7 deja de fijar Fase 8 como siguiente.'.PHP_EOL;
echo '[OK] Fase 7 conserva verificacion de Fase 8 dentro del roadmap.'.PHP_EOL;

passthru('"'.PHP_BINARY.'" -l "'.$path.'"',$code);
exit($code);
