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
ok(str_contains($view,"['view'=>'calendar'")||str_contains($view,"'view'=>'calendar'"),'Selector Calendario conserva enlace explícito a view=calendar');
ok(str_contains($view,'agenda-multiday-strip'),'Agenda conserva franja de actividades de varios días');
ok(!str_contains($view,'<?php if(!$calendarActivities): ?>'),'Calendario no oculta la cuadrícula cuando solo existen actividades multiday');
ok(str_contains($view,'class="agenda-calendar-grid"'),'Calendario mantiene la cuadrícula semanal disponible');
ok(str_contains($view,'class="agenda-calendar-days"'),'Calendario mantiene alternativa responsive por días');

if($errors){
    echo PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'[OK] Visibilidad del calendario compatible con actividades multiday.'.PHP_EOL;
