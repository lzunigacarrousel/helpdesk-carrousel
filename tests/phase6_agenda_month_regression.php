<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$viewPath=$root.'/app/Views/agenda/index.php';
$cssPath=$root.'/public/assets/css/agenda.css';
$controller=is_file($controllerPath)?file_get_contents($controllerPath):'';
$view=is_file($viewPath)?file_get_contents($viewPath):'';
$css=is_file($cssPath)?file_get_contents($cssPath):'';
$errors=0;

function ok(bool $condition,string $message):void{
    global $errors;
    echo($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok($controller!=='','Se puede leer AgendaController');
ok($view!=='','Se puede leer la vista Agenda');
ok($css!=='','Se puede leer agenda.css');
ok(str_contains($controller,"['month','calendar','list']"),'Controller acepta Mes, Semana y Lista');
ok(str_contains($controller,"'first day of this month'")&&str_contains($controller,"'last day of this month'"),'Controller define rango mensual predeterminado');
ok(str_contains($view,"['view'=>'month'")&&str_contains($view,'>Mes</a>'),'Agenda ofrece selector Mes');
ok(str_contains($view,'>Semana</a>'),'Agenda renombra Calendario a Semana');
ok(str_contains($view,'agenda-month-grid'),'Vista contiene cuadrícula mensual');
ok(str_contains($view,'agenda-month-day'),'Vista contiene celdas por día');
ok(str_contains($view,'agenda-month-event'),'Vista renderiza actividades dentro de cada fecha');
ok(str_contains($view,'agenda-month-more'),'Mes contempla indicador de actividades adicionales');
ok(str_contains($view,'monthStart'),'Vista calcula inicio del mes');
ok(str_contains($view,'monthGridStart'),'Vista completa semanas desde lunes');
ok(str_contains($view,'monthGridEnd'),'Vista completa semanas hasta domingo');
ok(str_contains($css,'.agenda-month-grid{'),'CSS define cuadrícula mensual');
ok(str_contains($css,'.agenda-month-day{'),'CSS define celdas del mes');
ok(str_contains($css,'.agenda-month-event{'),'CSS define eventos compactos de mes');
ok(str_contains($css,'@media(max-width:1023px)')&&str_contains($css,'.agenda-month-grid'),'Mes contempla responsive para tablet');
ok(str_contains($css,'@media(max-width:760px)')&&str_contains($css,'.agenda-month-grid'),'Mes contempla responsive para móvil');

if($errors){
    echo PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'[OK] Vista mensual de Agenda definida y responsive.'.PHP_EOL;
