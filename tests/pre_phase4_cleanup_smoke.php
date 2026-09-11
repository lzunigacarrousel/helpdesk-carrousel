<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ok=true;
function cleanupCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

$notification=(string)@file_get_contents($root.'/app/Services/NotificationService.php');
$supportController=(string)@file_get_contents($root.'/app/Controllers/SupportTeamController.php');
$supportView=(string)@file_get_contents($root.'/app/Views/management/support_team.php');
$routes=(string)@file_get_contents($root.'/public/index.php');
$ticketsIndex=(string)@file_get_contents($root.'/app/Views/tickets/index.php');
$dashboard=(string)@file_get_contents($root.'/app/Views/dashboard/index.php');
$knowledge=(string)@file_get_contents($root.'/app/Views/knowledge/index.php');
$problems=(string)@file_get_contents($root.'/app/Views/problems/index.php');

cleanupCheck(str_contains($notification,'support_team_members'),'Notificaciones de soporte usan support_team_members');
cleanupCheck(!str_contains($notification,'SUPPORT_GROUP_EMAIL'),'Notificaciones ya no dependen del correo grupal fijo');
cleanupCheck(str_contains($notification,'email_channel')&&str_contains($notification,'true,true'),'Integrantes de soporte reciben correo e in-app');
cleanupCheck(str_contains($supportController,'support_team_members'),'Equipo de soporte se obtiene de la membresía real');
cleanupCheck(str_contains($supportController,'function addMember')||str_contains($supportController,'function add'),'Equipo de soporte permite agregar integrantes');
cleanupCheck(str_contains($supportController,'function removeMember')||str_contains($supportController,'function remove'),'Equipo de soporte permite retirar integrantes');
cleanupCheck(str_contains($routes,'/gestion/equipo/agregar'),'Existe ruta backend para agregar integrante');
cleanupCheck(str_contains($routes,'/gestion/equipo/retirar'),'Existe ruta backend para retirar integrante');
cleanupCheck(str_contains($supportView,'_csrf'),'Edición del equipo conserva CSRF');
cleanupCheck(str_contains($supportView,'Agregar integrante'),'Pantalla permite administrar destinatarios de soporte');
cleanupCheck(str_contains($supportView,'Recibe avisos por correo'),'Pantalla explica quién recibe correos');

cleanupCheck(substr_count($ticketsIndex,'/crear-ticket')===1,'Mis solicitudes no repite el CTA Nueva solicitud');
cleanupCheck(substr_count($dashboard,'/crear-ticket')===1,'Inicio de solicitante no repite el CTA Nueva solicitud');
cleanupCheck(substr_count($knowledge,'/knowledge/new')===1,'Base de conocimiento no repite Nuevo artículo');
cleanupCheck(substr_count($problems,'/problems/new')===1,'Problemas conocidos no repite Nuevo problema');

exit($ok?0:1);
