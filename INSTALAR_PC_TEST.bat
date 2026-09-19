@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "APP_DIR=%CD%"
set "DB_NAME=carrousel_helpdesk"
set "PROTECTED_DB=helpdesk_carrousel"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
set "PHP=C:\xampp\php\php.exe"
set "DB_USER=root"
set "DB_PASS="

cls
echo ============================================================
echo       HELPDESK CARROUSEL - INSTALACION LIMPIA PC TEST
echo ============================================================
echo.
echo Nueva base V2: %DB_NAME%
echo Base historica protegida: %PROTECTED_DB%
echo Carpeta actual: %APP_DIR%
echo.

if /I "%DB_NAME%"=="%PROTECTED_DB%" (
  echo [ERROR] PROTECCION ACTIVADA.
  echo El instalador V2 nunca puede operar sobre %PROTECTED_DB%.
  pause
  exit /b 1
)

if /I not "%DB_NAME%"=="carrousel_helpdesk" (
  echo [ERROR] Nombre de base V2 inesperado: %DB_NAME%
  echo Se esperaba exclusivamente carrousel_helpdesk.
  pause
  exit /b 1
)

echo Flujo:
echo  1. Respaldar SOLO %DB_NAME% si existe.
echo  2. Eliminar SOLO %DB_NAME%.
echo  3. Crear TODO desde database\INSTALAR.sql.
echo  4. Instalar dependencias y ejecutar validaciones.
echo  5. Confirmar que la BD queda espejo limpio de produccion.
echo.
echo IMPORTANTE: %PROTECTED_DB% pertenece al Helpdesk anterior y NO se toca.
echo ADVERTENCIA: se perderan los datos actuales de %DB_NAME%
echo despues de crear el respaldo previo.
echo.

if not exist "%MYSQL%" (
  echo [ERROR] No se encontro MariaDB/MySQL de XAMPP: %MYSQL%
  pause
  exit /b 1
)
if not exist "%MYSQLDUMP%" (
  echo [ERROR] No se encontro mysqldump de XAMPP: %MYSQLDUMP%
  pause
  exit /b 1
)
if not exist "%PHP%" (
  echo [ERROR] No se encontro PHP de XAMPP: %PHP%
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
  if not exist "config\local.php.example" (
    echo [ERROR] Falta config\local.php.example
    pause
    exit /b 1
  )
  copy /y "config\local.php.example" "config\local.php" >nul
  echo [OK] Se creo config\local.php.
) else (
  echo [OK] Se conserva config\local.php existente.
)

findstr /L /C:"'db_name' => 'carrousel_helpdesk'" "config\local.php" >nul
if errorlevel 1 (
  echo.
  echo [ERROR] config\local.php no apunta a carrousel_helpdesk.
  echo Debe contener: 'db_name' =^> 'carrousel_helpdesk'
  echo No se modifico ninguna base de datos.
  pause
  exit /b 1
)

echo [OK] config\local.php apunta a %DB_NAME%.

echo.
set /p "CONFIRM=Escriba REINSTALAR para continuar: "
if /I not "%CONFIRM%"=="REINSTALAR" (
  echo [CANCELADO] No se modifico la base de datos.
  pause
  exit /b 0
)

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
if not defined STAMP set "STAMP=manual"
if not exist "backups" mkdir "backups"

set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"

"%MYSQL%" %MYSQL_AUTH% -N -B -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='%DB_NAME%';" > "%TEMP%\helpdesk_db_exists.txt" 2> "%TEMP%\helpdesk_mysql_error.txt"
if errorlevel 1 goto :mysql_error

set "DB_EXISTS="
for /f "usebackq delims=" %%A in ("%TEMP%\helpdesk_db_exists.txt") do set "DB_EXISTS=%%A"

if /I "%DB_EXISTS%"=="%DB_NAME%" (
  echo.
  echo [1/5] Creando respaldo previo de %DB_NAME%...
  "%MYSQLDUMP%" %MYSQL_AUTH% --single-transaction --routines --triggers --events --default-character-set=utf8mb4 "%DB_NAME%" > "backups\%DB_NAME%_antes_reinstalar_%STAMP%.sql"
  if errorlevel 1 (
    echo [ERROR] No se pudo crear el respaldo. Se cancela la reinstalacion.
    pause
    exit /b 1
  )
  echo [OK] Respaldo creado.
) else (
  echo.
  echo [1/5] %DB_NAME% no existe. No hay nada que respaldar.
)

echo.
echo [2/5] Creando base V2 canonica desde cero...
"%MYSQL%" %MYSQL_AUTH% -e "DROP DATABASE IF EXISTS `%DB_NAME%`;" || goto :sql_error
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\INSTALAR.sql" || goto :sql_error
echo [OK] INSTALAR.sql ejecutado.

echo.
echo [3/5] Preparando dependencias PHP...
where composer >nul 2>&1
if not errorlevel 1 (
  call composer install --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 goto :composer_error
) else if exist "C:\ProgramData\ComposerSetup\bin\composer.bat" (
  call "C:\ProgramData\ComposerSetup\bin\composer.bat" install --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 goto :composer_error
) else if exist "vendor\autoload.php" (
  echo [AVISO] Composer no esta en PATH, pero vendor\autoload.php ya existe.
) else (
  echo [ERROR] Composer no esta disponible y falta vendor\autoload.php.
  pause
  exit /b 1
)

echo.
echo [4/5] Ejecutando validaciones...
"%PHP%" tests\static_checks.php
if errorlevel 1 goto :test_error
"%PHP%" tests\installer_safety_smoke.php
if errorlevel 1 goto :test_error
"%PHP%" tests\project_quality.php
if errorlevel 1 goto :test_error
"%PHP%" tests\xlsx_smoke.php
if errorlevel 1 goto :test_error
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\VERIFICAR_INSTALACION.sql"
if errorlevel 1 goto :sql_error

echo.
echo [5/5] Verificando espejo limpio de produccion...
"%MYSQL%" %MYSQL_AUTH% -N -B --default-character-set=utf8mb4 < "database\VERIFICAR_PRODUCCION_LIMPIA.sql" > "%TEMP%\helpdesk_pc_test_clean.txt"
if errorlevel 1 goto :sql_error
type "%TEMP%\helpdesk_pc_test_clean.txt"
findstr /X /C:"PASS" "%TEMP%\helpdesk_pc_test_clean.txt" >nul
if errorlevel 1 (
  echo [ERROR] La BD de PC TEST contiene datos operativos o legado.
  pause
  exit /b 1
)
echo [OK] PC TEST replica una produccion nueva sin datos operativos.

echo.
echo ============================================================
echo                 INSTALACION COMPLETADA
echo ============================================================
echo Base V2: %DB_NAME%
echo Base historica intacta: %PROTECTED_DB%
echo Administrador inicial: luis@carrousel.com.gt
echo Acceso: OTP
for %%P in ("%APP_DIR%") do set "APP_FOLDER=%%~nxP"
echo URL: http://localhost/!APP_FOLDER!/public/
echo OTP modo log: storage\logs\mail.log
echo ============================================================
pause
exit /b 0

:mysql_error
echo [ERROR] No se pudo conectar a MariaDB con el usuario %DB_USER%.
type "%TEMP%\helpdesk_mysql_error.txt" 2>nul
pause
exit /b 1

:sql_error
echo [ERROR] Fallo una instruccion SQL. La instalacion NO es valida.
echo La base protegida %PROTECTED_DB% no debe modificarse.
pause
exit /b 1

:composer_error
echo [ERROR] Composer no pudo instalar las dependencias.
pause
exit /b 1

:test_error
echo [ERROR] Una prueba del proyecto fallo. La instalacion NO es valida.
pause
exit /b 1
