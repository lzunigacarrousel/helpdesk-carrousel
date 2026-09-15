<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$viewPath=$root.'/app/Views/agenda/index.php';
$cssPath=$root.'/public/assets/css/agenda.css';

function fail(string $message): never {
    fwrite(STDERR,'[ERROR] '.$message.PHP_EOL);
    exit(1);
}
function readNormalized(string $path,?string &$eol=null): string {
    $raw=@file_get_contents($path);
    if(!is_string($raw))fail('No se pudo leer '.$path);
    $eol=str_contains($raw,"\r\n")?"\r\n":"\n";
    return str_replace(["\r\n","\r"],"\n",$raw);
}
function writeNormalized(string $path,string $content,string $eol): void {
    $content=str_replace(["\r\n","\r"],"\n",$content);
    if($eol==="\r\n")$content=str_replace("\n","\r\n",$content);
    if(file_put_contents($path,$content)===false)fail('No se pudo escribir '.$path);
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count!==1)fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return str_replace($search,$replace,$content);
}
function replaceRegexOnce(string $content,string $pattern,string $replace,string $label): string {
    $count=0;
    $result=preg_replace($pattern,$replace,$content,1,$count);
    if(!is_string($result)||$count!==1)fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return $result;
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}

chdir($root)||fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0)fail('No se pudo consultar git status.');
if($status)fail('El working tree debe estar limpio antes de aplicar la vista Mes.');

$controller=readNormalized($controllerPath,$controllerEol);
$view=readNormalized($viewPath,$viewEol);
$css=readNormalized($cssPath,$cssEol);
$controllerOriginal=$controller;
$viewOriginal=$view;
$cssOriginal=$css;

$controllerOldView=<<<'PHP'
$view=in_array($_GET['view']??'', ['calendar','list'],true)?$_GET['view']:'calendar';
PHP;
$controllerNewView=<<<'PHP'
$view=in_array($_GET['view']??'', ['month','calendar','list'],true)?$_GET['view']:'month';
PHP;
$controller=replaceOnce($controller,$controllerOldView,$controllerNewView,'Selector de vistas del controller');

$controllerDefaultPattern=<<<'REGEX'
~\$defaultFrom=\$view==='calendar'\s*\?\$today->modify\('monday this week'\)\s*:\$today;\s*\$defaultTo=\$view==='calendar'\s*\?\$defaultFrom->modify\('\+6 days'\)\s*:\$today->modify\('\+6 days'\);~s
REGEX;
$controllerDefaultReplacement=<<<'PHP'
$defaultFrom=$view==='month'
            ?$today->modify('first day of this month')
            :($view==='calendar'?$today->modify('monday this week'):$today);
        $defaultTo=$view==='month'
            ?$today->modify('last day of this month')
            :($view==='calendar'?$defaultFrom->modify('+6 days'):$today->modify('+6 days'));
PHP;
$controller=replaceRegexOnce($controller,$controllerDefaultPattern,$controllerDefaultReplacement,'Rango predeterminado por vista');

$monthSetup=<<<'PHP'
$activeView=(string)($filters['view']??'month');
$anchorDate=DateTimeImmutable::createFromFormat('!Y-m-d',(string)($filters['from']??''));
if(!$anchorDate)$anchorDate=new DateTimeImmutable('today');
$anchorWeekStart=$anchorDate->modify('-'.((int)$anchorDate->format('N')-1).' days');
$monthStart=$anchorDate->modify('first day of this month');
$monthEnd=$anchorDate->modify('last day of this month');
$monthGridStart=$monthStart->modify('-'.((int)$monthStart->format('N')-1).' days');
$monthGridEnd=$monthEnd->modify('+'.(7-(int)$monthEnd->format('N')).' days');
$monthNames=[1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
$weekdayLabels=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
$monthDays=[];
for($cursor=$monthGridStart;$cursor<=$monthGridEnd;$cursor=$cursor->modify('+1 day')){
    $monthDays[$cursor->format('Y-m-d')]=[];
}
foreach($activities as $activity){
    $activityStart=DateTimeImmutable::createFromFormat('!Y-m-d',substr((string)($activity['scheduled_start_at']??''),0,10));
    $activityEnd=DateTimeImmutable::createFromFormat('!Y-m-d',substr((string)($activity['scheduled_end_at']??''),0,10));
    if(!$activityStart||!$activityEnd)continue;
    foreach($monthDays as $day=>$unused){
        $dayDate=DateTimeImmutable::createFromFormat('!Y-m-d',$day);
        if($dayDate&&$dayDate>=$activityStart&&$dayDate<=$activityEnd)$monthDays[$day][]=$activity;
    }
}

PHP;
$startHourAnchor=<<<'PHP'
$startHour=max(0,min(23,(int)($hourWindow['start_hour']??8)));
PHP;
$view=replaceOnce($view,$startHourAnchor,$monthSetup.$startHourAnchor,'Preparación de Mes');

$view=replaceOnce(
    $view,
    '<p class="page-subtitle">Calendario y Lista comparten las mismas actividades visibles de tu alcance.</p>',
    '<p class="page-subtitle">Mes, Semana y Lista comparten las mismas actividades visibles de tu alcance.</p>',
    'Subtítulo Agenda'
);

$viewSwitch=<<<'PHP'
    <nav class="agenda-view-switch" aria-label="Vista de agenda">
      <a class="btn <?= $activeView==='month'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'month','from'=>$monthStart->format('Y-m-d'),'to'=>$monthEnd->format('Y-m-d')])) ?>">Mes</a>
      <a class="btn <?= $activeView==='calendar'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'calendar','from'=>$anchorWeekStart->format('Y-m-d'),'to'=>$anchorWeekStart->modify('+6 days')->format('Y-m-d')])) ?>">Semana</a>
      <a class="btn <?= $activeView==='list'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'list'])) ?>">Lista</a>
    </nav>
PHP;
$view=replaceRegexOnce(
    $view,
    '~    <nav class="agenda-view-switch" aria-label="Vista de agenda">.*?    </nav>~s',
    $viewSwitch,
    'Selector Mes/Semana/Lista'
);

$quickLinks=<<<'PHP'
  <div class="agenda-quick-links" aria-label="Rangos rápidos">
    <?php if($activeView==='month'):
      $previousMonth=$monthStart->modify('-1 month');
      $nextMonth=$monthStart->modify('+1 month');
      $todayMonth=new DateTimeImmutable('first day of this month'); ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$previousMonth->format('Y-m-d'),'to'=>$previousMonth->modify('last day of this month')->format('Y-m-d')])) ?>">Mes anterior</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$todayMonth->format('Y-m-d'),'to'=>$todayMonth->modify('last day of this month')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$nextMonth->format('Y-m-d'),'to'=>$nextMonth->modify('last day of this month')->format('Y-m-d')])) ?>">Mes siguiente</a>
    <?php elseif($activeView==='calendar'): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('-7 days')->format('Y-m-d'),'to'=>$weekStart->modify('-1 day')->format('Y-m-d')])) ?>">Semana anterior</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>date('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('+7 days')->format('Y-m-d'),'to'=>$weekStart->modify('+13 days')->format('Y-m-d')])) ?>">Semana siguiente</a>
    <?php else: ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>date('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['view'=>'calendar','from'=>(new DateTimeImmutable('monday this week'))->format('Y-m-d'),'to'=>(new DateTimeImmutable('monday this week'))->modify('+6 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Esta semana</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['view'=>'list','from'=>date('Y-m-d'),'to'=>(new DateTimeImmutable('today'))->modify('+29 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Próximos 30 días</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['view'=>'list','history'=>'1','status'=>'all'])) ?>">Historial</a>
  </div>
PHP;
$view=replaceRegexOnce(
    $view,
    '~  <div class="agenda-quick-links" aria-label="Rangos rápidos">.*?  </div>~s',
    $quickLinks,
    'Navegación por vista'
);

$monthSection=<<<'PHP'
  <?php if($activeView==='month'): ?>
  <section class="agenda-month" aria-label="Calendario mensual">
    <header class="agenda-month-head">
      <div><span class="ticket-kicker">Mes</span><h2><?= $h(($monthNames[(int)$monthStart->format('n')]??$monthStart->format('m')).' '.$monthStart->format('Y')) ?></h2></div>
      <span class="agenda-month-summary"><?= count($activities) ?> actividad(es) en el rango</span>
    </header>
    <div class="agenda-month-scroll">
      <div class="agenda-month-grid">
        <?php foreach($weekdayLabels as $weekday): ?><div class="agenda-month-weekday"><?= $h($weekday) ?></div><?php endforeach; ?>
        <?php foreach($monthDays as $day=>$items):
          $dayDate=DateTimeImmutable::createFromFormat('!Y-m-d',$day);
          $outside=$dayDate?$dayDate->format('m')!==$monthStart->format('m'):false;
          $today=$day===date('Y-m-d');
          $visibleItems=array_slice($items,0,4);
        ?>
        <article class="agenda-month-day <?= $outside?'is-outside':'' ?> <?= $today?'is-today':'' ?>" data-date="<?= $h($day) ?>">
          <header class="agenda-month-date"><span><?= $h($dayDate?$dayDate->format('j'):$day) ?></span></header>
          <div class="agenda-month-events">
            <?php foreach($visibleItems as $item):
              $status=(string)($item['status']??'');
              $statusClass=strtolower(str_replace('_','-',$status));
              $ticketUrl=(string)($item['ticket_url']??(APP_BASE_URL.'/tickets/view?id='.(int)($item['ticket_id']??0).'#actividades'));
              $startTs=strtotime((string)($item['scheduled_start_at']??''));
              $endTs=strtotime((string)($item['scheduled_end_at']??''));
              $startDay=$startTs?date('Y-m-d',$startTs):'';
              $endDay=$endTs?date('Y-m-d',$endTs):'';
              if($startDay===$endDay)$monthEventTime=$startTs?date('H:i',$startTs):'';
              elseif($day===$startDay)$monthEventTime=($startTs?date('H:i',$startTs):'').' →';
              elseif($day===$endDay)$monthEventTime='→ '.($endTs?date('H:i',$endTs):'');
              else $monthEventTime='↔';
            ?>
            <a class="agenda-month-event is-<?= $h($statusClass) ?> <?= !empty($item['has_conflict'])?'has-conflict':'' ?>" href="<?= $h($ticketUrl) ?>" title="<?= $h(($item['ticket_code']??'').' · '.($item['ticket_subject']??'')) ?>"><time><?= $h($monthEventTime) ?></time><span><?= $h($typeLabels[(string)($item['activity_type']??'')]??'Actividad') ?></span></a>
            <?php endforeach; ?>
            <?php if(count($items)>4): ?><span class="agenda-month-more">+<?= count($items)-4 ?> más</span><?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

PHP;
$calendarSectionAnchor=<<<'PHP'
  <?php if(($filters['view']??'calendar')==='calendar'): ?>
PHP;
$view=replaceOnce($view,$calendarSectionAnchor,$monthSection.$calendarSectionAnchor,'Inserción de calendario mensual');

$monthCss=<<<'CSS'

.agenda-month{display:grid;gap:10px;border:1px solid var(--border);border-radius:12px;background:var(--card);padding:14px;overflow:hidden}
.agenda-month-head{display:flex;align-items:end;justify-content:space-between;gap:12px;flex-wrap:wrap}
.agenda-month-head h2{margin:2px 0 0;font-size:22px;color:var(--ink)}
.agenda-month-summary{font-size:12px;color:var(--muted)}
.agenda-month-scroll{overflow:hidden}
.agenda-month-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-top:1px solid var(--border);border-left:1px solid var(--border);background:var(--border);gap:1px}
.agenda-month-weekday{padding:8px 6px;background:var(--card);text-align:center;font-size:11px;font-weight:850;color:var(--muted)}
.agenda-month-day{display:grid;grid-template-rows:auto 1fr;gap:5px;min-width:0;min-height:112px;padding:7px;background:var(--card)}
.agenda-month-day.is-outside{background:color-mix(in srgb,var(--card) 88%,var(--bg) 12%);opacity:.58}
.agenda-month-date{display:flex;justify-content:flex-end;min-height:26px}
.agenda-month-date span{display:grid;place-items:center;width:26px;height:26px;border-radius:999px;font-weight:850;color:var(--ink)}
.agenda-month-day.is-today .agenda-month-date span{background:var(--brand);color:#fff}
.agenda-month-events{display:grid;align-content:start;gap:3px;min-width:0}
.agenda-month-event{display:flex;align-items:center;gap:4px;min-width:0;padding:3px 5px;border-radius:5px;background:color-mix(in srgb,var(--brand) 12%,var(--card) 88%);border-left:3px solid var(--brand);text-decoration:none;color:var(--ink);font-size:10px;line-height:1.2}
.agenda-month-event time{flex:0 0 auto;font-weight:850;color:var(--brand)}
.agenda-month-event span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.agenda-month-event.is-en-curso{border-left-color:var(--success,#067647)}
.agenda-month-event.is-finalizada,.agenda-month-event.is-cancelada{opacity:.68}
.agenda-month-event.has-conflict{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--warning,#b7791f) 55%,transparent 45%)}
.agenda-month-more{font-size:10px;font-weight:850;color:var(--brand);padding:2px 4px}
.agenda-view-month .agenda-list:not(.agenda-overdue){display:none}
@media(max-width:1023px){.agenda-month{padding:10px}.agenda-month-day{min-height:94px;padding:5px}.agenda-month-event{font-size:9px}.agenda-month-event time{display:none}}
@media(max-width:760px){.agenda-month{padding:8px}.agenda-month-head h2{font-size:20px}.agenda-month-summary{display:none}.agenda-month-weekday{padding:6px 2px;font-size:9px}.agenda-month-day{min-height:78px;padding:3px;gap:2px}.agenda-month-date{min-height:22px}.agenda-month-date span{width:22px;height:22px;font-size:12px}.agenda-month-events{gap:2px}.agenda-month-event{padding:2px 3px;font-size:8px;border-left-width:2px}.agenda-month-more{font-size:8px;padding:1px 2px}}
CSS;
if(str_contains($css,'.agenda-month-grid{'))fail('CSS mensual ya existe; no se aplicará dos veces.');
$css.=$monthCss."\n";

writeNormalized($controllerPath,$controller,$controllerEol);
writeNormalized($viewPath,$view,$viewEol);
writeNormalized($cssPath,$css,$cssEol);

foreach([$controllerPath,$viewPath] as $file){
    if(run('"'.$php.'" -l "'.$file.'"')!==0){
        writeNormalized($controllerPath,$controllerOriginal,$controllerEol);
        writeNormalized($viewPath,$viewOriginal,$viewEol);
        writeNormalized($cssPath,$cssOriginal,$cssEol);
        fail('Falló PHP lint; se restauraron automáticamente los archivos originales.');
    }
}

$tests=[
    'tests\\phase6_agenda_month_regression.php',
    'tests\\phase6_agenda_ui_regression.php',
    'tests\\phase6_agenda_list_range_regression.php',
    'tests\\phase6_agenda_multiday_calendar_regression.php',
    'tests\\phase6_agenda_service_regression.php',
];
foreach($tests as $test){
    echo PHP_EOL.'=== '.$test.' ==='.PHP_EOL;
    if(run('"'.$php.'" '.$test)!==0)fail('Regresión falló: '.$test);
}
if(run('git --no-pager diff --check')!==0)fail('git diff --check reportó problemas.');

echo PHP_EOL.'[OK] GREEN confirmado: Agenda incorpora Mes sin perder Semana ni Lista.'.PHP_EOL;
echo '[OK] Mes es la vista predeterminada y usa rango mensual completo.'.PHP_EOL;
echo '[OK] No se modificó la base de datos.'.PHP_EOL;
