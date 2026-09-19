@echo off
setlocal EnableExtensions
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"
set "FAILED=0"

echo ============================================================
echo  HELPDESK CARROUSEL - FASE 12 / FLUJOS E2E
echo  Entorno esperado: PC TEST
echo  Ensayo BD: transaccion + ROLLBACK
echo ============================================================
echo.

call :run tests\phase12_e2e_contract_regression.php "Contrato transversal E2E"
call :run tests\requester_ux_smoke.php "Crear ticket - solicitante"
call :run tests\phase3_operational_smoke.php "Tomar, alcance, SLA y ciclo"
call :run tests\phase4_feedback_regression.php "Confirmar y reabrir"
call :run tests\phase5_activities_service_regression.php "Actividades - servicio"
call :run tests\phase6_agenda_service_regression.php "Agenda - integración"
call :run tests\phase7_provider_participation_regression.php "Proveedor - participación"
call :run tests\phase7_provider_returns_regression.php "Proveedor - devolución y seguimiento"
call :run tests\phase9_knowledge_reference_regression.php "Conocimiento - usar como referencia"
call :run tests\phase10_ticket_sla_xlsx_regression.php "Reportes - ciclo y XLSX"

echo.
echo ------------------------------------------------------------
echo Ensayo transaccional real en carrousel_helpdesk
"%PHP%" "tests\phase12_e2e_transactional.php"
if errorlevel 1 (
  echo [FALLO] Ensayo transaccional E2E
  set "FAILED=1"
) else (
  echo [OK] Ensayo transaccional E2E
)

if "%FAILED%"=="1" goto :fail

echo.
echo ============================================================
echo [OK] VALIDACION E2E FASE 12 COMPLETADA SIN FALLOS
echo Crear ticket: OK
echo Tomar/asignar y conversacion: OK
echo Proveedor y espera/continuar: OK
echo Actividad/Agenda: OK
echo Referencia/Conocimiento: OK
echo Resolver/reabrir/cerrar/NPS: OK
echo Reportes/XLSX: OK
echo Caso temporal: ROLLBACK
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
echo [ERROR] VALIDACION E2E FASE 12 CON FALLOS
echo El ensayo transaccional hace ROLLBACK ante error.
echo No despliegues produccion.
echo ============================================================
exit /b 1
