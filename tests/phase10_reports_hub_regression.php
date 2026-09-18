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

$view=(string)file_get_contents($root.'/app/Views/management/reports.php');
$readme=(string)file_get_contents($root.'/README.md');
$router=(string)file_get_contents($root.'/public/index.php');

ok(str_contains($readme,'| 10 | Reportes | **EN CURSO — consolidación final** |'),'Roadmap marca Fase 10 Reportes en curso');
ok(str_contains($view,'report-catalog-grid'),'Informes expone hub compacto');
ok(str_contains($view,'Tickets y SLA'),'Hub conserva informe principal');
ok(str_contains($view,"APP_BASE_URL ?>/agenda"),'Hub enlaza Agenda y actividades');
ok(str_contains($view,"APP_BASE_URL ?>/admin/externos/informe"),'Hub enlaza informe de proveedores');
ok(str_contains($view,"APP_BASE_URL ?>/gestion/equipo"),'Hub enlaza carga y desempeño del equipo');
ok(str_contains($view,'$canAgenda'),'Agenda se condiciona por perfil');
ok(str_contains($view,'$canProviders'),'Proveedores se condiciona por capacidad');
ok(str_contains($view,'$canTeam'),'Equipo se condiciona por capacidad');
ok(str_contains($view,'@media(max-width:1180px){.report-catalog-grid'),'Hub tiene adaptación tablet');
ok(str_contains($view,'@media(max-width:700px){.report-catalog-grid'),'Hub tiene adaptación móvil');
ok(str_contains($view,'/gestion/informes/exportar'),'Informe general conserva exportación XLSX');
ok(str_contains($router,"'/gestion/informes'"),'Router conserva informe general');
ok(str_contains($router,"'/gestion/informes/exportar'"),'Router conserva exportación general');
ok(str_contains($router,"'/admin/externos/informe'"),'Router conserva informe de proveedores');
ok(str_contains($router,"'/gestion/equipo'"),'Router conserva informe del equipo');

foreach(['/tickets/claim','/tickets/assign','/tickets/status','/tickets/release'] as $operationalRoute){
    ok(!str_contains($view,$operationalRoute),"Centro de informes no expone acción operativa {$operationalRoute}");
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Hub inicial de Reportes Fase 10 consolidado.'.PHP_EOL;
