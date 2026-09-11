<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fails = 0;
function ok(bool $cond, string $msg): void {
    global $fails;
    echo ($cond ? '[OK] ' : '[FALLO] ') . $msg . PHP_EOL;
    if (!$cond) $fails++;
}
function body(string $path): string {
    $v = @file_get_contents($path);
    return is_string($v) ? $v : '';
}

$router = body($root.'/public/index.php');
$controller = body($root.'/app/Controllers/ExternalController.php');
$usersView = body($root.'/app/Views/admin/users.php');
$externalView = body($root.'/app/Views/admin/externals.php');

ok(str_contains($router, "['POST','/admin/users/convertir-externo',[ExternalController::class,'convertInternal']]"), 'Existe ruta para convertir usuario interno a proveedor');
ok(str_contains($router, "['POST','/admin/externos/actualizar',[ExternalController::class,'updateUser']]"), 'Existe ruta para editar proveedor');
ok(str_contains($router, "['POST','/admin/externos/desactivar',[ExternalController::class,'deactivateUser']]"), 'Existe ruta para desactivar proveedor');

ok(str_contains($controller, 'public function convertInternal(): void'), 'ExternalController implementa conversión desde usuario interno');
ok(str_contains($controller, "Auth::requirePermission('users.manage')"), 'Conversión exige permiso de usuarios');
ok(str_contains($controller, "Auth::requirePermission('external.manage')"), 'Conversión exige permiso de proveedores externos');
ok(str_contains($controller, "status NOT IN('RESOLVED','CLOSED','CANCELLED')"), 'Conversión bloquea usuarios con tickets activos asignados');
ok((bool)preg_match('/UPDATE\s+support_team_members\s+SET\s+is_active=0\s*,\s*ended_at=NOW\(\)/is',$controller), 'Conversión retira membresía del equipo de soporte');
ok((bool)preg_match('/UPDATE\s+user_assignments\s+SET\s+status=\'ENDED\'\s*,\s*ends_at=NOW\(\)/is',$controller), 'Conversión finaliza asignación interna');
ok((bool)preg_match('/UPDATE\s+user_assignments\s+SET\s+manager_user_id=NULL/is',$controller), 'Conversión elimina responsabilidad jerárquica interna');
ok(str_contains($controller, "access_type='EXTERNAL'"), 'Conversión cambia el alcance a EXTERNAL');
ok(str_contains($controller, "code='EXTERNAL'"), 'Conversión asigna el perfil EXTERNAL real');
ok(str_contains($controller, "UPDATE user_sessions SET revoked_at=NOW()"), 'Conversión revoca sesiones vigentes');
ok(str_contains($controller, "UPDATE otp_codes SET consumed_at=NOW()"), 'Conversión invalida OTP anteriores');
ok(str_contains($controller, "USER_CONVERTED_TO_EXTERNAL"), 'Conversión queda registrada en auditoría');
ok(str_contains($controller, 'public function updateUser(): void'), 'Proveedor puede editarse');
ok(str_contains($controller, 'public function deactivateUser(): void'), 'Proveedor puede desactivarse');
ok(str_contains($controller, "UPDATE external_ticket_access SET revoked_at=NOW()"), 'Desactivar proveedor revoca casos compartidos');
ok(str_contains($controller, "\$shareUsers=array_values(array_filter"), 'Compartir casos usa solo proveedores activos');
ok(str_contains($controller, "converted_from_internal"), 'Directorio identifica origen de la cuenta');

ok(str_contains($usersView, '/admin/users/convertir-externo'), 'Usuarios ofrece acción Convertir a proveedor externo');
ok(str_contains($usersView, 'Convertir a proveedor externo'), 'Usuarios explica claramente la conversión');
ok(str_contains($usersView, 'Esta persona dejará de tener acceso interno'), 'Usuarios advierte el cambio de alcance');
ok(str_contains($usersView, 'active_ticket_count'), 'Usuarios muestra bloqueo visual cuando existen tickets activos');

ok(str_contains($externalView, '/admin/externos/actualizar'), 'Directorio externo permite editar proveedor');
ok(str_contains($externalView, '/admin/externos/desactivar'), 'Directorio externo permite desactivar proveedor');
ok(str_contains($externalView, 'Convertido desde usuario interno'), 'Directorio muestra origen convertido');
ok(str_contains($externalView, 'Creado como proveedor'), 'Directorio muestra origen creado directamente');
ok(str_contains($externalView, '$shareUsers'), 'Selector de compartir caso usa proveedores activos');

if ($fails > 0) {
    fwrite(STDERR, PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo PHP_EOL."[OK] Regresión proveedores externos completada.".PHP_EOL;
