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

$widget=(string)file_get_contents($root.'/app/Views/shared/help_widget.php');
$manual=(string)file_get_contents($root.'/app/Views/help/manual.php');

foreach([
    "'requester_home'=>['anchor'=>'solicitudes'",
    "'external_home'=>['anchor'=>'solicitudes'",
    "'support_dashboard'=>['anchor'=>'soporte'",
    "'support_center'=>['anchor'=>'soporte'",
    "'problems'=>['anchor'=>'conocimiento'",
    "'knowledge'=>['anchor'=>'conocimiento'",
    "'externals'=>['anchor'=>'administracion'",
    "'users'=>['anchor'=>'administracion'",
    "'mail'=>['anchor'=>'administracion'",
    "'agenda'=>['anchor'=>'agenda'",
] as $target){
    ok(str_contains($widget,$target),'Existe destino contextual '.$target);
}

ok(str_contains($widget,"'faq'=>'SLA'"),'Dashboard soporte enlaza FAQ de SLA');
ok(str_contains($widget,"'faq'=>'espera'"),'Centro de soporte enlaza FAQ de espera');
ok(str_contains($widget,"'faq'=>'problema conocido'"),'Problemas enlaza FAQ contextual');
ok(str_contains($widget,"'faq'=>'Perfil, asignación'"),'Usuarios enlaza FAQ de perfiles');
ok(str_contains($widget,"'faq'=>'correo'"),'Correo enlaza FAQ contextual');

ok(str_contains($widget,"$helpContext==='ticket'"),'Ticket adapta ayuda según perfil');
ok(str_contains($widget,"'title'=>'Seguimiento del caso compartido'"),'Colaborador recibe ayuda específica de ticket');
ok(str_contains($widget,"'title'=>'Consulta del caso'"),'Gerencia/Supervisión reciben ayuda de consulta');
ok(str_contains($widget,"'title'=>'Tu solicitud'"),'Solicitante recibe ayuda propia');
ok(str_contains($widget,"'title'=>'Atención del caso'"),'Soporte conserva ayuda operativa');
ok(str_contains($widget,'Las notas internas del equipo no forman parte de tu acceso.'),'Ayuda externa protege notas internas');
ok(str_contains($widget,'la atención y los cambios operativos corresponden al equipo de soporte'),'Ayuda de gestión no invita a operar');

ok(str_contains($widget,"'agenda' => ["),'Agenda tiene ayuda rápida propia');
ok(str_contains($widget,'Abrir manual aquí'),'Widget ofrece acceso contextual al Manual');
ok(str_contains($widget,'Preguntas de esta pantalla'),'Widget ofrece FAQ solo cuando aplica');
ok(str_contains($widget,"http_build_query($manualParams)"),'Enlace al Manual conserva tema contextual');
ok(str_contains($widget,"http_build_query($faqParams)"),'Enlace FAQ pasa búsqueda contextual');
ok(str_contains($widget,"#preguntas"),'FAQ aterriza en sección de preguntas');
ok(str_contains($widget,"if($canOpenManual)"),'Manual sigue oculto sin sesión');

ok(str_contains($manual,'new URLSearchParams(window.location.search)'),'Manual lee parámetros de enlace profundo');
ok(str_contains($manual,"params.get('topic')"),'Manual lee tema solicitado');
ok(str_contains($manual,"params.get('q')"),'Manual lee búsqueda solicitada');
ok(str_contains($manual,'syncTopicButtons()'),'Manual sincroniza tema activo');
ok(str_contains($manual,"if(hash==='#preguntas'"),'Manual detecta FAQ contextual');
ok(str_contains($manual,"details:not(.is-hidden)"),'Manual localiza primera FAQ visible');
ok(str_contains($manual,'firstVisible.open=true'),'Manual abre automáticamente respuesta coincidente');
ok(str_contains($manual,'target.scrollIntoView'),'Manual lleva al ancla solicitada');

ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Task 3 no introduce migración de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Ayuda flotante y FAQ contextual de Fase 11 consolidadas.'.PHP_EOL;
