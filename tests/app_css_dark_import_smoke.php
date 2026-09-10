<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$css=(string)@file_get_contents($root.'/public/assets/css/app.css');
$ok=str_contains($css,"@import url('./dark-refinement.css');");
echo ($ok?'[OK] ':'[FALLO] ').'app.css carga dark-refinement.css para todo el proyecto'.PHP_EOL;
exit($ok?0:1);
