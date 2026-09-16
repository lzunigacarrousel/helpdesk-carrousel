<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$paths=[
    $root.'/app/Controllers/TicketController.php',
    $root.'/app/Views/tickets/show.php',
];

foreach($paths as $path){
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe: '.$path.PHP_EOL);
        exit(1);
    }

    $raw=(string)file_get_contents($path);
    $normalized=str_replace("\r\n","\n",$raw);
    $normalized=str_replace("\r","\n",$normalized);

    if($normalized===$raw){
        echo '[OK] Ya usa LF: '.$path.PHP_EOL;
        continue;
    }

    if(file_put_contents($path,$normalized)===false){
        fwrite(STDERR,'[ERROR] No se pudo normalizar: '.$path.PHP_EOL);
        exit(1);
    }

    echo '[OK] Normalizado a LF: '.$path.PHP_EOL;
}

echo '[OK] EOL de Task 4 normalizado. No se modifico la BD.'.PHP_EOL;
