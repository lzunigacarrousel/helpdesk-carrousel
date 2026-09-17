<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/KnowledgeRevisionService.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$body=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($body!=='','Existe KnowledgeRevisionService');

if($body!==''){
    require_once $servicePath;
    $class='App\\Services\\KnowledgeRevisionService';
    ok(class_exists($class),'Clase KnowledgeRevisionService disponible');

    foreach([
        'createArticle','createDraftFromRevision','updateDraft','submitForReview',
        'returnToDraft','publishInternal','publishPublic','restoreAsDraft','archiveArticle',
        'canTransition','nextRevisionNumber'
    ] as $method){
        ok(method_exists($class,$method),"Expone {$method}");
    }

    if(class_exists($class)){
        ok($class::canTransition('DRAFT','IN_REVIEW'),'DRAFT -> IN_REVIEW permitido');
        ok($class::canTransition('IN_REVIEW','DRAFT'),'IN_REVIEW -> DRAFT permitido');
        ok($class::canTransition('IN_REVIEW','PUBLISHED'),'IN_REVIEW -> PUBLISHED permitido');
        ok(!$class::canTransition('PUBLISHED','DRAFT'),'PUBLISHED -> DRAFT prohibido');
        ok(!$class::canTransition('DRAFT','PUBLISHED'),'DRAFT -> PUBLISHED directo prohibido');
        ok(!$class::canTransition('PUBLISHED','IN_REVIEW'),'PUBLISHED -> IN_REVIEW prohibido');
        ok($class::nextRevisionNumber([])===1,'Primera revisión inicia en 1');
        ok($class::nextRevisionNumber([1])===2,'Segunda revisión incrementa a 2');
        ok($class::nextRevisionNumber([1,2,3])===4,'Número de revisión es monotónico');
        ok($class::nextRevisionNumber([1,3,7])===8,'Número usa máximo existente + 1');
    }
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Dominio base de revisiones de conocimiento.'.PHP_EOL;
