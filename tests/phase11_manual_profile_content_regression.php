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

$controller=(string)file_get_contents($root.'/app/Controllers/HelpController.php');
$view=(string)file_get_contents($root.'/app/Views/help/manual.php');
$css=(string)file_get_contents($root.'/public/assets/css/components.css');

foreach([
    "'collaborator'=>[",
    "'technician'=>[",
    "'supervisor'=>[",
    "'management'=>[",
    "'admin'=>[",
    "default=>[",
] as $guide){
    ok(str_contains($controller,$guide),'Existe guía funcional '.$guide);
}

ok(str_contains($controller,"'workspace_href'=>APP_BASE_URL.'/mis-tickets'"),'Colaborador dirige a Mis casos');
ok(str_contains($controller,"'workspace_href'=>APP_BASE_URL.'/tickets/queue'"),'Técnico/Admin dirigen al Centro de soporte');
ok(str_contains($controller,"'workspace_href'=>APP_BASE_URL.'/gestion/informes'"),'Supervisor dirige a Informes');
ok(str_contains($controller,"'workspace_href'=>APP_BASE_URL.'/gestion'"),'Gerencia dirige al Dashboard interno');
ok(str_contains($controller,"'workspace_href'=>APP_BASE_URL.'/crear-ticket'"),'Solicitante dirige a Solicitar ayuda');

foreach([
    'canProviderReport',
    'canUsersManage',
    'canExternalManage',
    'canAudit',
    'canMailAdmin',
] as $cap){
    ok(str_contains($controller,"'".$cap."'=>\$".$cap),'Controller entrega capacidad '.$cap);
}

ok(str_contains($view,'manual-profile-guide'),'Manual muestra guía de tres pasos');
ok(str_contains($view,'Empieza aquí'),'Guía explica qué hacer primero');
ok(str_contains($view,'Tu espacio principal'),'Guía identifica espacio principal');
ok(str_contains($view,'Después'),'Guía explica qué ocurre después');

ok(str_contains($view,'if(!$isSupervisorProfile&&!$isManagementProfile)'),'Solicitudes se ocultan a perfiles de consulta');
ok(str_contains($view,'elseif($isSupervisorProfile||$isManagementProfile)'),'Accesos rápidos separan perfiles de consulta');
ok(str_contains($view,'elseif($isRequesterProfile)'),'Actividad visible al solicitante no se mezcla con Gestión');

ok(str_contains($view,'if($isTechnicianProfile)'),'Agenda distingue Técnico');
ok(str_contains($view,'if($isAdminProfile)'),'Agenda distingue Admin/Semiadmin');
ok(str_contains($view,'if($isSupervisorProfile)'),'Agenda distingue Supervisor');
ok(str_contains($view,'if($isManagementProfile)'),'Agenda distingue Gerencia');
ok(str_contains($view,'Seguimiento, no atención'),'Supervisor recibe instrucción de consulta');
ok(str_contains($view,'Lectura ejecutiva'),'Gerencia recibe instrucción ejecutiva');
ok(str_contains($view,'Sin operación de soporte'),'Gerencia no recibe flujo operativo');

ok(str_contains($view,'if($canProviderReport)'),'Informe de proveedores depende de acceso real');
ok(!str_contains($view,'if($canManagement||$canAdmin)'),'Proveedor ya no depende de condición genérica antigua');

ok(str_contains($view,'if($canUsersManage)'),'Usuarios depende de users.manage');
ok(str_contains($view,'if($canExternalManage)'),'Proveedores depende de external.manage');
ok(str_contains($view,'if($canAudit)'),'Auditoría depende de audit.view');
ok(str_contains($view,'if($canMailAdmin)'),'Correo depende de capacidad correspondiente');

ok(str_contains($view,'if($isRequesterProfile)'),'FAQ tiene bloque de Solicitante');
ok(str_contains($view,'if($isCollaboratorProfile)'),'FAQ tiene bloque de Colaborador');
ok(str_contains($view,'if($isSupport)'),'FAQ tiene bloque de Soporte');
ok(str_contains($view,'if($isSupervisorProfile)'),'FAQ tiene bloque de Supervisor');
ok(str_contains($view,'if($isManagementProfile)'),'FAQ tiene bloque de Gerencia');
ok(str_contains($view,'¿Puedo ver notas internas?'),'FAQ del colaborador aclara privacidad');
ok(str_contains($view,'La conversación interna es exclusivamente del equipo de soporte'),'FAQ de Soporte conserva canal privado');

ok(!str_contains($view,'</section>n>'),'Vista no contiene cierre residual');
ok(str_contains($css,'.manual-profile-guide'),'CSS cubre guía por perfil');
ok(str_contains($css,'.manual-profile-guide{grid-template-columns:1fr}'),'Guía pasa a una columna en móvil');
ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Task 2 no introduce migración de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contenido por perfil del Manual Fase 11 consolidado.'.PHP_EOL;
