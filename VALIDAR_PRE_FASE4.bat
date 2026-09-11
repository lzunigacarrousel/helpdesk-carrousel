@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set /a FAIL=0
set /a WARN=0

cls
echo ============================================================
echo       HELPDESK CARROUSEL - GATE PRE FASE 4
echo ============================================================
echo.
echo Este validador es SOLO LECTURA sobre la base de datos.
echo No instala, no elimina, no migra y no modifica informacion.
echo.

for /f "delims=" %%B in ('git branch --show-current 2^>nul') do set "BRANCH=%%B"
if /i "!BRANCH!"=="ui-normalization-working" (
  echo [OK] Rama: !BRANCH!
) else (
  echo [FALLO] Rama actual: !BRANCH!
  echo         Debe ejecutarse sobre ui-normalization-working
  set /a FAIL+=1
)

if exist "%PHP%" (
  echo [OK] PHP XAMPP disponible
) else (
  echo [FALLO] No existe %PHP%
  set /a FAIL+=1
)

if exist "%MYSQL%" (
  echo [OK] Cliente MariaDB/MySQL XAMPP disponible
) else (
  echo [FALLO] No existe %MYSQL%
  set /a FAIL+=1
)

if not exist "config\local.php" (
  echo [FALLO] Falta config\local.php
  set /a FAIL+=1
) else (
  findstr /C:"'db_name' => 'carrousel_helpdesk'" "config\local.php" >nul 2>&1
  if errorlevel 1 (
    echo [FALLO] config\local.php no apunta a carrousel_helpdesk
    set /a FAIL+=1
  ) else (
    echo [OK] Configuracion apunta a carrousel_helpdesk
  )
)

echo.
echo ------------------------------------------------------------
echo SINTAXIS

echo ------------------------------------------------------------
if exist "%PHP%" call :php_syntax
where node >nul 2>&1
if errorlevel 1 (
  echo [AVISO] Node.js no esta en PATH. JavaScript queda cubierto por GitHub Actions.
  set /a WARN+=1
) else (
  call :js_syntax
)
where composer >nul 2>&1
if errorlevel 1 (
  echo [AVISO] Composer no esta en PATH. composer validate queda cubierto por GitHub Actions.
  set /a WARN+=1
) else (
  echo [TEST] Composer validate
  call composer validate --no-check-publish
  if errorlevel 1 (echo [FALLO] Composer validate&set /a FAIL+=1) else echo [OK] Composer validate
)

echo.
echo ------------------------------------------------------------
echo REGRESIONES DEL PROYECTO

echo ------------------------------------------------------------
if exist "%PHP%" (
  call :run_php "Static checks" "tests\static_checks.php"
  call :run_php "Seguridad del instalador" "tests\installer_safety_smoke.php"
  call :run_php "Tema oscuro" "tests\dark_theme_smoke.php"
  call :run_php "Cobertura standalone tema oscuro" "tests\dark_theme_coverage_smoke.php"
  call :run_php "Superficies publicas tema oscuro" "tests\dark_theme_public_smoke.php"
  call :run_php "Interacciones y animaciones tema oscuro" "tests\dark_theme_motion_smoke.php"
  call :run_php "Navegacion de adjuntos y notificaciones" "tests\navigation_ux_smoke.php"
  call :run_php "Selects buscables" "tests\searchable_select_smoke.php"
  call :run_php "UX solicitante" "tests\requester_ux_smoke.php"
  call :run_php "Operacion y SLA Fase 3" "tests\phase3_operational_smoke.php"
  call :run_php "Limpieza previa a Fase 4" "tests\pre_phase4_cleanup_smoke.php"
  call :run_php "Quality gate rutas, vistas y CSS" "tests\project_quality.php"
  call :run_php "Exportacion XLSX" "tests\xlsx_smoke.php"
)

echo.
echo ------------------------------------------------------------
echo BASE DE DATOS - SOLO LECTURA

echo ------------------------------------------------------------
if exist "%MYSQL%" (
  "%MYSQL%" -u root -N -B -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='carrousel_helpdesk';" 2>nul | findstr /x /c:"carrousel_helpdesk" >nul
  if errorlevel 1 (
    echo [FALLO] No se puede consultar carrousel_helpdesk con el usuario root local
    set /a FAIL+=1
  ) else (
    echo [OK] Base V2 carrousel_helpdesk disponible
    call :run_sql "Estructura canonica" "database\VERIFICAR_INSTALACION.sql"
    call :run_sql "Estabilidad V2" "database\VERIFICAR_ESTABILIDAD_V2.sql"
  )
)

echo.
echo ============================================================
echo RESULTADO AUTOMATICO

echo ============================================================
if !FAIL! EQU 0 (
  echo [OK] GATE AUTOMATICO APROBADO
) else (
  echo [FALLO] GATE AUTOMATICO NO APROBADO - !FAIL! fallo(s)
)
if !WARN! GTR 0 echo [AVISO] !WARN! validacion(es) quedan respaldadas por CI.

echo.
echo PENDIENTE VALIDACION MANUAL

echo ------------------------------------------------------------
echo [ ] Solicitante: OTP, crear, correo, seguimiento, respuesta

echo [ ] IT: cola, tomar, SLA, mensajes, espera, resolver

echo [ ] Proveedor: acceso limitado, responder, adjuntar, revocar

echo [ ] Gerencia/Supervision: scopes, dashboard, informes, XLSX

echo [ ] Correo: solicitante + integrantes activos de Equipo de soporte

echo [ ] Visual: botones, textos, tablas, claro/oscuro/system y animaciones

echo [ ] Responsive: 1920, 1366, 1024/768 y movil ^<=760

echo.
echo Guia: docs\VALIDACION_PRE_FASE4.md

echo.
if !FAIL! EQU 0 (
  echo No avanzar a Fase 4 hasta completar tambien la validacion manual.
) else (
  echo Corrige los fallos antes de realizar la validacion manual.
)
echo.
pause
exit /b !FAIL!

:run_php
set "TEST_NAME=%~1"
set "TEST_FILE=%~2"
echo [TEST] !TEST_NAME!
"%PHP%" "!TEST_FILE!"
if errorlevel 1 (
  echo [FALLO] !TEST_NAME!
  set /a FAIL+=1
) else (
  echo [OK] !TEST_NAME!
)
exit /b 0

:run_sql
set "SQL_NAME=%~1"
set "SQL_FILE=%~2"
echo [TEST] !SQL_NAME!
"%MYSQL%" -u root --default-character-set=utf8mb4 < "!SQL_FILE!"
if errorlevel 1 (
  echo [FALLO] !SQL_NAME!
  set /a FAIL+=1
) else (
  echo [OK] !SQL_NAME!
)
exit /b 0

:php_syntax
echo [TEST] Sintaxis PHP
set /a PHP_ERRORS=0
for %%D in (app config public tests tools) do (
  if exist "%%D" (
    for /r "%%D" %%F in (*.php) do (
      "%PHP%" -l "%%F" >nul 2>&1
      if errorlevel 1 (
        echo [FALLO] PHP: %%F
        set /a PHP_ERRORS+=1
      )
    )
  )
)
if !PHP_ERRORS! GTR 0 (
  set /a FAIL+=1
) else (
  echo [OK] Sintaxis PHP
)
exit /b 0

:js_syntax
echo [TEST] Sintaxis JavaScript
set /a JS_ERRORS=0
if exist "public\assets\js" (
  for /r "public\assets\js" %%F in (*.js) do (
    node --check "%%F" >nul 2>&1
    if errorlevel 1 (
      echo [FALLO] JavaScript: %%F
      set /a JS_ERRORS+=1
    )
  )
)
if !JS_ERRORS! GTR 0 (
  set /a FAIL+=1
) else (
  echo [OK] Sintaxis JavaScript
)
exit /b 0
