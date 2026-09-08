@echo off
setlocal
cd /d "%~dp0"
:menu
cls
echo ============================================================
echo        HELPDESK CARROUSEL 360 - ADMIN GIT
echo ============================================================
echo [1] Ver estado Git + auditoria de seguridad
echo [2] Ver cambios pendientes
echo [3] Ver diferencias / diff
echo [4] Guardar version / Commit
echo [5] Subir a GitHub / Push
echo [6] Actualizar desde GitHub / Pull --rebase seguro
echo [7] Ver historial
echo [8] Tags / versiones
echo [9] Informacion del repositorio
echo [0] Salir
echo.
set /p op=Seleccione una opcion: 
if "%op%"=="1" goto status
if "%op%"=="2" goto changes
if "%op%"=="3" goto diff
if "%op%"=="4" goto commit
if "%op%"=="5" goto push
if "%op%"=="6" goto pull
if "%op%"=="7" goto history
if "%op%"=="8" goto tags
if "%op%"=="9" goto info
if "%op%"=="0" exit /b
goto menu
:status
git status
echo.
echo [AUDITORIA] Archivos sensibles rastreados:
git ls-files | findstr /i /r "config/local.php \.env storage/logs storage/attachments storage/exports \.sql\.gz \.bak"
pause&goto menu
:changes
git status --short
pause&goto menu
:diff
git diff
pause&goto menu
:commit
call :guard || goto menu
set /p msg=Mensaje del commit: 
if "%msg%"=="" goto menu
git add -A
git commit -m "%msg%"
pause&goto menu
:push
call :guard || goto menu
git push
pause&goto menu
:pull
git status --porcelain > "%temp%\hd360_status.txt"
for %%A in ("%temp%\hd360_status.txt") do if %%~zA GTR 0 (echo [DETENIDO] Hay cambios locales. Guarde o descarte manualmente antes del pull.&del "%temp%\hd360_status.txt"&pause&goto menu)
del "%temp%\hd360_status.txt" 2>nul
git pull --rebase
pause&goto menu
:history
git log --oneline --decorate -20
pause&goto menu
:tags
git tag --list --sort=-creatordate
pause&goto menu
:info
git remote -v
git branch -vv
pause&goto menu
:guard
for %%F in ("config\local.php" ".env") do if exist %%F (git ls-files --error-unmatch %%F >nul 2>&1 && (echo [BLOQUEADO] %%F esta rastreado por Git.&pause&exit /b 1))
git ls-files | findstr /i /r "storage/logs/ storage/attachments/ storage/exports/" | findstr /v /i ".gitkeep" >nul && (echo [BLOQUEADO] Hay archivos operativos rastreados por Git.&pause&exit /b 1)
exit /b 0
