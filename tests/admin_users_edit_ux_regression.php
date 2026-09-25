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

check(str_contains($view, 'data-admin-dialog-open="user-edit-'), 'Acciones abre editor de usuario como diálogo');
check(str_contains($view, '<dialog class="admin-record-dialog admin-record-dialog--wide"'), 'Editor de usuario usa diálogo administrativo canónico');
check(str_contains($view, 'data-admin-dialog'), 'Editor declara contrato de diálogo compartido');
check(str_contains($view, 'admin-record-dialog-title'), 'Diálogo muestra encabezado contextual');
check(str_contains($view, 'Editar usuario ·'), 'Título identifica al usuario editado');
check(str_contains($view, 'data-admin-dialog-close'), 'Editor ofrece cierre explícito');
check(str_contains($view, 'data-admin-dialog-focus'), 'Diálogo enfoca el primer campo editable');
check(!str_contains($view, 'data-user-edit-row'), 'Usuarios ya no usa filas de edición inline');
check(!str_contains($view, 'admin-user-edit-shell'), 'Usuarios elimina shell inline anterior');

check(str_contains($view, '.admin-user-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))'), 'Formulario conserva tres columnas en escritorio');
check(str_contains($view, '@media(max-width:1180px)'), 'Existe ajuste para tablet');
check(str_contains($view, '.admin-user-form{grid-template-columns:1fr}'), 'Móvil reduce formulario a una columna');

check(str_contains($view, '/admin/users/assign'), 'Se conserva endpoint actual de actualización');
check(str_contains($view, 'Convertir a proveedor externo'), 'Se conserva conversión a proveedor externo');
check(str_contains($view, 'Retirar acceso'), 'Se conserva retiro de acceso');
check(str_contains($view, 'Completar tickets históricos sin parque'), 'Se conserva corrección histórica de parque');

if ($fails) {
    fwrite(STDERR, PHP_EOL . '[ERROR] ' . $fails . ' validación(es) fallaron.' . PHP_EOL);
    exit(1);
}

echo PHP_EOL . '[OK] Regresión UX editor de usuarios completada.' . PHP_EOL;
