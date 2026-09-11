<?php
declare(strict_types=1);

$root=$argv[1]??dirname(__DIR__);
$fails=0;
function check(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}

$css=body($root.'/public/assets/css/help-tour-contrast.css');
$appStart=body($root.'/app/Views/shared/app_start.php');
$publicHome=body($root.'/app/Views/tickets/public_home.php');
$publicCreate=body($root.'/app/Views/tickets/public_create.php');
$manual=body($root.'/app/Views/help/manual.php');

check($css!=='','Existe capa visual dedicada del tutorial');
check(str_contains($css,'.tour-popover'),'Capa visual define panel del tutorial');
check(str_contains($css,'.tour-highlight'),'Capa visual define foco del elemento activo');
check(str_contains($css,'.tour-overlay'),'Capa visual define overlay de separación');
check(str_contains($css,'border:2px solid'),'Panel del tutorial usa borde de alto contraste');
check(str_contains($css,'box-shadow:0 24px 70px'),'Panel del tutorial usa sombra clara');
check(str_contains($css,'outline:4px solid'),'Elemento guiado recibe contorno visible');
check(str_contains($css,'@media(max-width:900px)'),'Tutorial incluye tratamiento específico para tablet/móvil');
check(str_contains($css,'bottom:16px!important'),'En tablet el tutorial usa panel inferior estable');
check(str_contains($appStart,'help-tour-contrast.css'),'Shell autenticado carga contraste del tutorial');
check(str_contains($publicHome,'help-tour-contrast.css'),'Portada pública carga contraste del tutorial');
check(str_contains($publicCreate,'help-tour-contrast.css'),'Solicitar ayuda carga contraste del tutorial');
check(!str_contains($manual,'\\n'),'Manual no muestra secuencia literal \\n entre tarjetas');

if($fails){fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validacion(es) fallaron.".PHP_EOL);exit(1);}echo PHP_EOL."[OK] Regresion visual tutorial/manual completada.".PHP_EOL;
