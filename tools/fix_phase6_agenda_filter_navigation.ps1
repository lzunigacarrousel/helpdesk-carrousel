$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$viewPath = Join-Path $root 'app\Views\agenda\index.php'

Push-Location $root
try {
    $status = git status --porcelain
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar git status.' }
    if ($status) { throw 'El working tree debe estar limpio antes de aplicar el ajuste.' }

    $content = [System.IO.File]::ReadAllText($viewPath)
    $original = $content
    $eol = if ($content.Contains("`r`n")) { "`r`n" } else { "`n" }

    # Insercion idempotente y tolerante a CRLF/LF.
    $anchorLine = '$monthEnd=$anchorDate->modify(''last day of this month'');'
    $weekSwitchBlock = @(
        '$weekSwitchStart=$activeView===''month''&&$monthStart->format(''Y-m'')===date(''Y-m'')',
        '    ?new DateTimeImmutable(''monday this week'')',
        '    :$anchorWeekStart;'
    ) -join $eol

    if (-not $content.Contains('$weekSwitchStart=')) {
        $anchorCount = ([regex]::Matches($content, [regex]::Escape($anchorLine))).Count
        if ($anchorCount -ne 1) { throw "El ancla monthEnd esperaba 1 coincidencia y encontro $anchorCount." }
        $content = $content.Replace($anchorLine, $anchorLine + $eol + $weekSwitchBlock)
    }

    $oldWeek = "['view'=>'calendar','from'=>`$anchorWeekStart->format('Y-m-d'),'to'=>`$anchorWeekStart->modify('+6 days')->format('Y-m-d')]"
    $newWeek = "['view'=>'calendar','from'=>`$weekSwitchStart->format('Y-m-d'),'to'=>`$weekSwitchStart->modify('+6 days')->format('Y-m-d')]"
    if ($content.Contains($oldWeek)) {
        $oldWeekCount = ([regex]::Matches($content, [regex]::Escape($oldWeek))).Count
        if ($oldWeekCount -ne 1) { throw "El enlace Semana esperaba 1 coincidencia y encontro $oldWeekCount." }
        $content = $content.Replace($oldWeek, $newWeek)
    }
    elseif (-not $content.Contains($newWeek)) {
        throw 'No se encontro el enlace Semana esperado ni su variante ya corregida.'
    }

    [System.IO.File]::WriteAllText($viewPath, $content, [System.Text.UTF8Encoding]::new($false))

    & 'C:\xampp\php\php.exe' -l $viewPath
    if ($LASTEXITCODE -ne 0) { throw 'Fallo PHP lint en Agenda.' }

    & 'C:\xampp\php\php.exe' 'tests\phase6_agenda_ui_regression.php'
    if ($LASTEXITCODE -ne 0) { throw 'Fallo phase6_agenda_ui_regression.php.' }
    & 'C:\xampp\php\php.exe' 'tests\phase6_agenda_month_regression.php'
    if ($LASTEXITCODE -ne 0) { throw 'Fallo phase6_agenda_month_regression.php.' }

    git --no-pager diff --check
    if ($LASTEXITCODE -ne 0) { throw 'git diff --check reporto problemas.' }

    Write-Host '[OK] Cambio Mes -> Semana usa la semana actual cuando se consulta el mes actual.'
}
catch {
    if ($null -ne $original) {
        [System.IO.File]::WriteAllText($viewPath, $original, [System.Text.UTF8Encoding]::new($false))
    }
    Write-Error $_
    exit 1
}
finally {
    Pop-Location
}
