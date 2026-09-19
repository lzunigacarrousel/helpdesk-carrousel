@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "APP_NAME=Helpdesk Carrousel"
set "DB_NAME=carrousel_helpdesk"
set "DB_USER=root"
set "DB_PASS="
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
set "LOCAL_URL=http://localhost/HelpdeskCarrousel/public/"

:menu
cls
echo ============================================================
echo                     %APP_NAME%
echo ============================================================
echo Estado: PREPRODUCCION
echo Rama: main
echo Base: %DB_NAME%
echo.
echo [1] Abrir Helpdesk local
echo [2] Estado y cambios Git
echo [3] Actualizar desde GitHub
echo [4] Validar PREPRODUCCION
echo [5] Reinstalar PC TEST
echo [6] Respaldar BD
echo [7] Guardar cambios ^(Commit^)
echo [8] Subir a GitHub ^(Push^)
echo [9] Instalar PRODUCCION
echo [0] Salir
echo.
set /p "OP=Seleccione una opcion: "

if "%OP%"=="1" goto open_local
if "%OP%"=="2" goto status
if "%OP%"=="3" goto pull
if "%OP%"=="4" goto validate
if "%OP%"=="5" goto install_test
if "%OP%"=="6" goto backup
if "%OP%"=="7" goto commit
if "%OP%"=="8" goto push
if "%OP%"=="9" goto install_prod
if "%OP%"=="0" exit /b 0
goto menu

:open_local
start "" "%LOCAL_URL%"
goto menu

:status
echo.
call :guard
echo.
git status --short
echo.
git diff --check
echo.
git log -1 --oneline
pause
goto menu

:pull
call :require_main || goto menu
call :require_clean || goto menu
echo.
git pull --ff-only origin main
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
echo [ATENCION] Reinstala SOLO %DB_NAME% en PC TEST.
echo El instalador crea un respaldo antes de reconstruir la base.
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

:commit
call :require_main || goto menu
call :guard || goto menu
git diff --check
if errorlevel 1 (
  echo [BLOQUEADO] git diff --check encontro errores.
  pause
  goto menu
)
echo.
git status --short
echo.
set /p "MSG=Mensaje del commit: "
if "%MSG%"=="" goto menu
set /p "CONF=Escriba COMMIT para guardar los cambios: "
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

:install_prod
call :require_main || goto menu
call :guard || goto menu
call :require_clean || goto menu
if not exist "INSTALAR_PRODUCCION.bat" (
  echo [ERROR] Falta INSTALAR_PRODUCCION.bat
  pause
  goto menu
)
call ".\INSTALAR_PRODUCCION.bat"
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
