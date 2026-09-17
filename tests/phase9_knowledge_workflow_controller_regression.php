<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/public/index.php');
$controller=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

foreach([
    '/knowledge/submit-review',
    '/knowledge/return-draft',
    '/knowledge/publish-internal',
    '/knowledge/publish-public',
    '/knowledge/restore'
] as $route){
    ok(str_contains($routes,$route),"Existe ruta {$route}");
}

ok(str_contains($controller,'KnowledgeRevisionService'),'Controller delega al servicio de revisiones');
ok(str_contains($controller,"knowledge.draft_manage"),'Borradores exigen permiso específico');
ok(str_contains($controller,"knowledge.review"),'Revisión exige permiso específico');
ok(str_contains($controller,"knowledge.publish_internal"),'Publicación interna exige permiso específico');
ok(str_contains($controller,"knowledge.publish_public"),'Publicación pública exige permiso específico');
ok(str_contains($controller,"knowledge.restore"),'Restauración exige permiso específico');

ok(!str_contains($controller,'UPDATE knowledge_articles SET title='),'Controller no sobrescribe contenido legacy directamente');
ok(!str_contains($controller,"SET status='PUBLISHED'"),'Controller no publica mediante UPDATE directo');
ok(!str_contains($controller,'private function setStatus'),'Se elimina workflow legacy setStatus');

foreach([
    'createArticle(',
    'updateDraft(',
    'submitForReview(',
    'returnToDraft(',
    'publishInternal(',
    'publishPublic(',
    'restoreAsDraft(',
    'archiveArticle('
] as $call){
    ok(str_contains($controller,$call),"Controller utiliza {$call}");
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] KnowledgeController usa workflow versionado.'.PHP_EOL;
