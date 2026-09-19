$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)

$root = (Get-Location).Path
$source = Join-Path $root 'config\local.php'
$outRoot = Join-Path $root 'dist\production-config'
$outConfigDir = Join-Path $outRoot 'config'
$outConfig = Join-Path $outConfigDir 'local.php'
$outInfo = Join-Path $outRoot 'INSTRUCCIONES.txt'

Write-Host '============================================================'
Write-Host ' HELPDESK CARROUSEL - PREPARAR CONFIGURACION PRODUCCION'
Write-Host ' No modifica la BD. No sube secretos a Git.'
Write-Host '============================================================'
Write-Host ''

if (-not (Test-Path $source)) {
    Write-Host '[ERROR] Falta config\local.php en PC TEST.' -ForegroundColor Red
    exit 1
}

Write-Host 'Destino de produccion:'
Write-Host '[1] https://portal.carrousel-apps.com/HelpdeskCarrousel/public/'
Write-Host '[2] http://94.74.71.96/HelpdeskCarrousel/public/'
$choice = Read-Host 'Seleccione 1 o 2'

switch ($choice) {
    '1' { $appUrl = 'https://portal.carrousel-apps.com/HelpdeskCarrousel/public/' }
    '2' { $appUrl = 'http://94.74.71.96/HelpdeskCarrousel/public/' }
    default {
        Write-Host '[CANCELADO] Opcion no valida.' -ForegroundColor Yellow
        exit 1
    }
}

$content = [System.IO.File]::ReadAllText($source)
if ($content -notmatch "'db_name'\s*=>\s*'carrousel_helpdesk'") {
    Write-Host '[ERROR] config\local.php no apunta a carrousel_helpdesk.' -ForegroundColor Red
    exit 1
}

if ($content -notmatch "'app_url'\s*=>") {
    Write-Host '[ERROR] config\local.php no contiene app_url.' -ForegroundColor Red
    exit 1
}

$content = [regex]::Replace(
    $content,
    "'app_url'\s*=>\s*'[^']*'",
    "'app_url' => '$appUrl'",
    1
)

if (Test-Path $outRoot) {
    Remove-Item $outRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $outConfigDir -Force | Out-Null

$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($outConfig, $content, $utf8)

$mailMode = ''
if ($content -match "'mail_mode'\s*=>\s*'([^']*)'") {
    $mailMode = $Matches[1].ToLowerInvariant()
}
$smtpUserConfigured = $content -match "'smtp_username'\s*=>\s*'[^']+'"
$smtpPassConfigured = $content -match "'smtp_password'\s*=>\s*'[^']+'"

$instructions = @"
HELPDESK CARROUSEL - CONFIGURACION PRIVADA DE PRODUCCION

Archivo a transferir:
  config\local.php

Destino dentro del servidor:
  C:\xampp\htdocs\HelpdeskCarrousel\config\local.php

app_url preparada:
  $appUrl

IMPORTANTE:
- Este archivo puede contener credenciales privadas.
- NO subirlo a GitHub, correo o chat.
- Transferirlo por un canal privado.
- En produccion ejecutar el gate/instalador solamente despues de colocar este archivo.
"@
[System.IO.File]::WriteAllText($outInfo, $instructions, $utf8)

Write-Host ''
Write-Host '[OK] Configuracion preparada:' -ForegroundColor Green
Write-Host "  $outConfig"
Write-Host "  $outInfo"
Write-Host ''
Write-Host "app_url: $appUrl"

if ($mailMode -ne 'smtp') {
    Write-Host '[AVISO] mail_mode no esta en smtp. Debe ajustarse antes del go-live.' -ForegroundColor Yellow
} else {
    Write-Host '[OK] mail_mode=smtp'
}
if (-not $smtpUserConfigured) {
    Write-Host '[AVISO] smtp_username parece vacio.' -ForegroundColor Yellow
}
if (-not $smtpPassConfigured) {
    Write-Host '[AVISO] smtp_password parece vacio.' -ForegroundColor Yellow
}

$ignored = git check-ignore "dist/production-config/config/local.php" 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host '[OK] El archivo privado esta ignorado por Git.' -ForegroundColor Green
} else {
    Write-Host '[ERROR] El archivo privado NO esta ignorado por Git. No lo transfiera aun.' -ForegroundColor Red
    exit 1
}

$hash = (Get-FileHash -Algorithm SHA256 $outConfig).Hash
Write-Host "SHA256: $hash"
Write-Host ''
Write-Host '[OK] Preparacion completada. La BD no fue modificada.' -ForegroundColor Green
