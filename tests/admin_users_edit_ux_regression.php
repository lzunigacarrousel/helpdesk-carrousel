<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fails = 0;

function text(string $path): string {
    $value = @file_get_contents($path);
    return is_string($value) ? $value : '';
}

function check(bool $condition, string $message): void {
    global $fails;
    echo ($condition ? '[OK] ' : '[FALLO] ') . $message . PHP_EOL;
    if (!$condition) $fails++;
}

$view = text($root . '/app/Views/admin/users.php');

check(str_contains($view, 'data-user-edit-toggle'), 'Acciones usa control dedicado para editar usuario');
check(str_contains($view, 'data-user-edit-row'), 'Editor vive en una fila independiente');
check(str_contains($view, 'colspan="7"'), 'Fila de edición ocupa el ancho completo de la tabla');
check(str_contains($view, 'data-user-edit-panel'), 'Existe panel de edición de ancho completo');
check(str_contains($view, 'data-user-edit-row hidden'), 'Fila de edición inicia oculta');
check(!str_contains($view, '<details class="admin-user-edit"'), 'Editor ya no se incrusta dentro de Acciones');
check(str_contains($view, 'admin-user-edit-shell'), 'Editor usa contenedor visual dedicado');
check(str_contains($view, 'admin-user-edit-head'), 'Editor muestra encabezado contextual');
check(str_contains($view, 'Cancelar edición'), 'Editor ofrece cierre explícito');

check(str_contains($view, "querySelectorAll('[data-user-edit-toggle]')"), 'JS controla botones de edición');
check(str_contains($view, "querySelectorAll('[data-user-edit-row]')"), 'JS controla filas de edición');
check(str_contains($view, "setAttribute('aria-expanded'"), 'JS actualiza accesibilidad');
check(str_contains($view, 'row.hidden='), 'JS alterna fila sin recargar');
check(str_contains($view, 'closeAllEditors'), 'JS cierra editores anteriores');

check(str_contains($view, 'grid-template-columns:repeat(3,minmax(0,1fr))'), 'Escritorio conserva tres columnas');
check(str_contains($view, '@media(max-width:1180px)'), 'Existe ajuste para tablet');
check(str_contains($view, '.admin-user-form{grid-template-columns:1fr}'), 'Móvil reduce editor a una columna');

check(str_contains($view, '/admin/users/assign'), 'Se conserva endpoint actual de actualización');
check(str_contains($view, 'Convertir a proveedor externo'), 'Se conserva conversión a proveedor externo');
check(str_contains($view, 'Retirar acceso'), 'Se conserva retiro de acceso');
check(str_contains($view, 'Completar tickets históricos sin parque'), 'Se conserva corrección histórica de parque');

if ($fails) {
    fwrite(STDERR, PHP_EOL . '[ERROR] ' . $fails . ' validación(es) fallaron.' . PHP_EOL);
    exit(1);
}

echo PHP_EOL . '[OK] Regresión UX editor de usuarios completada.' . PHP_EOL;
