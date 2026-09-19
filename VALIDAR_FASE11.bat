@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - GATE FASE 11 MANUAL
echo  Rama esperada: main
echo ============================================================
echo.

call :run tests\phase11_manual_profile_navigation_regression.php "Fase 11 - perfil y navegacion del manual"
call :run tests\phase11_manual_profile_content_regression.php "Fase 11 - contenido por perfil"
call :run tests\phase11_contextual_help_regression.php "Fase 11 - ayuda y FAQ contextual"
call :run tests\phase11_tutorial_depth_regression.php "Fase 11 - tutoriales de siete puntos"
call :run tests\phase11_manual_accessibility_regression.php "Fase 11 - UI responsive y accesibilidad"
call :run tests\button_semantics_regression.php "UI - semantica canonica de botones"
call :run tests\static_checks.php "Checks estaticos"
call :run tests\project_quality.php "Calidad de rutas, vistas y CSS"

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
echo [OK] GATE FASE 11 COMPLETADO SIN FALLOS
echo Este gate crecera con cada Task del Manual.
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
echo [ERROR] GATE FASE 11 CON FALLOS
echo ============================================================
exit /b 1
