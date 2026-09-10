<?php
declare(strict_types=1);

/**
 * Helpdesk Carrousel V2 · Auditoría estructural de tablas
 * SOLO LECTURA. No ejecuta DROP, DELETE, TRUNCATE ni UPDATE.
 *
 * Objetivo:
 * - listar las tablas reales de la BD configurada;
 * - detectar referencias explícitas desde el código de la aplicación;
 * - mostrar relaciones FK entrantes/salientes;
 * - marcar candidatas para revisión humana antes de eliminar cualquier tabla.
 */

$root = dirname(__DIR__);
require $root . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();
$db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
if ($db === '') {
    fwrite(STDERR, "[ERROR] No hay base de datos seleccionada.\n");
    exit(1);
}

$tablesStmt = $pdo->prepare(
    "SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE'
     ORDER BY TABLE_NAME"
);
$tablesStmt->execute([$db]);
$tables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);

$fkStmt = $pdo->prepare(
    "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL"
);
$fkStmt->execute([$db]);
$fkRows = $fkStmt->fetchAll(PDO::FETCH_ASSOC);

$inbound = [];
$outbound = [];
foreach ($fkRows as $fk) {
    $from = (string)$fk['TABLE_NAME'];
    $to = (string)$fk['REFERENCED_TABLE_NAME'];
    $outbound[$from][$to] = true;
    $inbound[$to][$from] = true;
}

$scanRoots = [
    $root . '/app',
    $root . '/public',
    $root . '/config',
    $root . '/bootstrap.php',
];
$extensions = ['php', 'js'];
$source = '';
$filesScanned = 0;

$appendFile = static function (string $path) use (&$source, &$filesScanned): void {
    if (!is_file($path)) return;
    $content = @file_get_contents($path);
    if (!is_string($content)) return;
    $source .= "\n" . $content;
    $filesScanned++;
};

foreach ($scanRoots as $scanRoot) {
    if (is_file($scanRoot)) {
        $appendFile($scanRoot);
        continue;
    }
    if (!is_dir($scanRoot)) continue;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $extensions, true)) continue;
        $appendFile($file->getPathname());
    }
}

$sourceLower = strtolower($source);

function fmtBytes(int $bytes): string
{
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $value = (float)$bytes;
    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }
    return number_format($value, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}

printf("============================================================\n");
printf(" HELPDESK CARROUSEL - AUDITORIA DE TABLAS (SOLO LECTURA)\n");
printf("============================================================\n");
printf("Base: %s\n", $db);
printf("Tablas detectadas: %d\n", count($tables));
printf("Archivos de codigo revisados: %d\n\n", $filesScanned);

$results = [];
foreach ($tables as $table) {
    $name = (string)$table['TABLE_NAME'];
    $needle = strtolower($name);
    $hits = substr_count($sourceLower, $needle);
    $in = array_keys($inbound[$name] ?? []);
    $out = array_keys($outbound[$name] ?? []);

    if ($hits > 0) {
        $status = 'USADA_EN_CODIGO';
    } elseif ($in || $out) {
        $status = 'REVISAR_FK';
    } else {
        $status = 'CANDIDATA_REVISAR';
    }

    $results[] = [
        'table' => $name,
        'hits' => $hits,
        'in' => $in,
        'out' => $out,
        'rows' => (int)($table['TABLE_ROWS'] ?? 0),
        'bytes' => (int)($table['DATA_LENGTH'] ?? 0) + (int)($table['INDEX_LENGTH'] ?? 0),
        'status' => $status,
    ];
}

usort($results, static function (array $a, array $b): int {
    $order = ['CANDIDATA_REVISAR' => 0, 'REVISAR_FK' => 1, 'USADA_EN_CODIGO' => 2];
    return [$order[$a['status']] ?? 9, $a['table']] <=> [$order[$b['status']] ?? 9, $b['table']];
});

foreach ($results as $r) {
    printf("[%s] %s\n", $r['status'], $r['table']);
    printf("  referencias_codigo=%d | filas_aprox=%d | tamano=%s\n", $r['hits'], $r['rows'], fmtBytes($r['bytes']));
    printf("  FK entrantes: %s\n", $r['in'] ? implode(', ', $r['in']) : '-');
    printf("  FK salientes: %s\n\n", $r['out'] ? implode(', ', $r['out']) : '-');
}

$candidates = array_values(array_filter($results, static fn(array $r): bool => $r['status'] === 'CANDIDATA_REVISAR'));
printf("============================================================\n");
printf(" CANDIDATAS A REVISION HUMANA: %d\n", count($candidates));
printf("============================================================\n");
foreach ($candidates as $r) {
    printf(" - %s\n", $r['table']);
}
printf("\nIMPORTANTE: que una tabla aparezca como candidata NO autoriza borrarla.\n");
printf("Primero se valida contra migraciones, historial y dependencias funcionales.\n");
