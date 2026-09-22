<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$service=body($root.'/app/Services/TicketActivityService.php');
$scope=body($root.'/app/Services/ScopeService.php');
$attachmentService=body($root.'/app/Services/TicketAttachmentService.php');
$conversation=body($root.'/app/Controllers/ConversationController.php');
$activityController=body($root.'/app/Controllers/TicketActivityController.php');

ok($service!=='','Existe TicketActivityService');
ok(str_contains($scope,'userCanAccessTicket'),'ScopeService permite validar acceso de un usuario objetivo a un ticket');

foreach(['VISITA_EN_SITIO','SOPORTE_REMOTO','SEGUIMIENTO','INTERVENCION_PROVEEDOR','OTRA'] as $type){
    ok(str_contains($service,$type),'Servicio contempla tipo '.$type);
}
foreach(['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $status){
    ok(str_contains($service,$status),'Servicio contempla estado '.$status);
}
foreach(['RESUELTA','PARCIAL','SIN_RESOLVER','REQUIERE_SEGUIMIENTO'] as $result){
    ok(str_contains($service,$result),'Servicio contempla resultado '.$result);
}

foreach([
    'types'=>'Expone catálogo de tipos',
    'results'=>'Expone catálogo de resultados',
    'listForTicket'=>'Lista actividades por ticket',
    'requesterVisibleForTicket'=>'Lista resumen seguro para solicitante',
    'responsibleOptionsForTicket'=>'Resuelve responsables válidos',
    'providerOptionsForTicket'=>'Resuelve proveedores válidos',
    'create'=>'Crea actividades',
    'reschedule'=>'Reprograma actividades',
    'start'=>'Inicia actividades',
    'complete'=>'Finaliza actividades',
    'cancel'=>'Cancela actividades',
    'addParticipant'=>'Agrega participantes',
    'removeParticipant'=>'Retira participantes',
] as $method=>$label){
    ok(str_contains($service,'function '.$method.'('),$label);
}

ok(str_contains($service,'scheduled_start_at')&&str_contains($service,'scheduled_end_at'),'Servicio valida fechas programadas');
ok(str_contains($service,'requester_visible')&&str_contains($service,'requester_summary'),'Visibilidad pública exige resumen seguro');
ok(str_contains($service,'provider_user_id'),'Intervención de proveedor valida proveedor');
ok(str_contains($service,'park_id'),'Visita en sitio valida parque');
ok(str_contains($service,'is_remote'),'Soporte remoto valida modalidad remota');
ok(str_contains($service,'result_code')&&str_contains($service,'work_performed')&&str_contains($service,'result_summary'),'Finalización exige documentación mínima');
ok(str_contains($service,'cancel_reason'),'Cancelación exige motivo');
ok(str_contains($service,'started_at')&&str_contains($service,'finished_at'),'Servicio conserva tiempos reales');

foreach(['ACTIVITY_CREATED','ACTIVITY_RESCHEDULED','ACTIVITY_STARTED','ACTIVITY_COMPLETED','ACTIVITY_CANCELLED'] as $event){
    ok(str_contains($service,$event),'Servicio registra evento '.$event);
}

ok(str_contains($service,'ticket_events'),'Servicio reutiliza ticket_events para trazabilidad');
ok(!str_contains($service,'UPDATE tickets SET status'),'Actividad no cambia automáticamente el estado del ticket');
ok(str_contains($service,'foreach((array)($input[\'participant_user_ids\']??[])'),'Servicio acepta múltiples participantes al crear actividad');
ok(str_contains($service,'$participantId===$responsibleUserId'),'Servicio excluye al responsable principal de participantes adicionales');
ok(str_contains($service,'INSERT IGNORE INTO ticket_activity_participants'),'Servicio persiste múltiples participantes sin duplicados');

// Task 5: almacenamiento de adjuntos compartido entre conversación y actividades.
ok($attachmentService!=='','Existe TicketAttachmentService');
ok(str_contains($attachmentService,'MAX_FILE_SIZE')&&str_contains($attachmentService,'10485760'),'Adjuntos conservan límite de 10 MB');
foreach([
    'application/pdf','image/jpeg','image/png','image/webp','text/plain','text/csv',
    'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
] as $mime){
    ok(str_contains($attachmentService,$mime),'Servicio compartido permite MIME '.$mime);
}
ok(str_contains($attachmentService,'function storeUploadedFile('),'Servicio compartido expone storeUploadedFile');
ok(str_contains($attachmentService,'activity_id'),'Servicio compartido persiste activity_id');
ok(str_contains($attachmentService,'ticket_activities')&&str_contains($attachmentService,'ticket_id'),'Servicio valida que la evidencia pertenezca al mismo ticket');
ok(str_contains($attachmentService,'move_uploaded_file'),'Servicio compartido centraliza almacenamiento físico');
ok(!str_contains($conversation,'move_uploaded_file'),'ConversationController ya no mueve archivos directamente');
ok(!str_contains($conversation,'function storeUpload('),'ConversationController elimina almacenamiento duplicado');
ok(str_contains($conversation,'TicketAttachmentService'),'ConversationController usa TicketAttachmentService');
ok(str_contains($activityController,'TicketAttachmentService'),'Finalización de actividad puede guardar evidencia');
ok(str_contains($activityController,"\$_FILES['evidence']")||str_contains($activityController,'$_FILES[\'evidence\']'),'Controlador reconoce evidencia de actividad');
ok(str_contains($activityController,'activity_id')||str_contains($activityController,'$activityId'),'Evidencia se vincula con la actividad');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión de servicio Fase 5 completada.".PHP_EOL;
