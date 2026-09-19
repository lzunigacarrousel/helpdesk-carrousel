@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "DB_NAME=carrousel_helpdesk"
set "DB_USER=root"
set "DB_PASS="
set "FAILED=0"

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

call :run tests\phase12_communication_regression.php "Contrato de correo y notificaciones"
call :run tests\phase12_mail_health.php "Salud de configuracion de correo"

echo.
echo ------------------------------------------------------------
echo Trazabilidad real en BD
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 -N -B "%DB_NAME%" --execute="source database/VERIFICAR_FASE12_COMUNICACION_20260919.sql" > "%TEMP%\helpdesk_f12_communication.txt"
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
