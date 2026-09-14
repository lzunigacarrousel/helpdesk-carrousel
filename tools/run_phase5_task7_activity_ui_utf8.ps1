$ErrorActionPreference = 'Stop'

$repo = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $PSScriptRoot 'apply_phase5_task7_activity_ui.ps1'
$viewPath = Join-Path $repo 'app\Views\tickets\show.php'

if (-not (Test-Path $sourcePath)) {
    throw "No existe $sourcePath"
}
if (-not (Test-Path $viewPath)) {
    throw "No existe $viewPath"
}

$utf8 = New-Object System.Text.UTF8Encoding($false)
$source = [System.IO.File]::ReadAllText($sourcePath, $utf8)
$script = [ScriptBlock]::Create($source)
& $script

$generated = [System.IO.File]::ReadAllText($viewPath, $utf8)
$badC3 = [string][char]0x00C3
$badC2 = [string][char]0x00C2

if ($generated.Contains($badC3) -or $generated.Contains($badC2)) {
    throw 'Se detecto texto con mojibake en app/Views/tickets/show.php. No continuar hasta corregir UTF-8.'
}

Write-Host '[OK] Task 7 aplicada con lectura UTF-8 segura.'
Write-Host '[OK] Vista generada sin indicadores de mojibake.'
