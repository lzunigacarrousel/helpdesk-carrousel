<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$guardPath=$root.'/public/assets/js/navigation-guards.js';
$appEnd=(string)@file_get_contents($root.'/app/Views/shared/app_end.php');
$appStart=(string)@file_get_contents($root.'/app/Views/shared/app_start.php');
$app=(string)@file_get_contents($root.'/public/assets/js/app.js');
$notifications=(string)@file_get_contents($root.'/public/assets/js/notifications.js');
$show=(string)@file_get_contents($root.'/app/Views/tickets/show.php');
$showExternal=(string)@file_get_contents($root.'/app/Views/tickets/show_external.php');
$guard=is_file($guardPath)?(string)file_get_contents($guardPath):'';
$ok=true;

function navCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

navCheck(is_file($guardPath),'Existe guardia de navegacion para descargas');
navCheck(str_contains($appEnd,'assets/js/navigation-guards.js'),'Shell carga la guardia de navegacion');
navCheck(str_contains($guard,"/tickets/attachment"),'La guardia reconoce adjuntos de ticket');
navCheck(str_contains($guard,"data-no-loading"),'Los adjuntos se excluyen del overlay global antes del click');
navCheck(str_contains($guard,"addEventListener('click'")&&str_contains($guard,',true)'),'La exclusión se aplica en fase capture antes de app.js');

// Regresión del defecto detectado manualmente: la exclusión debe vivir también en el
// controlador global del overlay y en el markup, no depender solo de mutar el DOM durante el click.
navCheck(str_contains($app,'isTicketAttachmentLink'),'app.js reconoce directamente la ruta de adjuntos');
navCheck(str_contains($app,"/tickets/attachment"),'app.js excluye explícitamente descargas de tickets');
navCheck(str_contains($show,'data-no-loading="1"'),'Workspace interno marca adjuntos como descarga sin overlay');
navCheck(str_contains($showExternal,'data-no-loading="1"'),'Workspace proveedor marca adjuntos como descarga sin overlay');
navCheck(str_contains($appStart,'filemtime'),'Versionado de assets cambia cuando cambia el código estático');
navCheck(!str_contains($appStart,'20260909-024'),'Versionado de assets ya no queda congelado en una cadena fija');

navCheck(str_contains($notifications,'normalizeNotificationHref'),'Notificaciones normalizan el destino antes de navegar');
navCheck(str_contains($notifications,"/tickets/view"),'Notificaciones de ticket conservan el destino al caso');
navCheck(str_contains($notifications,'keepalive:true'),'Marcar leida puede continuar durante la navegacion');
navCheck(!str_contains($notifications,'await post(readUrl'),'Marcar leida no bloquea la redireccion del usuario');
navCheck(!str_contains($notifications,"event.preventDefault();event.stopPropagation();\n    await post(readUrl"),'Click de notificacion no secuestra la navegacion');

exit($ok?0:1);
