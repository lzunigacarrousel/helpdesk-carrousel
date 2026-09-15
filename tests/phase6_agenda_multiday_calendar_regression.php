<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/agenda/index.php';
$cssPath=$root.'/public/assets/css/agenda.css';
$view=is_file($viewPath)?file_get_contents($viewPath):'';
$css=is_file($cssPath)?file_get_contents($cssPath):'';
$errors=0;

function ok(bool $condition,string $message):void{
    global $errors;
    echo($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok($view!=='','Se puede leer la vista Agenda');
ok($css!=='','Se puede leer el CSS de Agenda');
ok(str_contains($view,'$calendarMultiDayItems'),'Vista calcula segmentos multiday visibles en la semana');
ok(str_contains($view,'start_column')&&str_contains($view,'span_days'),'Segmentos multiday conocen columna inicial y días de alcance');
ok(str_contains($view,'agenda-calendar-multiday-grid'),'Calendario integra carril multiday alineado a los siete días');
ok(str_contains($view,'agenda-calendar-multiday-event'),'Actividad multiday se renderiza dentro del calendario semanal');
ok(str_contains($view,"grid-column:<?= (int)\$entry['start_column'] ?>/span <?= (int)\$entry['span_days'] ?>"),'Evento multiday usa columnas y alcance visibles de la semana');
ok(str_contains($css,'.agenda-calendar-multiday-grid{')&&str_contains($css,'grid-template-columns:72px repeat(7,minmax(0,1fr))'),'CSS alinea carril multiday con calendario');
ok(str_contains($css,'.agenda-calendar-multiday-event{'),'CSS define evento multiday del calendario');
ok(str_contains($css,'.agenda-calendar-multiday-event{grid-column:1!important;grid-row:auto!important}'),'Responsive convierte eventos multiday a tarjetas de una columna');

if($errors){
    echo PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'[OK] Actividades multiday integradas en calendario semanal.'.PHP_EOL;
