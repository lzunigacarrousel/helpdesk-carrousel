<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/public/index.php');
$controller=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$history=(string)@file_get_contents($root.'/app/Views/knowledge/history.php');
$compare=(string)@file_get_contents($root.'/app/Views/knowledge/compare.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok(str_contains($routes,'/knowledge/history'),'Ruta historial');
ok(str_contains($routes,'/knowledge/compare'),'Ruta comparar');
ok(str_contains($controller,"knowledge.history"),'Historial exige permiso específico');
ok(str_contains($controller,"knowledge.restore"),'Restauración exige permiso específico');
ok(str_contains($controller,'function history('),'Controller expone history');
ok(str_contains($controller,'function compare('),'Controller expone compare');
ok($history!=='','Existe vista history');
ok($compare!=='','Existe vista compare');
foreach(['Título','Resumen','Contenido','Categoría'] as $label){
    ok(str_contains($compare,$label),"Comparación incluye {$label}");
}
ok(!str_contains($compare,'UPDATE '),'Vista compare no muta revisiones');
ok(!str_contains($compare,'DELETE '),'Vista compare no elimina revisiones');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Historial y comparación de conocimiento.'.PHP_EOL;
