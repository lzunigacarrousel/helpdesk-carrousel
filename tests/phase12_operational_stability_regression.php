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

$bootstrap=(string)file_get_contents($root.'/bootstrap.php');
$logger=(string)file_get_contents($root.'/app/Core/Logger.php');
$attachment=(string)file_get_contents($root.'/app/Services/TicketAttachmentService.php');
$conversation=(string)file_get_contents($root.'/app/Controllers/ConversationController.php');
$xlsx=(string)file_get_contents($root.'/app/Services/XlsxExportService.php');
$export=(string)file_get_contents($root.'/app/Controllers/XlsxExportController.php');
$router=(string)file_get_contents($root.'/public/index.php');
$nav=(string)file_get_contents($root.'/public/assets/js/navigation-guards.js');

ok(str_contains($bootstrap,"ini_set('display_errors','0')"),'Errores PHP no se muestran al usuario');
ok(str_contains($bootstrap,"ini_set('display_startup_errors','0')"),'Errores de arranque no se muestran al usuario');
ok(str_contains($bootstrap,"ini_set('log_errors','1')"),'Errores PHP permanecen registrados');
ok(str_contains($bootstrap,'set_exception_handler'),'Existe handler global de excepciones');
ok(str_contains($bootstrap,'Logger::error($e)'),'Excepciones se registran antes de respuesta amigable');
ok(str_contains($bootstrap,'X-Content-Type-Options: nosniff'),'Bootstrap envía nosniff');
ok(str_contains($bootstrap,'Content-Security-Policy'),'Bootstrap conserva CSP');

ok(str_contains($logger,"STORAGE_PATH.'/logs/app.log'"),'Logger usa almacenamiento controlado');
ok(str_contains($logger,'FILE_APPEND|LOCK_EX'),'Logger agrega con lock');

ok(str_contains($attachment,'MAX_FILE_SIZE = 10485760'),'Adjuntos limitados a 10 MB');
foreach([
    'application/pdf','image/jpeg','image/png','image/webp','text/plain','text/csv',
    'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
] as $mime){
    ok(str_contains($attachment,"'".$mime."'"),'MIME permitido explícitamente: '.$mime);
}
ok(str_contains($attachment,'is_uploaded_file($tmp)'),'Adjunto exige archivo realmente subido');
ok(str_contains($attachment,'new \finfo(FILEINFO_MIME_TYPE)'),'Adjunto detecta MIME real');
ok(str_contains($attachment,'random_bytes(18)'),'Nombre almacenado es aleatorio');
ok(str_contains($attachment,"hash_file('sha256', $target)"),'Adjunto conserva hash SHA-256');
ok(str_contains($attachment,'@unlink($target)'),'Adjunto se elimina si falla persistencia');
ok(str_contains($attachment,"['PUBLIC', 'INTERNAL', 'EXTERNAL']"),'Adjunto conserva canales válidos');
ok(str_contains($attachment,'ticket_activity')||str_contains($attachment,'ticket_activities'),'Evidencia de actividad valida pertenencia al ticket');

ok(str_contains($conversation,"$"."visibility==='INTERNAL'&&!$"."context['is_support']"),'Descarga INTERNAL bloqueada fuera de soporte');
ok(str_contains($conversation,"$"."visibility==='EXTERNAL'&&!$"."context['is_support']&&!$"."context['is_external']"),'Descarga EXTERNAL bloqueada al solicitante');
ok(str_contains($conversation,"Content-Disposition: attachment"),'Descarga fuerza attachment');
ok(str_contains($conversation,"X-Content-Type-Options: nosniff"),'Descarga de adjunto usa nosniff');
ok(str_contains($conversation,"APP_ROOT.'/'.ltrim"),'Descarga resuelve ruta dentro de la aplicación');

ok(str_contains($xlsx,'ZipArchive'),'XLSX usa ZIP nativo');
ok(str_contains($xlsx,'tempnam(sys_get_temp_dir()'),'XLSX usa temporal del sistema');
ok(str_contains($xlsx,'@unlink($tmp)'),'XLSX limpia temporal');
ok(str_contains($xlsx,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),'XLSX entrega MIME correcto');
ok(str_contains($xlsx,'Content-Disposition: attachment'),'XLSX fuerza descarga');
ok(str_contains($export,'REPORT_EXPORTED_XLSX'),'Exportación queda auditada');
ok(str_contains($export,'TicketReportFilterService'),'Exportación respeta filtros');
ok(str_contains($export,'ScopeService'),'Exportación respeta alcance');

ok(str_contains($router,"['GET','/tickets/attachment',[ConversationController::class,'download']]"),'Ruta de adjuntos existe');
ok(str_contains($router,"['GET','/gestion/informes/exportar',[XlsxExportController::class,'export']]"),'Ruta XLSX principal existe');
ok(str_contains($nav,'/tickets/attachment'),'Navegación reconoce descargas de adjuntos');
ok(str_contains($nav,'data-no-loading'),'Descargas no quedan atrapadas por overlay');
ok(str_contains($nav,'download'),'Navegador recibe semántica de descarga');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Contrato de estabilidad operativa Fase 12 preparado.'.PHP_EOL;
