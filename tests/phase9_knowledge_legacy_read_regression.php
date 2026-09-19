<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$search=(string)file_get_contents($root.'/app/Controllers/SearchController.php');
$problem=(string)file_get_contents($root.'/app/Controllers/ProblemController.php');
$dashboard=(string)file_get_contents($root.'/app/Controllers/DashboardController.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok(str_contains($search,'current_internal_revision_id'),'Buscador contempla revisión interna vigente');
ok(str_contains($search,'current_public_revision_id'),'Buscador contempla revisión pública vigente');
ok(str_contains($search,'knowledge_revisions'),'Buscador lee contenido versionado');
foreach(['ka.title','ka.summary','ka.content','ka.status','ka.visibility','ka.category_id','ka.author_user_id','ka.published_at'] as $legacyRef){
    ok(!str_contains($search,$legacyRef),'Buscador no usa columna legacy '.$legacyRef);
}
ok(!str_contains($search,"Auth::can('knowledge.manage')"),'Buscador no usa permiso legacy para edición');

ok(str_contains($problem,'knowledge_revisions'),'Problemas muestran títulos desde revisiones');
ok(str_contains($problem,"ka.lifecycle_status='ACTIVE'"),'Problemas excluyen artículos archivados por lifecycle');
ok(!str_contains($problem,"status<>'ARCHIVED'"),'Problemas no dependen del status legacy de artículos');
foreach(['ka.title','ka.status','ka.author_user_id'] as $legacyRef){
    ok(!str_contains($problem,$legacyRef),'Problemas no usan columna legacy '.$legacyRef);
}

ok(str_contains($dashboard,'knowledge_revisions'),'Dashboard cuenta borradores/revisión desde revisiones');
ok(str_contains($dashboard,"state IN('DRAFT','IN_REVIEW')"),'Dashboard usa estados editoriales nuevos');
ok(!str_contains($dashboard,"knowledge_articles WHERE status='DRAFT'"),'Dashboard no cuenta borradores con status legacy');
ok(!str_contains($dashboard,"Auth::can('knowledge.manage')"),'Dashboard no usa knowledge.manage para borradores');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Runtime de conocimiento usa únicamente identidad + revisiones versionadas.'.PHP_EOL;
