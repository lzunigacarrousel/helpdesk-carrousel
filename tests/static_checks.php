<?php
declare(strict_types=1);
$root=dirname(__DIR__);$ok=true;
function check(bool $condition,string $message): void{global $ok;echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;$ok=$ok&&$condition;}
$required=[
'bootstrap.php','config/config.php','config/local.php.example',
'database/INSTALAR.sql','database/VERIFICAR_INSTALACION.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
'INSTALAR_PC_TEST.bat','README.md','docs/ESTANDAR_VISUAL_CARROUSEL.md',
'app/Core/Auth.php','app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/NotificationService.php','app/Services/MailService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Services/XlsxExportService.php',
'app/Controllers/DashboardController.php','app/Controllers/WorkflowController.php','app/Controllers/ConversationController.php','app/Controllers/ResolutionController.php','app/Controllers/TicketFeedbackController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php','app/Controllers/MailAdminController.php','app/Controllers/AdminController.php',
'app/Views/dashboard/index.php','app/Views/management/dashboard.php','app/Views/tickets/public_create.php','app/Views/tickets/index.php','app/Views/tickets/feedback.php','app/Views/tickets/show.php','app/Views/tickets/show_external.php','app/Views/help/manual.php','app/Views/admin/mail.php','app/Views/admin/users.php','app/Views/shared/app_start.php','app/Views/shared/help_widget.php',
'public/assets/js/help-tour.js','public/assets/js/notifications.js','public/assets/css/ui-refresh.css','public/assets/css/components.css','public/assets/css/visual-system.css','public/index.php','HELPDESK_ADMIN.bat'];
foreach($required as $file)check(is_file($root.'/'.$file),'Existe '.$file);

$checks=[
'config/config.php'=>['2.4.0-dev','APP_CANONICAL_URL','helpdesk_carrousel'],
'config/local.php.example'=>["'db_name' => 'helpdesk_carrousel'"],
'database/INSTALAR.sql'=>['CREATE DATABASE IF NOT EXISTS helpdesk_carrousel','CREATE TABLE positions','CREATE TABLE ticket_resolutions','CREATE TABLE ticket_feedback','CREATE TABLE external_profiles','CREATE TABLE schema_migrations','management.view','external.manage','tickets.resolve','trg_tickets_require_resolution','CC_PARK_504','CC_PARK_516','OK - INSTALACION CANONICA HELPDESK CARROUSEL V2'],
'database/VERIFICAR_INSTALACION.sql'=>['USE helpdesk_carrousel','required_tables','required_columns','tablas no canonicas/legacy','trg_tickets_require_resolution','OK - INSTALACION LIMPIA HELPDESK CARROUSEL V2'],
'database/VERIFICAR_ESTABILIDAD_V2.sql'=>['USE helpdesk_carrousel','CORREOS_ENVIADOS_SIN_FECHA','CORREOS_FALLIDOS_SIN_MOTIVO','CORREOS_PENDIENTES_MAS_10_MIN'],
'INSTALAR_PC_TEST.bat'=>['DB_NAME=helpdesk_carrousel','database\\INSTALAR.sql','database\\VERIFICAR_INSTALACION.sql','REINSTALAR'],
'README.md'=>['helpdesk_carrousel','database\\INSTALAR.sql','una sola base'],
'app/Core/Auth.php'=>['isSupportOperator','isManagementViewer','profileLabel'],
'app/Services/ScopeService.php'=>['ticketConstraint','SUPERVISOR','scopeLabel'],
'app/Views/tickets/feedback.php'=>['¿Quedó resuelto?','Calificar y cerrar','Devolver a soporte','nps_score'],
'app/Controllers/ResolutionController.php'=>['tickets.resolve','RESOLUTION_RECORDED','tickets/feedback?id=','confirmación del solicitante'],
'app/Controllers/TicketFeedbackController.php'=>['public function submit','public function reopen','ticket_feedback','TICKET_FEEDBACK_SUBMITTED','TICKET_REOPENED_BY_REQUESTER'],
'app/Views/admin/users.php'=>['+ Dar acceso','Acceso interno','Dónde trabaja','Retirar acceso','data-assignment-type'],
'public/index.php'=>['/tickets/feedback','/tickets/feedback/reopen','/tickets/resolve','/gestion/informes/exportar','/admin/users/create','/admin/users/assign','/admin/users/delete']];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle)check(is_string($content)&&str_contains($content,$needle),$file.' contiene '.$needle);}

$legacySql=[];
foreach(glob($root.'/database/*.sql')?:[] as $file){$name=basename($file);if(!in_array($name,['INSTALAR.sql','VERIFICAR_INSTALACION.sql','VERIFICAR_ESTABILIDAD_V2.sql'],true))$legacySql[]=$name;}
check($legacySql===[],'database/ no contiene SQL historicos o parches'.($legacySql?' → '.implode(', ',$legacySql):''));

$forbidden=[
'config/config.php'=>['helpdesk_carrousel_test'],
'config/local.php.example'=>['helpdesk_carrousel_test'],
'INSTALAR_PC_TEST.bat'=>['FINALIZAR_ESQUEMA_V2.sql','CATALOGOS_CARROUSEL.sql','helpdesk_carrousel_test'],
'README.md'=>['FINALIZAR_ESQUEMA_V2.sql','CATALOGOS_CARROUSEL.sql','helpdesk_carrousel_test','ACTUALIZAR_','INSTALAR_FASE1.sql','IMPORTAR_DESDE_CAJA_CHICA.sql'],
'app/Views/admin/users.php'=>['+ Nuevo usuario']];
foreach($forbidden as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle)check(!(is_string($content)&&str_contains($content,$needle)),$file.' no contiene '.$needle);}
exit($ok?0:1);
