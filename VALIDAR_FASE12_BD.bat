@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "DB_NAME=carrousel_helpdesk"
set "PROTECTED_DB=helpdesk_carrousel"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
set "DB_USER=root"
set "DB_PASS="
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - FASE 12 / VALIDACION DE BD
echo  Base activa: %DB_NAME%
echo  Base protegida: %PROTECTED_DB%
echo ============================================================
echo.

if /I "%DB_NAME%"=="%PROTECTED_DB%" (
  echo [ERROR] La base activa coincide con la base protegida.
  exit /b 1
)

if not exist "%MYSQL%" (
  echo [ERROR] No existe %MYSQL%
  exit /b 1
)
if not exist "%MYSQLDUMP%" (
  echo [ERROR] No existe %MYSQLDUMP%
  exit /b 1
)

for %%F in (
  "database\VERIFICAR_INSTALACION.sql"
  "database\VERIFICAR_ESTABILIDAD_V2.sql"
  "database\VERIFICAR_FASE5_ACTIVIDADES_20260913.sql"
  "database\VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql"
  "database\VERIFICAR_FASE12_BD_20260919.sql"
  "database\MIGRAR_FASE5_ACTIVIDADES_20260913.sql"
  "database\MIGRAR_FASE9_CONOCIMIENTO_20260916.sql"
) do (
  if not exist %%F (
    echo [ERROR] Falta %%~F
    exit /b 1
  )
)

set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 -N -B -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='%DB_NAME%';" > "%TEMP%\helpdesk_f12_db.txt" 2> "%TEMP%\helpdesk_f12_mysql_error.txt"
if errorlevel 1 goto :mysql_error
findstr /X /C:"%DB_NAME%" "%TEMP%\helpdesk_f12_db.txt" >nul
if errorlevel 1 (
  echo [ERROR] No existe la base %DB_NAME%.
  exit /b 1
)

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
if not defined STAMP set "STAMP=manual"
if not exist "backups" mkdir "backups"

echo [1/8] Respaldo previo de %DB_NAME%...
"%MYSQLDUMP%" %MYSQL_AUTH% -h 127.0.0.1 --single-transaction --routines --triggers --events --default-character-set=utf8mb4 --result-file="backups\%DB_NAME%_PRE_FASE12_BD_%STAMP%.sql" "%DB_NAME%"
if errorlevel 1 (
  echo [ERROR] No se pudo crear el respaldo. Se cancela.
  exit /b 1
)
for %%B in ("backups\%DB_NAME%_PRE_FASE12_BD_%STAMP%.sql") do echo [OK] Backup: %%~fB ^(%%~zB bytes^)

echo.
echo [2/8] Fingerprint de la base historica protegida...
call :protected_fingerprint "%TEMP%\helpdesk_f12_protected_before.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_protected_before.txt"

echo.
echo [3/8] Verificacion canonica y diagnostico previo...
call :source_sql "database/VERIFICAR_INSTALACION.sql"
if errorlevel 1 exit /b 1

call :source_sql "database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql"
if errorlevel 1 exit /b 1

call :source_capture "database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql" "%TEMP%\helpdesk_f12_phase9_before.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_phase9_before.txt"
findstr /X /C:"PASS" "%TEMP%\helpdesk_f12_phase9_before.txt" >nul
if errorlevel 1 (
  echo [ERROR] Fase 9 no reporta PASS antes de idempotencia.
  exit /b 1
)

call :stability
if errorlevel 1 exit /b 1

echo.
echo [4/8] Snapshot antes de reejecutar migraciones...
call :snapshot "%TEMP%\helpdesk_f12_snapshot_before.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_snapshot_before.txt"

echo.
echo [5/8] Idempotencia Fase 5 - dos ejecuciones...
call :source_sql "database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql"
if errorlevel 1 exit /b 1
call :source_sql "database/MIGRAR_FASE5_ACTIVIDADES_20260913.sql"
if errorlevel 1 exit /b 1

echo.
echo [6/8] Idempotencia Fase 9 - dos ejecuciones...
call :source_sql "database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql"
if errorlevel 1 exit /b 1
call :source_sql "database/MIGRAR_FASE9_CONOCIMIENTO_20260916.sql"
if errorlevel 1 exit /b 1

echo.
echo [7/8] Comparando snapshot y base protegida...
call :snapshot "%TEMP%\helpdesk_f12_snapshot_after.txt"
if errorlevel 1 exit /b 1
fc /b "%TEMP%\helpdesk_f12_snapshot_before.txt" "%TEMP%\helpdesk_f12_snapshot_after.txt" >nul
if errorlevel 1 (
  echo [ERROR] La reejecucion de migraciones cambio conteos sensibles.
  echo --- ANTES ---
  type "%TEMP%\helpdesk_f12_snapshot_before.txt"
  echo --- DESPUES ---
  type "%TEMP%\helpdesk_f12_snapshot_after.txt"
  exit /b 1
)
echo [OK] Migraciones Fase 5 y Fase 9 son idempotentes sobre el estado actual.

call :protected_fingerprint "%TEMP%\helpdesk_f12_protected_after.txt"
if errorlevel 1 exit /b 1
fc /b "%TEMP%\helpdesk_f12_protected_before.txt" "%TEMP%\helpdesk_f12_protected_after.txt" >nul
if errorlevel 1 (
  echo [ERROR] Cambio el fingerprint de la base protegida %PROTECTED_DB%.
  exit /b 1
)
echo [OK] Base protegida sin cambios de estructura/fingerprint.

echo.
echo [8/8] Verificacion final...
call :source_sql "database/VERIFICAR_INSTALACION.sql"
if errorlevel 1 exit /b 1

call :source_capture "database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql" "%TEMP%\helpdesk_f12_phase9_after.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_phase9_after.txt"
findstr /X /C:"PASS" "%TEMP%\helpdesk_f12_phase9_after.txt" >nul
if errorlevel 1 (
  echo [ERROR] Fase 9 no reporta PASS al final.
  exit /b 1
)

call :source_capture "database/VERIFICAR_FASE12_BD_20260919.sql" "%TEMP%\helpdesk_f12_final.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_final.txt"
findstr /X /C:"PASS" "%TEMP%\helpdesk_f12_final.txt" >nul
if errorlevel 1 (
  echo [ERROR] El gate SQL de Fase 12 no reporta PASS.
  exit /b 1
)

call :stability
if errorlevel 1 exit /b 1

echo.
echo ============================================================
echo [OK] VALIDACION BD FASE 12 COMPLETADA SIN FALLOS
echo Backup previo creado.
echo Instalacion canonica: OK
echo Estabilidad: OK
echo Fase 5: OK
echo Fase 9: PASS
echo Idempotencia Fase 5/Fase 9: OK
echo Base historica protegida: SIN CAMBIOS
echo Produccion: NO modificada
echo ============================================================
exit /b 0

:source_sql
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 "%DB_NAME%" --execute="source %~1"
exit /b %errorlevel%

:source_capture
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 -N -B "%DB_NAME%" --execute="source %~1" > "%~2"
exit /b %errorlevel%

:snapshot
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 -N -B "%DB_NAME%" -e "SELECT CONCAT('ticket_activities=',COUNT(*)) FROM ticket_activities; SELECT CONCAT('ticket_activity_participants=',COUNT(*)) FROM ticket_activity_participants; SELECT CONCAT('knowledge_articles=',COUNT(*)) FROM knowledge_articles; SELECT CONCAT('knowledge_revisions=',COUNT(*)) FROM knowledge_revisions; SELECT CONCAT('knowledge_article_sources=',COUNT(*)) FROM knowledge_article_sources; SELECT CONCAT('ticket_resolution_references=',COUNT(*)) FROM ticket_resolution_references; SELECT CONCAT('solution_suggestion_events=',COUNT(*)) FROM solution_suggestion_events; SELECT CONCAT('phase5_migration=',COUNT(*)) FROM schema_migrations WHERE version='2026-09-13-fase5-actividades'; SELECT CONCAT('phase9_migration=',COUNT(*)) FROM schema_migrations WHERE version='2026-09-16-fase9-conocimiento'; SELECT CONCAT('activity_permissions=',COUNT(*)) FROM permissions WHERE code LIKE 'activities.%%'; SELECT CONCAT('knowledge_permissions=',COUNT(*)) FROM permissions WHERE code LIKE 'knowledge.%%'; SELECT CONCAT('activity_role_permissions=',COUNT(*)) FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE p.code LIKE 'activities.%%'; SELECT CONCAT('knowledge_role_permissions=',COUNT(*)) FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE p.code LIKE 'knowledge.%%';" > "%~1"
exit /b %errorlevel%

:protected_fingerprint
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 -N -B -e "SELECT CASE WHEN EXISTS(SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='%PROTECTED_DB%') THEN CONCAT('protected_exists=1;tables=',(SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='%PROTECTED_DB%' AND TABLE_TYPE='BASE TABLE'),';columns=',(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%PROTECTED_DB%'),';constraints=',(SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='%PROTECTED_DB%'),';triggers=',(SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='%PROTECTED_DB%')) ELSE 'protected_exists=0' END;" > "%~1"
exit /b %errorlevel%

:stability
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 -N -B "%DB_NAME%" --execute="source database/VERIFICAR_ESTABILIDAD_V2.sql" > "%TEMP%\helpdesk_f12_stability.txt"
if errorlevel 1 exit /b 1
type "%TEMP%\helpdesk_f12_stability.txt"
set "CHECKING=1"
set "STABILITY_FAILED=0"
for /f "usebackq tokens=1,2" %%A in ("%TEMP%\helpdesk_f12_stability.txt") do (
  if /I "%%A"=="MIGRACIONES_REGISTRADAS" set "CHECKING=0"
  if "!CHECKING!"=="1" (
    if not "%%B"=="0" (
      echo [FALLO] Estabilidad: %%A = %%B
      set "STABILITY_FAILED=1"
    )
  )
)
if "!STABILITY_FAILED!"=="1" exit /b 1
echo [OK] Controles de estabilidad sin hallazgos.
exit /b 0

:mysql_error
echo [ERROR] No se pudo conectar a MariaDB.
type "%TEMP%\helpdesk_f12_mysql_error.txt" 2>nul
exit /b 1
