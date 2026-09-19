@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - FASE 12 / VISUAL Y RESPONSIVE
echo  Entorno esperado: PC TEST
echo  Gate automatico + matriz manual obligatoria
echo ============================================================
echo.

call :run tests\phase12_visual_responsive_regression.php "Contrato visual/responsive transversal"
call :run tests\dark_theme_smoke.php "Modo oscuro - base"
call :run tests\dark_theme_coverage_smoke.php "Modo oscuro - cobertura"
call :run tests\dark_theme_public_smoke.php "Modo oscuro - superficies publicas"
call :run tests\dark_theme_motion_smoke.php "Modo oscuro - interaccion/movimiento"
call :run tests\button_semantics_regression.php "Botones - jerarquia canonica"
call :run tests\phase10_ui_responsive_regression.php "Reportes - responsive"
call :run tests\phase11_manual_accessibility_regression.php "Manual - responsive/accesibilidad"
call :run tests\phase9_knowledge_ui_regression.php "Conocimiento - UI"
call :run tests\phase6_agenda_ui_regression.php "Agenda - UI"
call :run tests\phase6_agenda_filter_ux_regression.php "Agenda - filtros"
call :run tests\external_ticket_layout_regression.php "Proveedor - layout"
call :run tests\requester_ux_smoke.php "Solicitante - UX"
call :run tests\ux_tour_visual_regression.php "Tutorial - contraste y viewport"

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

if "%FAILED%"=="1" goto :fail

echo.
echo ============================================================
echo [OK] VALIDACION AUTOMATICA VISUAL FASE 12 SIN FALLOS
echo Claro/oscuro: contrato cubierto
echo Desktop 1920/1366: breakpoints cubiertos
echo iPad H/V: tablet preservada, no forzada a movil
echo Movil: breakpoints y tablas responsivas cubiertos
echo.
echo PENDIENTE OBLIGATORIO:
echo Completar docs\superpowers\checklists\fase12-visual-responsive-validacion-manual.md
echo con revision real por viewport, tema y perfil.
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
echo [ERROR] VALIDACION VISUAL FASE 12 CON FALLOS
echo Corrige solo defectos reales y agrega regresion.
echo Produccion permanece bloqueada.
echo ============================================================
exit /b 1
