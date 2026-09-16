<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$indexPath=$root.'/app/Views/tickets/index.php';
$showPath=$root.'/app/Views/tickets/show.php';
$cssPath=$root.'/public/assets/css/case-focus.css';

$normalize=static fn(string $body):string=>str_replace(["\r\n","\r"],"\n",$body);
$load=static function(string $path) use($normalize):string{
    if(!is_file($path)){
        fwrite(STDERR,"[ERROR] No existe: {$path}".PHP_EOL);
        exit(1);
    }
    $body=file_get_contents($path);
    if($body===false){
        fwrite(STDERR,"[ERROR] No se pudo leer: {$path}".PHP_EOL);
        exit(1);
    }
    return $normalize($body);
};
$replaceOnce=static function(string $body,string $from,string $to,string $label):string{
    $count=substr_count($body,$from);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba 1 coincidencia y encontro {$count}.".PHP_EOL);
        exit(1);
    }
    return str_replace($from,$to,$body,$count);
};

$index=$load($indexPath);
$show=$load($showPath);
$css=$load($cssPath);

$indexFrom='<a class="btn btn-primary" href="<?= APP_BASE_URL ?>/mis-tickets/exportar">Descargar Excel</a>';
$indexTo='<a class="btn btn-primary" href="<?= APP_BASE_URL ?>/mis-tickets/exportar" data-no-loading="1">Descargar Excel</a>';
$index=$replaceOnce($index,$indexFrom,$indexTo,'Descarga Excel externa');

$showFrom='<div><span>Registró</span><strong><?= htmlspecialchars((string)($cycle[\'provider_rating_actor\']?:\'Equipo IT\')) ?></strong><?php if(!empty($cycle[\'provider_rating_at\'])): ?><small><?= htmlspecialchars(date(\'d/m/Y H:i\',strtotime((string)$cycle[\'provider_rating_at\']))) ?></small><?php endif; ?></div>';
$showTo='<div class="provider-rating-registration"><span>Registró</span><strong><?= htmlspecialchars((string)($cycle[\'provider_rating_actor\']?:\'Equipo IT\')) ?></strong><?php if(!empty($cycle[\'provider_rating_at\'])): ?><small class="provider-rating-registered-at"><?= htmlspecialchars(date(\'d/m/Y H:i\',strtotime((string)$cycle[\'provider_rating_at\']))) ?></small><?php endif; ?></div>';
$show=$replaceOnce($show,$showFrom,$showTo,'Registro de valoración');

$cssRule="\n/* Fase 8 · calidad proveedor */\n.provider-rating-registration strong,.provider-rating-registration small{display:block}\n.provider-rating-registration small{margin-top:4px;color:var(--muted);font-weight:500;line-height:1.35}\n";
if(!str_contains($css,'.provider-rating-registration strong')&&!str_contains($css,'.provider-rating-registration small')){
    $css=rtrim($css,"\n")."\n".$cssRule;
}else{
    fwrite(STDERR,"[ERROR] CSS de registro proveedor ya existe; no se aplicaron cambios parciales.".PHP_EOL);
    exit(1);
}

foreach([
    $indexPath=>$index,
    $showPath=>$show,
    $cssPath=>$css,
] as $path=>$body){
    if(file_put_contents($path,$body)===false){
        fwrite(STDERR,"[ERROR] No se pudo escribir: {$path}".PHP_EOL);
        exit(1);
    }
}

echo '[OK] Descarga Excel externa ya no activa overlay global.'.PHP_EOL;
echo '[OK] Registro de valoración separa actor y fecha.'.PHP_EOL;
echo '[OK] Ajustes UI Fase 8 aplicados. No se modifico la BD.'.PHP_EOL;
