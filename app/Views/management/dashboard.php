<?php
$pageTitle='Dashboard interno';$pageSection='Gestión';$activeNav='management';$helpContext='management';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Disponible','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$q=http_build_query($filters);
$maxStatus=max(1,...array_map(fn($x)=>(int)$x['total'],$byStatus?:[['total'=>1]]));
$maxCategory=max(1,...array_map(fn($x)=>(int)$x['total'],$byCategory?:[['total'=>1]]));
$maxPark=max(1,...array_map(fn($x)=>(int)$x['total'],$byPark?:[['total'=>1]]));
$maxAssignee=max(1,...array_map(fn($x)=>(int)$x['total'],$byAssignee?:[['total'=>1]]));
$total=(int)($kpis['total']??0);
$open=(int)($kpis['abiertos']??0);
$done=(int)($kpis['resueltos']??0)+(int)($kpis['cerrados']??0);
$donePct=$total>0?round($done/$total*100):0;
?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/management.css">

<div class="mgmt-head">
  <div><span class="mgmt-kicker">Control interno</span><h1>Dashboard de gestión</h1><p>Métricas, carga de trabajo, cumplimiento y detalle para toma de decisiones.</p></div>
  <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes?<?= htmlspecialchars($q) ?>">Ver informes</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/informes/exportar?<?= htmlspecialchars($q) ?>" data-action-message="Preparando archivo Excel…">Exportar Excel (.xlsx)</a></div>
</div>

<form class="mgmt-filterbar" method="get" action="<?= APP_BASE_URL ?>/gestion" data-processing-form>
  <label>Desde<input class="form-control" type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></label>
  <label>Hasta<input class="form-control" type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></label>
  <label>Parque<select class="form-control" name="park_id"><option value="0">Todos</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['park_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
  <label>Categoría<select class="form-control" name="category_id"><option value="0">Todas</option><?php foreach($categories as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['category_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
  <label>Responsable<select class="form-control" name="assigned_to"><option value="0">Todos</option><?php foreach($supportUsers as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['assigned_to']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['full_name']) ?></option><?php endforeach; ?></select></label>
  <label>Estado<select class="form-control" name="status"><option value="">Todos</option><?php foreach($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
  <label>Prioridad<select class="form-control" name="priority"><option value="">Todas</option><?php foreach($priorityLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['priority']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
  <div class="mgmt-filter-actions"><button class="btn btn-primary">Aplicar filtros</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion">Limpiar</a></div>
</form>

<section class="mgmt-kpis">
  <article><span>Total casos</span><strong><?= $total ?></strong><small>Periodo seleccionado</small></article>
  <article><span>Abiertos</span><strong><?= $open ?></strong><small>Requieren seguimiento</small></article>
  <article><span>Sin asignar</span><strong><?= (int)($kpis['sin_asignar']??0) ?></strong><small>Disponibles en cola</small></article>
  <article class="<?= (int)($kpis['vencidos']??0)>0?'kpi-alert':'' ?>"><span>Fuera de SLA</span><strong><?= (int)($kpis['vencidos']??0) ?></strong><small>Casos activos vencidos</small></article>
  <article><span>Primera respuesta</span><strong><?= $kpis['promedio_primera_respuesta']!==null?htmlspecialchars((string)$kpis['promedio_primera_respuesta']).' min':'—' ?></strong><small>Promedio del periodo</small></article>
  <article><span>Cumplimiento SLA</span><strong><?= $kpis['sla_porcentaje']!==null?htmlspecialchars((string)$kpis['sla_porcentaje']).'%':'—' ?></strong><small>Casos resueltos medibles</small></article>
</section>

<section class="mgmt-grid mgmt-grid-top">
  <article class="mgmt-card"><div class="mgmt-card-head"><div><span>Panorama</span><h2>Estado de los casos</h2></div><b><?= $donePct ?>% completados</b></div><div class="mgmt-donut-wrap"><div class="mgmt-donut" style="--done:<?= $donePct ?>"><div><strong><?= $total ?></strong><span>casos</span></div></div><div class="mgmt-bars"><?php foreach($byStatus as $r): ?><div class="mgmt-bar"><div><span><?= htmlspecialchars($statusLabels[$r['label']]??$r['label']) ?></span><b><?= (int)$r['total'] ?></b></div><i><em style="width:<?= round((int)$r['total']/$maxStatus*100) ?>%"></em></i></div><?php endforeach; ?></div></div></article>
  <article class="mgmt-card"><div class="mgmt-card-head"><div><span>Demanda</span><h2>Tipos de solicitud</h2></div></div><div class="mgmt-bars mgmt-bars-large"><?php foreach($byCategory as $r): ?><div class="mgmt-bar"><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?></b></div><i><em style="width:<?= round((int)$r['total']/$maxCategory*100) ?>%"></em></i></div><?php endforeach; ?><?php if(!$byCategory): ?><div class="empty-state">Sin datos para el filtro actual.</div><?php endif; ?></div></article>
</section>

<section class="mgmt-grid">
  <article class="mgmt-card"><div class="mgmt-card-head"><div><span>Ubicaciones</span><h2>Casos por parque</h2></div></div><div class="mgmt-bars"><?php foreach($byPark as $r): ?><div class="mgmt-bar"><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?></b></div><i><em style="width:<?= round((int)$r['total']/$maxPark*100) ?>%"></em></i></div><?php endforeach; ?></div></article>
  <article class="mgmt-card"><div class="mgmt-card-head"><div><span>Equipo</span><h2>Carga por responsable</h2></div></div><div class="mgmt-bars"><?php foreach($byAssignee as $r): ?><div class="mgmt-bar"><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?> <small>· <?= (int)$r['activos'] ?> activos</small></b></div><i><em style="width:<?= round((int)$r['total']/$maxAssignee*100) ?>%"></em></i></div><?php endforeach; ?></div></article>
</section>

<section class="mgmt-card"><div class="mgmt-card-head"><div><span>Tendencia</span><h2>Casos creados vs. completados</h2></div></div><div class="mgmt-trend"><?php $trendMax=max(1,...array_map(fn($x)=>(int)$x['total'],$trend?:[['total'=>1]])); foreach($trend as $r): ?><div class="mgmt-trend-item"><span><?= htmlspecialchars($r['periodo']) ?></span><div><i style="height:<?= max(8,round((int)$r['total']/$trendMax*100)) ?>%" title="Creados: <?= (int)$r['total'] ?>"></i><em style="height:<?= max(4,round((int)$r['completados']/$trendMax*100)) ?>%" title="Completados: <?= (int)$r['completados'] ?>"></em></div><small><?= (int)$r['total'] ?>/<?= (int)$r['completados'] ?></small></div><?php endforeach; ?><?php if(!$trend): ?><div class="empty-state">Sin tendencia disponible.</div><?php endif; ?></div></section>

<section class="mgmt-card"><div class="mgmt-card-head"><div><span>Detalle</span><h2>Casos recientes del filtro</h2></div><a href="<?= APP_BASE_URL ?>/gestion/informes?<?= htmlspecialchars($q) ?>">Ver informe completo →</a></div><div class="table-responsive"><table class="table"><thead><tr><th>Ticket</th><th>Fecha</th><th>Asunto</th><th>Parque</th><th>Categoría</th><th>Responsable</th><th>Estado</th></tr></thead><tbody><?php foreach($tickets as $t): ?><tr><td><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>"><strong><?= htmlspecialchars($t['ticket_number']) ?></strong></a></td><td><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></td><td><?= htmlspecialchars($t['subject']) ?></td><td><?= htmlspecialchars($t['park_name']??'—') ?></td><td><?= htmlspecialchars($t['category_name']??'—') ?></td><td><?= htmlspecialchars($t['assigned_name']??'Sin asignar') ?></td><td><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>

<div class="processing-overlay" data-processing-overlay hidden><div class="processing-box"><div class="processing-spinner"></div><h2>Procesando información</h2><p>Actualizando métricas y filtros…</p><div class="processing-line"><i></i></div><small>Preparando el dashboard interno.</small></div></div>
<script>document.querySelectorAll('[data-processing-form]').forEach(f=>f.addEventListener('submit',()=>{const x=document.querySelector('[data-processing-overlay]');if(x)x.hidden=false;}));</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>