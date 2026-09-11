<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fails=0;
function ok(bool $c,string $m):void{global $fails;echo ($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$fails++;}
function body(string $p):string{$v=@file_get_contents($p);return is_string($v)?$v:'';}
$r=body($root.'/public/index.php');
$admin=body($root.'/app/Controllers/AdminController.php');
$ext=body($root.'/app/Controllers/ExternalController.php');
$users=body($root.'/app/Views/admin/users.php');
$externals=body($root.'/app/Views/admin/externals.php');
ok(str_contains($r,"['POST','/admin/externos/convertir-interno',[ExternalController::class,'convertExternalToInternal']]"),'Existe ruta externo → interno');
ok(str_contains($ext,'public function convertExternalToInternal(): void'),'ExternalController implementa externo → interno');
ok(str_contains($ext,"access_type='INTERNAL'"),'Conversión restaura acceso interno');
ok(str_contains($ext,"INSERT INTO user_assignments"),'Conversión crea nueva asignación interna');
ok(str_contains($ext,"USER_CONVERTED_TO_INTERNAL"),'Conversión queda auditada');
ok(str_contains($ext,"UPDATE external_ticket_access SET revoked_at=NOW()"),'Conversión revoca casos compartidos como externo');
ok(str_contains($ext,"UPDATE user_sessions SET revoked_at=NOW()")&&str_contains($ext,"UPDATE otp_codes SET consumed_at=NOW()"),'Conversión invalida sesiones y OTP');
ok(str_contains($externals,'Convertir a usuario interno'),'Directorio externo ofrece reconversión');
ok(str_contains($externals,'name="role_id"')&&str_contains($externals,'name="position_id"')&&str_contains($externals,'name="assignment_type"')&&str_contains($externals,'name="manager_user_id"'),'Reconversión solicita estructura interna');
ok(str_contains($users,'Referencia organizacional')&&str_contains($users,'No asigna tickets')&&str_contains($users,'no cambia permisos ni alcance'),'Responsable directo queda definido en lenguaje claro');
ok(str_contains($admin,'Este correo ya pertenece a un proveedor externo'),'Alta interna detecta correo de proveedor externo');
ok(str_contains($admin,"Flash::set(\$e->getMessage(),'danger')")&&str_contains($admin,"/admin/users?create=1"),'Alta interna maneja validación sin pantalla genérica');
if($fails){fwrite(STDERR,"\n[ERROR] {$fails} validacion(es) fallaron.\n");exit(1);}echo "\n[OK] Regresion reversibilidad interno/externo.\n";
