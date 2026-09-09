<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=[
    'bootstrap.php','config/config.php','config/local.php.example','database/INSTALAR_FASE1.sql',
    'database/ACTUALIZAR_FLUJO_ESPERA_P1.sql','database/ACTUALIZAR_ITSM_PROBLEMAS_CONOCIMIENTO_V2.sql','database/ACTUALIZAR_NOTIFICACIONES_EVENTOS_V2.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
    'app/Services/AuthService.php','app/Services/ScopeService.php','app/Services/ProblemService.php','app/Services/SolutionSuggestionService.php','app/Services/NotificationService.php','app/Core/Auth.php',
    'app/Controllers/SearchController.php','app/Controllers/WorkflowController.php','app/Controllers/ProblemController.php','app/Controllers/KnowledgeController.php','app/Controllers/HelpController.php','app/Controllers/NotificationController.php',
    'app/Views/search/index.php','app/Views/problems/index.php','app/Views/problems/form.php','app/Views/problems/show.php',
    'app/Views/knowledge/index.php','app/Views/knowledge/form.php','app/Views/knowledge/show.php','app/Views/help/manual.php',
    'public/assets/js/help-tour.js','public/assets/js/notifications.js','public/assets/css/components.css','public/index.php','HELPDESK_ADMIN.bat'
];
$ok=true;
foreach($required as $f){$exists=is_file($root.'/'.$f);echo ($exists?'[OK] ':'[FALTA] ').$f.PHP_EOL;$ok=$ok&&$exists;}

$checks=[
    'public/index.php'=>['/problems','/knowledge','/manual','/notifications/read','/notifications/read-all'],
    'app/Views/shared/app_start.php'=>['Problemas conocidos','Base de conocimiento','Tutorial guiado','<span class="side-label">Manual</span>','Notificaciones','data-notification-link','userContextLabel'],
    'app/Views/shared/help_widget.php'=>['Iniciar tutorial guiado','Abrir manual completo'],
    'public/assets/js/help-tour.js'=>['support_dashboard','reports','ticket','problems','knowledge'],
    'public/assets/js/notifications.js'=>['data-notification-link','delivery_id','readAllUrl'],
    'app/Services/NotificationService.php'=>['IN_APP','notification_events','notification_deliveries','read_at'],
    'app/Services/MailService.php'=>['Abrir en Helpdesk','gradient','Mensaje automático'],
    'app/Views/tickets/show.php'=>['Posibles soluciones','Crear artículo desde solución','Problema conocido relacionado'],
    'app/Views/tickets/show_external.php'=>['Equipo de soporte','Enviar actualización','Disponible en este caso'],
    'app/Views/dashboard/index.php'=>['Tu espacio de colaboración','dashboard-profile-stats'],
    'app/Controllers/ProblemController.php'=>['problems.manage','PROBLEM_LINKED'],
    'app/Controllers/KnowledgeController.php'=>['knowledge.manage','KNOWLEDGE_CREATED'],
    'app/Controllers/WorkflowController.php'=>['NotificationService','Estado actualizado'],
    'app/Controllers/ConversationController.php'=>['PUBLIC_RESPONSE_ADDED','INTERNAL_NOTE_ADDED'],
    'app/Controllers/ResolutionController.php'=>['RESOLUTION_RECORDED','NotificationService'],
];
foreach($checks as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo ($found?'[OK] ':'[FALTA] ').$file.' contiene '.$needle.PHP_EOL;$ok=$ok&&$found;}}

$forbidden=[
    'app/Views/dashboard/index.php'=>['Acceso restringido y seguro'],
    'app/Views/tickets/index.php'=>['Responsable Carrousel','Aún sin responsable Carrousel'],
    'app/Views/tickets/show_external.php'=>['Mensaje para Carrousel','Enviar a Carrousel','¿Qué necesitas informar a Carrousel?'],
];
foreach($forbidden as $file=>$needles){$content=@file_get_contents($root.'/'.$file);foreach($needles as $needle){$found=is_string($content)&&str_contains($content,$needle);echo (!$found?'[OK] ':'[NO DEBE ESTAR] ').$file.' no contiene '.$needle.PHP_EOL;$ok=$ok&&!$found;}}
exit($ok?0:1);
