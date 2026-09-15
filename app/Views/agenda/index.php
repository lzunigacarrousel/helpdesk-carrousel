<?php
$pageTitle='Agenda';$pageSection='Agenda';$activeNav='agenda';$helpContext='agenda';
$filters=$filters??[];$activities=$activities??[];$overdue=$overdue??[];$options=$options??['responsibles'=>[],'parks'=>[]];$ticketMatches=$ticketMatches??[];$hourWindow=$hourWindow??['start_hour'=>8,'end_hour'=>18];$canProgram=(bool)($canProgram??false);$notice=$notice??null;$scopeLabel=$scopeLabel??'';
$ticketQuery=trim((string)($filters['ticket_q']??''));
$typeLabels=['VISITA_EN_SITIO'=>'Visita en sitio','SOPORTE_REMOTO'=>'Soporte remoto','SEGUIMIENTO'=>'Seguimiento','INTERVENCION_PROVEEDOR'=>'Intervención de proveedor','OTRA'=>'Otra atención'];
$statusLabels=['PROGRAMADA'=>'Programada','EN_CURSO'=>'En curso','FINALIZADA'=>'Finalizada','CANCELADA'=>'Cancelada','active'=>'Activas','all'=>'Todas'];
$h=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$dateLabel=static function(string $date):string{$ts=strtotime($date);return $ts?strtoupper(date('d M Y',$ts)):$date;};
$timeRange=static function(array $row):string{$s=strtotime((string)($row['scheduled_start_at']??''));$e=strtotime((string)($row['scheduled_end_at']??''));return($s?date('H:i',$s):'--:--').'–'.($e?date('H:i',$e):'--:--');};
$withFilter=static function(array $changes)use($filters):string{$query=array_merge($filters,$changes);foreach($query as $k=>$v){if($v===false||$v===null||$v==='')unset($query[$k]);elseif($v===true)$query[$k]='1';}return APP_BASE_URL.'/agenda?'.http_build_query($query);};
$renderItem=static function(array $item)use($h,$timeRange,$typeLabels,$statusLabels):void{
    $status=(string)($item['status']??'');$statusClass=strtolower(str_replace('_','-',$status));
    $ticketUrl=(string)($item['ticket_url']??(APP_BASE_URL.'/tickets/view?id='.(int)($item['ticket_id']??0).'#actividades'));
    ?>
    <a class="agenda-item is-<?= $h($statusClass) ?> <?= !empty($item['is_overdue'])?'is-overdue':'' ?>" href="<?= $h($ticketUrl) ?>">
      <time><?= $h($timeRange($item)) ?></time>
      <span class="agenda-item-main"><strong><?= $h($typeLabels[(string)($item['activity_type']??'')]??($item['activity_type']??'Actividad')) ?></strong><span><?= $h($item['ticket_code']??'') ?> · <?= $h($item['ticket_subject']??'') ?></span><small><?= $h($item['park_name']??'Sin parque') ?> · <?= $h($item['responsible_name']??'Sin responsable') ?></small><?php if(!empty($item['has_conflict'])): ?><em>Conflicto de horario</em><?php endif; ?></span>
      <span class="agenda-status"><?= $h($statusLabels[$status]??$status) ?></span>
    </a>
    <?php
};
$days=[];foreach($activities as $activity){$day=substr((string)($activity['scheduled_start_at']??''),0,10);if($day==='')$day='Sin fecha';$days[$day][]=$activity;}
ksort($days);
$hourWindow=$hourWindow??['start_hour'=>8,'end_hour'=>18];
$calendarStart=DateTimeImmutable::createFromFormat('!Y-m-d',(string)($filters['from']??''));
if(!$calendarStart)$calendarStart=new DateTimeImmutable('monday this week');
$weekStart=$calendarStart;
$calendarDays=[];
for($offset=0;$offset<7;$offset++){
    $key=$calendarStart->modify('+'.$offset.' days')->format('Y-m-d');
    $calendarDays[$key]=[];
}
foreach($activities as $activity){
    $key=substr((string)($activity['scheduled_start_at']??''),0,10);
    if(isset($calendarDays[$key]))$calendarDays[$key][]=$activity;
}
$startHour=max(0,min(23,(int)($hourWindow['start_hour']??8)));
$endHour=max($startHour+1,min(24,(int)($hourWindow['end_hour']??18)));
$slotCount=max(1,($endHour-$startHour)*2);
$slotFor=static function(string $value)use($startHour,$slotCount):int{
    $ts=strtotime($value);
    if(!$ts)return 0;
    $hour=(int)date('G',$ts);
    $minute=(int)date('i',$ts);
    return min(max(0,(($hour-$startHour)*2)+(int)floor($minute/30)),max(0,$slotCount-1));
};
$spanFor=static function(array $row):int{
    $s=strtotime((string)($row['scheduled_start_at']??''));
    $e=strtotime((string)($row['scheduled_end_at']??''));
    if(!$s||!$e||$e<=$s)return 1;
    return max(1,(int)ceil(($e-$s)/1800));
};
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<section class="agenda-shell agenda-view-<?= $h($filters['view']??'calendar') ?>">
  <header class="agenda-toolbar">
    <div>
      <div class="ticket-kicker"><?= $h($scopeLabel) ?></div>
      <h1 class="page-title">Agenda</h1>
      <p class="page-subtitle">Calendario y Lista comparten las mismas actividades visibles de tu alcance.</p>
    </div>
    <nav class="agenda-view-switch" aria-label="Vista de agenda">
      <a class="btn <?= ($filters['view']??'calendar')==='calendar'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'calendar'])) ?>">Calendario</a>
      <a class="btn <?= ($filters['view']??'calendar')==='list'?'btn-primary':'btn-outline-secondary' ?>" href="<?= $h($withFilter(['view'=>'list'])) ?>">Lista</a>
    </nav>
  </header>

  <?php if($notice): ?><div class="alert alert-info"><?= $h($notice) ?></div><?php endif; ?>

  <?php if($canProgram===true): ?>
  <section class="card agenda-program-card" aria-labelledby="agenda-program-title">
    <div class="card-body">
      <div class="agenda-program-head">
        <div>
          <div class="ticket-kicker">Programacion</div>
          <h2 id="agenda-program-title">Programar actividad</h2>
        </div>
      </div>
      <form class="agenda-program-search" method="get" action="<?= APP_BASE_URL ?>/agenda">
        <input type="hidden" name="program" value="1">
        <input type="hidden" name="view" value="<?= $h($filters['view']??'calendar') ?>">
        <label class="form-label">Ticket<input class="form-control" type="search" name="ticket_q" value="<?= $h($ticketQuery) ?>" maxlength="100" placeholder="Buscar por codigo, asunto o solicitante" autocomplete="off"></label>
        <button class="btn btn-primary" type="submit">Buscar ticket</button>
      </form>
      <?php if($ticketMatches): ?>
      <div class="agenda-ticket-matches">
        <?php foreach(array_slice($ticketMatches,0,10) as $match):
          $matchId=(int)($match['ticket_id']??($match['id']??0));
          $ticketUrl=(string)($match['ticket_url']??'');
          if($ticketUrl==='')$ticketUrl=APP_BASE_URL.'/tickets/view?id='.$matchId;
          $ticketUrl=(string)preg_replace('/#.*$/','',$ticketUrl).'#actividades';
          $ticketCode=(string)($match['ticket_code']??($match['code']??('Ticket #'.$matchId)));
          $ticketSubject=(string)($match['ticket_subject']??($match['subject']??''));
          $ticketMeta=trim((string)($match['park_name']??($match['requester_name']??'')));
        ?>
        <a class="agenda-ticket-match" href="<?= $h($ticketUrl) ?>">
          <strong><?= $h($ticketCode) ?></strong>
          <?php if($ticketSubject!==''): ?><span><?= $h($ticketSubject) ?></span><?php endif; ?>
          <?php if($ticketMeta!==''): ?><small><?= $h($ticketMeta) ?></small><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php elseif($ticketQuery!==''): ?>
      <div class="empty-state">No se encontraron tickets visibles.</div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="agenda-quick-links" aria-label="Rangos rápidos">
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('-7 days')->format('Y-m-d'),'to'=>$weekStart->modify('-1 day')->format('Y-m-d')])) ?>">Semana anterior</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>date('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>$weekStart->modify('+7 days')->format('Y-m-d'),'to'=>$weekStart->modify('+13 days')->format('Y-m-d')])) ?>">Semana siguiente</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>(new DateTimeImmutable('monday this week'))->format('Y-m-d'),'to'=>(new DateTimeImmutable('monday this week'))->modify('+6 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Esta semana</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>(new DateTimeImmutable('today'))->modify('+29 days')->format('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Próximos 30 días</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['history'=>'1','status'=>'all'])) ?>">Historial</a>
  </div>

  <form class="card agenda-filter-card" method="get" action="<?= APP_BASE_URL ?>/agenda">
    <div class="card-body agenda-filters">
      <input type="hidden" name="view" value="<?= $h($filters['view']??'calendar') ?>">
      <label class="form-label">Responsable<select class="form-control" name="responsible_user_id"><option value="0">Todos visibles</option><?php foreach($options['responsibles']??[] as $person): $id=(int)($person['id']??0); ?><option value="<?= $id ?>" <?= (int)($filters['responsible_user_id']??0)===$id?'selected':'' ?>><?= $h($person['full_name']??'') ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Parque<select class="form-control" name="park_id"><option value="0">Todos visibles</option><?php foreach($options['parks']??[] as $park): $id=(int)($park['id']??0); ?><option value="<?= $id ?>" <?= (int)($filters['park_id']??0)===$id?'selected':'' ?>><?= $h($park['name']??'') ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Tipo<select class="form-control" name="activity_type"><option value="">Todos</option><?php foreach($typeLabels as $code=>$label): ?><option value="<?= $h($code) ?>" <?= ($filters['activity_type']??'')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Estado<select class="form-control" name="status"><option value="active" <?= ($filters['status']??'active')==='active'?'selected':'' ?>>Activas</option><option value="all" <?= ($filters['status']??'')==='all'?'selected':'' ?>>Todas</option><?php foreach(['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $code): ?><option value="<?= $code ?>" <?= ($filters['status']??'')===$code?'selected':'' ?>><?= $h($statusLabels[$code]) ?></option><?php endforeach; ?></select></label>
      <label class="form-label">Desde<input class="form-control" type="date" name="from" value="<?= $h($filters['from']??'') ?>"></label>
      <label class="form-label">Hasta<input class="form-control" type="date" name="to" value="<?= $h($filters['to']??'') ?>"></label>
      <label class="agenda-check"><input type="checkbox" name="history" value="1" <?= !empty($filters['history'])?'checked':'' ?>> Incluir historial</label>
      <?php if(($filters['scope_mode']??'all')==='mine'||($filters['scope_mode']??'all')==='all'): ?><input type="hidden" name="scope_mode" value="<?= $h($filters['scope_mode']??'all') ?>"><?php endif; ?>
      <div class="agenda-filter-actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/agenda">Limpiar</a></div>
    </div>
  </form>

  <?php if($overdue): ?>
  <section class="agenda-list agenda-overdue" aria-labelledby="agenda-overdue-title">
    <h2 id="agenda-overdue-title">Pendientes atrasadas</h2>
    <?php foreach($overdue as $item): $renderItem($item); endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if(($filters['view']??'calendar')==='calendar'): ?>
  <section class="agenda-calendar" aria-label="Calendario semanal">
    <nav class="agenda-calendar-nav" aria-label="Navegacion semanal">
      <a class="btn btn-outline-secondary" href="<?= $h($withFilter(['view'=>'calendar','from'=>$calendarStart->modify('-7 days')->format('Y-m-d'),'to'=>$calendarStart->modify('-1 day')->format('Y-m-d')])) ?>">Semana anterior</a>
      <a class="btn btn-outline-secondary" href="<?= $h($withFilter(['view'=>'calendar','from'=>(new DateTimeImmutable('monday this week'))->format('Y-m-d'),'to'=>(new DateTimeImmutable('monday this week'))->modify('+6 days')->format('Y-m-d')])) ?>">Hoy</a>
      <a class="btn btn-outline-secondary" href="<?= $h($withFilter(['view'=>'calendar','from'=>$calendarStart->modify('+7 days')->format('Y-m-d'),'to'=>$calendarStart->modify('+13 days')->format('Y-m-d')])) ?>">Semana siguiente</a>
    </nav>
    <?php if(!$activities): ?>
    <div class="empty-state">No hay actividades visibles en esta semana.</div>
    <?php else: ?>
    <div class="agenda-calendar-grid" style="--agenda-slot-count:<?= (int)$slotCount ?>">
      <div class="agenda-calendar-time-heading">Hora</div>
      <?php foreach($calendarDays as $day=>$items): ?><div class="agenda-calendar-day-heading"><?= $h($dateLabel($day)) ?></div><?php endforeach; ?>
      <?php for($slot=0;$slot<$slotCount;$slot++):$minutes=($startHour*60)+($slot*30); ?>
      <time class="agenda-calendar-time" style="grid-row:<?= $slot+2 ?>"><?= sprintf('%02d:%02d',intdiv($minutes,60),$minutes%60) ?></time>
      <?php $dayColumn=0;foreach($calendarDays as $day=>$items): ?><div class="agenda-calendar-slot" style="grid-column:<?= $dayColumn+2 ?>;grid-row:<?= $slot+2 ?>"></div><?php $dayColumn++;endforeach; ?>
      <?php endfor; ?>
      <?php $dayColumn=0;foreach($calendarDays as $day=>$items):foreach($items as $item):$startSlot=$slotFor((string)($item['scheduled_start_at']??''));$span=max(1,min($spanFor($item),$slotCount-$startSlot));$statusClass=strtolower(str_replace('_','-',(string)($item['status']??'')));$ticketUrl=(string)($item['ticket_url']??(APP_BASE_URL.'/tickets/view?id='.(int)($item['ticket_id']??0).'#actividades')); ?>
      <a class="agenda-event is-<?= $h($statusClass) ?> <?= !empty($item['is_overdue'])?'is-overdue':'' ?>" href="<?= $h($ticketUrl) ?>" data-start-slot="<?= (int)$startSlot ?>" data-span-slots="<?= (int)$span ?>" style="grid-column:<?= $dayColumn+2 ?>;grid-row:<?= $startSlot+2 ?>/span <?= $span ?>"><time><?= $h($timeRange($item)) ?></time><strong><?= $h($typeLabels[(string)($item['activity_type']??'')]??($item['activity_type']??'Actividad')) ?></strong><span><?= $h($item['ticket_code']??'') ?></span><small><?= $h($item['park_name']??'Sin parque') ?> · <?= $h($item['responsible_name']??'Sin responsable') ?></small><?php if(!empty($item['has_conflict'])):?><em>Conflicto de horario</em><?php endif; ?></a>
      <?php endforeach;$dayColumn++;endforeach; ?>
    </div>
    <div class="agenda-calendar-days">
      <?php foreach($calendarDays as $day=>$items):?><section class="agenda-calendar-day-card"><h2><?= $h($dateLabel($day)) ?></h2><?php if(!$items):?><p class="agenda-calendar-empty">Sin actividades.</p><?php endif;?><?php foreach($items as $item):$renderItem($item);endforeach;?></section><?php endforeach;?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
  <section class="agenda-list" aria-label="Actividades por día">
    <?php if(!$days): ?><div class="empty-state">No hay actividades visibles en este rango.</div><?php endif; ?>
    <?php foreach($days as $day=>$items): ?>
      <section class="agenda-day">
        <h2><?= $h($dateLabel($day)) ?></h2>
        <?php foreach($items as $item): $renderItem($item); endforeach; ?>
      </section>
    <?php endforeach; ?>
  </section>
</section>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
