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

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión de servicio Fase 5 completada.".PHP_EOL;
