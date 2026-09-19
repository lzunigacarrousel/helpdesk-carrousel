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

$install=(string)file_get_contents($root.'/database/INSTALAR.sql');
$verify=(string)file_get_contents($root.'/database/VERIFICAR_INSTALACION.sql');
$prodVerify=(string)file_get_contents($root.'/database/VERIFICAR_PRODUCCION_LIMPIA.sql');
$service=(string)file_get_contents($root.'/app/Services/KnowledgeRevisionService.php');
$knowledge=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$search=(string)file_get_contents($root.'/app/Controllers/SearchController.php');
$problem=(string)file_get_contents($root.'/app/Controllers/ProblemController.php');
$main=(string)file_get_contents($root.'/MAIN.bat');
$prodInstaller=(string)file_get_contents($root.'/INSTALAR_PRODUCCION.bat');
$gitignore=(string)file_get_contents($root.'/.gitignore');

preg_match('/CREATE TABLE knowledge_articles \(([\s\S]*?)\) ENGINE=/i',$install,$km);
$knowledgeCreate=$km[1]??'';

ok($knowledgeCreate!=='','INSTALAR define knowledge_articles');
foreach(['title','summary','content','status','visibility','category_id','author_user_id','published_at'] as $legacy){
    ok(!preg_match('/^\s*'.preg_quote($legacy,'/').'\s+/mi',$knowledgeCreate),'knowledge_articles no contiene '.$legacy);
}
foreach(['article_number','lifecycle_status','current_internal_revision_id','current_public_revision_id','created_by_user_id','archived_at'] as $canonical){
    ok((bool)preg_match('/^\s*'.preg_quote($canonical,'/').'\s+/mi',$knowledgeCreate),'knowledge_articles conserva '.$canonical);
}

ok(!str_contains($install,'CREATE TABLE problem_attachments'),'INSTALAR elimina tabla sin runtime problem_attachments');
ok(!str_contains($install,"'knowledge.manage'"),'INSTALAR elimina permiso legacy knowledge.manage');
ok(!str_contains($install,'ADD COLUMN report_template'),'report_template ya no se agrega como parche');
ok(!str_contains($install,'ADD COLUMN activity_id'),'activity_id ya no se agrega como parche');

preg_match('/CREATE TABLE external_profiles \(([\s\S]*?)\) ENGINE=/i',$install,$ep);
preg_match('/CREATE TABLE external_ticket_access \(([\s\S]*?)\) ENGINE=/i',$install,$ea);
preg_match('/CREATE TABLE ticket_attachments \(([\s\S]*?)\) ENGINE=/i',$install,$ta);
ok(str_contains($ep[1]??'','report_template ENUM'),'external_profiles crea report_template directamente');
ok(str_contains($ea[1]??'','report_template ENUM'),'external_ticket_access crea report_template directamente');
ok(str_contains($ta[1]??'','activity_id BIGINT UNSIGNED NULL'),'ticket_attachments crea activity_id directamente');

foreach([
    'ka.title','ka.summary','ka.content','ka.status','ka.visibility',
    'ka.category_id','ka.author_user_id','ka.published_at'
] as $legacyRef){
    ok(!str_contains($knowledge,$legacyRef),'KnowledgeController no usa '.$legacyRef);
    ok(!str_contains($search,$legacyRef),'SearchController no usa '.$legacyRef);
    ok(!str_contains($problem,$legacyRef),'ProblemController no usa '.$legacyRef);
}

ok(!str_contains($service,'knowledge_articles SET visibility'),'Servicio no mantiene visibility legacy');
ok(!str_contains($service,"lifecycle_status='ARCHIVED',status='ARCHIVED'"),'Servicio no mantiene status legacy');
ok(!str_contains($service,'created_by_user_id,title,summary,content,status,visibility,category_id,author_user_id'),'Alta de artículo no duplica contenido');

ok(str_contains($verify,'forbidden_columns'),'Verificador canónico controla columnas legacy');
ok(str_contains($verify,"code='knowledge.manage'"),'Verificador canónico controla permiso legacy');
ok(str_contains($prodVerify,'produccion_limpia_gate'),'Existe gate SQL de producción limpia');
ok(str_contains($prodVerify,"THEN 'PASS'"),'Gate SQL de producción limpia emite PASS');

ok(is_file($root.'/MAIN.bat'),'Existe MAIN.bat canónico');
ok(!is_file($root.'/HELPDESK_ADMIN.bat'),'HELPDESK_ADMIN.bat duplicado fue retirado');
ok(is_file($root.'/INSTALAR_PRODUCCION.bat'),'Existe instalador seguro de producción');
ok(str_contains($prodInstaller,'NUNCA ejecuta DROP DATABASE'),'Instalador de producción declara política sin DROP');
ok(
    !preg_match(
        '/^[^\r\n]*(?:mysql(?:\.exe)?|%MYSQL%)[^\r\n]*(?:-e|--execute)[^\r\n]*\bDROP\s+DATABASE\b/im',
        $prodInstaller
    ),
    'Instalador de producciÃ³n no ejecuta DROP DATABASE'
);

$legacyTools=array_merge(
    glob($root.'/tools/apply_*')?:[],
    glob($root.'/tools/fix_*')?:[],
    glob($root.'/tools/run_phase5_*')?:[]
);
ok($legacyTools===[],'No quedan herramientas temporales apply/fix de fases');

foreach(['/dist/','/.idea/','/.vscode/','Thumbs.db','*.tmp'] as $ignore){
    ok(str_contains($gitignore,$ignore),'gitignore cubre '.$ignore);
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contrato de preproducción limpia consolidado.'.PHP_EOL;
