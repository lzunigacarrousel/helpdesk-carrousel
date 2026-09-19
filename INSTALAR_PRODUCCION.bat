@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "DB_NAME=carrousel_helpdesk"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "PHP=C:\xampp\php\php.exe"
set "DB_USER=root"
set "DB_PASS="

cls
echo ============================================================
echo      HELPDESK CARROUSEL - INSTALACION PRODUCCION
echo ============================================================
echo.
echo Este instalador SOLO funciona sobre una BD inexistente o vacia.
echo NUNCA ejecuta DROP DATABASE.
echo Ruta esperada: /HelpdeskCarrousel/public/
echo.

if not exist "%MYSQL%" (
  echo [ERROR] No se encontro MySQL/MariaDB: %MYSQL%
  pause
  exit /b 1
)
if not exist "%PHP%" (
  echo [ERROR] No se encontro PHP: %PHP%
  pause
  exit /b 1
)
if not exist "database\INSTALAR.sql" (
  echo [ERROR] Falta database\INSTALAR.sql
  pause
  exit /b 1
)
if not exist "database\VERIFICAR_INSTALACION.sql" (
  echo [ERROR] Falta database\VERIFICAR_INSTALACION.sql
  pause
  exit /b 1
)
if not exist "database\VERIFICAR_PRODUCCION_LIMPIA.sql" (
  echo [ERROR] Falta database\VERIFICAR_PRODUCCION_LIMPIA.sql
  pause
  exit /b 1
)
if not exist "config\local.php" (
  echo [ERROR] Falta config\local.php.
  echo Copie config\local.php.example y configure el servidor antes de continuar.
  pause
  exit /b 1
)

findstr /L /C:"'db_name' => 'carrousel_helpdesk'" "config\local.php" >nul
if errorlevel 1 (
  echo [ERROR] config\local.php no apunta a carrousel_helpdesk.
  pause
  exit /b 1
)

findstr /L /C:"http://94.74.71.96/HelpdeskCarrousel/public/" "config\local.php" >nul
if not errorlevel 1 goto :app_url_ok
findstr /L /C:"https://portal.carrousel-apps.com/HelpdeskCarrousel/public/" "config\local.php" >nul
if errorlevel 1 (
  echo [ERROR] app_url de produccion no esta configurada.
  echo Use una de estas URLs:
  echo   http://94.74.71.96/HelpdeskCarrousel/public/
  echo   https://portal.carrousel-apps.com/HelpdeskCarrousel/public/
  pause
  exit /b 1
)

:app_url_ok
set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

"%MYSQL%" %MYSQL_AUTH% -N -B -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_TYPE='BASE TABLE';" > "%TEMP%\helpdesk_prod_tables.txt"
if errorlevel 1 (
  echo [ERROR] No se pudo consultar MariaDB.
  pause
  exit /b 1
)
set "TABLE_COUNT="
set /p TABLE_COUNT=<"%TEMP%\helpdesk_prod_tables.txt"
if not defined TABLE_COUNT set "TABLE_COUNT=0"

if not "%TABLE_COUNT%"=="0" (
  echo [BLOQUEADO] %DB_NAME% ya contiene %TABLE_COUNT% tablas.
  echo Este instalador no modifica una produccion ya inicializada.
  pause
  exit /b 1
)

echo.
echo Destinos permitidos:
echo   http://94.74.71.96/HelpdeskCarrousel/public/
echo   https://portal.carrousel-apps.com/HelpdeskCarrousel/public/
echo.
set /p "CONFIRM=Escriba INSTALAR PRODUCCION para continuar: "
if /I not "%CONFIRM%"=="INSTALAR PRODUCCION" (
  echo [CANCELADO] No se modifico la base.
  pause
  exit /b 0
)

echo.
echo [1/4] Instalando dependencias PHP de produccion...
where composer >nul 2>&1
if not errorlevel 1 (
  call composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 goto :composer_error
) else if exist "C:\ProgramData\ComposerSetup\bin\composer.bat" (
  call "C:\ProgramData\ComposerSetup\bin\composer.bat" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 goto :composer_error
) else if exist "vendor\autoload.php" (
  echo [AVISO] Composer no esta en PATH, pero vendor\autoload.php existe.
) else (
  echo [ERROR] Composer no esta disponible y falta vendor\autoload.php.
  pause
  exit /b 1
)

echo.
echo [2/4] Creando esquema canonico...
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\INSTALAR.sql"
if errorlevel 1 goto :sql_error

echo.
echo [3/4] Verificando estructura...
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\VERIFICAR_INSTALACION.sql"
if errorlevel 1 goto :sql_error

echo.
echo [4/4] Verificando produccion sin datos operativos...
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\VERIFICAR_PRODUCCION_LIMPIA.sql"
if errorlevel 1 goto :sql_error

echo.
echo ============================================================
echo [OK] INSTALACION DE PRODUCCION COMPLETADA
echo Base: %DB_NAME%
echo Datos operativos: VACIOS
echo Administrador inicial: luis@carrousel.com.gt
echo Produccion queda instalada, pero el go-live requiere validacion manual.
echo ============================================================
pause
exit /b 0

:composer_error
echo [ERROR] Composer no pudo preparar dependencias.
pause
exit /b 1

:sql_error
echo [ERROR] Fallo la instalacion/verificacion SQL.
echo No ejecute DROP ni intente reparar manualmente sin revisar el error.
pause
exit /b 1
