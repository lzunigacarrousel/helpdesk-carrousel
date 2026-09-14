$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repo = Split-Path -Parent $scriptDir
$viewPath = Join-Path $repo 'app\Views\tickets\show.php'

if (-not (Test-Path $viewPath)) {
    throw "No existe $viewPath"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText($viewPath, $utf8NoBom)

$activeFrom = '<?php if($activeActivities): ?><div class="ticket-activity-grid">'
$activeTo = '<?php if($activeActivities): ?><div class="ticket-activity-grid <?= count($activeActivities)===1?''is-single'':'' ?>">'
$historyFrom = '<?php if($historyActivities): ?><div class="ticket-activity-grid">'
$historyTo = '<?php if($historyActivities): ?><div class="ticket-activity-grid <?= count($historyActivities)===1?''is-single'':'' ?>">'

$content = $content.Replace($activeFrom, $activeTo)
$content = $content.Replace($historyFrom, $historyTo)

if (-not $content.Contains('ticket-activity-more-options')) {
    $prepNeedle = 'name="internal_preparation_notes"'
    $summaryNeedle = 'name="requester_summary"'
    $prepPos = $content.IndexOf($prepNeedle)
    $summaryPos = $content.IndexOf($summaryNeedle)

    if ($prepPos -lt 0 -or $summaryPos -lt 0) {
        throw 'No se encontraron los campos secundarios del formulario de actividades.'
    }

    $start = $content.LastIndexOf('<label class="activity-span-2">', $prepPos)
    $end = $content.IndexOf('</label>', $summaryPos)
    if ($start -lt 0 -or $end -lt 0) {
        throw 'No fue posible delimitar las opciones secundarias del formulario.'
    }
    $end += '</label>'.Length

    $moreLabel = 'M' + [char]0x00E1 + 's opciones'
    $open = '<details class="ticket-activity-more-options activity-span-2"><summary>' + $moreLabel + '</summary><div class="ticket-activity-more-options-body">' + [Environment]::NewLine
    $close = [Environment]::NewLine + '</div></details>'
    $content = $content.Substring(0, $start) + $open + $content.Substring($start, $end - $start) + $close + $content.Substring($end)
}

[System.IO.File]::WriteAllText($viewPath, $content, $utf8NoBom)
Write-Host '[OK] Pulido visual Task 7 aplicado.'
Write-Host '[OK] Una sola actividad usa ancho completo y opciones secundarias quedan colapsadas.'
