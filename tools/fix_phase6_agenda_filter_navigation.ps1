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

    $oldAnchor = @'
$anchorWeekStart=$anchorDate->modify('-'.((int)$anchorDate->format('N')-1).' days');
$monthStart=$anchorDate->modify('first day of this month');
$monthEnd=$anchorDate->modify('last day of this month');
'@
    $newAnchor = @'
$anchorWeekStart=$anchorDate->modify('-'.((int)$anchorDate->format('N')-1).' days');
$monthStart=$anchorDate->modify('first day of this month');
$monthEnd=$anchorDate->modify('last day of this month');
$weekSwitchStart=$activeView==='month'&&$monthStart->format('Y-m')===date('Y-m')
    ?new DateTimeImmutable('monday this week')
    :$anchorWeekStart;
'@

    if (($content.Split($oldAnchor).Count - 1) -ne 1) { throw 'No se encontró exactamente una vez el bloque de ancla de vistas.' }
    $content = $content.Replace($oldAnchor, $newAnchor)

    $oldWeek = "['view'=>'calendar','from'=>`$anchorWeekStart->format('Y-m-d'),'to'=>`$anchorWeekStart->modify('+6 days')->format('Y-m-d')]"
    $newWeek = "['view'=>'calendar','from'=>`$weekSwitchStart->format('Y-m-d'),'to'=>`$weekSwitchStart->modify('+6 days')->format('Y-m-d')]"
    if (($content.Split($oldWeek).Count - 1) -ne 1) { throw 'No se encontró exactamente una vez el enlace Semana.' }
    $content = $content.Replace($oldWeek, $newWeek)

    [System.IO.File]::WriteAllText($viewPath, $content, [System.Text.UTF8Encoding]::new($false))

    & 'C:\xampp\php\php.exe' -l $viewPath
    if ($LASTEXITCODE -ne 0) { throw 'Falló PHP lint en Agenda.' }

    & 'C:\xampp\php\php.exe' 'tests\phase6_agenda_ui_regression.php'
    if ($LASTEXITCODE -ne 0) { throw 'Falló phase6_agenda_ui_regression.php.' }
    & 'C:\xampp\php\php.exe' 'tests\phase6_agenda_month_regression.php'
    if ($LASTEXITCODE -ne 0) { throw 'Falló phase6_agenda_month_regression.php.' }

    git --no-pager diff --check
    if ($LASTEXITCODE -ne 0) { throw 'git diff --check reportó problemas.' }

    Write-Host '[OK] Cambio Mes -> Semana usa la semana actual cuando se consulta el mes actual.'
}
catch {
    if ($original) { [System.IO.File]::WriteAllText($viewPath, $original, [System.Text.UTF8Encoding]::new($false)) }
    Write-Error $_
    exit 1
}
finally {
    Pop-Location
}
