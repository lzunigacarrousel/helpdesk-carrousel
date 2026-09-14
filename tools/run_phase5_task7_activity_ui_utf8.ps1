$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repo = Split-Path -Parent $scriptDir
$sourcePath = Join-Path $scriptDir 'apply_phase5_task7_activity_ui.ps1'
$polishPath = Join-Path $scriptDir 'apply_phase5_task7_visual_polish.ps1'
$viewPath = Join-Path $repo 'app\Views\tickets\show.php'
$tempPath = Join-Path $scriptDir ('._task7_utf8_' + [Guid]::NewGuid().ToString('N') + '.ps1')
$phpPath = 'C:\xampp\php\php.exe'

if (-not (Test-Path $sourcePath)) {
    throw "No existe $sourcePath"
}
if (-not (Test-Path $polishPath)) {
    throw "No existe $polishPath"
}
if (-not (Test-Path $viewPath)) {
    throw "No existe $viewPath"
}
if (-not (Test-Path $phpPath)) {
    throw "No existe $phpPath"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$utf8Bom = New-Object System.Text.UTF8Encoding($true)

try {
    $source = [System.IO.File]::ReadAllText($sourcePath, $utf8NoBom)
    [System.IO.File]::WriteAllText($tempPath, $source, $utf8Bom)
    & $tempPath
} finally {
    if (Test-Path $tempPath) {
        Remove-Item -LiteralPath $tempPath -Force
    }
}

& $polishPath

$generated = [System.IO.File]::ReadAllText($viewPath, $utf8NoBom)
$badC3 = [string][char]0x00C3
$badC2 = [string][char]0x00C2

if ($generated.Contains($badC3) -or $generated.Contains($badC2)) {
    throw 'Se detecto texto con mojibake en app/Views/tickets/show.php. No continuar hasta corregir UTF-8.'
}

& $phpPath -l $viewPath
if ($LASTEXITCODE -ne 0) {
    throw 'La vista generada no supera php -l. Task 7 no puede considerarse aplicada.'
}

Write-Host '[OK] Task 7 aplicada con lectura UTF-8 segura.'
Write-Host '[OK] Vista generada sin indicadores de mojibake.'
Write-Host '[OK] Vista generada supera php -l.'
