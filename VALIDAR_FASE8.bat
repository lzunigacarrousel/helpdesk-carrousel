@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - GATE FINAL FASE 8

echo  Rama esperada: fase8-calidad-proveedor
echo ============================================================
echo.

call :run tests\phase7_provider_participation_regression.php "Fase 7 - participacion proveedor"
call :run tests\phase7_provider_catalog_regression.php "Fase 7 - catalogo proveedor"
call :run tests\phase8_provider_cycle_identity_regression.php "Fase 8 - identidad de ciclos"
call :run tests\phase8_provider_rating_service_regression.php "Fase 8 - reglas de valoracion"
call :run tests\phase8_provider_rating_controller_regression.php "Fase 8 - controller y autorizacion"
call :run tests\phase8_provider_rating_ui_regression.php "Fase 8 - UI y privacidad"
call :run tests\phase8_provider_rating_report_regression.php "Fase 8 - informes y XLSX admin"
call :run tests\phase8_external_case_history_regression.php "Fase 8 - historial propio proveedor"
call :run tests\phase8_external_case_export_regression.php "Fase 8 - Excel propio proveedor"
call :run tests\phase8_provider_rating_closeout_regression.php "Fase 8 - cierre documental y CI"
call :run tests\static_checks.php "Checks estaticos"
call :run tests\project_quality.php "Calidad de rutas, vistas y CSS"
call :run tests\xlsx_smoke.php "Smoke XLSX"

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
echo [OK] GATE FINAL FASE 8 COMPLETADO SIN FALLOS

echo Siguiente paso: revisar git status y cerrar/integrar la rama.
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
echo [ERROR] GATE FASE 8 CON FALLOS

echo Corrige los puntos marcados antes de integrar a main.
echo ============================================================
exit /b 1
