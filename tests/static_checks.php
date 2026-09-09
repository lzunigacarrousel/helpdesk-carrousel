<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=[
    'bootstrap.php','config/config.php','config/local.php.example','database/INSTALAR_FASE1.sql',
    'database/ACTUALIZAR_FLUJO_ESPERA_P1.sql','database/ACTUALIZAR_ITSM_PROBLEMAS_CONOCIMIENTO_V2.sql','database/ACTUALIZAR_NOTIFICACIONES_EVENTOS_V2.sql','database/ACTUALIZAR_PERFILES_ALCANCES_V2.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
    'app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Services/NotificationService.php','app/Services/MailService.php','app/Core/Auth.php',
    'app/Controllers/SearchController.php','app/Controllers/WorkflowController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php','app/Controllers/HelpController.php','app/Controllers/NotificationController.php','app/Controllers/ManagementController.php','app/Controllers/XlsxExportController.php','app/Controllers/MailAdminController.php',
    'app/Views/search/index.php','app/Views/problems/index.php','app/Views/problems/form.php','app/Views/problems/show.php',
    'app/Views/knowledge/index.php','app/Views/knowledge/form.php','app/Views/knowledge/show.php','app/Views/help/manual.php','app/Views/tickets/public_create.php','app/Views/admin/mail.php',
    'public/assets/js/help-tour.js','public/assets/js/notifications.js','public/assets/css/components.css','public/assets/css/ui-refresh.css','public/index.php','HELPDESK_ADMIN.bat'
];
$ok=true;
foreach($required as $f){$exists=is_file($root.'/'.$f);echo ($exists?'[OK] ':'[FALTA] ').$f.PHP_EOL;$ok=$ok&&$exists;}

$checks=[
    'config/config.php'=>['APP_CANONICAL_URL','APP_CANONICAL_CONFIGURED','2.4.0-dev'],
    'config/local.php.example'=>["'app_url'","mail_mode","smtp_secure"],
    'public/index.php'=>['/problems','/knowledge','/manual','/notifications/read','/notifications/read-all','/admin/correo','/admin/correo/probar','/admin/correo/reintentar'],
    'app/Core/Auth.php'=>['isSupportOperator','isManagementViewer','profileLabel'],
    'app/Services/ScopeService.php'=>['ticketConstraint','SUPERVISOR','supportScopes','scopeLabel'],
    'app/Controllers/DashboardController.php'=>['isManagementViewer','/gestion','isSupportOperator','externalCollabStats','WAITING_PROVIDER'],
    'app/Controllers/ManagementController.php'=>['ScopeService','ticketConstraint','scopeLabel'],
    'app/Controllers/XlsxExportController.php'=>['ScopeService','ticketConstraint','scopeLabel'],
    'app/Views/management/dashboard.php'=>['Colaboración externa','waiting_provider','ticketConstraint'],
    'app/Views/shared/app_start.php'=>['Problemas conocidos','Base de conocimiento','Tutorial guiado','<span class="side-label">Manual</span>','Notificaciones','data-notification-link','profileLabel','isSupportOperator','isManagementViewer'],
    'app/Views/admin/users.php'=>['Perfiles predefinidos','Atiende soporte','No atiende tickets','Gerencia','Supervisor','Alcance:'],
    'app/Views/shared/help_widget.php'=>['Iniciar tutorial guiado','Abrir manual completo','Empieza con esta guía rápida','Correo y notificaciones'],
    'public/assets/js/help-tour.js'=>['public_create','Cuéntanos qué sucede','Conversaciones','manual-search','support_dashboard','reports','ticket','problems','knowledge','mail','tour-tip'],
    'public/assets/js/notifications.js'=>['data-notification-link','delivery_id','readAllUrl'],
    'public/assets/css/components.css'=>['public-problem-layout','public-reporting-help','manual-quick-grid','manual-faq','tour-tip'],
    'public/assets/css/ui-refresh.css'=>['--surface-soft','--surface-brand-soft','conversation-channel-label','dashboard-external-collab','manual-search'],
    'app/Views/tickets/public_create.php'=>['type="hidden" name="subject"','public-problem-layout','public-reporting-help','Cuéntanos qué sucede','data-public-step="submit"','assets/js/help-tour.js'],
    'app/Views/help/manual.php'=>['data-manual-search','manual-quick-grid','manual-faq','Preguntas frecuentes','Correo y notificaciones'],
    'app/Views/admin/mail.php'=>['Enviar correo de prueba','Entregas recientes','Reintentar','Modo prueba'],
    'app/Services/NotificationService.php'=>['IN_APP','notification_events','notification_deliveries','read_at','sendOtpDelivery','retryEmailDelivery','SKIPPED','normalizeActionUrl'],
    'app/Services/MailService.php'=>['addEmbeddedImage','carrousel-logo','cid:','Ver solicitud','linear-gradient','Mensaje automático','RESULT_LOGGED','configurationHealth','APP_CANONICAL_URL','Timeout=15'],
    'app/Services/AuthService.php'=>['sendOtpDelivery','$otpId'],
    'app/Controllers/MailAdminController.php'=>['MAIL_TEST','retryEmailDelivery','configurationHealth'],
    'app/Controllers/ExternalController.php'=>['NotificationService','EXTERNAL_USER_CREATED','email_status'],
    'app/Views/tickets/show.php'=>['Posibles soluciones','Problema conocido','Conversación interna','Solo equipo de soporte','Proveedor participando'],
    'app/Views/tickets/show_external.php'=>['Equipo de soporte','Enviar actualización','Conversación con soporte','Solución final'],
    'app/Views/dashboard/index.php'=>['dashboard-profile-stats','Colaboración externa','Proveedores participando'],
    'app/Controllers/ProblemController.php'=>['problems.manage','PROBLEM_LINKED'],
    'app/Controllers/KnowledgeController.php'=>['knowledge.manage','KNOWLEDGE_CREATED'],
    'app/Controllers/WorkflowController.php'=>['NotificationService','Estado actualizado'],
    'app/Controllers/ConversationController.php'=>['PUBLIC_RESPONSE_ADDED','INTERNAL_NOTE_ADDED'],
    'app/Controllers/ResolutionController.php'=>['RESOLUTION_RECORDED','NotificationService'],
    'database/VERIFICAR_ESTABILIDAD_V2.sql'=>['CORREOS_ENVIADOS_SIN_FECHA','CORREOS_FALLIDOS_SIN_MOTIVO','CORREOS_PENDIENTES_MAS_10_MIN','CORREOS_MODO_PRUEBA'],
];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo ($found?'[OK] ':'[FALTA] ').$file.' contiene '.$needle.PHP_EOL;$ok=$ok&&$found;}}

$forbidden=[
    'app/Views/dashboard/index.php'=>['Acceso restringido y seguro','Tu espacio de colaboración'],
    'app/Views/tickets/index.php'=>['Responsable Carrousel','Aún sin responsable Carrousel'],
    'app/Views/tickets/public_create.php'=>['Resumen breve'],
    'app/Views/tickets/show_external.php'=>['Mensaje para Carrousel','Enviar a Carrousel','¿Qué necesitas informar a Carrousel?','Disponible en este caso'],
    'app/Views/tickets/show.php'=>['Problem Management','workaround','Nota interna · Solo Soporte'],
    'app/Controllers/ExternalController.php'=>['new MailService'],
];
foreach($forbidden as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo (!$found?'[OK] ':'[NO DEBE ESTAR] ').$file.' no contiene '.$needle.PHP_EOL;$ok=$ok&&!$found;}}
exit($ok?0:1);
