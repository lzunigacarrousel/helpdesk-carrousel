@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - GATE FINAL FASE 9
echo  Rama esperada: main
echo ============================================================
echo.

call :run tests\phase9_knowledge_schema_regression.php "Fase 9 - esquema y migracion"
call :run tests\phase9_knowledge_revision_service_regression.php "Fase 9 - nucleo de revisiones"
call :run tests\phase9_knowledge_permissions_regression.php "Fase 9 - permisos editoriales"
call :run tests\phase9_knowledge_workflow_controller_regression.php "Fase 9 - workflow controller"
call :run tests\phase9_knowledge_candidate_regression.php "Fase 9 - candidatos a conocimiento"
call :run tests\phase9_knowledge_reference_regression.php "Fase 9 - referencias de solucion"
call :run tests\phase9_solution_suggestions_regression.php "Fase 9 - sugerencias versionadas"
call :run tests\phase9_self_service_regression.php "Fase 9 - autoservicio publico"
call :run tests\phase9_knowledge_history_regression.php "Fase 9 - historial y comparacion"
call :run tests\phase9_knowledge_metrics_regression.php "Fase 9 - metricas de conocimiento"
call :run tests\phase9_knowledge_ui_regression.php "Fase 9 - UI manual y tutorial"
call :run tests\phase9_knowledge_legacy_read_regression.php "Fase 9 - lecturas transversales versionadas"
call :run tests\phase9_closeout_regression.php "Fase 9 - cierre documental y CI"
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
echo [OK] GATE FINAL FASE 9 COMPLETADO SIN FALLOS
echo.
echo Este resultado NO autoriza produccion por si solo.
echo Antes de desplegar deben quedar GREEN:
echo   1. migracion y verificacion en PC TEST
echo   2. matriz manual responsive/seguridad
echo   3. CI remoto
echo   4. aprobacion explicita
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
echo [ERROR] GATE FASE 9 CON FALLOS
echo Corrige los puntos marcados antes de considerar el cierre.
echo ============================================================
exit /b 1
