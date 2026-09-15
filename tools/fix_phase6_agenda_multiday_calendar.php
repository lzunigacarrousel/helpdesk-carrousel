<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$view=$root.'/app/Views/agenda/index.php';
$css=$root.'/public/assets/css/agenda.css';
$test=$root.'/tests/phase6_agenda_multiday_calendar_regression.php';
$uiTest=$root.'/tests/phase6_agenda_ui_regression.php';

function fail(string $message): never {
    fwrite(STDERR,'[ERROR] '.$message.PHP_EOL);
    exit(1);
}
function readStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function writeStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false) fail('No se pudo escribir '.$path);
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}
function normalize(string $content): string {
    return str_replace(["\r\n","\r"],"\n",$content);
}

chdir($root) || fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0) fail('No se pudo consultar git status.');
if($status) fail('El working tree debe estar limpio antes de aplicar este ajuste.');

$viewBody=readStrict($view);
$viewEol=str_contains($viewBody,"\r\n")?"\r\n":"\n";
$viewBody=normalize($viewBody);
if(str_contains($viewBody,'$calendarMultiDayItems')) fail('La vista ya contiene la integración multiday semanal.');

$logicMarker='$startHour=max(0,min(23,(int)($hourWindow[\'start_hour\']??8)));';
$logic=<<<'PHP'
$calendarEnd=$calendarStart->modify('+6 days');
$calendarMultiDayItems=[];
$calendarMultiDayLane=1;
foreach($multiDayActivities as $item){
    $itemStart=DateTimeImmutable::createFromFormat('!Y-m-d',substr((string)($item['scheduled_start_at']??''),0,10));
    $itemEnd=DateTimeImmutable::createFromFormat('!Y-m-d',substr((string)($item['scheduled_end_at']??''),0,10));
    if(!$itemStart||!$itemEnd||$itemEnd<$calendarStart||$itemStart>$calendarEnd)continue;
    $visibleStart=$itemStart<$calendarStart?$calendarStart:$itemStart;
    $visibleEnd=$itemEnd>$calendarEnd?$calendarEnd:$itemEnd;
    $startColumn=((int)$calendarStart->diff($visibleStart)->days)+2;
    $spanDays=((int)$visibleStart->diff($visibleEnd)->days)+1;
    $calendarMultiDayItems[]=['item'=>$item,'start_column'=>$startColumn,'span_days'=>$spanDays,'lane'=>$calendarMultiDayLane++];
}

PHP;
$pos=strpos($viewBody,$logicMarker);
if($pos===false) fail('No se encontró el punto de inserción de lógica multiday.');
$viewBody=substr($viewBody,0,$pos).$logic.substr($viewBody,$pos);

$startMarker='    <?php if($multiDayActivities): ?>';
$endMarker='    <?php if(!$calendarActivities&&!$multiDayActivities): ?>';
$start=strpos($viewBody,$startMarker);
$end=strpos($viewBody,$endMarker);
if($start===false||$end===false||$end<=$start) fail('No se pudo localizar el bloque multiday actual.');
$newMarkup=<<<'PHP'
    <?php if($calendarMultiDayItems): ?>
    <section class="agenda-multiday-strip" aria-labelledby="agenda-multiday-title">
      <div class="agenda-multiday-head">
        <span class="ticket-kicker">Rango extendido</span>
        <h2 id="agenda-multiday-title">Actividades de varios días</h2>
      </div>
      <div class="agenda-calendar-multiday-grid">
        <div class="agenda-calendar-multiday-label" style="grid-row:1 / span <?= max(1,count($calendarMultiDayItems)) ?>">Varios días</div>
        <?php foreach($calendarMultiDayItems as $entry):
          $item=$entry['item'];
          $status=(string)($item['status']??'');
          $statusClass=strtolower(str_replace('_','-',$status));
          $ticketUrl=(string)($item['ticket_url']??(APP_BASE_URL.'/tickets/view?id='.(int)($item['ticket_id']??0).'#actividades'));
        ?>
        <a class="agenda-multiday-item agenda-calendar-multiday-event is-<?= $h($statusClass) ?> <?= !empty($item['is_overdue'])?'is-overdue':'' ?>" href="<?= $h($ticketUrl) ?>" style="grid-column:<?= (int)$entry['start_column'] ?>/span <?= (int)$entry['span_days'] ?>;grid-row:<?= (int)$entry['lane'] ?>">
          <time><?= $h($multiDayRange($item)) ?></time>
          <span class="agenda-multiday-main"><strong><?= $h($typeLabels[(string)($item['activity_type']??'')]??($item['activity_type']??'Actividad')) ?></strong><span><?= $h($item['ticket_code']??'') ?> · <?= $h($item['ticket_subject']??'') ?></span><small><?= $h($item['park_name']??'Sin parque') ?> · <?= $h($item['responsible_name']??'Sin responsable') ?></small><?php if(!empty($item['has_conflict'])): ?><em>Conflicto de horario</em><?php endif; ?></span>
          <span class="agenda-status"><?= $h($statusLabels[$status]??$status) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

PHP;
$viewBody=substr($viewBody,0,$start).$newMarkup.substr($viewBody,$end);
if($viewEol==="\r\n")$viewBody=str_replace("\n","\r\n",$viewBody);
writeStrict($view,$viewBody);

$cssBody=readStrict($css);
$cssEol=str_contains($cssBody,"\r\n")?"\r\n":"\n";
$cssBody=normalize($cssBody);
if(str_contains($cssBody,'.agenda-calendar-multiday-grid{')) fail('El CSS ya contiene la integración multiday semanal.');
$cssMarker='.agenda-multiday-head h2{margin:0;font-size:16px;color:var(--ink)}';
$cssInsert=<<<'CSS'
.agenda-calendar-multiday-grid{display:grid;grid-template-columns:72px repeat(7,minmax(0,1fr));grid-auto-rows:minmax(58px,auto);gap:6px;align-items:stretch}
.agenda-calendar-multiday-label{grid-column:1;display:flex;align-items:center;justify-content:center;padding:8px;font-size:11px;font-weight:850;color:var(--muted);text-align:center}
.agenda-calendar-multiday-event{grid-template-columns:1fr;align-content:start;gap:3px;min-width:0;padding:8px 10px}
.agenda-calendar-multiday-event .agenda-status{justify-self:start}
CSS;
$pos=strpos($cssBody,$cssMarker);
if($pos===false) fail('No se encontró el punto de inserción CSS multiday.');
$pos+=strlen($cssMarker);
$cssBody=substr($cssBody,0,$pos)."\n".$cssInsert.substr($cssBody,$pos);

$mediaNeedle='.agenda-multiday-items{grid-template-columns:1fr}}';
$mediaReplacement='.agenda-multiday-items{grid-template-columns:1fr}.agenda-calendar-multiday-grid{grid-template-columns:1fr}.agenda-calendar-multiday-label{display:none}.agenda-calendar-multiday-event{grid-column:1!important;grid-row:auto!important}}';
if(substr_count($cssBody,$mediaNeedle)!==1) fail('No se encontró exactamente una regla responsive multiday para extender.');
$cssBody=str_replace($mediaNeedle,$mediaReplacement,$cssBody);
if($cssEol==="\r\n")$cssBody=str_replace("\n","\r\n",$cssBody);
writeStrict($css,$cssBody);

echo '[OK] Actividades multiday integradas como franjas dentro de la semana visible.'.PHP_EOL;
if(run('"'.$php.'" -l "'.$view.'"')!==0) fail('Falló PHP lint en Agenda.');
if(run('"'.$php.'" "'.$test.'"')!==0) fail('La regresión multiday del calendario no quedó en verde.');
if(run('"'.$php.'" "'.$uiTest.'"')!==0) fail('La regresión UI de Agenda no quedó en verde.');

echo '[OK] GREEN confirmado: multiday conserva rango real y ocupa los días visibles correspondientes.'.PHP_EOL;
