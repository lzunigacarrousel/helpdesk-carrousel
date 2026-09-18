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

$general=(string)file_get_contents($root.'/app/Controllers/XlsxExportController.php');
$providers=(string)file_get_contents($root.'/app/Controllers/ExternalReportController.php');
$providerView=(string)file_get_contents($root.'/app/Views/management/external_report.php');
$team=(string)file_get_contents($root.'/app/Controllers/SupportTeamController.php');
$teamView=(string)file_get_contents($root.'/app/Views/management/support_team.php');
$reportsView=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($general,'new TicketReportFilterService()'),'Informe general usa filtros canónicos');
ok(str_contains($general,"'scope'=>(new ScopeService())->scopeLabel()"),'Informe general audita alcance');
ok(str_contains($general,"'Filtros aplicados',\$filterText"),'Informe general documenta filtros en XLSX');
ok(str_contains($general,"'name'=>'Resumen'"),'Informe general conserva hoja Resumen');
ok(str_contains($general,"'name'=>'Tickets'"),'Informe general conserva hoja Tickets');

ok(str_contains($providers,'private function filterDescription(array $filters,array $providers): string'),'Proveedores describe filtros aplicados');
ok(str_contains($providers,"'scope'=>(new ScopeService())->scopeLabel()"),'Proveedores audita alcance');
ok(substr_count($providers,"'subtitle'=>\$filterText")>=2,'Proveedores documenta filtros en resumen y detalle XLSX');
ok(str_contains($providers,"['Proveedores',(int)\$summary['providers']]"),'XLSX proveedores incluye cantidad de proveedores');
ok(str_contains($providers,"['Entregas listas',(int)\$summary['deliveries']]"),'XLSX proveedores incluye entregas');
ok(str_contains($providers,"['Calidad promedio',"),'XLSX proveedores incluye calidad promedio');
ok(str_contains($providerView,'<span>Entregas</span>'),'Pantalla proveedores muestra entregas');
ok(str_contains($providerView,'<span>Calidad promedio</span>'),'Pantalla proveedores muestra calidad promedio');

ok(str_contains($team,'new TicketReportFilterService()'),'Equipo normaliza período con filtros canónicos');
ok(substr_count($team,'->reportSummary($periodFilters)')>=2,'Equipo usa mismo resumen de período en pantalla y XLSX');
ok(str_contains($team,"'filters'=>\$periodFilters"),'Exportación de equipo audita período');
ok(str_contains($team,"['Período',\$periodFilters['from'].' a '.\$periodFilters['to']]"),'XLSX equipo explicita período');
ok(str_contains($team,"['Tickets del período',(int)\$periodSummary['tickets_period']]"),'XLSX equipo incluye tickets del período');
ok(str_contains($team,"['Primera respuesta promedio (min)',"),'XLSX equipo incluye primera respuesta del período');
ok(str_contains($teamView,'Desempeño del período'),'Pantalla equipo muestra bloque de período');
ok(str_contains($teamView,'name="from"'),'Pantalla equipo permite fecha inicial');
ok(str_contains($teamView,'name="to"'),'Pantalla equipo permite fecha final');
ok(str_contains($teamView,'/gestion/equipo/exportar?<?= htmlspecialchars($periodQuery) ?>'),'Excel equipo conserva período visible');
ok(str_contains($reportsView,'/gestion/equipo?from=<?= urlencode($filters[\'from\']) ?>&to=<?= urlencode($filters[\'to\']) ?>'),'Centro conserva período al abrir Equipo');

foreach([$general,$providers,$team] as $controller){
    ok(str_contains($controller,'XlsxExportService::download('),'Informe especializado usa exportador XLSX canónico');
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Exportaciones especializadas Fase 10 consistentes.'.PHP_EOL;
