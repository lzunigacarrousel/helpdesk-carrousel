<?php
declare(strict_types=1);
$root=dirname(__DIR__);$ok=true;
function check(bool $condition,string $message): void{global $ok;echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;$ok=$ok&&$condition;}
$required=[
'bootstrap.php','config/config.php','config/local.php.example',
'database/INSTALAR.sql','database/VERIFICAR_INSTALACION.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
'INSTALAR_PC_TEST.bat','README.md','docs/ESTANDAR_VISUAL_CARROUSEL.md',
'app/Core/Auth.php','app/Core/SearchText.php','app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/NotificationService.php','app/Services/MailService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Services/XlsxExportService.php',
'app/Controllers/DashboardController.php','app/Controllers/WorkflowController.php','app/Controllers/ConversationController.php','app/Controllers/ResolutionController.php','app/Controllers/TicketFeedbackController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php','app/Controllers/MailAdminController.php','app/Controllers/AdminController.php',
'app/Views/dashboard/index.php','app/Views/management/dashboard.php','app/Views/tickets/public_create.php','app/Views/tickets/index.php','app/Views/tickets/feedback.php','app/Views/tickets/show.php','app/Views/tickets/show_external.php','app/Views/help/manual.php','app/Views/admin/mail.php','app/Views/admin/users.php','app/Views/shared/app_start.php','app/Views/shared/app_end.php','app/Views/shared/help_widget.php',
'public/assets/js/help-tour.js','public/assets/js/notifications.js','public/assets/js/table-normalization.js','public/assets/css/ui-refresh.css','public/assets/css/components.css','public/assets/css/searchable-select.css','public/assets/css/visual-system.css','public/assets/css/layout-density-v25.css','public/assets/css/data-tables.css','public/index.php','MAIN.bat','INSTALAR_PRODUCCION.bat','database/VERIFICAR_PRODUCCION_LIMPIA.sql'];
foreach($required as $file)check(is_file($root.'/'.$file),'Existe '.$file);

$checks=[
'config/config.php'=>['2.4.0-dev','APP_CANONICAL_URL','carrousel_helpdesk','carrousel_helpdesk_session'],
'config/local.php.example'=>["'db_name' => 'carrousel_helpdesk'"],
'database/INSTALAR.sql'=>['CREATE DATABASE IF NOT EXISTS carrousel_helpdesk','USE carrousel_helpdesk','CREATE TABLE positions','CREATE TABLE ticket_resolutions','CREATE TABLE ticket_feedback','CREATE TABLE external_profiles','CREATE TABLE schema_migrations','management.view','external.manage','tickets.resolve','trg_tickets_require_resolution','CC_PARK_504','CC_PARK_516','OK - INSTALACION CANONICA HELPDESK CARROUSEL V2'],
'database/VERIFICAR_INSTALACION.sql'=>['USE carrousel_helpdesk','required_tables','required_columns','tablas no canonicas/legacy','trg_tickets_require_resolution','OK - INSTALACION LIMPIA HELPDESK CARROUSEL V2'],
'database/VERIFICAR_ESTABILIDAD_V2.sql'=>['USE carrousel_helpdesk','CORREOS_ENVIADOS_SIN_FECHA','CORREOS_FALLIDOS_SIN_MOTIVO','CORREOS_PENDIENTES_MAS_10_MIN'],
'INSTALAR_PC_TEST.bat'=>['DB_NAME=carrousel_helpdesk','PROTECTED_DB=helpdesk_carrousel','database\\INSTALAR.sql','database\\VERIFICAR_INSTALACION.sql','REINSTALAR','if /I "%DB_NAME%"=="%PROTECTED_DB%"'],
'README.md'=>['carrousel_helpdesk','helpdesk_carrousel','Base histórica protegida','database/INSTALAR.sql'],
'tools/AUDITAR_TABLAS_HELPDESK.php'=>["'carrousel_helpdesk'"],
'app/Core/Auth.php'=>['isSupportOperator','isManagementViewer','profileLabel'],
'app/Services/ScopeService.php'=>['ticketConstraint','SUPERVISOR','scopeLabel'],
'app/Views/tickets/feedback.php'=>['¿Tu problema quedó resuelto?','Calificar y cerrar','Devolver a soporte','nps_score'],
'app/Controllers/ResolutionController.php'=>['tickets.resolve','RESOLUTION_RECORDED','tickets/feedback?id=','confirmación del solicitante'],
'app/Controllers/TicketFeedbackController.php'=>['public function submit','public function reopen','ticket_feedback','TICKET_FEEDBACK_SUBMITTED','TICKET_REOPENED_BY_REQUESTER'],
'app/Views/admin/users.php'=>['+ Dar acceso','Acceso interno','Dónde trabaja','Retirar acceso','data-assignment-type','<table','data-label='],
'app/Views/admin/audit.php'=>['<table','data-label='],
'app/Views/management/support_team.php'=>['<table','data-label='],
'app/Views/admin/externals.php'=>['<table','data-label='],
'app/Views/management/external_report.php'=>['<table','data-label='],
'app/Views/shared/app_end.php'=>['layout-density-v25.css','data-tables.css','table-normalization.js'],
'public/assets/css/data-tables.css'=>['.data-table','content:attr(data-label)','min-width:0!important'],
'public/assets/css/itsm-classification.css'=>['.ticket-classification-form{display:grid','align-items:start','.classification-submit{align-self:end}'],
'public/assets/js/table-normalization.js'=>['.content table:not([data-table-skip])','data-label'],
'public/index.php'=>['/tickets/feedback','/tickets/feedback/reopen','/tickets/resolve','/gestion/informes/exportar','/admin/users/create','/admin/users/assign','/admin/users/delete']];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle)check(is_string($content)&&str_contains($content,$needle),$file.' contiene '.$needle);}

$allowedSql=[
'INSTALAR.sql',
'VERIFICAR_INSTALACION.sql',
'VERIFICAR_ESTABILIDAD_V2.sql',
'MIGRAR_TICKET_WORK_REPORTS_20260912.sql',
'VERIFICAR_TICKET_WORK_REPORTS_20260912.sql',
'MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql',
'VERIFICAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql',
'MIGRAR_FASE5_ACTIVIDADES_20260913.sql',
'VERIFICAR_FASE5_ACTIVIDADES_20260913.sql',
'MIGRAR_FASE9_CONOCIMIENTO_20260916.sql',
'VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql',
'VERIFICAR_FASE12_BD_20260919.sql',
'VERIFICAR_FASE12_SEGURIDAD_20260919.sql',
'VERIFICAR_FASE12_COMUNICACION_20260919.sql',
'VERIFICAR_PRODUCCION_LIMPIA.sql',
];
$legacySql=[];
foreach(glob($root.'/database/*.sql')?:[] as $file){$name=basename($file);if(!in_array($name,$allowedSql,true))$legacySql[]=$name;}
check($legacySql===[],'database/ no contiene SQL historicos o parches no autorizados'.($legacySql?' → '.implode(', ',$legacySql):''));

$legacyDb='helpdesk_carrousel'.'_test';
$legacyFinalize='FINALIZAR_ESQUEMA'.'_V2.sql';
$legacyCatalogs='CATALOGOS_'.'CARROUSEL.sql';
$legacyUpdate='ACTUALIZAR'.'_';
$legacyPhase='INSTALAR_'.'FASE1.sql';
$legacyImport='IMPORTAR_DESDE_'.'CAJA_CHICA.sql';
$forbidden=[
'config/config.php'=>[$legacyDb,"?? 'helpdesk_carrousel'","session_name('helpdesk_carrousel_session')"],
'config/local.php.example'=>[$legacyDb,"'db_name' => 'helpdesk_carrousel'"],
'tools/AUDITAR_TABLAS_HELPDESK.php'=>[$legacyDb,"?? 'helpdesk_carrousel'"],
'database/INSTALAR.sql'=>['CREATE DATABASE IF NOT EXISTS helpdesk_carrousel','USE helpdesk_carrousel'],
'database/VERIFICAR_INSTALACION.sql'=>['USE helpdesk_carrousel'],
'database/VERIFICAR_ESTABILIDAD_V2.sql'=>['USE helpdesk_carrousel'],
'INSTALAR_PC_TEST.bat'=>[$legacyFinalize,$legacyCatalogs,$legacyDb,'set "DB_NAME=helpdesk_carrousel"'],
'README.md'=>[$legacyFinalize,$legacyCatalogs,$legacyDb,$legacyUpdate,$legacyPhase,$legacyImport],
'app/Views/admin/users.php'=>['+ Nuevo usuario'],
'app/Views/shared/app_end.php'=>['sticky-footer.css']];
foreach($forbidden as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle)check(!(is_string($content)&&str_contains($content,$needle)),$file.' no contiene '.$needle);}
check(!is_file($root.'/public/assets/css/sticky-footer.css'),'No existe public/assets/css/sticky-footer.css');
exit($ok?0:1);