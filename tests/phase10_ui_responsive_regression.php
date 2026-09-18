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

$reports=(string)file_get_contents($root.'/app/Views/management/reports.php');
$team=(string)file_get_contents($root.'/app/Views/management/support_team.php');
$providers=(string)file_get_contents($root.'/app/Views/management/external_report.php');
$visual=(string)file_get_contents($root.'/public/assets/css/visual-system.css');

ok(str_contains($reports,'@media(max-width:1180px)'),'Centro de informes conserva breakpoint tablet');
ok(str_contains($reports,'@media(max-width:700px)'),'Centro de informes conserva breakpoint móvil');
ok(str_contains($reports,'@media(max-width:430px)'),'Centro de informes tiene cierre para móvil pequeño');
ok(str_contains($reports,'.report-catalog-grid{grid-template-columns:1fr}'),'Catálogo usa una columna en móvil');
ok(str_contains($reports,'.report-knowledge-grid,.report-activity-grid,.report-provider-grid,.report-team-grid,.report-summary-grid{grid-template-columns:1fr}'),'Métricas pasan a una columna en móvil pequeño');
ok(str_contains($reports,'scroll-margin-top:92px'),'Anclajes dejan espacio para topbar');
ok(str_contains($reports,'.report-module-actions .btn'),'Acciones principales se adaptan al ancho móvil');
ok(str_contains($reports,'.report-filterbar .mgmt-filter-actions .btn'),'Acciones de filtros se adaptan al ancho móvil');
ok(str_contains($reports,'.report-knowledge-actions .btn'),'Acciones de panel se adaptan al ancho móvil');

ok(str_contains($team,'@media(max-width:1100px)'),'Equipo conserva breakpoint tablet');
ok(str_contains($team,'@media(max-width:700px)'),'Equipo conserva breakpoint móvil');
ok(str_contains($team,'@media(max-width:430px)'),'Equipo tiene cierre para móvil pequeño');
ok(str_contains($team,'.support-period-filter,.support-period-grid,.support-team-summary{grid-template-columns:1fr}'),'Equipo usa una columna en móvil pequeño');
ok(str_contains($team,'support-period-grid'),'Equipo mantiene resumen de período');

ok(str_contains($providers,'@media(max-width:1280px)'),'Proveedores adapta escritorio compacto');
ok(str_contains($providers,'@media(max-width:900px)'),'Proveedores adapta tablet');
ok(str_contains($providers,'@media(max-width:700px)'),'Proveedores adapta móvil');
ok(str_contains($providers,'@media(max-width:430px)'),'Proveedores adapta móvil pequeño');
ok(str_contains($providers,'.external-report-summary{grid-template-columns:1fr}'),'Resumen de proveedores usa una columna en móvil pequeño');
ok(str_contains($providers,'.external-report-filter-actions{display:grid;grid-template-columns:1fr}'),'Botones de filtros de proveedores se apilan');

ok(str_contains($visual,'.btn:not(.btn-sm):not(.theme-btn)'),'Reportes hereda contrato global de botones');
ok(!str_contains($reports,'min-width:1200px'),'Centro de informes no fuerza ancho de escritorio');
ok(!str_contains($reports,'overflow-x:auto'),'Centro de informes no depende de scroll horizontal');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] UI y responsive de Reportes Fase 10 consolidados.'.PHP_EOL;
