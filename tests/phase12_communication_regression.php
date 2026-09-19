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

$mail=(string)file_get_contents($root.'/app/Services/MailService.php');
$notifications=(string)file_get_contents($root.'/app/Services/NotificationService.php');
$auth=(string)file_get_contents($root.'/app/Services/AuthService.php');
$tickets=(string)file_get_contents($root.'/app/Controllers/TicketController.php');
$conversation=(string)file_get_contents($root.'/app/Controllers/ConversationController.php');
$workflow=(string)file_get_contents($root.'/app/Controllers/WorkflowController.php');
$resolution=(string)file_get_contents($root.'/app/Controllers/ResolutionController.php');
$feedback=(string)file_get_contents($root.'/app/Controllers/TicketFeedbackController.php');
$external=(string)file_get_contents($root.'/app/Controllers/ExternalController.php');
$mailAdmin=(string)file_get_contents($root.'/app/Controllers/MailAdminController.php');
$mailView=(string)file_get_contents($root.'/app/Views/admin/mail.php');
$shell=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$router=(string)file_get_contents($root.'/public/index.php');
$sql=(string)file_get_contents($root.'/database/VERIFICAR_FASE12_COMUNICACION_20260919.sql');

ok(str_contains($mail,"private const LOGO_CID='carrousel-logo'"),'Correo define CID estable del logo');
ok(str_contains($mail,"'cid:'.self::LOGO_CID"),'HTML usa CID cuando existe logo local');
ok(str_contains($mail,'addEmbeddedImage($logoPath,self::LOGO_CID'), 'PHPMailer incrusta logo local');
ok(str_contains($mail,"APP_ROOT.'/public/assets/images/logo.png'"),'Logo se toma de archivo local canónico');
ok(str_contains($mail,"APP_CANONICAL_URL.'/assets/images/logo.png'"),'Existe respaldo por URL pública absoluta');
ok(!str_contains(strtolower($mail),'192.168.'),'MailService no incrusta IP privada');
ok(!str_contains(strtolower($mail),'localhost'),'MailService no incrusta localhost');
ok(str_contains($mail,'sendOtp('),'MailService envía OTP');
ok(str_contains($mail,'sendTicketNotification('),'MailService envía avisos de tickets');

ok(str_contains($notifications,'sendOtpDelivery('),'OTP pasa por trazabilidad de notificaciones');
ok(str_contains($notifications,"if((string)$" . "delivery['event_key']==='OTP_REQUESTED')"),'OTP no puede reintentarse manualmente');
ok(str_contains($notifications,"['FAILED','PENDING']"),'Reintento solo aplica a fallidos o pendientes');
ok(str_contains($notifications,"'EMAIL','PENDING'")||str_contains($notifications,"'EMAIL',?,?,?,?,?,'PENDING'"),'Correo se registra inicialmente como pendiente');
ok(str_contains($notifications,"status='SENT'"),'Correo registra SENT');
ok(str_contains($notifications,"status='FAILED'"),'Correo registra FAILED');
ok(str_contains($notifications,"status='SKIPPED'"),'Modo prueba registra SKIPPED');
ok(str_contains($notifications,"'requester'"),'Notificaciones resuelven solicitante');
ok(str_contains($notifications,"'assignee'"),'Notificaciones resuelven responsable');
ok(str_contains($notifications,"'externals'"),'Notificaciones resuelven colaboradores');
ok(str_contains($notifications,"'support_group'"),'Notificaciones resuelven equipo de soporte');
ok(str_contains($notifications,"r.code IN('ADMIN','SEMIADMIN')"),'Administradores se resuelven sin incluir Gerencia');
ok(str_contains($notifications,"$" . "add((int)$" . "row['id'],(string)$" . "row['email'],(string)$" . "row['full_name'],false,true)"),'Administradores reciben eventos operativos solo dentro del Helpdesk');
ok(str_contains($notifications,'normalizeActionUrl'),'URLs de correo pasan por normalización canónica');

ok(str_contains($auth,'sendOtpDelivery'),'AuthService usa NotificationService para OTP');
ok(str_contains($auth,'password_hash($code, PASSWORD_DEFAULT)'),'OTP se almacena con hash');
ok(str_contains($auth,'Límite de códigos alcanzado'),'OTP conserva rate limit');

ok(str_contains($tickets,"'TICKET_CREATED_REQUESTER'"),'Ticket nuevo avisa al solicitante');
ok(str_contains($tickets,"'TICKET_CREATED_SUPPORT'"),'Ticket nuevo avisa al equipo de soporte');
ok(str_contains($tickets,"'TICKET_CLAIMED'"),'Caso tomado genera aviso');
ok(str_contains($tickets,"'TICKET_REASSIGNED'"),'Reasignación genera aviso');
ok(str_contains($tickets,"'TICKET_RELEASED'"),'Caso devuelto a cola genera aviso');

ok(str_contains($conversation,"'PUBLIC_RESPONSE_ADDED'"),'Respuesta pública genera aviso');
ok(str_contains($conversation,"'EXTERNAL_RESPONSE_ADDED'"),'Respuesta de proveedor genera aviso');
ok(str_contains($conversation,"'INTERNAL_NOTE_ADDED'"),'Nota interna queda trazada');
ok(str_contains($conversation,"['email'=>false,'in_app'=>true]"),'Nota interna explícitamente no envía correo');

ok(str_contains($workflow,"'STATUS_CHANGED'"),'Cambio de estado genera aviso');
ok(str_contains($workflow,"'PENDING_REASON_CHANGED'"),'Cambio de motivo de espera genera aviso');
ok(str_contains($workflow,"if($" . "status==='REOPENED')"),'Reapertura amplía audiencia a soporte');
ok(str_contains($workflow,"$" . "audiences[]='support_group'"),'Reapertura puede avisar al grupo de soporte');

ok(str_contains($resolution,"'RESOLUTION_RECORDED'"),'Resolución avisa al solicitante');
ok(str_contains($resolution,"'RESOLUTION_RECORDED_INTERNAL'"),'Resolución genera aviso interno separado');
ok(str_contains($resolution,"['email'=>false,'in_app'=>true]"),'Aviso interno de resolución no genera correo masivo');

ok(str_contains($feedback,"'TICKET_REOPENED'"),'Reapertura del solicitante genera aviso');
ok(str_contains($feedback,"'TICKET_FEEDBACK_SUBMITTED'"),'Confirmación/NPS genera aviso interno');
ok(str_contains($feedback,"['email'=>false,'in_app'=>true]"),'Feedback confirmado no genera correo operativo masivo');

ok(str_contains($external,"'EXTERNAL_GRANTED'"),'Proveedor agregado recibe aviso');
ok(str_contains($external,"'EXTERNAL_REVOKED'"),'Proveedor retirado recibe aviso');
ok(str_contains($external,"'EXTERNAL_USER_CREATED'"),'Alta de colaborador genera aviso');

ok(str_contains($router,"['GET','/admin/correo',[MailAdminController::class,'index']]"),'Existe panel /admin/correo');
ok(str_contains($router,"['POST','/admin/correo/probar',[MailAdminController::class,'test']]"),'Existe prueba explícita de correo');
ok(str_contains($router,"['POST','/admin/correo/reintentar',[MailAdminController::class,'retry']]"),'Existe reintento controlado');

ok(str_contains($mailAdmin,"'sent_today'"),'Panel calcula enviados');
ok(str_contains($mailAdmin,"'failed'"),'Panel calcula fallidos');
ok(str_contains($mailAdmin,"'pending'"),'Panel calcula pendientes');
ok(str_contains($mailAdmin,"recipient_email"),'Panel consulta destinatario');
ok(str_contains($mailAdmin,"attempts"),'Panel consulta intentos');
ok(str_contains($mailAdmin,"'EXTERNAL_RESPONSE_ADDED'=>'Respuesta de proveedor'"),'Panel etiqueta respuesta de proveedor');
ok(str_contains($mailAdmin,"'TICKET_REOPENED'=>'Caso reabierto'"),'Panel etiqueta reapertura');
ok(str_contains($mailAdmin,"'TICKET_FEEDBACK_SUBMITTED'=>'Confirmación del solicitante'"),'Panel etiqueta feedback');

ok(str_contains($mailView,'Enviar correo de prueba'),'UI expone prueba explícita');
ok(str_contains($mailView,'Reintentar'),'UI expone reintento');
ok(str_contains($mailView,"(string)$" . "d['event_key']!=='OTP_REQUESTED'"),'UI nunca ofrece reintentar OTP');
ok(str_contains($mailView,'Destinatario'),'UI muestra destinatario');
ok(str_contains($mailView,'Evento'),'UI muestra evento');
ok(str_contains($mailView,'Intentos'),'UI muestra intentos');
ok(str_contains($shell,".' actualizaciones nuevas'"),'Campana resume novedades sin crear otro inbox');

ok(str_contains($sql,'EMAIL_NOTA_INTERNA'),'SQL controla nota interna sin correo');
ok(str_contains($sql,'EMAIL_OPERATIVO_GERENCIA'),'SQL controla correo operativo a Gerencia');
ok(str_contains($sql,'DUPLICADOS_ENTREGA'),'SQL controla duplicados');
ok(str_contains($sql,'ACCIONES_LOCALHOST_EMAIL_HISTORICAS'),'SQL conserva evidencia histórica de URLs locales');
ok(str_contains($sql,'ACCIONES_LOCALHOST_EMAIL_NUEVAS'),'SQL bloquea nuevas URLs locales');
ok(str_contains($sql,'@phase12_baseline_delivery_id'),'SQL usa baseline local sin alterar BD');
ok(str_contains($sql,"status='SENT'"),'Control de URL local se limita a correos realmente enviados');
ok(str_contains($sql,"THEN 'PASS'"),'SQL emite PASS');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Correo y notificaciones Fase 12 preparados.'.PHP_EOL;
