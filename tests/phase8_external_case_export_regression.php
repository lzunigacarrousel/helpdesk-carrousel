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

$controllerPath=$root.'/app/Controllers/ExternalCaseHistoryController.php';
$routerPath=$root.'/public/index.php';
$viewPath=$root.'/app/Views/tickets/index.php';

ok(is_file($routerPath),'Existe router principal');
ok(is_file($viewPath),'Existe vista Mis casos');
ok(is_file($controllerPath),'Existe ExternalCaseHistoryController');

$controller=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$router=is_file($routerPath)?(string)file_get_contents($routerPath):'';
$view=is_file($viewPath)?(string)file_get_contents($viewPath):'';

ok(str_contains($router,'ExternalCaseHistoryController'),'Router importa ExternalCaseHistoryController');
ok(str_contains($router,"['GET','/mis-tickets/exportar',[ExternalCaseHistoryController::class,'export']]"),'Existe ruta GET de Excel externo');

ok(str_contains($controller,'XlsxExportService'),'Export usa servicio XLSX');
ok(str_contains($controller,"access_type']??'INTERNAL')!=='EXTERNAL'"),'Export exige cuenta EXTERNAL');
ok(str_contains($controller,'eta.user_id=?'),'Export limita datos al usuario autenticado');
ok(str_contains($controller,'eta.granted_at external_granted_at'),'Export conserva fecha de asignación');
ok(str_contains($controller,'eta.revoked_at external_revoked_at'),'Export conserva fecha de finalización');
ok(str_contains($controller,"eta.revoked_at IS NOT NULL OR t.visibility_mode='EXTERNAL_ALLOWED'"),'Export conserva activas e históricas sin reabrir acceso');

ok(str_contains($controller,"'Ticket','Asunto','Categoría','Ubicación','Participación','Asignado','Finalizado'"),'Excel usa columnas seguras para proveedor');
ok(!str_contains($controller,'provider_rating'),'Excel externo no expone valoración interna');
ok(!str_contains($controller,'requester_email'),'Excel externo no expone correo del solicitante');
ok(!str_contains($controller,'ticket_comments'),'Excel externo no consulta comentarios');
ok(!str_contains($controller,'ticket_resolutions'),'Excel externo no consulta resolución interna');

ok(str_contains($view,'/mis-tickets/exportar'),'Vista enlaza descarga Excel externa');
ok(str_contains($view,'Descargar Excel'),'Vista muestra botón Descargar Excel');
ok(str_contains($view,'/mis-tickets/exportar\" data-no-loading=\"1\"'),'Descarga Excel externa no deja overlay global bloqueado');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Excel seguro de historial para proveedor externo.'.PHP_EOL;
