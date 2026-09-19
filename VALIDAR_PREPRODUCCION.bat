@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "DB_NAME=carrousel_helpdesk"
set "DB_USER=root"
set "DB_PASS="
set "FAILED=0"

echo ============================================================
echo   HELPDESK CARROUSEL - GATE PREPRODUCCION
echo   Requiere PC TEST reinstalado desde INSTALAR.sql limpio
echo ============================================================
echo.

set "BRANCH="
for /f "delims=" %%B in ('git branch --show-current') do set "BRANCH=%%B"
if /I not "!BRANCH!"=="main" (
  echo [FALLO] Rama actual: !BRANCH!
  set "FAILED=1"
) else (
  echo [OK] Rama main.
)

git status --porcelain > "%TEMP%\helpdesk_preprod_status.txt"
for %%F in ("%TEMP%\helpdesk_preprod_status.txt") do set "STATUS_SIZE=%%~zF"
if not "!STATUS_SIZE!"=="0" (
  echo [FALLO] Working tree no esta limpio.
  type "%TEMP%\helpdesk_preprod_status.txt"
  set "FAILED=1"
) else (
  echo [OK] Working tree limpio.
)

call :run tests\preprod_clean_schema_regression.php "Contrato de esquema limpio"
call :run tests\static_checks.php "Checks estaticos"
call :run tests\project_quality.php "Rutas, vistas, CSS y CSRF"
call :run tests\xlsx_smoke.php "XLSX"
call :run tests\phase9_knowledge_revision_service_regression.php "Conocimiento - servicio versionado"
call :run tests\phase9_knowledge_schema_regression.php "Conocimiento - esquema"
call :run tests\phase9_knowledge_legacy_read_regression.php "Conocimiento - sin fallback legacy"
call :run tests\phase12_deployment_targets_regression.php "Destinos de despliegue"
call :run tests\phase12_runtime_health.php "Runtime PHP/DB/storage"

if not exist "%MYSQL%" (
  echo [FALLO] No existe %MYSQL%
  set "FAILED=1"
  goto :after_sql
)

set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

echo.
echo ------------------------------------------------------------
echo Verificacion SQL canonica
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\VERIFICAR_INSTALACION.sql"
if errorlevel 1 (
  echo [FALLO] VERIFICAR_INSTALACION.sql
  set "FAILED=1"
) else (
  echo [OK] Estructura canonica.
)

echo.
echo ------------------------------------------------------------
echo Verificacion de datos operativos vacios
"%MYSQL%" %MYSQL_AUTH% -N -B --default-character-set=utf8mb4 < "database\VERIFICAR_PRODUCCION_LIMPIA.sql" > "%TEMP%\helpdesk_prod_clean.txt"
if errorlevel 1 (
  echo [FALLO] VERIFICAR_PRODUCCION_LIMPIA.sql
  set "FAILED=1"
) else (
  type "%TEMP%\helpdesk_prod_clean.txt"
  findstr /X /C:"PASS" "%TEMP%\helpdesk_prod_clean.txt" >nul
  if errorlevel 1 (
    echo [FALLO] produccion_limpia_gate no reporta PASS.
    set "FAILED=1"
  ) else (
    echo [OK] Base espejo de produccion sin datos operativos.
  )
)

:after_sql
echo.
git diff --check
if errorlevel 1 (
  echo [FALLO] git diff --check
  set "FAILED=1"
) else (
  echo [OK] git diff --check
)

if "!FAILED!"=="1" goto :fail

echo.
echo ============================================================
echo [OK] PREPRODUCCION TECNICA GREEN
echo Repo: limpio
echo Esquema: canonico y sin legado
echo Datos operativos PC TEST: vacios
echo MAIN.bat: disponible
echo Produccion: NO modificada
echo.
echo Pendiente antes de go-live:
echo - Matriz visual manual
echo - app_url final
echo - SMTP real Gmail/Outlook
echo ============================================================
exit /b 0

:run
echo.
echo ------------------------------------------------------------
echo %~2
"%PHP%" "%~1"
if errorlevel 1 (
  echo [FALLO] %~2
  set "FAILED=1"
) else (
  echo [OK] %~2
)
exit /b 0

:fail
echo.
echo ============================================================
echo [ERROR] PREPRODUCCION CON FALLOS
echo No instalar ni habilitar produccion.
echo ============================================================
exit /b 1
