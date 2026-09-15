<?php
$pageTitle='Agenda';$pageSection='Agenda';$activeNav='agenda';$helpContext='agenda';
$filters=$filters??[];$activities=$activities??[];$overdue=$overdue??[];$options=$options??['responsibles'=>[],'parks'=>[]];$ticketMatches=$ticketMatches??[];$canProgram=(bool)($canProgram??false);$notice=$notice??null;$scopeLabel=$scopeLabel??'';
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
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<section class="agenda-shell">
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

  <div class="agenda-quick-links" aria-label="Rangos rápidos">
    <a class="btn btn-outline-secondary btn-sm" href="<?= $h($withFilter(['from'=>date('Y-m-d'),'to'=>date('Y-m-d'),'history'=>false,'status'=>'active'])) ?>">Hoy</a>
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
