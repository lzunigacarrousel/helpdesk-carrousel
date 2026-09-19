@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "DB_NAME=carrousel_helpdesk"
set "DB_USER=root"
set "DB_PASS="
set "FAILED=0"
set "BASELINE_FILE=%CD%\storage\logs\phase12_communication_baseline.txt"
set "BASELINE_ID="

echo ============================================================
echo  HELPDESK CARROUSEL - FASE 12 / CORREO Y NOTIFICACIONES
echo  Entorno esperado: PC TEST
echo  Modo: VALIDACION SEGURA, SIN ENVIO AUTOMATICO
echo ============================================================
echo.

if not exist "%MYSQL%" (
  echo [ERROR] No existe %MYSQL%
  exit /b 1
)

set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

if not exist "%CD%\storage\logs" mkdir "%CD%\storage\logs"

set "CURRENT_MAX_FILE=%TEMP%\helpdesk_f12_current_delivery_id.txt"
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 -N -B "%DB_NAME%" -e "SELECT COALESCE(MAX(id),0) FROM notification_deliveries;" > "%CURRENT_MAX_FILE%"
if errorlevel 1 (
  echo [ERROR] No se pudo consultar el ID maximo de notification_deliveries.
  exit /b 1
)
set "CURRENT_MAX_ID="
set /p CURRENT_MAX_ID=<"%CURRENT_MAX_FILE%"
if not defined CURRENT_MAX_ID set "CURRENT_MAX_ID=0"

set "BASELINE_NEEDS_WRITE=0"
if exist "%BASELINE_FILE%" set /p BASELINE_ID=<"%BASELINE_FILE%"
if not defined BASELINE_ID (
  set "BASELINE_ID=!CURRENT_MAX_ID!"
  set "BASELINE_NEEDS_WRITE=1"
)
if "!BASELINE_ID!"=="0" (
  if not "!CURRENT_MAX_ID!"=="0" (
    set "BASELINE_ID=!CURRENT_MAX_ID!"
    set "BASELINE_NEEDS_WRITE=1"
    echo [INFO] Baseline invalido 0 detectado; se recalculara.
  )
)
if "!BASELINE_NEEDS_WRITE!"=="1" (
  >"%BASELINE_FILE%" echo !BASELINE_ID!
  echo [INFO] Linea base de comunicacion guardada en ID !BASELINE_ID!.
)
echo [INFO] Baseline de entregas: !BASELINE_ID!

call :run tests\phase12_communication_regression.php "Contrato de correo y notificaciones"
call :run tests\phase12_mail_health.php "Salud de configuracion de correo"

echo.
echo ------------------------------------------------------------
echo Trazabilidad real en BD
set "COMM_SQL_RUN=%TEMP%\helpdesk_f12_communication_run.sql"
>"%COMM_SQL_RUN%" echo SET @phase12_baseline_delivery_id=!BASELINE_ID!;
type "database\VERIFICAR_FASE12_COMUNICACION_20260919.sql" >> "%COMM_SQL_RUN%"
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 -N -B "%DB_NAME%" < "%COMM_SQL_RUN%" > "%TEMP%\helpdesk_f12_communication.txt"
if errorlevel 1 (
  echo [FALLO] No se pudo ejecutar VERIFICAR_FASE12_COMUNICACION_20260919.sql
  set "FAILED=1"
) else (
  type "%TEMP%\helpdesk_f12_communication.txt"
  findstr /X /C:"PASS" "%TEMP%\helpdesk_f12_communication.txt" >nul
  if errorlevel 1 (
    echo [FALLO] El gate SQL de comunicacion no reporta PASS.
    set "FAILED=1"
  ) else (
    echo [OK] Trazabilidad SQL de correo y notificaciones.
  )
)

if "%FAILED%"=="1" goto :fail

echo.
echo ============================================================
echo [OK] VALIDACION COMUNICACION FASE 12 COMPLETADA SIN FALLOS
echo OTP y rate limit: OK
echo Eventos de ticket: OK
echo Nota interna sin correo: OK
echo Proveedor agregado/respondio/revocado: OK
echo Reintentos y panel admin/correo: OK
echo Logo CID y configuracion: OK
echo Integridad de entregas: PASS
echo URLs locales historicas: visibles, no bloqueantes
echo URLs locales nuevas: 0
echo.
echo IMPORTANTE:
echo Este gate NO envia correos reales.
echo La prueba SMTP/Gmail/Outlook es manual y explicita desde /admin/correo.
echo Produccion: NO modificada
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
echo [ERROR] VALIDACION COMUNICACION FASE 12 CON FALLOS
echo No envies pruebas reales hasta identificar el control exacto.
echo Produccion permanece bloqueada.
echo ============================================================
exit /b 1
