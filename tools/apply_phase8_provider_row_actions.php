<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/app/Views/admin/externals.php';

if(!is_file($path)){
    fwrite(STDERR,"[ERROR] No existe {$path}".PHP_EOL);
    exit(1);
}

$body=(string)file_get_contents($path);

$styleOld='.external-directory-tools{display:flex;align-items:center;gap:8px}.external-directory-tools .form-control{min-width:280px}.external-provider-name strong{display:block}.external-provider-name small{display:block;margin-top:3px}.external-admin-kpis article small{display:none}.external-provider-origin{font-size:10.5px;color:var(--muted)}.external-provider-disabled{opacity:.72}.external-provider-edit-toggle{white-space:nowrap}';
$styleNew='.external-directory-tools{display:flex;align-items:center;gap:8px}.external-directory-tools .form-control{min-width:280px}.external-provider-name strong{display:block}.external-provider-name small{display:block;margin-top:3px}.external-admin-kpis article small{display:none}.external-provider-origin{font-size:10.5px;color:var(--muted)}.external-provider-disabled{opacity:.72}.external-provider-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}.external-provider-actions .btn{white-space:nowrap}.external-provider-edit-toggle{white-space:nowrap}';

if(substr_count($body,$styleOld)!==1){
    fwrite(STDERR,'[ERROR] CSS acciones proveedor: esperaba exactamente 1 coincidencia y encontro '.substr_count($body,$styleOld).'.'.PHP_EOL);
    exit(1);
}
$body=str_replace($styleOld,$styleNew,$body);

$cellOld='<td data-label="Acciones"><button class="btn btn-outline-secondary external-provider-edit-toggle" type="button" data-external-edit-toggle aria-expanded="false" aria-controls="external-edit-<?= (int)$u[\'id\'] ?>">Editar</button></td>';
$cellNew=<<<'HTML'
<td data-label="Acciones"><div class="external-provider-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe?provider=<?= (int)$u['id'] ?>">Historial</a><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar?provider=<?= (int)$u['id'] ?>" data-no-loading="1">Excel</a><button class="btn btn-outline-secondary external-provider-edit-toggle" type="button" data-external-edit-toggle aria-expanded="false" aria-controls="external-edit-<?= (int)$u['id'] ?>">Editar</button></div></td>
HTML;

if(substr_count($body,$cellOld)!==1){
    fwrite(STDERR,'[ERROR] Celda acciones proveedor: esperaba exactamente 1 coincidencia y encontro '.substr_count($body,$cellOld).'.'.PHP_EOL);
    exit(1);
}
$body=str_replace($cellOld,$cellNew,$body);

if(file_put_contents($path,$body)===false){
    fwrite(STDERR,'[ERROR] No se pudo escribir externals.php'.PHP_EOL);
    exit(1);
}

$php=PHP_BINARY;
passthru('"'.$php.'" -l "'.$path.'"',$code);
if($code!==0)exit($code);

echo '[OK] Acciones Historial, Excel y Editar agregadas por proveedor. No se modifico la BD.'.PHP_EOL;
