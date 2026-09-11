<?php
declare(strict_types=1);
$root=$argv[1]??dirname(__DIR__);
$fails=0;
function body(string $p):string{$v=@file_get_contents($p);return is_string($v)?$v:'';}
function ok(bool $c,string $m):void{global $fails;echo ($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$fails++;}
$notif=body($root.'/public/assets/js/notifications.js');
$appStart=body($root.'/app/Views/shared/app_start.php');
$tour=body($root.'/public/assets/js/help-tour.js');
$manual=body($root.'/app/Views/help/manual.php');
$publicHome=body($root.'/app/Views/tickets/public_home.php');
$publicCreate=body($root.'/app/Views/tickets/public_create.php');
$dashboard=body($root.'/app/Views/dashboard/index.php');

ok(str_contains($appStart,'data-notification-base-url='),'Campanita expone base de la instancia actual');
ok(str_contains($appStart,'data-notification-fallback-url='),'Campanita expone destino seguro de respaldo');
ok(str_contains($appStart,'data-notification-ticket-id='),'Notificación conserva ticket_id para recuperación de destino');
ok(str_contains($notif,'INTERNAL_ROUTE_MARKERS'),'Resolvedor cubre rutas internas más allá de tickets/view');
ok(str_contains($notif,'notificationTicketFallback'),'Campanita reconstruye ticket cuando action_url no es utilizable');
ok(str_contains($notif,"link.getAttribute('href')||''"),'Campanita resuelve el href original antes de navegar');
ok(str_contains($notif,'protocol!==\'http:\'&&destination.protocol!==\'https:\''),'Campanita rechaza protocolos inseguros');

ok(!str_contains($tour,"['.public-form-head'"),'Tutorial ya no depende del selector legacy public-form-head');
ok(!str_contains($tour,'[data-public-step="classification"]'),'Tutorial ya no depende del paso classification eliminado');
ok(str_contains($tour,"['.public-request-heading'"),'Tutorial usa encabezado actual de Solicitar ayuda');
ok(str_contains($tour,"'#requester_topic'"),'Tutorial explica el selector humano actual');
ok(str_contains($tour,'function resolveTour()'),'Tutorial audita pasos encontrados y faltantes');
ok(str_contains($tour,'Recorrido adaptado'),'Tutorial avisa cuando una pantalla no contiene todos los pasos');
ok(str_contains($tour,"requester_home:["),'Tutorial cubre panel del solicitante');
ok(str_contains($tour,"external_home:["),'Tutorial cubre panel del proveedor');
ok(str_contains($tour,"my_tickets:["),'Tutorial cubre Mis solicitudes / Mis casos');
ok(str_contains($tour,"public_home:["),'Tutorial cubre portada pública');
ok(str_contains($publicHome,'assets/js/help-tour.js'),'Portada pública carga el tutorial guiado');
ok(str_contains($dashboard,"\$isExternal?'external_home':'requester_home'"),'Dashboard usa contexto específico para proveedor y solicitante');

ok(str_contains($manual,'Notificaciones y campanita'),'Manual explica de forma explícita la campanita');
ok(str_contains($manual,'Convertir entre usuario interno y proveedor externo'),'Manual documenta la conversión reversible');
ok(str_contains($manual,'no asigna tickets, no cambia permisos ni modifica el alcance'),'Manual define Responsable directo sin ambigüedad');
ok(!str_contains($manual,'Caja Chica/NIT, Payout/Kiddies, Tickets Destruidos'),'Manual no congela un catálogo viejo de categorías');
ok(str_contains($manual,'El catálogo se mantiene actualizado'),'Manual remite al catálogo dinámico actual');
ok(str_contains($publicCreate,"20260911-UXHELP1"),'Solicitar ayuda fuerza versión nueva del tutorial');

if($fails){fwrite(STDERR,"\n[ERROR] {$fails} validación(es) fallaron.\n");exit(1);}echo "\n[OK] Regresión UX ayuda/notificaciones completada.\n";
