<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/app/Views/shared/app_start.php';

if(!is_file($path)){
    fwrite(STDERR,"[ERROR] No existe app_start.php".PHP_EOL);
    exit(1);
}

$body=file_get_contents($path);
if($body===false){
    fwrite(STDERR,"[ERROR] No se pudo leer app_start.php".PHP_EOL);
    exit(1);
}

$body=str_replace(["\r\n","\r"],"\n",$body);

$versionFrom="\$assetVersion='20260911-UXHELP1';";
$versionTo="\$assetVersion='20260911-UXHELP1';\n\$caseFocusAssetVersion=(string)(@filemtime(APP_ROOT.'/public/assets/css/case-focus.css')?:\$assetVersion);";
$linkFrom='<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/case-focus.css?v=<?= $assetVersion ?>">';
$linkTo='<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/case-focus.css?v=<?= htmlspecialchars($caseFocusAssetVersion) ?>">';

if(str_contains($body,"@filemtime(APP_ROOT.'/public/assets/css/case-focus.css')")
    ||str_contains($body,'case-focus.css?v=<?= htmlspecialchars($caseFocusAssetVersion) ?>')){
    fwrite(STDERR,"[ERROR] Cache bust de case-focus ya parece aplicado.".PHP_EOL);
    exit(1);
}

if(substr_count($body,$versionFrom)!==1){
    fwrite(STDERR,"[ERROR] No se encontró ancla única de assetVersion.".PHP_EOL);
    exit(1);
}
if(substr_count($body,$linkFrom)!==1){
    fwrite(STDERR,"[ERROR] No se encontró enlace único de case-focus.css.".PHP_EOL);
    exit(1);
}

$body=str_replace($versionFrom,$versionTo,$body);
$body=str_replace($linkFrom,$linkTo,$body);

if(file_put_contents($path,$body)===false){
    fwrite(STDERR,"[ERROR] No se pudo escribir app_start.php".PHP_EOL);
    exit(1);
}

passthru('"'.PHP_BINARY.'" -l "'.$path.'"',$exitCode);
if($exitCode!==0){
    fwrite(STDERR,"[ERROR] app_start.php quedó con error de sintaxis.".PHP_EOL);
    exit($exitCode);
}

echo '[OK] case-focus.css usa versión dinámica por filemtime.'.PHP_EOL;
echo '[OK] El navegador recibirá una URL nueva cuando cambie case-focus.css.'.PHP_EOL;
echo '[OK] No se modificó la BD.'.PHP_EOL;
