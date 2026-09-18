<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controller=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$index=(string)file_get_contents($root.'/app/Views/knowledge/index.php');
$form=(string)file_get_contents($root.'/app/Views/knowledge/form.php');
$show=(string)file_get_contents($root.'/app/Views/knowledge/show.php');
$manual=(string)file_get_contents($root.'/app/Views/help/manual.php');
$helpController=(string)file_get_contents($root.'/app/Controllers/HelpController.php');
$tour=(string)file_get_contents($root.'/public/assets/js/help-tour.js');
$caseFocus=(string)file_get_contents($root.'/public/assets/css/case-focus.css');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok(str_contains($controller,'current_internal_revision_id'),'Lectura interna usa puntero interno');
ok(str_contains($controller,'current_public_revision_id'),'Lectura solicitante usa puntero público');
ok(str_contains($controller,'knowledge_revisions'),'Controller lee revisiones');
ok(str_contains($show,'Publicado para soporte'),'UI distingue publicación interna');
ok(str_contains($show,'Disponible para solicitantes'),'UI distingue publicación pública');
ok(
    str_contains($controller,"'IN_REVIEW'=>'En revisión'")
    && str_contains($show,'$stateLabel'),
    'UI muestra estado editorial legible mediante el mapa de estados'
);
ok(!str_contains(strtolower($show),'visibility'),'Detalle no expone tecnicismo visibility');
ok(!str_contains(strtolower($show),'scope'),'Detalle no expone tecnicismo scope');
ok(!str_contains($form,'name="visibility"'),'Formulario ya no pide visibilidad legacy');
ok(str_contains($form,'Guardar borrador'),'Formulario conserva acción principal clara');
ok(str_contains($index,'Disponible para solicitantes'),'Listado identifica disponibilidad pública con copy legible');
ok(!str_contains($index,'Toda visibilidad'),'Listado elimina filtro legacy de visibilidad');
ok(str_contains($show,'Más acciones'),'Acciones secundarias se agrupan');
ok(str_contains($show,'No disponible (archivado)'),'Artículo archivado no aparenta seguir publicado');
ok(str_contains($show,'$hasSecondaryActions'),'Menú secundario solo aparece cuando tiene acciones');
ok(str_contains($show,'Ver historial'),'Detalle ofrece acceso real al historial');
ok(!str_contains($show,"<?php if(\$workingState==='IN_REVIEW'): ?> · En revisión<?php endif; ?>"),'Estado En revisión no se duplica');
ok(str_contains($controller,"ka.lifecycle_status='ACTIVE' AND ka.current_public_revision_id IS NOT NULL"),'Listado no marca archivados como disponibles públicamente');
ok(str_contains($index,'knowledge-filter-grid'),'Listado usa grid de filtros propio de conocimiento');
foreach([
    '.knowledge-article-page .page-heading',
    '.knowledge-state-bar .card-body',
    '.knowledge-actions-menu>.card',
    '.knowledge-layout',
    '.knowledge-card .card-body',
    '.knowledge-filter-grid:has(select[name="status"])',
    '@media(max-width:900px)',
    '@media(max-width:760px)',
] as $selector){
    ok(str_contains($caseFocus,$selector),"CSS de conocimiento incluye {$selector}");
}
ok(str_contains($caseFocus,'position:absolute;right:0;top:calc(100% + 8px)'), 'Más acciones funciona como popover sin deformar la barra editorial');
ok(str_contains($caseFocus,'grid-auto-rows:1fr'), 'Cards de conocimiento mantienen altura alineada');
ok(str_contains($caseFocus,'position:sticky;top:84px'), 'Panel lateral permanece alineado en escritorio');
ok(str_contains((string)file_get_contents($root.'/app/Views/knowledge/history.php'),"(\$article['lifecycle_status']??'ACTIVE')!=='ARCHIVED'"),'Historial no ofrece restauración directa de artículo archivado');

foreach([
    'Crear borrador',
    'Enviar a revisión',
    'Publicado para soporte',
    'Disponible para solicitantes',
    'Comparar versiones',
    'Restaurar versión',
    'Usar como referencia',
] as $concept){
    ok(str_contains($manual,$concept),"Manual explica {$concept}");
}
foreach([
    'canKnowledgeDraft',
    'canKnowledgeReview',
    'canKnowledgePublishInternal',
    'canKnowledgePublishPublic',
    'canKnowledgeHistory',
    'canKnowledgeRestore',
] as $capability){
    ok(str_contains($helpController,$capability),"HelpController expone {$capability}");
}
ok(str_contains($manual,'$canKnowledge&&!$canKnowledgeDraft'),'Solicitante recibe ayuda sin instrucciones editoriales');
ok(str_contains($manual,'$canKnowledgeDraft'),'Crear borrador depende de capacidad editorial');
ok(str_contains($manual,'$canKnowledgePublishPublic'),'Publicación para solicitantes depende de capacidad administrativa');
ok(str_contains($manual,'$canKnowledgeRestore'),'Restauración depende de capacidad administrativa');
ok(str_contains($tour,'Crear borrador'),'Tutorial explica borradores');
ok(str_contains($tour,'Enviar a revisión'),'Tutorial explica revisión');
ok(str_contains($tour,'Publicado para soporte'),'Tutorial explica publicación interna');
ok(str_contains($tour,'Disponible para solicitantes'),'Tutorial explica publicación pública');
ok(str_contains($tour,'Usar como referencia'),'Tutorial de ticket explica referencias');
ok(!str_contains($tour,'estado, visibilidad, categoría'),'Tutorial elimina copy legacy de visibilidad');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] UI de conocimiento usa modelo versionado y copy simplificado.'.PHP_EOL;
