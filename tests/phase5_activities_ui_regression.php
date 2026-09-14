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

$routes=body($root.'/public/index.php');
$controller=body($root.'/app/Controllers/TicketActivityController.php');
$ticketController=body($root.'/app/Controllers/TicketController.php');
$ticketViewController=body($root.'/app/Controllers/TicketViewController.php');

ok($controller!=='','Existe TicketActivityController');
ok(str_contains($routes,'TicketActivityController'),'Router conoce TicketActivityController');

foreach([
    '/tickets/activities/create'=>'Ruta crear actividad',
    '/tickets/activities/reschedule'=>'Ruta reprogramar actividad',
    '/tickets/activities/start'=>'Ruta iniciar actividad',
    '/tickets/activities/complete'=>'Ruta finalizar actividad',
    '/tickets/activities/cancel'=>'Ruta cancelar actividad',
    '/tickets/activities/participants/add'=>'Ruta agregar participante',
    '/tickets/activities/participants/remove'=>'Ruta retirar participante',
] as $path=>$label){
    ok(str_contains($routes,"['POST','{$path}'"),$label.' usa POST');
}

foreach([
    'create'=>'Controlador crea actividad',
    'reschedule'=>'Controlador reprograma actividad',
    'start'=>'Controlador inicia actividad',
    'complete'=>'Controlador finaliza actividad',
    'cancel'=>'Controlador cancela actividad',
    'addParticipant'=>'Controlador agrega participante',
    'removeParticipant'=>'Controlador retira participante',
] as $method=>$label){
    ok(str_contains($controller,'function '.$method.'('),$label);
}

ok(str_contains($controller,'TicketActivityService'),'Controlador delega dominio en TicketActivityService');
ok(str_contains($controller,'Csrf::verify'),'Operaciones verifican CSRF');
ok(str_contains($controller,"activities.create"),'Crear exige activities.create');
ok(str_contains($controller,"activities.manage"),'Gestión exige activities.manage');
ok(str_contains($controller,"activities.cancel"),'Cancelar exige activities.cancel');
ok(str_contains($controller,"#actividades"),'Operaciones regresan al bloque Actividades');

$csrfCount=substr_count($controller,'Csrf::verify');
ok($csrfCount>=1,'Existe gate CSRF reutilizable para las operaciones');
ok(!str_contains($controller,'UPDATE tickets SET status'),'Controlador no cambia automáticamente el estado del ticket');

// Task 6: el workspace interno carga actividades según el perfil que está viendo el ticket.
ok(str_contains($ticketController,'TicketActivityService'),'TicketController integra TicketActivityService');
ok(str_contains($ticketController,'listForTicket'),'Soporte recibe actividades operativas del ticket');
ok(str_contains($ticketController,'requesterVisibleForTicket'),'Solicitante recibe únicamente actividades visibles');
ok(str_contains($ticketController,'responsibleOptionsForTicket'),'Workspace resuelve responsables válidos para actividades');
ok(str_contains($ticketController,'providerOptionsForTicket'),'Workspace resuelve proveedores válidos para actividades');
ok(str_contains($ticketController,"activities.create"),'Workspace calcula permiso para crear actividades');
ok(str_contains($ticketController,"activities.manage"),'Workspace calcula permiso para gestionar actividades');
ok(str_contains($ticketController,"activities.cancel"),'Workspace calcula permiso para cancelar actividades');

foreach([
    "'activities'"=>'Vista recibe activities',
    "'requesterActivities'"=>'Vista recibe requesterActivities',
    "'activityTypes'"=>'Vista recibe catálogo de tipos',
    "'activityResults'"=>'Vista recibe catálogo de resultados',
    "'activityResponsibleUsers'"=>'Vista recibe responsables de actividades',
    "'activityProviderUsers'"=>'Vista recibe proveedores de actividades',
    "'activityParks'"=>'Vista recibe parques de actividades',
    "'canCreateActivities'"=>'Vista recibe permiso de creación',
    "'canManageActivities'"=>'Vista recibe permiso de gestión',
    "'canCancelActivities'"=>'Vista recibe permiso de cancelación',
] as $needle=>$label){
    ok(str_contains($ticketController,$needle),$label);
}

ok(
    str_contains($ticketController,'$isSupport')&&
    str_contains($ticketController,'requesterVisibleForTicket'),
    'Carga distingue soporte de solicitante'
);
ok(!str_contains($ticketViewController,'TicketActivityService'),'Vista de proveedor externo no recibe dominio interno de actividades');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión de rutas/controlador Fase 5 completada.".PHP_EOL;
