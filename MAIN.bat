@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "APP_NAME=Helpdesk Carrousel 360"
set "DB_NAME=carrousel_helpdesk"
set "DB_USER=root"
set "DB_PASS="
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
set "PHP=C:\xampp\php\php.exe"
set "LOCAL_URL=http://localhost/HelpdeskCarrousel/public/"
set "SERVER_IP_URL=http://94.74.71.96/HelpdeskCarrousel/public/"
set "DOMAIN_URL=https://portal.carrousel-apps.com/HelpdeskCarrousel/public/"

:menu
cls
echo ============================================================
echo              %APP_NAME% - MAIN
echo ============================================================
echo Rama canonica: main
echo Base canonica: %DB_NAME%
echo.
echo [1] Abrir Helpdesk local
echo [2] Estado Git + auditoria de seguridad
echo [3] Actualizar main desde GitHub (pull --ff-only)
echo [4] Ver cambios / diff
echo [5] Validar PREPRODUCCION
echo [6] Reinstalar BD en PC TEST
echo [7] Respaldar BD actual
echo [8] Instalar PRODUCCION (solo BD vacia)
echo [9] Guardar cambios / Commit
echo [10] Subir main a GitHub / Push
echo [11] Historial Git
echo [12] Mostrar URLs
echo [0] Salir
echo.
set /p "OP=Seleccione una opcion: "

if "%OP%"=="1" goto open_local
if "%OP%"=="2" goto status
if "%OP%"=="3" goto pull
if "%OP%"=="4" goto diff
if "%OP%"=="5" goto validate
if "%OP%"=="6" goto install_test
if "%OP%"=="7" goto backup
if "%OP%"=="8" goto install_prod
if "%OP%"=="9" goto commit
if "%OP%"=="10" goto push
if "%OP%"=="11" goto history
if "%OP%"=="12" goto urls
if "%OP%"=="0" exit /b 0
goto menu

:open_local
start "" "%LOCAL_URL%"
goto menu

:status
echo.
git status
echo.
call :guard
echo.
echo Archivos raiz:
for %%F in (*.bat *.php *.json *.md) do echo   %%F
pause
goto menu

:pull
call :require_main || goto menu
call :require_clean || goto menu
echo.
git pull --ff-only origin main
pause
goto menu

:diff
echo.
git status --short
echo.
git diff --check
echo.
git diff
pause
goto menu

:validate
call :require_main || goto menu
call :guard || goto menu
if not exist "VALIDAR_PREPRODUCCION.bat" (
  echo [ERROR] Falta VALIDAR_PREPRODUCCION.bat
  pause
  goto menu
)
call ".\VALIDAR_PREPRODUCCION.bat"
pause
goto menu

:install_test
echo.
echo [ATENCION] Esta opcion reinstala carrousel_helpdesk en PC TEST.
echo El instalador crea respaldo antes de eliminar la BD de pruebas.
set /p "CONF=Escriba TEST para continuar: "
if /I not "%CONF%"=="TEST" goto menu
call ".\INSTALAR_PC_TEST.bat"
goto menu

:backup
if not exist "%MYSQLDUMP%" (
  echo [ERROR] No se encontro mysqldump: %MYSQLDUMP%
  pause
  goto menu
)
if not exist "backups" mkdir "backups"
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
if not defined STAMP set "STAMP=manual"
set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"
echo.
echo Respaldando %DB_NAME%...
"%MYSQLDUMP%" %MYSQL_AUTH% --single-transaction --routines --triggers --events --default-character-set=utf8mb4 "%DB_NAME%" > "backups\%DB_NAME%_%STAMP%.sql"
if errorlevel 1 (
  echo [ERROR] No se pudo crear el respaldo.
) else (
  echo [OK] backups\%DB_NAME%_%STAMP%.sql
)
pause
goto menu

:install_prod
call :require_main || goto menu
call :guard || goto menu
if not exist "INSTALAR_PRODUCCION.bat" (
  echo [ERROR] Falta INSTALAR_PRODUCCION.bat
  pause
  goto menu
)
call ".\INSTALAR_PRODUCCION.bat"
goto menu

:commit
call :require_main || goto menu
call :guard || goto menu
git diff --check
if errorlevel 1 (
  echo [BLOQUEADO] git diff --check encontro errores.
  pause
  goto menu
)
git status --short
echo.
set /p "MSG=Mensaje del commit: "
if "%MSG%"=="" goto menu
echo.
set /p "CONF=Escriba COMMIT para guardar todos los cambios: "
if /I not "%CONF%"=="COMMIT" goto menu
git add -A
git commit -m "%MSG%"
pause
goto menu

:push
call :require_main || goto menu
call :guard || goto menu
call :require_clean || goto menu
git push origin main
pause
goto menu

:history
git log --oneline --decorate -25
pause
goto menu

:urls
echo.
echo PC TEST : %LOCAL_URL%
echo SERVER  : %SERVER_IP_URL%
echo DOMINIO : %DOMAIN_URL%
echo PORTAL  : https://portal.carrousel-apps.com/portal/
pause
goto menu

:require_main
set "CURRENT_BRANCH="
for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"
if /I not "!CURRENT_BRANCH!"=="main" (
  echo [BLOQUEADO] Rama actual: !CURRENT_BRANCH!
  echo Cambie a main antes de continuar.
  pause
  exit /b 1
)
exit /b 0

:require_clean
git status --porcelain > "%TEMP%\helpdesk_main_status.txt"
for %%A in ("%TEMP%\helpdesk_main_status.txt") do set "STATUS_SIZE=%%~zA"
if not "!STATUS_SIZE!"=="0" (
  echo [BLOQUEADO] Hay cambios locales pendientes:
  type "%TEMP%\helpdesk_main_status.txt"
  del "%TEMP%\helpdesk_main_status.txt" >nul 2>&1
  pause
  exit /b 1
)
del "%TEMP%\helpdesk_main_status.txt" >nul 2>&1
exit /b 0

:guard
for %%F in ("config\local.php" ".env") do (
  if exist %%F (
    git ls-files --error-unmatch %%F >nul 2>&1
    if not errorlevel 1 (
      echo [BLOQUEADO] %%F contiene configuracion local y esta rastreado por Git.
      pause
      exit /b 1
    )
  )
)
git ls-files | findstr /i /r "storage/logs/ storage/attachments/ storage/ticket_uploads/ storage/exports/ backups/" | findstr /v /i ".gitkeep" >nul
if not errorlevel 1 (
  echo [BLOQUEADO] Hay archivos operativos o respaldos rastreados por Git.
  pause
  exit /b 1
)
echo [OK] Auditoria basica de archivos sensibles.
exit /b 0
