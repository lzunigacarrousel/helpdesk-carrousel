<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$gate=(string)file_get_contents($root.'/VALIDAR_FASE12_BD.bat');
$sql=(string)file_get_contents($root.'/database/VERIFICAR_FASE12_BD_20260919.sql');
$m5=(string)file_get_contents($root.'/database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql');
$m9=(string)file_get_contents($root.'/database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql');

ok(str_contains($gate,'set "DB_NAME=carrousel_helpdesk"'),'Gate BD apunta a carrousel_helpdesk');
ok(str_contains($gate,'set "PROTECTED_DB=helpdesk_carrousel"'),'Gate BD declara base histórica protegida');
ok(str_contains($gate,'if /I "%DB_NAME%"=="%PROTECTED_DB%"'),'Gate BD impide usar la base protegida');
ok(str_contains($gate,'PRE_FASE12_BD'),'Gate BD crea backup previo');
ok(str_contains($gate,'--single-transaction'),'Backup usa transacción consistente');
ok(str_contains($gate,'--routines --triggers --events'),'Backup incluye objetos de BD');

$backupPos=strpos($gate,'Respaldo previo');
$migratePos=strpos($gate,'Idempotencia Fase 5');
ok($backupPos!==false&&$migratePos!==false&&$backupPos<$migratePos,'Backup ocurre antes de reejecutar migraciones');

ok(substr_count($gate,'MIGRAR_FASE5_ACTIVIDADES_20260913.sql')>=3,'Gate referencia y ejecuta Fase 5 dos veces');
ok(substr_count($gate,'MIGRAR_FASE9_CONOCIMIENTO_20260916.sql')>=3,'Gate referencia y ejecuta Fase 9 dos veces');
ok(str_contains($gate,'helpdesk_f12_snapshot_before.txt'),'Gate captura snapshot previo');
ok(str_contains($gate,'helpdesk_f12_snapshot_after.txt'),'Gate captura snapshot posterior');
ok(str_contains($gate,'fc /b "%TEMP%\helpdesk_f12_snapshot_before.txt" "%TEMP%\helpdesk_f12_snapshot_after.txt"'),'Gate compara snapshots de idempotencia');
ok(str_contains($gate,'protected_before.txt')&&str_contains($gate,'protected_after.txt'),'Gate captura fingerprint de base histórica');
ok(str_contains($gate,'Base protegida sin cambios de estructura/fingerprint'),'Gate exige base histórica intacta');

ok(str_contains($gate,'VERIFICAR_INSTALACION.sql'),'Gate ejecuta verificación canónica');
ok(str_contains($gate,'VERIFICAR_ESTABILIDAD_V2.sql'),'Gate ejecuta estabilidad');
ok(str_contains($gate,'VERIFICAR_FASE5_ACTIVIDADES_20260913.sql'),'Gate ejecuta verificador Fase 5');
ok(str_contains($gate,'VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql'),'Gate ejecuta verificador Fase 9');
ok(str_contains($gate,'VERIFICAR_FASE12_BD_20260919.sql'),'Gate ejecuta verificador final Fase 12');
ok(str_contains($gate,'findstr /X /C:"PASS"'),'Gate exige PASS explícito');
ok(str_contains($gate,'STABILITY_FAILED'),'Gate trata hallazgos de estabilidad como fallo');
ok(str_contains($gate,'Produccion: NO modificada'),'Gate deja producción fuera de alcance');

ok(str_contains($sql,'USE carrousel_helpdesk;'),'Verificador Fase 12 usa base V2');
ok(!str_contains($sql,'USE helpdesk_carrousel'),'Verificador Fase 12 no usa base histórica');
ok(str_contains($sql,"version='2026-09-13-fase5-actividades'"),'Verificador exige migración Fase 5');
ok(str_contains($sql,"version='2026-09-16-fase9-conocimiento'"),'Verificador exige migración Fase 9');
ok(str_contains($sql,'ARTICULOS_SIN_REVISION'),'Verificador controla artículos sin revisión');
ok(str_contains($sql,'ARTICULOS_SIN_FUENTE'),'Verificador controla artículos sin fuente');
ok(str_contains($sql,'REVISIONES_DUPLICADAS'),'Verificador controla revisiones duplicadas');
ok(str_contains($sql,'TECHNICIAN_CON_PERMISOS_EDITORIALES_PROHIBIDOS'),'Verificador controla gobierno editorial');
ok(str_contains($sql,"THEN 'PASS'"),'Verificador emite PASS');

ok(str_contains($m5,'CREATE TABLE IF NOT EXISTS'),'Migración Fase 5 crea tablas de forma idempotente');
ok(str_contains($m5,'ADD COLUMN IF NOT EXISTS'),'Migración Fase 5 protege columna existente');
ok(str_contains($m5,'INSERT IGNORE INTO schema_migrations'),'Migración Fase 5 registra versión sin duplicar');
ok(str_contains($m5,'ON DUPLICATE KEY UPDATE'),'Migración Fase 5 actualiza permisos sin duplicar');

ok(str_contains($m9,'CREATE TABLE IF NOT EXISTS'),'Migración Fase 9 crea tablas de forma idempotente');
ok(str_contains($m9,'ADD COLUMN IF NOT EXISTS'),'Migración Fase 9 protege columnas existentes');
ok(str_contains($m9,'CREATE INDEX IF NOT EXISTS'),'Migración Fase 9 protege índices existentes');
ok(str_contains($m9,'WHERE NOT EXISTS'),'Migración Fase 9 evita backfill duplicado');
ok(str_contains($m9,'INSERT IGNORE INTO schema_migrations'),'Migración Fase 9 registra versión sin duplicar');
ok(!str_contains($m5,'helpdesk_carrousel'),'Migración Fase 5 no referencia base histórica');
ok(!str_contains($m9,'helpdesk_carrousel'),'Migración Fase 9 no referencia base histórica');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Seguridad e idempotencia de BD Fase 12 preparadas.'.PHP_EOL;
