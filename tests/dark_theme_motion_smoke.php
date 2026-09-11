<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$dark=(string)@file_get_contents($root.'/public/assets/css/dark-refinement.css');
$config=(string)@file_get_contents($root.'/config/config.php');
$appStart=(string)@file_get_contents($root.'/app/Views/shared/app_start.php');
$ok=true;

function motionCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

motionCheck($dark!=='','Se puede leer dark-refinement.css');
motionCheck(str_contains($dark,'--dark-motion-shadow:'),'Modo oscuro define sombra visible para interacción');
motionCheck(str_contains($dark,'--dark-spinner-track:'),'Modo oscuro define pista visible para spinners');
motionCheck(str_contains($dark,'--dark-spinner-head:'),'Modo oscuro define segmento visible para spinners');
motionCheck(str_contains($dark,'--dark-progress-track:'),'Modo oscuro define pista para progreso animado');

$interactiveSelectors=[
    'html[data-theme="dark"] .btn:hover',
    'html[data-theme="dark"] .search-ticket-card:hover',
    'html[data-theme="dark"] .manual-quick-card:hover',
    'html[data-theme="dark"] .dashboard-action-card:hover',
    'html[data-theme="dark"] .ticket-list-card:hover',
    'html[data-theme="dark"] .support-ticket-row:hover',
];
foreach($interactiveSelectors as $selector){
    motionCheck(str_contains($dark,$selector),'Interacción oscura explícita: '.$selector);
}

motionCheck(str_contains($dark,'html[data-theme="dark"] .global-action-spinner'),'Spinner global conserva contraste en oscuro');
motionCheck(str_contains($dark,'html[data-theme="dark"] .auth-body-v2>#global-action-status .global-action-spinner'),'Spinner de acceso conserva contraste en oscuro');
motionCheck(str_contains($dark,'html[data-theme="dark"] .processing-spinner'),'Spinner de informes conserva contraste en oscuro');
motionCheck(str_contains($dark,'html[data-theme="dark"] .processing-line'),'Barra animada de informes conserva contraste en oscuro');
motionCheck(str_contains($dark,'html[data-theme="dark"] .mgmt-donut'),'Indicador circular de gestión tiene pista oscura');
motionCheck(str_contains($dark,'html[data-theme="dark"] .mgmt-bar i'),'Barras de gestión tienen pista oscura');

motionCheck(str_contains($config,"define('ASSET_VERSION'"),'Existe versionado central de assets');
motionCheck(str_contains($config,'filemtime('),'Versionado central cambia cuando cambia CSS/JS');
motionCheck(str_contains($appStart,'$assetVersion=ASSET_VERSION;'),'Shell autenticado consume versionado central');

$missingAssetVersion=[];
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Views',FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
    $content=(string)@file_get_contents($file->getPathname());
    if($content===''||stripos($content,'<!doctype html')===false)continue;
    if(!str_contains($content,'ASSET_VERSION')){
        $missingAssetVersion[]=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    }
}
if($missingAssetVersion){
    foreach($missingAssetVersion as $path)echo '[CACHE] '.$path.PHP_EOL;
}
motionCheck(!$missingAssetVersion,'Todas las vistas HTML independientes usan versionado central de assets');

exit($ok?0:1);
