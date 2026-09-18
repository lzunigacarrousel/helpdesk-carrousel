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

$participation=(string)file_get_contents($root.'/app/Services/ProviderParticipationService.php');
$externalController=(string)file_get_contents($root.'/app/Controllers/ExternalReportController.php');
$management=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$view=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($participation,'public function scopedRows(array $rows): array'),'ProviderParticipationService expone scopedRows');
ok(str_contains($participation,"ticketConstraint('t')"),'Proveedores reutilizan ScopeService');
ok(str_contains($participation,'SELECT t.id FROM tickets t'),'Scope se aplica contra tickets');
ok(str_contains($participation,"'deliveries'=>0"),'Resumen cuenta entregas');
ok(str_contains($participation,"'rated_cycles'=>0"),'Resumen cuenta ciclos evaluados');
ok(str_contains($participation,"'unrated_cycles'=>0"),'Resumen cuenta ciclos sin evaluar');
ok(str_contains($participation,"'average_quality'=>null"),'Resumen expone calidad promedio');
ok(str_contains($participation,'$qualityScores[]=(int)$score;'),'Calidad usa valoraciones vigentes');

ok(substr_count($externalController,'->scopedRows(')>=2,'Informe especializado aplica scope en pantalla y exportación');
ok(str_contains($management,'ProviderParticipationService'),'Centro de informes reutiliza participación existente');
ok(str_contains($management,'ProviderRatingService'),'Centro de informes reutiliza calidad existente');
ok(str_contains($management,'$providerService->scopedRows('),'Centro de informes aplica alcance a proveedores');
ok(str_contains($management,"['from'=>\$filters['from'],'to'=>\$filters['to']]"),'Resumen usa el mismo período del Centro de informes');
ok(str_contains($management,'ProviderParticipationService::summary($providerRows)'),'Centro reutiliza resumen canónico');
ok(str_contains($management,"'providerReport'=>\$providerReport"),'Controller entrega resumen de proveedores');

ok(str_contains($view,'id="informe-proveedores"'),'Vista incorpora sección de proveedores');
ok(str_contains($view,'Participación y calidad'),'Vista usa copy ejecutivo');
ok(str_contains($view,'Sin respuesta'),'Vista muestra ciclos sin respuesta');
ok(str_contains($view,'Calidad promedio'),'Vista muestra calidad');
ok(str_contains($view,'Devoluciones'),'Vista muestra devoluciones');
ok(str_contains($view,'entrega(s) listas'),'Vista muestra entregas');
ok(str_contains($view,'primera respuesta promedio'),'Vista muestra tiempo de primera respuesta');
ok(str_contains($view,'Abrir informe de proveedores'),'Vista conserva acceso al informe especializado');
ok(str_contains($view,'from=<?= urlencode($filters[\'from\']) ?>'),'Acceso al informe conserva fecha inicial');
ok(str_contains($view,'to=<?= urlencode($filters[\'to\']) ?>'),'Acceso al informe conserva fecha final');
ok(str_contains($view,'$canProviders && is_array($p)'),'Bloque no aparece sin capacidad/datos');
ok(!str_contains($view,'<table class="provider'),'Centro no duplica tabla especializada de proveedores');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Resumen de Proveedores Fase 10 consolidado.'.PHP_EOL;
