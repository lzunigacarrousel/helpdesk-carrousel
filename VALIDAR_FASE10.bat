@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - GATE FASE 10 REPORTES
echo  Rama esperada: main
echo ============================================================
echo.

call :run tests\phase10_reports_hub_regression.php "Fase 10 - hub de reportes"
call :run tests\phase10_knowledge_report_regression.php "Fase 10 - resumen de conocimiento"
call :run tests\phase10_activity_report_regression.php "Fase 10 - resumen de actividades"
call :run tests\phase10_ticket_sla_xlsx_regression.php "Fase 10 - tickets, SLA y XLSX"
call :run tests\button_semantics_regression.php "UI - semantica canonica de botones"
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
echo [OK] GATE FASE 10 COMPLETADO SIN FALLOS
echo Este gate crecera con cada Task de Reportes.
echo Produccion sigue fuera de alcance.
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
echo [ERROR] GATE FASE 10 CON FALLOS
echo ============================================================
exit /b 1
