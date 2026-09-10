<?php
declare(strict_types=1);

/**
 * Helpdesk Carrousel V2 · Auditoría estructural de tablas
 * SOLO LECTURA. No ejecuta DROP, DELETE, TRUNCATE ni UPDATE.
 *
 * Este script es deliberadamente independiente del bootstrap web para que:
 * - funcione correctamente desde CMD/PowerShell;
 * - no inicialice sesión, autenticación ni vistas HTML;
 * - muestre errores reales de conexión en consola;
 * - no pueda confundirse con una petición web.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este diagnóstico solo puede ejecutarse desde consola.');
}

$root = dirname(__DIR__);
$localFile = $root . '/config/local.php';

if (!is_file($localFile)) {
    fwrite(STDERR, "[ERROR] Falta config/local.php.\n");
    exit(1);
}

$local = require $localFile;
if (!is_array($local)) {
    fwrite(STDERR, "[ERROR] config/local.php no devolvió una configuración válida.\n");
    exit(1);
}

$dbHost = (string)($local['db_host'] ?? '127.0.0.1');
$dbName = (string)($local['db_name'] ?? 'carrousel_helpdesk');
$dbUser = (string)($local['db_user'] ?? 'root');
$dbPass = (string)($local['db_pass'] ?? '');

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] No se pudo conectar a MariaDB.\n");
    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Host: {$dbHost} | Base: {$dbName} | Usuario: {$dbUser}\n");
    exit(1);
}

try {
    $db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($db === '') {
        throw new RuntimeException('No hay una base seleccionada.');
    }

    $tablesStmt = $pdo->prepare(
        "SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE'
         ORDER BY TABLE_NAME"
    );
    $tablesStmt->execute([$db]);
    $tables = $tablesStmt->fetchAll();

    $fkStmt = $pdo->prepare(
        "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    $fkStmt->execute([$db]);
    $fkRows = $fkStmt->fetchAll();
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] No se pudo leer la estructura de la base.\n");
    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");
    exit(1);
}

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
printf("Host: %s\n", $dbHost);
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
printf("Primero se valida contra el esquema canónico y las dependencias funcionales.\n");
