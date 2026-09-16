<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$installPath=$root.'/database/INSTALAR.sql';
$migrationPath=$root.'/database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql';
$verifyPath=$root.'/database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$install=is_file($installPath)?(string)file_get_contents($installPath):'';
$migration=is_file($migrationPath)?(string)file_get_contents($migrationPath):'';
$verify=is_file($verifyPath)?(string)file_get_contents($verifyPath):'';

ok($install!=='','Existe database/INSTALAR.sql');
ok($migration!=='','Existe migración incremental de Fase 9');
ok($verify!=='','Existe verificador SQL de Fase 9');

foreach(['knowledge_revisions','knowledge_article_sources','ticket_resolution_references','solution_suggestion_events'] as $table){
    ok(str_contains($install,'CREATE TABLE '.$table),"INSTALAR contiene {$table}");
    ok(str_contains($migration,$table),"Migración contiene {$table}");
}

foreach(['lifecycle_status','current_internal_revision_id','current_public_revision_id','created_by_user_id','archived_at'] as $column){
    ok(str_contains($install,$column),"INSTALAR contiene {$column}");
    ok(str_contains($migration,$column),"Migración contiene {$column}");
}

foreach(['knowledge.view','knowledge.draft_manage','knowledge.review','knowledge.publish_internal','knowledge.publish_public','knowledge.history','knowledge.restore'] as $permission){
    ok(str_contains($install,"'{$permission}'"),"INSTALAR contiene permiso {$permission}");
    ok(str_contains($migration,"'{$permission}'"),"Migración contiene permiso {$permission}");
}

ok(!str_contains(strtoupper($migration),'DROP TABLE KNOWLEDGE_ARTICLES'),'Migración conserva knowledge_articles');
ok(!str_contains(strtoupper($migration),'DROP COLUMN TITLE'),'Migración no elimina title legacy');
ok(!str_contains(strtoupper($migration),'DROP COLUMN CONTENT'),'Migración no elimina content legacy');
ok(str_contains($migration,'NOT EXISTS'),'Migración incluye guardas idempotentes');
ok(str_contains($verify,'published_without_internal_pointer'),'Verificador controla publicados sin puntero interno');
ok(str_contains($verify,'public_without_public_pointer'),'Verificador controla públicos sin puntero público');
ok(str_contains($verify,'problem_solutions'),'Verificador controla relaciones problem_solutions');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contrato de esquema Fase 9 completo.'.PHP_EOL;
