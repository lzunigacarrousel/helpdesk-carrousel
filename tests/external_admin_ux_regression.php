<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$view=body($root.'/app/Views/admin/externals.php');

// El directorio conserva la tabla intacta y edita en un diálogo contextual.
ok(str_contains($view,'data-admin-dialog-open="external-edit-'),'Acciones abre proveedor en diálogo');
ok(str_contains($view,'<dialog class="admin-record-dialog admin-record-dialog--wide"'),'Proveedor usa diálogo administrativo canónico');
ok(str_contains($view,'Editar proveedor ·'),'Diálogo identifica claramente al proveedor');
ok(str_contains($view,'data-admin-dialog-close'),'Diálogo ofrece cierre explícito');
ok(str_contains($view,'data-admin-dialog-focus'),'Diálogo enfoca el primer campo');
ok(!str_contains($view,'data-external-edit-row'),'Proveedor ya no usa fila de edición inline');
ok(!str_contains($view,'external-provider-edit-shell'),'Se elimina shell inline anterior');
ok(!str_contains($view,'closeAllExternalEditors'),'Vista ya no mantiene JS duplicado para editores inline');

// El formulario interno conserva su retícula, mientras el contenedor modal es compartido.
ok(str_contains($view,'.external-provider-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))'),'Formulario usa tres columnas en escritorio');
ok((bool)preg_match('/@media\(max-width:1180px\).*?\.external-provider-edit-grid.*?grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/s',$view),'Tablet reduce formulario a dos columnas');
ok((bool)preg_match('/@media\(max-width:760px\).*?\.external-provider-edit-grid.*?grid-template-columns:1fr/s',$view),'Móvil reduce formulario a una columna');

// Funcionalidad existente que no se toca en este paso.
ok(str_contains($view,'/admin/externos/actualizar'),'Se conserva endpoint de edición');
ok(str_contains($view,'/admin/externos/convertir-interno'),'Se conserva conversión a usuario interno');
ok(str_contains($view,'/admin/externos/desactivar'),'Se conserva desactivación');
ok(str_contains($view,'/admin/externos/asignar'),'Se conserva compartir caso');
ok(str_contains($view,'/admin/externos/crear'),'Se conserva registro de proveedor');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión UX administración de externos completada.".PHP_EOL;
