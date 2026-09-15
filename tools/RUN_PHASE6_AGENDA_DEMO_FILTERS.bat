@echo off
setlocal
cd /d "%~dp0.."

echo ============================================================
echo  FASE 6 - AGENDA - DATASET DEMO Y FILTROS
echo ============================================================
echo.

if not exist "C:\xampp\mysql\bin\mysql.exe" (
  echo [ERROR] No se encontro C:\xampp\mysql\bin\mysql.exe
  exit /b 1
)
if not exist "C:\xampp\php\php.exe" (
  echo [ERROR] No se encontro C:\xampp\php\php.exe
  exit /b 1
)

echo [1/4] Ajustando navegacion Mes - Semana y defaults visuales...
powershell -NoProfile -ExecutionPolicy Bypass -File "tools\fix_phase6_agenda_filter_navigation.ps1"
if errorlevel 1 (
  echo [ERROR] Fallo el ajuste de navegacion/filtros.
  exit /b 1
)

echo.
echo [2/4] Preparando datos de prueba existentes...
"C:\xampp\mysql\bin\mysql.exe" -u root carrousel_helpdesk < "database\DEMO_FASE6_AGENDA_TEST.sql"
if errorlevel 1 (
  echo [ERROR] Fallo la preparacion del dataset demo.
  exit /b 1
)

echo.
echo [3/4] Ejecutando regresiones de Agenda...
"C:\xampp\php\php.exe" -l app\Controllers\AgendaController.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" -l app\Services\AgendaService.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" -l app\Views\agenda\index.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" tests\phase6_agenda_service_regression.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" tests\phase6_agenda_month_regression.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" tests\phase6_agenda_ui_regression.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" tests\phase6_agenda_list_range_regression.php
if errorlevel 1 exit /b 1
"C:\xampp\php\php.exe" tests\phase6_agenda_multiday_calendar_regression.php
if errorlevel 1 exit /b 1

echo.
echo [4/4] Estado Git...
git --no-pager diff --check
if errorlevel 1 exit /b 1
git --no-pager diff --stat
git status --short

echo.
echo [OK] Dataset demo aplicado, filtros revisados y regresiones completadas.
exit /b 0
