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

$filterService=(string)file_get_contents($root.'/app/Services/TicketReportFilterService.php');
$management=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$xlsx=(string)file_get_contents($root.'/app/Controllers/XlsxExportController.php');
$view=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($filterService,'final class TicketReportFilterService'),'Existe servicio canónico de filtros');
ok(str_contains($filterService,'public function filters(array $input): array'),'Servicio normaliza filtros');
ok(str_contains($filterService,'public function where(array $f,string $alias=\'t\'): array'),'Servicio construye WHERE compartido');
ok(str_contains($filterService,'new ScopeService()'),'Servicio aplica ScopeService');
ok(str_contains($filterService,'category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)'),'Categoría padre incluye subcategorías');
ok(str_contains($filterService,'public const STATUS_LABELS'),'Estados tienen catálogo único');
ok(str_contains($filterService,'public const PRIORITY_LABELS'),'Prioridades tienen catálogo único');

ok(str_contains($management,'new TicketReportFilterService()'),'Pantalla usa servicio canónico de filtros');
ok(str_contains($xlsx,'new TicketReportFilterService()'),'XLSX usa servicio canónico de filtros');
ok(!str_contains($management,'private function filters():array'),'ManagementController ya no duplica filters');
ok(!str_contains($management,'private function where(array $f):array'),'ManagementController ya no duplica where');
ok(!str_contains($xlsx,'private function filters():array'),'XlsxExportController ya no duplica filters');
ok(!str_contains($xlsx,'private function where(array $f):array'),'XlsxExportController ya no duplica where');

ok(str_contains($management,'$allRows=$q->fetchAll();'),'Pantalla calcula sobre todo el conjunto filtrado');
ok(str_contains($management,'$reportStats=$this->reportStats($allRows);'),'KPIs usan todo el conjunto');
ok(str_contains($management,'$rows=array_slice($allRows,0,500);'),'Solo el detalle visual se limita a 500');
ok(str_contains($view,'Excel incluye todo el filtro'),'UI explica diferencia entre detalle visual y exportación');

ok(str_contains($view,'TicketReportFilterService::STATUS_LABELS'),'Vista usa etiquetas canónicas de estado');
ok(str_contains($view,'TicketReportFilterService::PRIORITY_LABELS'),'Vista usa etiquetas canónicas de prioridad');
ok(str_contains($xlsx,'TicketReportFilterService::STATUS_LABELS'),'Excel usa etiquetas canónicas de estado');
ok(str_contains($xlsx,'TicketReportFilterService::PRIORITY_LABELS'),'Excel usa etiquetas canónicas de prioridad');

foreach([
    'Primera respuesta promedio (min)',
    'Hasta resolución promedio (min)',
    'Trabajo efectivo promedio (min)',
    'En espera promedio (min)',
    'Documentados (%)',
    'Cambios de estado registrados',
] as $metric){
    ok(str_contains($xlsx,$metric),"XLSX incluye métrica {$metric}");
}

ok(str_contains($xlsx,'$reportFilters->description($pdo,$filters)'),'Excel describe los mismos filtros aplicados');
ok(str_contains($xlsx,"'filters'=>\$filters"),'Auditoría de exportación registra filtros canónicos');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Paridad Tickets/SLA pantalla y XLSX consolidada.'.PHP_EOL;
