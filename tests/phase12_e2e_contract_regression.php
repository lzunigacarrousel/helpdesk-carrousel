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

$router=(string)file_get_contents($root.'/public/index.php');
$ticket=(string)file_get_contents($root.'/app/Controllers/TicketController.php');
$workflow=(string)file_get_contents($root.'/app/Controllers/WorkflowController.php');
$conversation=(string)file_get_contents($root.'/app/Controllers/ConversationController.php');
$resolution=(string)file_get_contents($root.'/app/Controllers/ResolutionController.php');
$feedback=(string)file_get_contents($root.'/app/Controllers/TicketFeedbackController.php');
$activity=(string)file_get_contents($root.'/app/Services/TicketActivityService.php');
$reference=(string)file_get_contents($root.'/app/Services/KnowledgeReferenceService.php');
$management=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$xlsx=(string)file_get_contents($root.'/app/Controllers/XlsxExportController.php');

foreach([
    "['POST','/crear-ticket',[TicketController::class,'publicStore']]",
    "['POST','/tickets/claim',[TicketController::class,'claim']]",
    "['POST','/tickets/assign',[TicketController::class,'assign']]",
    "['POST','/tickets/status',[WorkflowController::class,'changeStatus']]",
    "['POST','/tickets/respond',[ConversationController::class,'respond']]",
    "['POST','/tickets/activities/create',[TicketActivityController::class,'create']]",
    "['POST','/tickets/activities/start',[TicketActivityController::class,'start']]",
    "['POST','/tickets/activities/complete',[TicketActivityController::class,'complete']]",
    "['POST','/tickets/reference/use',[ResolutionController::class,'useReference']]",
    "['POST','/tickets/resolve',[ResolutionController::class,'store']]",
    "['POST','/tickets/feedback',[TicketFeedbackController::class,'submit']]",
    "['POST','/tickets/feedback/reopen',[TicketFeedbackController::class,'reopen']]",
    "['GET','/gestion/informes/exportar',[XlsxExportController::class,'export']]",
] as $route){
    ok(str_contains($router,$route),'Ruta E2E disponible: '.$route);
}

ok(str_contains($ticket,"INSERT INTO tickets"),'Creación pública persiste ticket');
ok(str_contains($ticket,"'CREATED'"),'Creación registra evento CREATED');
ok(str_contains($ticket,"status='IN_PROGRESS'"),'Claim mueve el caso a En proceso');
ok(str_contains($ticket,"'CLAIMED'"),'Claim registra trazabilidad');
ok(str_contains($ticket,"'REASSIGNED'"),'Reasignación registra trazabilidad');

ok(str_contains($conversation,"$" . "visibility='PUBLIC'"),'Conversación parte de canal público');
ok(str_contains($conversation,"$" . "visibility='INTERNAL'"),'Conversación soporta canal interno');
ok(str_contains($conversation,"$" . "visibility='EXTERNAL'"),'Conversación soporta canal proveedor');
ok(str_contains($conversation,"'COMMENTED'"),'Conversación registra evento');
ok(str_contains($conversation,"['email'=>false,'in_app'=>true]"),'Nota interna no genera correo');

ok(str_contains($workflow,"'WAITING_PROVIDER'=>'Esperando proveedor'"),'Workflow soporta espera por proveedor');
ok(str_contains($workflow,"if($" . "status!=='PENDING'){ $" . "pendingReason='';$" . "pendingNote='';}"),'Continuar atención limpia motivo de espera');
ok(str_contains($workflow,"'PENDING_REASON_CHANGED'"),'Cambio de motivo de espera queda trazado');

foreach(['create','start','complete','cancel'] as $method){
    ok(str_contains($activity,'function '.$method.'('),'Actividad expone '.$method);
}
ok(str_contains($activity,"'ACTIVITY_CREATED'"),'Actividad registra creación');
ok(str_contains($activity,"'ACTIVITY_STARTED'"),'Actividad registra inicio');
ok(str_contains($activity,"'ACTIVITY_COMPLETED'"),'Actividad registra finalización');
ok(!str_contains($activity,'UPDATE tickets SET status'),'Actividad no cambia ticket automáticamente');

ok(str_contains($reference,"function useReference("),'Servicio de referencia reutilizable disponible');
ok(str_contains($reference,"'KNOWLEDGE','PROBLEM','TICKET'"),'Referencia admite conocimiento, problema y ticket');
ok(str_contains($reference,'ticket_resolution_references'),'Uso de referencia deja relación estructurada');
ok(str_contains($reference,'recordUsedReference'),'Uso de referencia alimenta métricas');

ok(str_contains($resolution,"Auth::requirePermission('tickets.resolve')"),'Resolución exige permiso');
ok(str_contains($resolution,'ticket_resolutions'),'Resolución documenta causa/solución');
ok(str_contains($resolution,"UPDATE tickets SET status='RESOLVED'"),'Resolver mueve a RESOLVED');
ok(str_contains($resolution,"'RESOLUTION_RECORDED'"),'Resolución registra evento');
ok(str_contains($resolution,'awaiting_requester_confirmation'),'Resolución espera confirmación del solicitante');

ok(str_contains($feedback,"UPDATE tickets SET status='CLOSED'"),'Confirmación del solicitante cierra');
ok(str_contains($feedback,"'REQUESTER_CONFIRMATION'"),'Cierre registra origen solicitante');
ok(str_contains($feedback,"UPDATE tickets SET status='REOPENED'"),'Solicitante puede reabrir');
ok(str_contains($feedback,"'REQUESTER_RETURN'"),'Reapertura registra origen solicitante');
ok(str_contains($feedback,"'TICKET_REOPENED_BY_REQUESTER'"),'Reapertura queda auditada');

ok(str_contains($management,'TicketLifecycleService'),'Informes consumen ciclo de vida');
ok(str_contains($management,'KnowledgeMetricsService'),'Informes integran conocimiento');
ok(str_contains($management,'AgendaService'),'Informes integran actividades');
ok(str_contains($management,'ProviderParticipationService'),'Informes integran proveedores');
ok(str_contains($xlsx,'TicketLifecycleService'),'XLSX conserva ciclo completo');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Contrato E2E transversal Fase 12 preparado.'.PHP_EOL;
