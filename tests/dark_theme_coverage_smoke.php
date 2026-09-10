<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewsRoot=$root.'/app/Views';
$requiredStylesheet='assets/css/dark-refinement.css';
$ok=true;
$standalone=[];
$missing=[];

$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
    $path=$file->getPathname();
    $content=(string)@file_get_contents($path);
    if($content===''||stripos($content,'<!doctype html')===false)continue;
    $relative=str_replace('\\','/',substr($path,strlen($root)+1));
    $standalone[]=$relative;
    if(!str_contains($content,$requiredStylesheet))$missing[]=$relative;
}

sort($standalone);
sort($missing);

function check(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

check(count($standalone)>0,'Se detectaron vistas HTML independientes');
check(count($missing)===0,'Todas las vistas HTML independientes cargan dark-refinement.css');

if($missing){
    echo 'Vistas sin tema oscuro global:'.PHP_EOL;
    foreach($missing as $path)echo ' - '.$path.PHP_EOL;
}

exit($ok?0:1);
