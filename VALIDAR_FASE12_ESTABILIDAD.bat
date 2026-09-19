@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - FASE 12 / ESTABILIDAD Y ACEPTACION
echo  Entorno esperado: PC TEST
echo  Produccion: NO autorizada
echo ============================================================
echo.

call :run tests\phase12_operational_stability_regression.php "Contrato de estabilidad operativa"
call :run tests\phase12_runtime_health.php "Health runtime PC TEST"
call :run tests\static_checks.php "Checks estaticos"
call :run tests\project_quality.php "Rutas, vistas, CSS y CSRF"
call :run tests\navigation_ux_smoke.php "Navegacion y descargas"
call :run tests\xlsx_smoke.php "XLSX base"
call :run tests\phase10_specialized_exports_regression.php "Exportaciones especializadas"
call :run tests\phase8_external_case_export_regression.php "Exportacion historial proveedor"

echo.
echo ------------------------------------------------------------
echo Sintaxis JavaScript
where node >nul 2>&1
if errorlevel 1 (
  echo [AVISO] Node.js no esta disponible en PC TEST.
  echo [AVISO] La sintaxis JS sigue cubierta obligatoriamente por CI.
) else (
  for /R "public\assets\js" %%F in (*.js) do (
    node --check "%%F" >nul 2>&1
    if errorlevel 1 (
      echo [FALLO] JavaScript: %%F
      set "FAILED=1"
    )
  )
  if "%FAILED%"=="0" echo [OK] Sintaxis JavaScript.
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

echo.
echo ------------------------------------------------------------
echo Estado Git
git status --short

if "%FAILED%"=="1" goto :fail

echo.
echo ============================================================
echo [OK] VALIDACION ESTABILIDAD FASE 12 COMPLETADA SIN FALLOS
echo Runtime PHP/DB/storage: OK
echo Adjuntos y descargas: OK
echo Rutas/CSRF/CSS: OK
echo XLSX/exportaciones: OK
echo Navegacion: OK
echo Git diff: OK
echo.
echo PENDIENTE:
echo Completar checklist manual de aceptacion y matriz visual.
echo SMTP real sigue pendiente hasta disponer de app_url canonica.
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
echo [ERROR] VALIDACION ESTABILIDAD FASE 12 CON FALLOS
echo Corrige solo defectos reales antes del closeout.
echo Produccion permanece bloqueada.
echo ============================================================
exit /b 1
