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
echo  HELPDESK CARROUSEL - FASE 12 / SEGURIDAD Y SCOPES
echo  Base: %DB_NAME%
echo  Modo: SOLO LECTURA
echo ============================================================
echo.

if not exist "%MYSQL%" (
  echo [ERROR] No existe %MYSQL%
  exit /b 1
)

set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

call :run tests\phase12_security_scope_regression.php "Contrato transversal de seguridad"
call :run tests\phase9_knowledge_permissions_regression.php "Conocimiento - gobierno editorial"
call :run tests\phase6_agenda_view_scope_regression.php "Agenda - scope compartido"
call :run tests\itsm21_requester_scope_location_regression.php "Solicitante/Supervisor - ubicacion y alcance"
call :run tests\external_ticket_layout_regression.php "Proveedor - superficie limitada"
call :run tests\requester_return_visibility_smoke.php "Solicitante - visibilidad y reapertura"

echo.
echo ------------------------------------------------------------
echo Matriz real de permisos y scopes en BD
"%MYSQL%" %MYSQL_AUTH% -h 127.0.0.1 --default-character-set=utf8mb4 -N -B "%DB_NAME%" --execute="source database/VERIFICAR_FASE12_SEGURIDAD_20260919.sql" > "%TEMP%\helpdesk_f12_security.txt"
if errorlevel 1 (
  echo [FALLO] No se pudo ejecutar VERIFICAR_FASE12_SEGURIDAD_20260919.sql
  set "FAILED=1"
) else (
  type "%TEMP%\helpdesk_f12_security.txt"
  findstr /X /C:"PASS" "%TEMP%\helpdesk_f12_security.txt" >nul
  if errorlevel 1 (
    echo [FALLO] El gate SQL de seguridad no reporta PASS.
    set "FAILED=1"
  ) else (
    echo [OK] Matriz SQL de seguridad y scopes.
  )
)

if "%FAILED%"=="1" goto :fail

echo.
echo ============================================================
echo [OK] VALIDACION SEGURIDAD FASE 12 COMPLETADA SIN FALLOS
echo ADMIN/SEMIADMIN: gobierno administrativo y operativo validado
echo TECHNICIAN: operacion sin privilegios administrativos validada
echo MANAGEMENT: consulta sin operacion de tickets validada
echo SUPERVISOR: consulta por alcance validada
echo REQUESTER: informacion propia validada
echo EXTERNAL: casos compartidos y aislamiento interno validado
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
echo [ERROR] VALIDACION SEGURIDAD FASE 12 CON FALLOS
echo No cambies permisos ni datos hasta identificar el control exacto.
echo Produccion permanece bloqueada.
echo ============================================================
exit /b 1
