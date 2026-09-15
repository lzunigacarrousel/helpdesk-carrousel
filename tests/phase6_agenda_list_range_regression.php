<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/agenda/index.php';
$view=is_file($viewPath)?file_get_contents($viewPath):'';
$errors=0;

function ok(bool $condition,string $message):void{
    global $errors;
    echo($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok($view!=='','Se puede leer la vista Agenda');
ok(str_contains($view,'$multiDayRange'),'Agenda conserva formato con fecha y hora para rangos multiday');
ok(str_contains($view,'$listRange'),'Lista define formateador de rango según duración');
ok(str_contains($view,'use($h,$listRange,$typeLabels,$statusLabels)'),'Render de Lista usa el formateador de rango contextual');
ok(str_contains($view,'$h($listRange($item))'),'Lista imprime fecha completa cuando la actividad cruza días');
ok(str_contains($view,'$h($timeRange($item))'),'Calendario horario conserva formato HH:mm–HH:mm para actividades del mismo día');

if($errors){
    echo PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'[OK] Lista de Agenda distingue rangos horarios y multiday.'.PHP_EOL;
