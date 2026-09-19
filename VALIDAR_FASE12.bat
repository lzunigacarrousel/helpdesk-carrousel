@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"
set "FAIL_LOG=%TEMP%\helpdesk_phase12_failures_%RANDOM%.txt"
type nul > "%FAIL_LOG%"

echo ============================================================
echo  HELPDESK CARROUSEL - GATE FASE 12 VALIDACION INTEGRAL
echo  Rama esperada: main
echo  Produccion: NO autorizada por este gate
echo ============================================================
echo.

call :run tests\phase12_release_baseline_regression.php "Fase 12 - preflight y baseline"
call :run tests\phase12_database_integrity_regression.php "Fase 12 - seguridad e idempotencia BD"
call :run tests\phase12_security_scope_regression.php "Fase 12 - perfiles permisos y scopes"
call :run tests\phase12_e2e_contract_regression.php "Fase 12 - contrato E2E transversal"
call :run tests\phase12_communication_regression.php "Fase 12 - correo y notificaciones"
call :run tests\phase12_mail_health.php "Fase 12 - salud correo"
call :run tests\phase12_visual_responsive_regression.php "Fase 12 - visual y responsive"
call :run tests\installer_safety_smoke.php "Instalacion - seguridad"
call :run tests\requester_ux_smoke.php "Solicitante - UX base"
call :run tests\phase3_operational_smoke.php "Tickets y SLA - operacion base"
call :run tests\phase4_feedback_regression.php "Feedback - resolucion y reapertura"

call :run tests\phase5_activities_schema_regression.php "Actividades - esquema"
call :run tests\phase5_activities_service_regression.php "Actividades - servicio"
call :run tests\phase5_activities_ui_regression.php "Actividades - UI"

call :run tests\phase6_agenda_service_regression.php "Agenda - servicio y scope"
call :run tests\phase6_agenda_ui_regression.php "Agenda - UI"
call :run tests\phase6_agenda_filter_ux_regression.php "Agenda - filtros"

call :run tests\phase7_provider_participation_regression.php "Proveedores - participacion"
call :run tests\phase7_provider_returns_regression.php "Proveedores - devoluciones"
call :run tests\phase8_provider_rating_closeout_regression.php "Proveedores - calidad y closeout"

call :run tests\phase9_knowledge_permissions_regression.php "Conocimiento - permisos"
call :run tests\phase9_self_service_regression.php "Conocimiento - autoservicio"
call :run tests\phase9_closeout_regression.php "Conocimiento - closeout"

call :run tests\phase10_ticket_sla_xlsx_regression.php "Reportes - tickets SLA y XLSX"
call :run tests\phase10_ui_responsive_regression.php "Reportes - UI responsive"
call :run tests\phase10_closeout_regression.php "Reportes - closeout"

call :run tests\phase11_contextual_help_regression.php "Manual - ayuda contextual"
call :run tests\phase11_manual_accessibility_regression.php "Manual - accesibilidad"
call :run tests\phase11_closeout_regression.php "Manual - closeout"

call :run tests\navigation_ux_smoke.php "Navegacion - UX"
call :run tests\dark_theme_smoke.php "Visual - modo oscuro"
call :run tests\dark_theme_coverage_smoke.php "Visual - cobertura oscuro"
call :run tests\dark_theme_public_smoke.php "Visual - superficies publicas"
call :run tests\dark_theme_motion_smoke.php "Visual - interaccion y movimiento"
call :run tests\button_semantics_regression.php "Visual - botones canonicos"

call :run tests\static_checks.php "Checks estaticos"
call :run tests\project_quality.php "Calidad de rutas vistas y CSS"
call :run tests\xlsx_smoke.php "Smoke XLSX"

echo.
echo ------------------------------------------------------------
echo Git diff check
git diff --check
if errorlevel 1 (
    echo [FALLO] git diff --check
    >>"%FAIL_LOG%" echo git diff --check
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
echo [OK] GATE AUTOMATIZADO FASE 12 COMPLETADO SIN FALLOS
echo Este resultado es PRELIMINAR.
echo Aun faltan: BD, perfiles/scopes, E2E, correo/notificaciones
echo y matriz visual manual antes del closeout final.
echo Produccion NO queda autorizada.
echo ============================================================
del /q "%FAIL_LOG%" >nul 2>&1
exit /b 0

:run
echo.
echo ------------------------------------------------------------
echo %~2
"%PHP%" "%~1"
if errorlevel 1 (
    echo [FALLO] %~2
    >>"%FAIL_LOG%" echo %~2
    set "FAILED=1"
) else (
    echo [OK] %~2
)
exit /b 0

:fail
echo.
echo ============================================================
echo [ERROR] GATE FASE 12 CON FALLOS
echo.
echo RESUMEN DE FALLOS:
if exist "%FAIL_LOG%" type "%FAIL_LOG%"
echo.
echo Corrige solamente defectos reales y agrega regresion.
echo Produccion permanece bloqueada.
echo ============================================================
del /q "%FAIL_LOG%" >nul 2>&1
exit /b 1
