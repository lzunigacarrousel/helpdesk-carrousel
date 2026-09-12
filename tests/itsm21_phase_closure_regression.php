<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}

$authController=body($root.'/app/Controllers/AuthController.php');
$authService=body($root.'/app/Services/AuthService.php');
$register=body($root.'/app/Views/auth/register.php');
$admin=body($root.'/app/Controllers/AdminController.php');
$users=body($root.'/app/Views/admin/users.php');
$policy=body($root.'/app/Services/RequesterLocationPolicyService.php');
$ticket=body($root.'/app/Controllers/TicketController.php');
$location=body($root.'/app/Controllers/TicketLocationController.php');
$install=body($root.'/database/INSTALAR.sql');

ok(str_contains($authController,'historicalTicketCount'),'Primer ingreso detecta solicitudes históricas del correo');
ok(!str_contains($authController,"'requester_entity_type'=>Http::post"),'Primer ingreso no pide clasificar PARK/PERSON/DEPARTMENT');
ok(str_contains($register,'Ubicación y función'),'Primer ingreso conserva flujo simple');
ok(str_contains($register,'name="assignment_type" value="PARK"'),'Primer ingreso conserva Parque / ubicación');
ok(str_contains($register,'name="assignment_type" value="CORPORATE"'),'Primer ingreso conserva Área corporativa');
ok(str_contains($register,'name="assignment_type" value="OTHER"'),'Primer ingreso conserva Otro');
ok(!str_contains($register,'name="requester_entity_type"'),'Primer ingreso no muestra tipo administrativo de cuenta');
ok(!str_contains($register,'¿Qué representa este correo?'),'Primer ingreso elimina pregunta redundante');
ok(str_contains($register,'anteriores con este correo'),'Primer ingreso avisa si hay tickets anteriores');
ok(!str_contains($authService,'requester_entity_type'),'Autorregistro depende del default PERSON de BD');
ok(str_contains($authService,"status'=>'PENDING'"),'Autorregistro continúa PENDING');
ok(str_contains($authService,'UPDATE tickets SET requester_user_id=?'),'Autorregistro conserva vinculación de tickets históricos');

ok(str_contains($install,'requester_entity_type'),'BD conserva clasificación administrativa de cuenta');
ok(str_contains($admin,'requester_entity_type'),'Administración conserva clasificación PARK/PERSON/DEPARTMENT');
ok(str_contains($users,'¿Qué representa esta cuenta?'),'Clasificación administrativa permanece en Usuarios');

ok(str_contains($policy,"MODE_FIXED_PARK='FIXED_PARK'"),'Cuenta Parque conserva parque fijo');
ok(str_contains($policy,"MODE_ASSIGNED_PARKS='ASSIGNED_PARKS'"),'Supervisión conserva parques asignados');
ok(str_contains($policy,"\$role==='SUPERVISOR'"),'Supervisión tiene política específica');
ok(str_contains($ticket,'RequesterLocationPolicyService'),'Creación de ticket aplica política de alcance');
ok(str_contains($location,"'LOCATION_CHANGED'"),'Corrección histórica conserva auditoría');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo PHP_EOL."[OK] Cierre funcional ITSM 2.1 validado.".PHP_EOL;
