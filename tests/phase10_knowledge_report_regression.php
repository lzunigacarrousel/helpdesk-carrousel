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

$service=(string)file_get_contents($root.'/app/Services/KnowledgeMetricsService.php');
$controller=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$view=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($service,'public function reportSummary(string $from,string $to): array'),'KnowledgeMetricsService expone reportSummary');
ok(str_contains($service,"lifecycle_status='ACTIVE'"),'Resumen usa lifecycle activo');
ok(str_contains($service,'current_internal_revision_id IS NOT NULL'),'Resumen cuenta publicación para soporte');
ok(str_contains($service,'current_public_revision_id IS NOT NULL'),'Resumen cuenta disponibilidad pública');
ok(str_contains($service,"state='DRAFT'"),'Resumen cuenta borradores');
ok(str_contains($service,"state='IN_REVIEW'"),'Resumen cuenta revisiones');
ok(str_contains($service,"sse.reference_type='KNOWLEDGE'"),'Métricas de uso se limitan a conocimiento');
ok(str_contains($service,"sse.event_type='SUGGESTED'"),'Resumen cuenta sugerencias');
ok(str_contains($service,"sse.event_type='OPENED'"),'Resumen cuenta aperturas');
ok(str_contains($service,"sse.event_type='USED_REFERENCE'"),'Resumen cuenta referencias usadas');
ok(str_contains($service,"ticketConstraint('t')"),'Uso operativo respeta ScopeService');
ok(str_contains($service,"sse.ticket_id IS NOT NULL"),'Scope restringido no mezcla autoservicio global');
ok(!str_contains($service,"ka.status='PUBLISHED'"),'Resumen no depende de status legacy para publicación');
ok(!str_contains($service,"ka.visibility='PUBLIC'"),'Resumen no depende de visibility legacy');

ok(str_contains($controller,'KnowledgeMetricsService'),'ManagementController reutiliza servicio de métricas');
ok(str_contains($controller,"Auth::can('knowledge.view')"),'Bloque de conocimiento exige capacidad');
ok(str_contains($controller,'reportSummary($filters[\'from\'],$filters[\'to\'])'),'Resumen usa período del informe');
ok(str_contains($controller,"'knowledgeReport'=>\$knowledgeReport"),'Controller entrega resumen a la vista');

ok(str_contains($view,'id="informe-conocimiento"'),'Vista incorpora sección de conocimiento');
ok(str_contains($view,'Artículos activos'),'Vista muestra artículos activos');
ok(str_contains($view,'Para soporte'),'Vista muestra publicación interna');
ok(str_contains($view,'Para solicitantes'),'Vista muestra disponibilidad pública');
ok(str_contains($view,'Trabajo editorial'),'Vista resume borradores y revisión');
ok(str_contains($view,'usos como referencia'),'Vista muestra referencias usadas');
ok(str_contains($view,'Abrir conocimiento'),'Vista ofrece navegación secundaria');
ok(str_contains($view,'$canKnowledge && is_array($k)'),'Vista no expone bloque sin permiso/datos');
ok(str_contains($view,'@media(max-width:1180px)'),'Resumen conserva adaptación tablet');
ok(str_contains($view,'@media(max-width:700px)'),'Resumen conserva adaptación móvil');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Resumen de Conocimiento Fase 10 consolidado.'.PHP_EOL;
