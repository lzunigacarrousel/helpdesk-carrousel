@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - CLOSEOUT TECNICO FASE 12
echo  Cierre de las 12 fases en PC TEST
echo  Produccion NO autorizada
echo ============================================================
echo.

set "BRANCH="
for /f "delims=" %%B in ('git branch --show-current') do set "BRANCH=%%B"
if /I not "!BRANCH!"=="main" (
  echo [FALLO] Rama actual: !BRANCH!
  echo [FALLO] El closeout debe ejecutarse sobre main.
  set "FAILED=1"
) else (
  echo [OK] Rama main.
)

git status --porcelain > "%TEMP%\helpdesk_f12_closeout_status.txt"
for %%F in ("%TEMP%\helpdesk_f12_closeout_status.txt") do set "STATUS_SIZE=%%~zF"
if not "!STATUS_SIZE!"=="0" (
  echo [FALLO] Working tree no esta limpio.
  type "%TEMP%\helpdesk_f12_closeout_status.txt"
  set "FAILED=1"
) else (
  echo [OK] Working tree limpio.
)

echo.
echo ------------------------------------------------------------
echo Gate transversal Fase 12
call ".\VALIDAR_FASE12.bat"
if errorlevel 1 (
  echo [FALLO] VALIDAR_FASE12.bat
  set "FAILED=1"
) else (
  echo [OK] Gate transversal Fase 12.
)

echo.
echo ------------------------------------------------------------
echo Regresion de closeout
"%PHP%" "tests\phase12_closeout_regression.php"
if errorlevel 1 (
  echo [FALLO] phase12_closeout_regression.php
  set "FAILED=1"
) else (
  echo [OK] Closeout documental y tecnico.
)

echo.
echo ------------------------------------------------------------
echo Git diff check
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
echo [OK] CLOSEOUT TECNICO FASE 12 COMPLETADO SIN FALLOS
echo Las 12 fases quedan integradas y validadas tecnicamente en PC TEST.
echo APP_VERSION permanece 2.4.0-dev.
echo.
echo PENDIENTES PREPRODUCCION:
echo - Matriz visual manual real.
echo - app_url canonica del entorno final.
echo - SMTP real y revision Gmail/Outlook.
echo.
echo Produccion NO autorizada por este closeout.
echo No se crea tag, release ni despliegue.
echo ============================================================
exit /b 0

:fail
echo.
echo ============================================================
echo [ERROR] CLOSEOUT TECNICO FASE 12 CON FALLOS
echo No cierres ni despliegues produccion.
echo ============================================================
exit /b 1
