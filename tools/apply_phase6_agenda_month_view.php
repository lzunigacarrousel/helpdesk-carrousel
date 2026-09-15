<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$controllerPath=$root.'/app/Controllers/AgendaController.php';
$viewPath=$root.'/app/Views/agenda/index.php';
$cssPath=$root.'/public/assets/css/agenda.css';

function fail(string $message): never { fwrite(STDERR,'[ERROR] '.$message.PHP_EOL); exit(1); }
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
function run(string $command): int { passthru($command,$code); return $code; }

chdir($root)||fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0)fail('No se pudo consultar git status.');
if($status)fail('El working tree debe estar limpio antes de aplicar la vista Mes.');

$controller=readNormalized($controllerPath,$controllerEol);
$controller=replaceOnce($controller,
    <<<'PHP'
$view=in_array($_GET['view']??'', ['calendar','list'],true)?$_GET['view']:'calendar';
PHP,
    <<<'PHP'
$view=in_array($_GET['view']??'', ['month','calendar','list'],true)?$_GET['view']:'month';
PHP,
    'Selector de vistas del controller'
);
$controller=replaceOnce($controller,
    <<<'PHP'
$defaultFrom=$view==='calendar'
            ?$today->modify('monday this week')
            :$today;
        $defaultTo=$view==='calendar'
            ?$defaultFrom->modify('+6 days')
            :$today->modify('+6 days');
PHP,
    <<<'PHP'
$defaultFrom=$view==='month'
            ?$today->modify('first day of this month')
            :($view==='calendar'?$today->modify('monday this week'):$today);
        $defaultTo=$view==='month'
            ?$today->modify('last day of this month')
            :($view==='calendar'?$defaultFrom->modify('+6 days'):$today->modify('+6 days'));
PHP,
    'Rango predeterminado por vista'
);
writeNormalized($controllerPath,$controller,$controllerEol);

$view=readNormalized($viewPath,$viewEol);
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
$view=replaceOnce($view,
    <<<'PHP'
}

$startHour=max(0,min(23,(int)($hourWindow['start_hour']??8)));
PHP,
    "}\n\n".$monthSetup.<<<'PHP'
$startHour=max(0,min(23,(int)($hourWindow['start_hour']??8)));
PHP,
    'Preparación de Mes'
);
$view=replaceOnce($view,
    '<p class="page-subtitle">Calendario y Lista comparten las mismas actividades visibles de tu alcance.</p>',
    '<p class="page-subtitle">Calendario mensual, Semana y Lista comparten las mismas actividades visibles de tu alcance.</p>',
    'Subtítulo Agenda'
);
$view=replaceOnce($view,
    <<<'PHP'
    <nav class="agenda-view-switch" aria-label="Vista de agenda">
      <a class="btn <?= ($filters['view']??'calendar')==='calendar'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'calendar'])) ?>">Calendario</a>
      <a class="btn <?= ($filters['view']??'calendar')==='list'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'list'])) ?>">Lista</a>
    </nav>
PHP,
    <<<'PHP'
    <nav class="agenda-view-switch" aria-label="Vista de agenda">
      <a class="btn <?= $activeView==='month'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'month']+['from'=>$monthStart->format('Y-m-d'),'to'=>$monthEnd->format('Y-m-d')])) ?>">Mes</a>
      <a class="btn <?= $activeView==='calendar'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'calendar','from'=>$anchorWeekStart->format('Y-m-d'),'to'=>$anchorWeekStart->modify('+6 days')->format('Y-m-d')])) ?>">Semana</a>
      <a class="btn <?= $activeView==='list'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'list'])) ?>">Lista</a>
    </nav>
PHP,
    'Selector Mes/Semana/Lista'
);
$view=replaceOnce($view,
    <<<'PHP'
  <div class="agenda-quick-links" aria-label="Rangos rápidos">
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('-7 days')->format('Y-m-d'),'to'=>$weekStart->modify('-1 day')->format('Y-m-d')])) ?>">Semana anterior</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>date('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('+7 days')->format('Y-m-d'),'to'=>$weekStart->modify('+13 days')->format('Y-m-d')])) ?>">Semana siguiente</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>(new DateTimeImmutable('monday this week'))->format('Y-m-d'),'to'=>(new DateTimeImmutable('monday this week'))->modify('+6 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Esta semana</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>(new DateTimeImmutable('today'))->modify('+29 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Próximos 30 días</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['history'=>'1','status'=>'all'])) ?>">Historial</a>
  </div>
PHP,
    <<<'PHP'
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
PHP,
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
$view=replaceOnce($view,
    <<<'PHP'
  <?php if(($filters['view']??'calendar')==='calendar'): ?>
PHP,
    $monthSection.<<<'PHP'
  <?php if(($filters['view']??'calendar')==='calendar'): ?>
PHP,
    'Inserción de calendario mensual'
);
writeNormalized($viewPath,$view,$viewEol);

$css=readNormalized($cssPath,$cssEol);
$css=replaceOnce($css,
    ".agenda-view-list .agenda-calendar{display:none}\n.agenda-view-calendar .agenda-list:not(.agenda-overdue){display:none}",
    ".agenda-view-list .agenda-calendar,.agenda-view-list .agenda-month{display:none}\n.agenda-view-calendar .agenda-month,.agenda-view-calendar .agenda-list:not(.agenda-overdue){display:none}\n.agenda-view-month .agenda-calendar,.agenda-view-month .agenda-list:not(.agenda-overdue){display:none}",
    'Visibilidad por vista'
);
$monthCss=<<<'CSS'
.agenda-month{display:grid;gap:12px;border:1px solid var(--border);border-radius:12px;background:var(--card);padding:14px;overflow:hidden}
.agenda-month-head{display:flex;align-items:end;justify-content:space-between;gap:12px;flex-wrap:wrap}
.agenda-month-head h2{margin:2px 0 0;font-size:22px;color:var(--ink)}
.agenda-month-summary{font-size:12px;color:var(--muted)}
.agenda-month-scroll{min-width:0;overflow:hidden}
.agenda-month-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-top:1px solid var(--border);border-left:1px solid var(--border);background:var(--border);gap:1px}
.agenda-month-weekday{padding:8px 6px;text-align:center;font-size:11px;font-weight:850;color:var(--muted);background:var(--card)}
.agenda-month-day{min-height:128px;padding:8px;background:var(--card);display:grid;align-content:start;gap:6px;min-width:0}
.agenda-month-day.is-outside{background:color-mix(in srgb,var(--card) 84%,var(--bg) 16%);opacity:.62}
.agenda-month-date{display:flex;justify-content:flex-end;min-height:28px;font-weight:850;color:var(--ink)}
.agenda-month-date span{display:grid;place-items:center;width:28px;height:28px;border-radius:999px}
.agenda-month-day.is-today .agenda-month-date span{background:var(--brand);color:#fff}
.agenda-month-events{display:grid;gap:4px;min-width:0}
.agenda-month-event{display:grid;grid-template-columns:auto minmax(0,1fr);gap:5px;align-items:center;min-width:0;padding:4px 6px;border-radius:6px;text-decoration:none;color:var(--ink);background:color-mix(in srgb,var(--brand) 13%,var(--card) 87%);border-left:3px solid var(--brand);font-size:11px;line-height:1.15}
.agenda-month-event time{font-weight:850;white-space:nowrap;color:var(--brand)}
.agenda-month-event span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.agenda-month-event.is-en-curso{border-left-color:var(--success,#067647);background:color-mix(in srgb,var(--success,#067647) 10%,var(--card) 90%)}
.agenda-month-event.is-finalizada,.agenda-month-event.is-cancelada{opacity:.7}
.agenda-month-event.has-conflict{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--warning,#b7791f) 65%,transparent 35%)}
.agenda-month-more{font-size:11px;font-weight:800;color:var(--brand);padding:2px 4px}
CSS;
$css=replaceOnce($css,'.agenda-list,.agenda-day{display:grid;gap:12px}',$monthCss."\n.agenda-list,.agenda-day{display:grid;gap:12px}",'CSS mensual');
$css.="\n@media(max-width:1023px){.agenda-month-scroll{overflow-x:auto}.agenda-month-grid{min-width:760px}.agenda-month-day{min-height:116px}}\n";
$css.="@media(max-width:760px){.agenda-month{padding:10px}.agenda-month-head h2{font-size:19px}.agenda-month-summary{display:none}.agenda-month-scroll{overflow:visible}.agenda-month-grid{min-width:0}.agenda-month-weekday{padding:6px 2px;font-size:9px}.agenda-month-day{min-height:96px;padding:4px;gap:3px}.agenda-month-date{min-height:24px}.agenda-month-date span{width:24px;height:24px;font-size:12px}.agenda-month-event{display:block;padding:3px 4px;font-size:9px;white-space:nowrap;overflow:hidden}.agenda-month-event time{display:block;font-size:8px}.agenda-month-event span{display:block}.agenda-month-more{font-size:9px;padding:1px 2px}}\n";
writeNormalized($cssPath,$css,$cssEol);

foreach([$controllerPath,$viewPath] as $file){
    if(run('"'.$php.'" -l "'.$file.'"')!==0)fail('Falló PHP lint en '.$file);
}
foreach([
    'tests\\phase6_agenda_month_regression.php',
    'tests\\phase6_agenda_ui_regression.php',
    'tests\\phase6_agenda_list_range_regression.php',
    'tests\\phase6_agenda_multiday_calendar_regression.php',
    'tests\\phase6_agenda_service_regression.php',
] as $test){
    if(run('"'.$php.'" '.$test)!==0)fail('Falló '.$test);
}
if(run('git --no-pager diff --check')!==0)fail('git diff --check encontró problemas.');

echo '[OK] GREEN confirmado: Agenda incorpora Mes sin perder Semana ni Lista.'.PHP_EOL;
echo '[OK] Mes es la vista predeterminada y usa rango mensual completo.'.PHP_EOL;
echo '[OK] No se modificó la base de datos.'.PHP_EOL;
