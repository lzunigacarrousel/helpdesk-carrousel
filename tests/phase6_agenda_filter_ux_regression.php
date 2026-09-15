<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/agenda/index.php';
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$servicePath=$root.'/app/Services/AgendaService.php';

$view=is_file($viewPath)?file_get_contents($viewPath):'';
$controller=is_file($controllerPath)?file_get_contents($controllerPath):'';
$service=is_file($servicePath)?file_get_contents($servicePath):'';
$errors=0;

function ok(bool $condition,string $message):void{
    global $errors;
    echo($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok($view!=='','Se puede leer la vista Agenda');
ok($controller!=='','Se puede leer AgendaController');
ok($service!=='','Se puede leer AgendaService');

ok(str_contains($view,'>Mes</a>')&&str_contains($view,'>Semana</a>')&&str_contains($view,'>Lista</a>'),'Agenda conserva solo Mes, Semana y Lista como vistas principales');
ok(!str_contains($view,'>Esta semana</a>'),'Agenda elimina atajo duplicado Esta semana');
ok(!str_contains($view,'>Próximos 30 días</a>'),'Agenda elimina atajo duplicado Próximos 30 días');
ok(!str_contains($view,'>Historial</a>'),'Agenda elimina botón Historial duplicado');
ok(!str_contains($view,'Incluir historial'),'Agenda elimina checkbox Incluir historial');
ok(!str_contains($view,'name="history"'),'Formulario ya no envía parámetro history');

ok(str_contains($view,"if(\$activeView==='month')")&&str_contains($view,'>Mes anterior</a>')&&str_contains($view,'>Mes siguiente</a>'),'Mes conserva navegación temporal propia');
ok(str_contains($view,"elseif(\$activeView==='calendar')")&&str_contains($view,'>Semana anterior</a>')&&str_contains($view,'>Semana siguiente</a>'),'Semana conserva navegación temporal propia');
ok(str_contains($view,"if(\$activeView==='list')")&&str_contains($view,'name="from"')&&str_contains($view,'name="to"'),'Desde y Hasta quedan como filtros de Lista');

ok(str_contains($view,'value="active"'),'Estado ofrece active');
ok(str_contains($view,"['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA']"),'Estado ofrece Programada, En curso, Finalizada y Cancelada');
ok(str_contains($view,'value="<?= $code ?>"'),'Estados explícitos se renderizan dinámicamente');
ok(str_contains($view,'value="all"'),'Estado ofrece all');

ok(!str_contains($controller,"\$_GET['history']"),'Controller deja de depender del checkbox history');
ok(!str_contains($controller,"'history'=>"),'Controller ya no expone history como filtro de Agenda');
ok(str_contains($service,"if(\$status==='active')return self::ACTIVE_STATUSES;"),'Activas significa solo Programada + En curso');
ok(str_contains($service,"if(\$status==='all')return self::ALL_STATUSES;"),'Todas incluye todos los estados sin checkbox adicional');
ok(str_contains($service,"return[\$status];"),'Estado explícito filtra exactamente ese estado');

if($errors){
    fwrite(STDERR,PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL);
    exit(1);
}

echo PHP_EOL.'[OK] Filtros de Agenda simplificados y coherentes.'.PHP_EOL;
