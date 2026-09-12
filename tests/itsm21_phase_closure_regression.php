<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}

$authController=body($root.'/app/Controllers/AuthController.php');
$authService=body($root.'/app/Services/AuthService.php');
$router=body($root.'/public/index.php');
$admin=body($root.'/app/Controllers/AdminController.php');
$users=body($root.'/app/Views/admin/users.php');
$policy=body($root.'/app/Services/RequesterLocationPolicyService.php');
$ticket=body($root.'/app/Controllers/TicketController.php');
$location=body($root.'/app/Controllers/TicketLocationController.php');
$install=body($root.'/database/INSTALAR.sql');

ok(!str_contains($router,"['POST','/auth/register'"),'Onboarding administrado elimina creación pública de cuentas');
ok(str_contains($router,"['GET','/register',[AuthController::class,'legacyRegister']"),'Ruta legacy de registro conserva salida segura al login');
ok(str_contains($authController,'legacyRegister'),'Acceso conserva compatibilidad con URL antigua de registro');
ok(!str_contains($authService,'public function register('),'AuthService ya no contiene autorregistro');
ok(str_contains($authService,'public function sendOtp('),'Acceso conserva envío OTP');
ok(str_contains($authService,'public function verify('),'Acceso conserva verificación OTP');
ok(str_contains($admin,'UPDATE tickets SET requester_user_id=? WHERE requester_user_id IS NULL AND LOWER(requester_email)=LOWER(?)'),'Administración vincula solicitudes históricas por correo al crear usuario');

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
echo PHP_EOL."[OK] Cierre funcional ITSM 2.1 validado con onboarding administrado.".PHP_EOL;
