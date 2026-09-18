<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$service=(string)file_get_contents($root.'/app/Services/AgendaService.php');
$controller=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$view=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($service,'public function reportSummary(array $filters): array'),'AgendaService expone reportSummary');
ok(str_contains($service,"'status'=>'all'"),'Resumen incluye todos los estados');
ok(str_contains($service,"'scope_mode'=>'all'"),'Resumen no fuerza vista personal del técnico');
ok(str_contains($service,"'park_id'=>(int)"),'Resumen hereda filtro de parque');
ok(str_contains($service,'$rows=$this->activities($reportFilters);'),'Resumen reutiliza activities y su ScopeService');
ok(str_contains($service,"'scheduled'=>0"),'Resumen cuenta programadas');
ok(str_contains($service,"'in_progress'=>0"),'Resumen cuenta en curso');
ok(str_contains($service,"'completed'=>0"),'Resumen cuenta finalizadas');
ok(str_contains($service,"'cancelled'=>0"),'Resumen cuenta canceladas');
ok(str_contains($service,"'overdue'=>0"),'Resumen cuenta atrasadas');
ok(str_contains($service,"'conflicts'=>0"),'Resumen cuenta conflictos');
ok(str_contains($service,"if(!empty(\$row['is_overdue']))"),'Atrasos reutilizan normalización de Agenda');
ok(str_contains($service,"if(!empty(\$row['has_conflict']))"),'Conflictos reutilizan detección de Agenda');

ok(str_contains($controller,'AgendaService'),'ManagementController reutiliza AgendaService');
ok(str_contains($controller,"Auth::can('activities.view')"),'Resumen de actividades exige permiso de consulta');
ok(str_contains($controller,'(new AgendaService())->reportSummary($filters)'),'Controller usa filtros del Centro de informes');
ok(str_contains($controller,"'canActivities'=>\$canActivities"),'Controller entrega capacidad de actividades');
ok(str_contains($controller,"'activityReport'=>\$activityReport"),'Controller entrega resumen de actividades');

ok(str_contains($view,'id="informe-actividades"'),'Vista incorpora sección de actividades');
ok(str_contains($view,'Programadas'),'Vista muestra programadas');
ok(str_contains($view,'En curso'),'Vista muestra en curso');
ok(str_contains($view,'Finalizadas'),'Vista muestra finalizadas');
ok(str_contains($view,'Canceladas'),'Vista muestra canceladas');
ok(str_contains($view,'Atrasadas'),'Vista muestra atrasadas');
ok(str_contains($view,'conflicto de horario'),'Vista muestra conflictos de horario');
ok(str_contains($view,'Abrir agenda'),'Vista conserva navegación a Agenda completa');
ok(str_contains($view,'$canAgenda && is_array($a)'),'Vista no expone resumen sin permiso/datos');
ok(str_contains($view,'report-activity-grid'),'Vista usa grid compacto');
ok(str_contains($view,'@media(max-width:1180px)'),'Actividades conserva adaptación tablet');
ok(str_contains($view,'@media(max-width:700px)'),'Actividades conserva adaptación móvil');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Resumen de Actividades Fase 10 consolidado.'.PHP_EOL;
