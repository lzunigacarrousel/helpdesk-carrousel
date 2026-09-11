<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$ok = true;

function gateCheck(bool $condition, string $message): void
{
    global $ok;
    echo ($condition ? '[OK] ' : '[FALLO] ') . $message . PHP_EOL;
    $ok = $ok && $condition;
}

$batPath = $root . '/VALIDAR_PRE_FASE4.bat';
$manualPath = $root . '/docs/VALIDACION_PRE_FASE4.md';
$bat = is_file($batPath) ? (string) file_get_contents($batPath) : '';
$manual = is_file($manualPath) ? (string) file_get_contents($manualPath) : '';

gateCheck(is_file($batPath), 'Existe VALIDAR_PRE_FASE4.bat');
gateCheck(is_file($manualPath), 'Existe docs/VALIDACION_PRE_FASE4.md');

$requiredTests = [
    'tests\\static_checks.php',
    'tests\\installer_safety_smoke.php',
    'tests\\dark_theme_smoke.php',
    'tests\\dark_theme_coverage_smoke.php',
    'tests\\dark_theme_public_smoke.php',
    'tests\\searchable_select_smoke.php',
    'tests\\requester_ux_smoke.php',
    'tests\\phase3_operational_smoke.php',
    'tests\\pre_phase4_cleanup_smoke.php',
    'tests\\project_quality.php',
    'tests\\xlsx_smoke.php',
];
foreach ($requiredTests as $test) {
    gateCheck(str_contains($bat, $test), 'Gate local ejecuta ' . $test);
}

gateCheck(str_contains($bat, 'database\\VERIFICAR_INSTALACION.sql'), 'Gate valida esquema canónico');
gateCheck(str_contains($bat, 'database\\VERIFICAR_ESTABILIDAD_V2.sql'), 'Gate valida estabilidad V2');
gateCheck(str_contains($bat, 'carrousel_helpdesk'), 'Gate verifica la base V2 correcta');
gateCheck(str_contains($bat, 'ui-normalization-working'), 'Gate exige la rama de trabajo correcta');
gateCheck(str_contains($bat, 'PENDIENTE VALIDACION MANUAL'), 'Gate separa pruebas automáticas de validación manual');

$forbidden = ['DROP DATABASE', 'DROP TABLE', 'TRUNCATE TABLE', 'DELETE FROM', 'UPDATE ', 'INSERT INTO', 'ALTER TABLE'];
foreach ($forbidden as $needle) {
    gateCheck(!str_contains(strtoupper($bat), $needle), 'Gate no contiene operación destructiva: ' . trim($needle));
}

$manualTerms = ['Solicitante', 'IT', 'Proveedor', 'Gerencia', 'Correo', 'Responsive', 'Claro', 'Oscuro', '1920', '1366', '1024', '760'];
foreach ($manualTerms as $term) {
    gateCheck(str_contains($manual, $term), 'Matriz manual cubre ' . $term);
}

gateCheck(str_contains($manual, 'Fase 4'), 'La guía bloquea Fase 4 hasta cerrar el gate');

exit($ok ? 0 : 1);
