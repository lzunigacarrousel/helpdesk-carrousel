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

// El directorio debe comportarse como Usuarios: fila compacta + editor completo debajo.
ok(str_contains($view,'data-external-edit-toggle'),'Acciones usa botón dedicado Editar');
ok(str_contains($view,'data-external-edit-row'),'Editor vive en una fila independiente');
ok(str_contains($view,'colspan="9"'),'Fila de edición ocupa el ancho completo del directorio');
ok(str_contains($view,'data-external-edit-panel'),'Existe panel de edición de ancho completo');
ok(str_contains($view,'data-external-edit-row hidden'),'Fila de edición inicia oculta');
ok(!str_contains($view,'<td data-label="Acciones" class="external-provider-actions">\n          <details><summary>Editar</summary>'),'Editar ya no incrusta el formulario dentro de Acciones');
ok(str_contains($view,'external-provider-edit-shell'),'Editor usa contenedor visual dedicado');
ok(str_contains($view,'Editar proveedor ·'),'Editor muestra encabezado contextual');
ok(str_contains($view,'Cancelar edición'),'Editor ofrece cierre explícito');

// Comportamiento JS: solo un editor abierto y accesibilidad coherente.
ok(str_contains($view,"querySelectorAll('[data-external-edit-toggle]')"),'JS controla botones de edición');
ok(str_contains($view,"querySelectorAll('[data-external-edit-row]')"),'JS controla filas de edición');
ok(str_contains($view,"setAttribute('aria-expanded'"),'JS actualiza aria-expanded');
ok(str_contains($view,'closeAllExternalEditors'),'JS cierra editores anteriores');
ok(str_contains($view,'row.hidden='),'JS alterna fila sin recargar');

// Responsive equivalente a Usuarios.
ok(str_contains($view,'.external-provider-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))'),'Editor usa tres columnas en escritorio');
ok((bool)preg_match('/@media\(max-width:1180px\).*?\.external-provider-edit-grid.*?grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/s',$view),'Tablet reduce editor a dos columnas');
ok((bool)preg_match('/@media\(max-width:760px\).*?\.external-provider-edit-grid.*?grid-template-columns:1fr/s',$view),'Móvil reduce editor a una columna');

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
