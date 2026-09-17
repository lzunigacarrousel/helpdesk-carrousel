<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controller=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$index=(string)file_get_contents($root.'/app/Views/knowledge/index.php');
$form=(string)file_get_contents($root.'/app/Views/knowledge/form.php');
$show=(string)file_get_contents($root.'/app/Views/knowledge/show.php');
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
ok(str_contains($show,'En revisión'),'UI muestra estado editorial legible');
ok(!str_contains(strtolower($show),'visibility'),'Detalle no expone tecnicismo visibility');
ok(!str_contains(strtolower($show),'scope'),'Detalle no expone tecnicismo scope');
ok(!str_contains($form,'name="visibility"'),'Formulario ya no pide visibilidad legacy');
ok(str_contains($form,'Guardar borrador'),'Formulario conserva acción principal clara');
ok(str_contains($index,'Disponible para solicitantes'),'Listado identifica disponibilidad pública con copy legible');
ok(!str_contains($index,'Toda visibilidad'),'Listado elimina filtro legacy de visibilidad');
ok(str_contains($show,'Más acciones'),'Acciones secundarias se agrupan');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] UI de conocimiento usa modelo versionado y copy simplificado.'.PHP_EOL;
