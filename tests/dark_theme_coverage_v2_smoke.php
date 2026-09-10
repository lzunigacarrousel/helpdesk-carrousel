<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewsRoot=$root.'/app/Views';
$appCss=(string)@file_get_contents($root.'/public/assets/css/app.css');
$ok=true;
$standalone=[];
$missingAppCss=[];

$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
    $path=$file->getPathname();
    $content=(string)@file_get_contents($path);
    if($content===''||stripos($content,'<!doctype html')===false)continue;
    $relative=str_replace('\\','/',substr($path,strlen($root)+1));
    $standalone[]=$relative;
    if(!str_contains($content,'assets/css/app.css'))$missingAppCss[]=$relative;
}

sort($standalone);
sort($missingAppCss);

function check(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

check(count($standalone)>0,'Se detectaron vistas HTML independientes');
check(count($missingAppCss)===0,'Todas las vistas HTML independientes cargan app.css');
check(str_contains($appCss,"@import url('./dark-refinement.css');"),'app.css incorpora la capa oscura canónica');

if($missingAppCss){
    echo 'Vistas standalone fuera de la hoja global:'.PHP_EOL;
    foreach($missingAppCss as $path)echo ' - '.$path.PHP_EOL;
}

exit($ok?0:1);
