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
$mailService=(string)@file_get_contents($root.'/app/Services/MailService.php');
$mailView=(string)@file_get_contents($root.'/app/Views/admin/mail.php');
$supportController=(string)@file_get_contents($root.'/app/Controllers/SupportTeamController.php');
$supportView=(string)@file_get_contents($root.'/app/Views/management/support_team.php');
$routes=(string)@file_get_contents($root.'/public/index.php');
$ticketsIndex=(string)@file_get_contents($root.'/app/Views/tickets/index.php');
$queueView=(string)@file_get_contents($root.'/app/Views/tickets/queue.php');
$dashboard=(string)@file_get_contents($root.'/app/Views/dashboard/index.php');
$knowledge=(string)@file_get_contents($root.'/app/Views/knowledge/index.php');
$problems=(string)@file_get_contents($root.'/app/Views/problems/index.php');
$config=(string)@file_get_contents($root.'/config/config.php');
$configExample=(string)@file_get_contents($root.'/config/local.php.example');
$visualSystem=(string)@file_get_contents($root.'/public/assets/css/visual-system.css');
$dataTables=(string)@file_get_contents($root.'/public/assets/css/data-tables.css');
$appEnd=(string)@file_get_contents($root.'/app/Views/shared/app_end.php');

cleanupCheck(str_contains($notification,'support_team_members'),'Notificaciones de soporte usan support_team_members');
cleanupCheck(!str_contains($notification,'SUPPORT_GROUP_EMAIL'),'Notificaciones ya no dependen del correo grupal fijo');
cleanupCheck(!str_contains($mailService,'SUPPORT_GROUP_EMAIL'),'MailService no referencia la constante de correo grupal eliminada');
cleanupCheck(!str_contains($config,'SUPPORT_GROUP_EMAIL')&&!str_contains($configExample,'support_group_email'),'Configuración ya no publica un destinatario grupal fijo');
cleanupCheck(str_contains($mailService,'support_delivery'),'Diagnóstico de correo describe el destino dinámico del equipo de soporte');
cleanupCheck(str_contains($mailView,'support_delivery'),'Administración de correo muestra el destino dinámico de soporte');
cleanupCheck(str_contains($notification,'include_actor_email'),'La capa de notificaciones puede conservar correo para un actor cuando el flujo lo requiera');
cleanupCheck(str_contains($notification,'email_channel')&&str_contains($notification,'true,true'),'Integrantes de soporte reciben correo e in-app');
cleanupCheck(str_contains($supportController,'support_team_members'),'Equipo de soporte se obtiene de la membresía real');
cleanupCheck(str_contains($supportController,'function addMember')||str_contains($supportController,'function add'),'Equipo de soporte permite agregar integrantes');
cleanupCheck(str_contains($supportController,'function removeMember')||str_contains($supportController,'function remove'),'Equipo de soporte permite retirar integrantes');
cleanupCheck(str_contains($supportController,"Flash::pull()"),'Equipo de soporte muestra confirmaciones después de editar');
cleanupCheck(str_contains($supportController,'al menos un integrante activo'),'No se puede dejar el equipo sin destinatarios');
cleanupCheck(str_contains($routes,'/gestion/equipo/agregar'),'Existe ruta backend para agregar integrante');
cleanupCheck(str_contains($routes,'/gestion/equipo/retirar'),'Existe ruta backend para retirar integrante');
cleanupCheck(str_contains($supportView,'_csrf'),'Edición del equipo conserva CSRF');
cleanupCheck(str_contains($supportView,'Agregar integrante'),'Pantalla permite administrar destinatarios de soporte');
cleanupCheck(str_contains($supportView,'Recibe avisos por correo'),'Pantalla explica quién recibe correos');

cleanupCheck(substr_count($ticketsIndex,'/crear-ticket')===1,'Mis solicitudes no repite el CTA Nueva solicitud');
cleanupCheck(substr_count($dashboard,'/crear-ticket')===1,'Inicio de solicitante no repite el CTA Nueva solicitud');
cleanupCheck(substr_count($knowledge,'/knowledge/new')===1,'Base de conocimiento no repite Nuevo artículo');
cleanupCheck(substr_count($problems,'/problems/new')===1,'Problemas conocidos no repite Nuevo problema');
cleanupCheck(!str_contains($queueView,'<div class="queue-summary">'),'Centro de soporte no repite el conteo activo en el encabezado');
cleanupCheck(str_contains($queueView,'$showDescription'),'Centro de soporte no repite descripción cuando es igual al asunto');
cleanupCheck(str_contains($visualSystem,'a.btn.btn-primary:visited'),'Botones primarios mantienen texto blanco también cuando son enlaces visitados');
cleanupCheck(str_contains($dataTables,'.data-table a:not(.btn)'),'Tablas aplican color de enlace solo a enlaces que no son botones');
cleanupCheck(!str_contains($dataTables,'.data-table a{'),'Tablas no sobrescriben el color de los botones-enlace');
cleanupCheck(str_contains($appEnd,"data-tables.css?v=<?= htmlspecialchars(\$assetVersion"),'Tabla canónica usa el versionado global de assets');

// Auditoría global: una misma vista no debe renderizar dos enlaces de acción primaria
// con exactamente el mismo destino literal. Botones de formulario se excluyen porque
// pueden repetirse legítimamente por fila o por contexto.
$duplicatePrimary=[];
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Views',FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
    $content=(string)@file_get_contents($file->getPathname());
    preg_match_all('/<a\b[^>]*>/iu',$content,$matches);
    $destinations=[];
    foreach($matches[0]??[] as $anchor){
        if(!preg_match('/class\s*=\s*["\'][^"\']*\bbtn-primary\b[^"\']*["\']/iu',$anchor))continue;
        if(!preg_match('/href\s*=\s*["\']([^"\']+)["\']/iu',$anchor,$href))continue;
        $target=trim(html_entity_decode($href[1],ENT_QUOTES|ENT_HTML5,'UTF-8'));
        if($target==='')continue;
        $destinations[$target]=($destinations[$target]??0)+1;
    }
    foreach($destinations as $target=>$count){
        if($count>1)$duplicatePrimary[]=str_replace($root.'/','',$file->getPathname()).' -> '.$target.' x'.$count;
    }
}
if($duplicatePrimary){foreach($duplicatePrimary as $row)echo '[DUPLICADO] '.$row.PHP_EOL;}
cleanupCheck(!$duplicatePrimary,'Auditoría global no encuentra enlaces primarios duplicados en una misma vista');

exit($ok?0:1);
