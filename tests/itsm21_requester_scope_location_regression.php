<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$fails=0;
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}

$policy=body($root.'/app/Services/RequesterLocationPolicyService.php');
$ticket=body($root.'/app/Controllers/TicketController.php');
$admin=body($root.'/app/Controllers/AdminController.php');
$location=body($root.'/app/Controllers/TicketLocationController.php');
$public=body($root.'/app/Views/tickets/public_create.php');
$users=body($root.'/app/Views/admin/users.php');
$show=body($root.'/app/Views/tickets/show.php');
$router=body($root.'/public/index.php');
$install=body($root.'/database/INSTALAR.sql');
$verify=body($root.'/database/VERIFICAR_INSTALACION.sql');

ok(str_contains($policy,"MODE_FIXED_PARK='FIXED_PARK'"),'Existe modo cuenta parque fija');
ok(str_contains($policy,"MODE_ASSIGNED_PARKS='ASSIGNED_PARKS'"),'Existe modo Supervisión por parques asignados');
ok(str_contains($policy,"MODE_ANY_PARK='ANY_PARK'"),'Existe modo corporativo con cualquier parque');
ok(str_contains($policy,"\$role==='SUPERVISOR'"),'Supervisión tiene política específica');
ok(str_contains($policy,'No puedes reportar solicitudes de un parque fuera de tu alcance.'),'Backend bloquea parques fuera del alcance de Supervisión');
ok(str_contains($policy,'Esta cuenta solo puede reportar solicitudes de su parque asignado.'),'Backend bloquea otro parque en cuenta de parque');

ok(str_contains($ticket,'RequesterLocationPolicyService'),'Alta usa política de ubicación');
ok(str_contains($ticket,'resolveParkId($authUser,$parkId)'),'Alta resuelve parque en backend');
ok(str_contains($public,'Cuenta de parque'),'Formulario explica parque fijo');
ok(str_contains($public,'Parques asignados'),'Formulario de Supervisión usa parques asignados');
ok(str_contains($public,'No especificar'),'Usuarios corporativos pueden dejar parque sin especificar');

ok(str_contains($admin,'requester_entity_type'),'Administración maneja qué representa la cuenta');
ok(str_contains($admin,'public function backfillParkTickets(): void'),'Existe backfill de históricos sin parque');
ok(str_contains($admin,"t.park_id IS NULL"),'Backfill solo toma tickets históricos sin parque');
ok(!str_contains($admin,"SET park_id=? WHERE requester_user_id=? AND park_id IS NOT NULL"),'Backfill no sobreescribe tickets con parque');
ok(str_contains($users,'¿Qué representa esta cuenta?'),'UI distingue Parque / Persona / Área');
ok(str_contains($users,'tickets anteriores sin parque'),'UI avisa históricos sin parque');
ok(str_contains($users,'Motivo de la actualización histórica'),'Backfill exige motivo');

ok(str_contains($location,"'LOCATION_CHANGED'"),'Corrección individual deja evento');
ok(str_contains($location,'location_change_reason'),'Corrección individual exige motivo');
ok(str_contains($router,"'/tickets/location'"),'Existe ruta para corregir ubicación del caso');
ok(str_contains($router,"'/admin/users/backfill-park-tickets'"),'Existe ruta para completar históricos de cuenta parque');
ok(str_contains($show,'Corregir ubicación del caso'),'Detalle permite corregir histórico');
ok(str_contains($show,'Dónde ocurrió'),'Detalle conserva concepto de ubicación histórica');

ok(str_contains($install,'requester_entity_type'),'Instalación canónica incluye tipo de cuenta');
ok(str_contains($verify,"('users','requester_entity_type')"),'Verificador exige tipo de cuenta');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo PHP_EOL."[OK] Regresión alcance de solicitante + ubicación histórica completada.".PHP_EOL;
