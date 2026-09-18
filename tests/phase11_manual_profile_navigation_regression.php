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
$components=(string)file_get_contents($root.'/public/assets/css/components.css');
$refresh=(string)file_get_contents($root.'/public/assets/css/ui-refresh.css');
$readme=(string)file_get_contents($root.'/README.md');

foreach([
    "'key'=>'requester'",
    "'key'=>'technician'",
    "'key'=>'supervisor'",
    "'key'=>'management'",
    "'key'=>'admin'",
    "'key'=>'collaborator'",
] as $profile){
    ok(str_contains($controller,$profile),"HelpController define perfil {$profile}");
}
ok(str_contains($controller,"'manualProfile'=>\$manualProfile"),'Controller entrega contexto de perfil al Manual');

ok(str_contains($view,'manual-profile-card'),'Manual muestra contexto del perfil');
ok(str_contains($view,'Manual para <?= htmlspecialchars($manualProfile[\'label\']) ?>'),'Manual nombra el perfil actual');
ok(str_contains($view,'data-manual-topics'),'Manual incorpora navegación por temas');
ok(str_contains($view,'data-manual-topic="all"'),'Filtro incluye Todo');
ok(str_contains($view,'data-manual-topic="solicitudes"'),'Filtro incluye Solicitudes');
ok(str_contains($view,'data-manual-topic="soporte"'),'Soporte aparece de forma condicionada');
ok(str_contains($view,'data-manual-topic="conocimiento"'),'Conocimiento aparece de forma condicionada');
ok(str_contains($view,'data-manual-topic="gestion"'),'Gestión aparece de forma condicionada');
ok(str_contains($view,'data-manual-topic="administracion"'),'Administración aparece de forma condicionada');
ok(str_contains($view,'data-manual-topic="ayuda"'),'Filtro incluye Ayuda y FAQ');
ok(str_contains($view,'data-manual-clear'),'Manual permite limpiar búsqueda/filtros');

foreach(['inicio','notificaciones','solicitudes','actividades','agenda','soporte','conocimiento','gestion','administracion','preguntas'] as $section){
    ok(str_contains($view,'id="'.$section.'" data-manual-section data-manual-topics='),"Sección {$section} declara tema");
}

ok(str_contains($view,"const requestedTopic=(params.get('topic')||'all').trim()"),'JS acepta tema inicial desde enlace profundo');
ok(str_contains($view,"let activeTopic=topicButtons.some(el=>el.dataset.manualTopic===requestedTopic)?requestedTopic:'all'"),'JS mantiene tema activo con fallback Todo');
ok(str_contains($view,"topics.includes(activeTopic)"),'JS combina sección y tema');
ok(str_contains($view,'normalize(section.textContent).includes(q)'),'JS combina búsqueda textual');
ok(str_contains($view,"item.setAttribute('aria-pressed'"),'Filtro expone estado accesible');
ok(str_contains($view,"link.classList.toggle('is-hidden'"),'Índice se sincroniza con resultados');
ok(str_contains($view,"input.focus();"),'Limpiar devuelve foco al buscador');

ok(str_contains($components,'.manual-profile-card'),'CSS define contexto de perfil');
ok(str_contains($components,'.manual-topic-filter'),'CSS define filtros de tema');
ok(str_contains($components,'.manual-topic.is-active'),'CSS distingue tema activo');
ok(str_contains($components,'@media(max-width:760px)'),'Manual conserva responsive tablet/móvil');
ok(str_contains($components,'.manual-topic-filter{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}'),'Temas se adaptan a móvil');
ok(str_contains($refresh,'grid-template-columns:minmax(0,1fr) auto auto'),'Buscador contempla Limpiar + estado');
ok(str_contains($refresh,'.manual-search-status{grid-column:1/-1}'),'Estado de búsqueda se adapta en móvil');

ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Task 1 no introduce migración de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Perfil y navegación por tareas del Manual Fase 11 consolidados.'.PHP_EOL;
