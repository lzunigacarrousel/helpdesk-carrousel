@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "APP_DIR=%CD%"
set "DB_NAME=helpdesk_carrousel"
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
echo Esta opcion prepara una instalacion NUEVA de la aplicacion.
echo.
echo Base canonica que se recreara: %DB_NAME%
echo Carpeta actual: %APP_DIR%
echo.
echo Se hara lo siguiente:
echo  1. Respaldar %DB_NAME% si ya existe.
echo  2. Eliminar y recrear %DB_NAME%.
echo  3. Crear el esquema V2 actual.
echo  4. Cargar regiones y parques Carrousel.
echo  5. Instalar dependencias PHP.
echo  6. Ejecutar verificaciones de codigo y base de datos.
echo.
echo ADVERTENCIA: los tickets, usuarios y pruebas que existan actualmente
echo dentro de %DB_NAME% se perderan despues de crear el respaldo.
echo.

if not exist "%MYSQL%" (
  echo [ERROR] No se encontro MariaDB/MySQL de XAMPP en:
  echo         %MYSQL%
  pause
  exit /b 1
)
if not exist "%MYSQLDUMP%" (
  echo [ERROR] No se encontro mysqldump de XAMPP en:
  echo         %MYSQLDUMP%
  pause
  exit /b 1
)
if not exist "%PHP%" (
  echo [ERROR] No se encontro PHP de XAMPP en:
  echo         %PHP%
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
  echo [OK] Se creo config\local.php para PC TEST.
) else (
  echo [OK] Se conserva config\local.php existente.
)

echo.
set /p "CONFIRM=Escriba REINSTALAR para continuar: "
if /I not "%CONFIRM%"=="REINSTALAR" (
  echo.
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
  echo [1/6] Creando respaldo previo...
  "%MYSQLDUMP%" %MYSQL_AUTH% --single-transaction --routines --triggers --events --default-character-set=utf8mb4 "%DB_NAME%" > "backups\%DB_NAME%_antes_reinstalar_%STAMP%.sql"
  if errorlevel 1 (
    echo [ERROR] No se pudo crear el respaldo. La reinstalacion se detuvo.
    pause
    exit /b 1
  )
  echo [OK] Respaldo: backups\%DB_NAME%_antes_reinstalar_%STAMP%.sql
) else (
  echo.
  echo [1/6] La base %DB_NAME% no existe. No hay nada que respaldar.
)

echo.
echo [2/6] Recreando base de datos...
"%MYSQL%" %MYSQL_AUTH% -e "DROP DATABASE IF EXISTS `%DB_NAME%`;" || goto :sql_error
powershell -NoProfile -Command "$c=[IO.File]::ReadAllText('database\INSTALAR.sql'); $c=$c.Replace('helpdesk_carrousel_test','helpdesk_carrousel'); [IO.File]::WriteAllText('%TEMP%\helpdesk_instalar.sql',$c,(New-Object Text.UTF8Encoding($false)))" || goto :sql_error
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "%TEMP%\helpdesk_instalar.sql" || goto :sql_error
echo [OK] Esquema base creado.

echo.
echo [3/6] Consolidando estructura final actual...
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\FINALIZAR_ESQUEMA_V2.sql" || goto :sql_error
echo [OK] Estructura final aplicada.

echo.
echo [4/6] Cargando catalogos Carrousel...
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\CATALOGOS_CARROUSEL.sql" || goto :sql_error
echo [OK] Catalogos cargados.

echo.
echo [5/6] Preparando dependencias PHP...
where composer >nul 2>&1
if not errorlevel 1 (
  call composer install --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 (
    echo [ERROR] Composer no pudo instalar las dependencias.
    pause
    exit /b 1
  )
) else if exist "C:\ProgramData\ComposerSetup\bin\composer.bat" (
  call "C:\ProgramData\ComposerSetup\bin\composer.bat" install --no-interaction --prefer-dist --optimize-autoloader
  if errorlevel 1 (
    echo [ERROR] Composer no pudo instalar las dependencias.
    pause
    exit /b 1
  )
) else if exist "vendor\autoload.php" (
  echo [AVISO] Composer no esta en PATH, pero vendor\autoload.php ya existe.
) else (
  echo [ERROR] Composer no esta instalado o no esta en PATH y no existe vendor\autoload.php.
  echo         Instale Composer y vuelva a ejecutar este BAT.
  pause
  exit /b 1
)

echo.
echo [6/6] Ejecutando verificaciones...
"%PHP%" tests\static_checks.php
if errorlevel 1 goto :test_error
"%PHP%" tests\project_quality.php
if errorlevel 1 goto :test_error
"%PHP%" tests\xlsx_smoke.php
if errorlevel 1 goto :test_error
"%MYSQL%" %MYSQL_AUTH% --default-character-set=utf8mb4 < "database\VERIFICAR_INSTALACION.sql"
if errorlevel 1 goto :sql_error

echo.
echo ============================================================
echo                 INSTALACION COMPLETADA
echo ============================================================
echo.
echo Base unica: %DB_NAME%
echo Usuario administrador inicial: luis@carrousel.com.gt
echo Acceso: codigo OTP ^(sin contrasena permanente^)
echo Modo correo PC TEST: log
for %%P in ("%APP_DIR%") do set "APP_FOLDER=%%~nxP"
echo URL: http://localhost/!APP_FOLDER!/public/
echo.
echo Si solicita un OTP en modo log, revise:
echo storage\logs\mail.log
echo ============================================================
pause
exit /b 0

:mysql_error
echo.
echo [ERROR] No se pudo conectar a MariaDB con el usuario %DB_USER%.
type "%TEMP%\helpdesk_mysql_error.txt" 2>nul
echo.
echo Si su root de XAMPP tiene contrasena, ajuste DB_PASS al inicio de este BAT.
pause
exit /b 1

:sql_error
echo.
echo [ERROR] Fallo una instruccion SQL. Se detuvo la instalacion.
echo Revise el mensaje de MariaDB mostrado arriba.
pause
exit /b 1

:test_error
echo.
echo [ERROR] Una prueba del proyecto fallo. La base fue creada, pero la instalacion
echo NO debe considerarse valida hasta corregir la prueba.
pause
exit /b 1
