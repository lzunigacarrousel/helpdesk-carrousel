<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$service=(string)file_get_contents($root.'/app/Services/SolutionSuggestionService.php');
$view=(string)file_get_contents($root.'/app/Views/tickets/show.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok(str_contains($service,'current_internal_revision_id'),'Artículos usan puntero interno vigente');
ok(str_contains($service,'knowledge_revisions'),'Ranking usa revisiones');
ok(str_contains($service,"ka.lifecycle_status='ACTIVE'"),'Solo sugiere artículos activos');
ok(!str_contains($service,"ka.status='PUBLISHED'"),'No depende de status legacy');
ok(str_contains($service,'revision_id'),'Sugerencia conserva revisión exacta');
ok(str_contains($view,'Usar como referencia'),'UI ofrece usar referencia');
ok(!str_contains($view,"['score'] ?>%"),'UI no muestra score numérico crudo');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Sugerencias internas usan conocimiento versionado.'.PHP_EOL;
