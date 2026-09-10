<?php
declare(strict_types=1);
$root=dirname(__DIR__);$ok=true;
$required=[
'bootstrap.php','config/config.php','config/local.php.example',
'database/INSTALAR.sql','database/FINALIZAR_ESQUEMA_V2.sql','database/CATALOGOS_CARROUSEL.sql','database/VERIFICAR_INSTALACION.sql','database/VERIFICAR_ESTABILIDAD_V2.sql','database/ACTUALIZAR_NPS_CIERRE_V2.sql',
'INSTALAR_PC_TEST.bat','README.md','docs/ESTANDAR_VISUAL_CARROUSEL.md',
'app/Core/Auth.php','app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/NotificationService.php','app/Services/MailService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Services/XlsxExportService.php',
'app/Controllers/DashboardController.php','app/Controllers/WorkflowController.php','app/Controllers/ConversationController.php','app/Controllers/ResolutionController.php','app/Controllers/TicketFeedbackController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php','app/Controllers/MailAdminController.php','app/Controllers/AdminController.php',
'app/Views/dashboard/index.php','app/Views/management/dashboard.php','app/Views/tickets/public_create.php','app/Views/tickets/index.php','app/Views/tickets/feedback.php','app/Views/tickets/show.php','app/Views/tickets/show_external.php','app/Views/help/manual.php','app/Views/admin/mail.php','app/Views/admin/users.php','app/Views/shared/app_start.php','app/Views/shared/help_widget.php',
'public/assets/js/help-tour.js','public/assets/js/notifications.js','public/assets/css/ui-refresh.css','public/assets/css/components.css','public/assets/css/visual-system.css','public/index.php','HELPDESK_ADMIN.bat'];
foreach($required as $file){$exists=is_file($root.'/'.$file);echo ($exists?'[OK] ':'[FALTA] ').$file.PHP_EOL;$ok=$ok&&$exists;}
$checks=[
'config/config.php'=>['2.4.0-dev','APP_CANONICAL_URL','helpdesk_carrousel_test'],
'app/Core/Auth.php'=>['isSupportOperator','isManagementViewer','profileLabel'],
'app/Services/ScopeService.php'=>['ticketConstraint','SUPERVISOR','scopeLabel'],
'app/Controllers/DashboardController.php'=>['externalCollabStats','WAITING_PROVIDER'],
'app/Views/dashboard/index.php'=>['Colaboración externa','Proveedores participando'],
'app/Views/management/dashboard.php'=>['Colaboración externa','ticketConstraint','Esperando proveedor'],
'app/Views/tickets/public_create.php'=>['type="hidden" name="subject"','<strong>Problema</strong>','public-inline-help','data-public-step="submit"'],
'app/Views/tickets/index.php'=>['Revisar solución y cerrar','ticket-awaiting-confirmation'],
'app/Views/tickets/feedback.php'=>['¿Quedó resuelto?','Calificar y cerrar','Devolver a soporte','nps_score'],
'app/Views/tickets/show.php'=>['Posibles soluciones','Problema conocido','Conversación interna','Solo equipo de soporte','Proveedor participando'],
'app/Views/tickets/show_external.php'=>['Conversación con soporte','Enviar actualización','Solución final','author_display_name','conversation-author-role'],
'app/Views/help/manual.php'=>['data-manual-search','manual-faq','Preguntas frecuentes','Correo y notificaciones'],
'public/assets/js/help-tour.js'=>['Conversaciones','manual-search','support_dashboard','mail'],
'public/assets/css/ui-refresh.css'=>['--surface-soft','--surface-brand-soft','conversation-channel-label','dashboard-external-collab','manual-search'],
'public/assets/css/visual-system.css'=>['--vs-max','admin-users-heading','admin-user-danger-zone','help-tour-hint'],
'app/Services/MailService.php'=>['addEmbeddedImage','carrousel-logo','cid:','Ver solicitud','RESULT_LOGGED','configurationHealth','Timeout=15'],
'app/Services/NotificationService.php'=>['notification_events','notification_deliveries','sendOtpDelivery','retryEmailDelivery','SKIPPED'],
'app/Controllers/ConversationController.php'=>['PUBLIC_RESPONSE_ADDED','INTERNAL_NOTE_ADDED'],
'app/Controllers/WorkflowController.php'=>['WAITING_PROVIDER','pendiente de confirmación del solicitante'],
'app/Controllers/ResolutionController.php'=>['tickets.resolve','RESOLUTION_RECORDED','tickets/feedback?id=','confirmación del solicitante'],
'app/Controllers/TicketFeedbackController.php'=>['public function submit','public function reopen','ticket_feedback','TICKET_FEEDBACK_SUBMITTED','TICKET_REOPENED_BY_REQUESTER'],
'app/Controllers/AdminController.php'=>['public function create','public function assign','public function delete','USER_CREATED','USER_UPDATED','USER_DELETED','assertAnotherActiveAdmin'],
'app/Views/admin/users.php'=>['+ Dar acceso','Acceso interno','Dónde trabaja','Retirar acceso','data-assignment-type'],
'app/Views/admin/mail.php'=>['Enviar correo de prueba','Entregas recientes','Reintentar','Modo prueba'],
'app/Views/shared/help_widget.php'=>['Administrar usuarios','Dar acceso','Abrir manual'],
'public/index.php'=>['/tickets/feedback','/tickets/feedback/reopen','/tickets/resolve','/gestion/informes/exportar','/admin/users/create','/admin/users/assign','/admin/users/delete'],
'database/ACTUALIZAR_NPS_CIERRE_V2.sql'=>['CREATE TABLE IF NOT EXISTS ticket_feedback','nps_score','2026-09-09_nps_cierre_v2'],
'database/FINALIZAR_ESQUEMA_V2.sql'=>['CREATE TABLE schema_migrations','CREATE TABLE positions','CREATE TABLE ticket_resolutions','CREATE TABLE ticket_feedback','CREATE TABLE external_profiles','management.view','external.manage','tickets.resolve','trg_tickets_require_resolution'],
'database/CATALOGOS_CARROUSEL.sql'=>['CC_REGION_6','CC_REGION_7','CC_REGION_8','CC_REGION_9','CC_PARK_504','CC_PARK_516'],
'database/VERIFICAR_INSTALACION.sql'=>['required_tables','required_columns','tablas no canonicas/legacy','trg_tickets_require_resolution','OK - INSTALACION LIMPIA HELPDESK CARROUSEL V2'],
'INSTALAR_PC_TEST.bat'=>['REINSTALAR','FINALIZAR_ESQUEMA_V2.sql','CATALOGOS_CARROUSEL.sql','VERIFICAR_INSTALACION.sql','helpdesk_carrousel: NO SE TOCA'],
'README.md'=>['INSTALAR_PC_TEST.bat','helpdesk_carrousel_test','FINALIZAR_ESQUEMA_V2.sql','CATALOGOS_CARROUSEL.sql'],
'database/VERIFICAR_ESTABILIDAD_V2.sql'=>['CORREOS_ENVIADOS_SIN_FECHA','CORREOS_FALLIDOS_SIN_MOTIVO','CORREOS_PENDIENTES_MAS_10_MIN']];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo ($found?'[OK] ':'[FALTA] ').$file.' contiene '.$needle.PHP_EOL;$ok=$ok&&$found;}}
$forbidden=[
'app/Views/tickets/public_create.php'=>['Resumen breve','public-reporting-help','No necesitas conocer la causa técnica'],
'app/Views/dashboard/index.php'=>['Acceso restringido y seguro','Tu espacio de colaboración'],
'app/Views/tickets/show_external.php'=>['Mensaje para Carrousel','Enviar a Carrousel','Disponible en este caso','Tu equipo</strong>'],
'app/Views/tickets/show.php'=>['Problem Management','Nota interna · Solo Soporte'],
'app/Views/admin/users.php'=>['Perfiles predefinidos','Qué hace cada perfil','Selecciona el perfil según la responsabilidad real','Nuevo acceso</span><h2>Crear usuario','Tipo de ubicación','+ Nuevo usuario'],
'app/Controllers/ExternalController.php'=>['new MailService'],
'README.md'=>['helpdesk360_test','C:\\xampp\\htdocs\\Helpdesk360']];
foreach($forbidden as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo (!$found?'[OK] ':'[NO DEBE ESTAR] ').$file.' no contiene '.$needle.PHP_EOL;$ok=$ok&&!$found;}}
exit($ok?0:1);