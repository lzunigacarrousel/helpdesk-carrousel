<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;

function body(string $path): string {
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}
function ok(bool $cond,string $msg): void {
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}

$auth=body($root.'/app/Controllers/AuthController.php');
$authService=body($root.'/app/Services/AuthService.php');
$router=body($root.'/public/index.php');
$login=body($root.'/app/Views/auth/login.php');
$admin=body($root.'/app/Controllers/AdminController.php');
$manual=body($root.'/app/Views/help/manual.php');
$authCss=body($root.'/public/assets/css/auth-v2.css');

ok(str_contains($router,"['GET','/crear-ticket',[TicketController::class,'publicCreate']"),'Solicitar ayuda público continúa disponible sin cuenta');
ok(str_contains($router,"['POST','/auth/request',[AuthController::class,'requestOtp']"),'Login por correo/OTP continúa disponible');
ok(str_contains($router,"['GET','/otp',[AuthController::class,'otp']"),'Pantalla OTP continúa disponible');
ok(str_contains($router,"['POST','/auth/verify',[AuthController::class,'verify']"),'Verificación OTP continúa disponible');

ok(str_contains($router,"['GET','/register',[AuthController::class,'legacyRegister']"),'URL antigua /register conserva redirección compatible');
ok(!str_contains($router,"['POST','/auth/register'"),'Se elimina endpoint de creación por autorregistro');
ok(!str_contains($auth,"APP_BASE_URL.'/register'"),'Correo desconocido ya no se redirige a registro');
ok(!str_contains($auth,"register_email"),'Autenticación ya no mantiene sesión de autorregistro');
ok(!str_contains($auth,'public function register(): void'),'AuthController elimina pantalla de autorregistro');
ok(!str_contains($auth,'public function createUser(): void'),'AuthController elimina creación pública de usuario');
ok(str_contains($auth,'public function legacyRegister(): void'),'AuthController conserva compatibilidad para /register');
ok(str_contains($auth,"Flash::set('El registro de cuentas se gestiona desde Administración.'"),'Compatibilidad /register explica que Administración habilita la cuenta');
ok(str_contains($auth,"header('Location: '.APP_BASE_URL.'/login')"),'Compatibilidad /register vuelve al login');
ok(str_contains($auth,'Este correo todavía no tiene acceso al Helpdesk.'),'Correo desconocido recibe mensaje simple de acceso no habilitado');

ok(!str_contains($login,'¿Primer ingreso?'),'Login elimina invitación al autorregistro');
ok(str_contains($login,'Reportar una solicitud'),'Login ofrece reportar sin iniciar sesión');
ok(str_contains($login,"APP_BASE_URL ?>/crear-ticket"),'CTA de acceso no habilitado apunta a Solicitar ayuda');

ok(str_contains($admin,'UPDATE tickets SET requester_user_id=? WHERE requester_user_id IS NULL AND LOWER(requester_email)=LOWER(?)'),'Admin conserva vinculación automática de tickets históricos por correo');
ok(str_contains($admin,"Flash::set('Usuario creado. Ya puede ingresar con su correo y código de acceso.'"),'Admin deja claro que la cuenta creada usa código OTP');
ok(str_contains($manual,'Las cuentas de acceso las crea Administración'),'Manual explica que el acceso se habilita desde Administración');

ok(!is_file($root.'/app/Views/auth/register.php'),'Se elimina la vista de autorregistro que ya no tiene ruta');
ok(!str_contains($authService,'public function register('),'AuthService elimina lógica muerta de autorregistro');
ok(!str_contains($authService,'private function resolveManager('),'AuthService elimina helper usado solo por autorregistro');
ok(str_contains($authService,'public function sendOtp('),'AuthService conserva envío OTP');
ok(str_contains($authService,'public function verify('),'AuthService conserva verificación OTP');
ok(!str_contains($authCss,'auth-register-page'),'CSS elimina estilos exclusivos de la pantalla de autorregistro');
ok(!str_contains($authCss,'auth-register-section'),'CSS elimina secciones exclusivas de autorregistro');
ok(!str_contains($authCss,'auth-register-grid'),'CSS elimina grid exclusivo de autorregistro');
ok(!str_contains($authCss,'auth-work-option'),'CSS elimina opciones exclusivas de autorregistro');
ok(!str_contains($authCss,'auth-register-note'),'CSS elimina notas exclusivas de autorregistro');

if($fails>0){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión onboarding administrado Pre-Fase 4 completada.".PHP_EOL;
