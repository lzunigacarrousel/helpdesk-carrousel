<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$installer=$root.'/database/INSTALAR.sql';

if(!is_file($installer)){
    fwrite(STDERR,"[ERROR] Falta database/INSTALAR.sql".PHP_EOL);
    exit(1);
}

$sql=(string)file_get_contents($installer);
$runtimeRoots=[$root.'/app',$root.'/public',$root.'/config'];
$runtimeFiles=[$root.'/bootstrap.php'];

foreach($runtimeRoots as $dir){
    if(!is_dir($dir)) continue;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if(!$file->isFile()) continue;
        if(!preg_match('/\\.(php|js)$/i',$file->getFilename())) continue;
        $runtimeFiles[]=$file->getPathname();
    }
}

$runtime='';
foreach(array_unique($runtimeFiles) as $file){
    if(is_file($file)) $runtime.=(string)file_get_contents($file)."\n";
}

$tables=[];
$current=null;
foreach(preg_split('/\\R/',$sql) as $raw){
    $line=trim($raw);
    if(preg_match('/^CREATE TABLE(?: IF NOT EXISTS)?\\s+`?([A-Za-z0-9_]+)`?\\s*\\(/i',$line,$m)){
        $current=$m[1];
        $tables[$current]=[];
        continue;
    }
    if($current===null) continue;
    if(preg_match('/^\\)\\s*(?:ENGINE|;)/i',$line)){
        $current=null;
        continue;
    }
    if(preg_match('/^`?([A-Za-z_][A-Za-z0-9_]*)`?\\s+(?:BIGINT|INT|TINYINT|SMALLINT|MEDIUMINT|VARCHAR|CHAR|TEXT|LONGTEXT|MEDIUMTEXT|DATETIME|TIMESTAMP|DATE|TIME|DECIMAL|FLOAT|DOUBLE|JSON|ENUM|SET|BLOB|VARBINARY|BINARY)\\b/i',$line,$m)){
        $tables[$current][]=$m[1];
    }
}

function tokenUsed(string $haystack,string $token):bool
{
    return (bool)preg_match('/(?<![A-Za-z0-9_])'.preg_quote($token,'/').'(?![A-Za-z0-9_])/i',$haystack);
}

echo "============================================================".PHP_EOL;
echo " HELPDESK CARROUSEL - AUDITORIA PREPRODUCCION".PHP_EOL;
echo " Modo: SOLO LECTURA / NO BORRA NADA".PHP_EOL;
echo "============================================================".PHP_EOL.PHP_EOL;
echo "Tablas en INSTALAR.sql: ".count($tables).PHP_EOL;
echo "Archivos runtime escaneados: ".count(array_unique($runtimeFiles)).PHP_EOL.PHP_EOL;

$unusedTables=[];
$unusedColumns=[];
foreach($tables as $table=>$columns){
    if(!tokenUsed($runtime,$table)) $unusedTables[]=$table;
    foreach($columns as $column){
        if(!tokenUsed($runtime,$column)) $unusedColumns[$table][]=$column;
    }
}

echo "=== TABLAS SIN REFERENCIA DIRECTA EN RUNTIME ===".PHP_EOL;
if(!$unusedTables){
    echo "[OK] Ninguna.".PHP_EOL;
}else{
    foreach($unusedTables as $table) echo "[CANDIDATA] ".$table.PHP_EOL;
}
echo PHP_EOL;

echo "=== COLUMNAS SIN REFERENCIA DIRECTA EN RUNTIME ===".PHP_EOL;
if(!$unusedColumns){
    echo "[OK] Ninguna.".PHP_EOL;
}else{
    foreach($unusedColumns as $table=>$columns){
        echo "[".$table."] ".implode(', ',$columns).PHP_EOL;
    }
}
echo PHP_EOL;
echo "IMPORTANTE:".PHP_EOL;
echo "- CANDIDATA no significa BORRAR.".PHP_EOL;
echo "- SELECT *, triggers, FK, indices, reportes dinamicos o compatibilidad pueden ocultar uso textual.".PHP_EOL;
echo "- Toda poda debe validarse manualmente antes de cambiar INSTALAR.sql.".PHP_EOL;
echo "- Este auditor no modifica archivos ni base de datos.".PHP_EOL;
