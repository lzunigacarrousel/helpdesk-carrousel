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

$service=(string)file_get_contents($root.'/app/Services/SupportTeamReportService.php');
$supportController=(string)file_get_contents($root.'/app/Controllers/SupportTeamController.php');
$management=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$view=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($service,'final class SupportTeamReportService'),'Existe servicio compartido del equipo');
ok(str_contains($service,'public function activeTeamId(): int'),'Servicio resuelve equipo activo');
ok(str_contains($service,'public function members(int $teamId): array'),'Servicio centraliza integrantes');
ok(str_contains($service,'public function summary(array $members): array'),'Servicio conserva resumen especializado');
ok(str_contains($service,'public function reportSummary(array $filters): array'),'Servicio expone resumen para Centro de informes');
ok(str_contains($service,'new TicketReportFilterService()'),'Resumen usa filtros canónicos de Fase 10');
ok(str_contains($service,"->where(\$filters,'t')"),'Resumen reutiliza alcance y filtros de tickets');
ok(str_contains($service,'t.assigned_to IN ({$memberPlaceholders})'),'Resumen limita casos a integrantes del equipo');
ok(str_contains($service,"'tickets_period'=>0"),'Resumen cuenta tickets del período');
ok(str_contains($service,"'resolved_period'=>0"),'Resumen cuenta resueltos');
ok(str_contains($service,"'avg_first_response_min'=>null"),'Resumen mide primera respuesta');
ok(str_contains($service,"'avg_resolution_hours'=>null"),'Resumen mide resolución');
ok(str_contains($service,"'nps_value'=>null"),'Resumen incluye NPS');
ok(str_contains($service,'JOIN ticket_feedback tf'),'Satisfacción usa feedback real');

ok(str_contains($supportController,'new SupportTeamReportService($pdo)'),'Pantalla especializada reutiliza servicio');
ok(!str_contains($supportController,'private function members('),'SupportTeamController ya no duplica consulta de integrantes');
ok(!str_contains($supportController,'private function summary('),'SupportTeamController ya no duplica resumen');

ok(str_contains($management,'SupportTeamReportService'),'Centro de informes reutiliza servicio del equipo');
ok(str_contains($management,"Auth::can('management.view')"),'Acceso al resumen respeta capacidad de gestión');
ok(str_contains($management,'->reportSummary($filters)'),'Centro usa filtros actuales');
ok(str_contains($management,"'teamReport'=>\$teamReport"),'Controller entrega resumen a la vista');

ok(str_contains($view,'id="informe-equipo"'),'Vista incorpora sección del equipo');
ok(str_contains($view,'Carga y desempeño'),'Vista usa copy ejecutivo');
ok(str_contains($view,'Tickets del período'),'Vista muestra volumen del período');
ok(str_contains($view,'Primera respuesta'),'Vista muestra primera respuesta');
ok(str_contains($view,'NPS'),'Vista muestra satisfacción');
ok(str_contains($view,'resolución promedio'),'Vista muestra tiempo de resolución');
ok(str_contains($view,'Abrir equipo de soporte'),'Vista conserva acceso a pantalla especializada');
ok(str_contains($view,'$canTeam && is_array($t)'),'Bloque no aparece sin capacidad/datos');
ok(str_contains($view,'report-team-grid'),'Vista usa grid compacto');
ok(!str_contains($view,'support-member-name'),'Centro no duplica tabla especializada del equipo');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Resumen de Equipo de soporte Fase 10 consolidado.'.PHP_EOL;
