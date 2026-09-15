<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$jsPath=$root.'/public/assets/js/agenda.js';
$js=is_file($jsPath)?file_get_contents($jsPath):'';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok($js!=='','Se puede leer agenda.js');
ok(str_contains($js,'.agenda-quick-links'),'Agenda usa la navegación temporal existente');
ok(str_contains($js,"input.type = 'date'"),'Agenda incorpora selector directo de fecha');
ok(str_contains($js,"textContent = 'Ir a fecha'"),'Agenda muestra una única acción Ir a fecha');
ok(str_contains($js,"shell.classList.contains('agenda-view-month')"),'Selector reconoce vista Mes');
ok(str_contains($js,"shell.classList.contains('agenda-view-calendar')"),'Selector reconoce vista Semana');
ok(str_contains($js,"new Date(year, monthIndex, 1)"),'Mes salta al primer día del mes seleccionado');
ok(str_contains($js,"new Date(year, monthIndex + 1, 0)"),'Mes calcula el último día del mes seleccionado');
ok(str_contains($js,"const mondayOffset = (selected.getDay() + 6) % 7"),'Semana calcula lunes de la fecha seleccionada');
ok(str_contains($js,"weekEnd.setDate(weekStart.getDate() + 6)"),'Semana calcula domingo de la fecha seleccionada');
ok(str_contains($js,"params.set('from', toIso(from))")&&str_contains($js,"params.set('to', toIso(to))"),'Navegación actualiza rango desde/hasta');
ok(str_contains($js,"params.delete('program')")&&str_contains($js,"params.delete('ticket_q')"),'Salto de fecha no conserva búsqueda de programación temporal');
ok(str_contains($js,"if (!isMonth && !isWeek) return"),'Lista conserva únicamente su rango Desde/Hasta');

if($errors){
    fwrite(STDERR,PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL);
    exit(1);
}

echo PHP_EOL.'[OK] Navegación directa por fecha definida para Mes y Semana.'.PHP_EOL;
