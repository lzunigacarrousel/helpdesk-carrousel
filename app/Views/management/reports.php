<?php
$pageTitle='Informes';$pageSection='Gestión';$activeNav='reports';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$resolutionLabels=['CONFIGURATION'=>'Configuración','RESTART'=>'Reinicio','REPLACEMENT'=>'Cambio / reemplazo','PROVIDER'=>'Gestión con proveedor','USER_GUIDANCE'=>'Orientación al usuario','SOFTWARE'=>'Software','NETWORK'=>'Red / conectividad','HARDWARE'=>'Hardware','PERMISSION'=>'Acceso / permisos','MAINTENANCE'=>'Mantenimiento','OTHER'=>'Otro'];
$q=http_build_query($filters);
$fmtMinutes=static function($minutes):string{
  if($minutes===null||$minutes==='')return '—';
  $minutes=(int)round((float)$minutes);
  if($minutes<60)return $minutes.' min';
  $hours=intdiv($minutes,60);$mins=$minutes%60;
  if($hours<24)return $hours.' h'.($mins>0?' '.$mins.' min':'');
  $days=intdiv($hours,24);$rem=$hours%24;
  return $days.' d'.($rem>0?' '.$rem.' h':'');
};
$s=$reportStats??[];
?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/management.css">

<div class="mgmt-head report-head">
  <div>
    <span class="mgmt-kicker">Reportería operativa y aprendizaje</span>
    <h1>Informes de tickets</h1>
    <p>Seguimiento completo del caso: qué ocurrió, quién lo atendió, cuánto tiempo pasó en cada estado y cómo se resolvió.</p>
  </div>
  <div class="mgmt-head-actions">
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion?<?= htmlspecialchars($q) ?>">← Dashboard</a>
    <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/informes/exportar?<?= htmlspecialchars($q) ?>">Descargar CSV completo</a>
  </div>
</div>

<section class="report-summary-grid" aria-label="Resumen del período">
  <article><span>Tickets</span><strong><?= (int)($s['total']??0) ?></strong><small>Resultado actual</small></article>
  <article><span>Documentados</span><strong><?= (int)($s['documented']??0) ?></strong><small><?= htmlspecialchars((string)($s['documented_pct']??0)) ?>% con solución registrada</small></article>
  <article class="report-summary-alert"><span>Sin documentar</span><strong><?= (int)($s['undocumented']??0) ?></strong><small>Casos sin aprendizaje registrado</small></article>
  <article><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_first_response_minutes']??null)) ?></strong><small>Promedio del período</small></article>
  <article><span>Hasta resolución</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_resolution_minutes']??null)) ?></strong><small>Desde creación hasta resuelto</small></article>
  <article><span>Trabajo efectivo</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_work_minutes']??null)) ?></strong><small>Tiempo promedio en proceso</small></article>
  <article><span>En espera</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_pending_minutes']??null)) ?></strong><small>Tiempo promedio pausado</small></article>
  <article><span>Cambios de estado</span><strong><?= (int)($s['status_changes']??0) ?></strong><small>Movimientos registrados</small></article>
</section>

<form class="mgmt-filterbar report-filterbar" method="get" action="<?= APP_BASE_URL ?>/gestion/informes" data-processing-form>
<label>Desde<input class="form-control" type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></label>
<label>Hasta<input class="form-control" type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></label>
<label>Parque<select class="form-control" name="park_id"><option value="0">Todos</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['park_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
<label>Categoría<select class="form-control" name="category_id"><option value="0">Todas</option><?php foreach($categories as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['category_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
<label>Responsable<select class="form-control" name="assigned_to"><option value="0">Todos</option><?php foreach($supportUsers as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['assigned_to']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['full_name']) ?></option><?php endforeach; ?></select></label>
<label>Estado<select class="form-control" name="status"><option value="">Todos</option><?php foreach($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
<label>Prioridad<select class="form-control" name="priority"><option value="">Todas</option><?php foreach($priorityLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['priority']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
<div class="mgmt-filter-actions"><button class="btn btn-primary">Aplicar filtros</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes">Limpiar</a></div>
</form>

<section class="mgmt-card report-results-card">
  <div class="mgmt-card-head report-results-head">
    <div><span>Detalle operativo</span><h2><?= count($rows) ?> tickets analizados</h2></div>
    <small class="muted">Aquí se concentra la trazabilidad útil del caso. La exportación descarga todos los registros del filtro.</small>
  </div>

  <?php if(!$rows): ?>
    <div class="empty-state"><strong>No hay tickets para este filtro.</strong><br>Ajusta el período o los criterios de búsqueda.</div>
  <?php else: ?>
  <div class="report-records">
    <?php foreach($rows as $r): $life=$r['lifecycle']??[]; ?>
      <article class="report-record">
        <header class="report-record-head">
          <div class="report-ticket-id">
            <a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['id'] ?>"><strong><?= htmlspecialchars($r['ticket_number']) ?></strong></a>
            <span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($r['created_at']))) ?> · <?= htmlspecialchars($priorityLabels[$r['priority']]??$r['priority']) ?></span>
          </div>
          <div class="report-record-status">
            <span class="badge badge-primary"><?= htmlspecialchars($statusLabels[$r['status']]??$r['status']) ?></span>
            <small><?= htmlspecialchars($r['assigned_name']??'Sin asignar') ?></small>
          </div>
        </header>

        <div class="report-record-grid">
          <section class="report-block report-context">
            <span class="report-label">Contexto</span>
            <h3><?= htmlspecialchars($r['subject']) ?></h3>
            <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
            <dl>
              <div><dt>Solicitante</dt><dd><?= htmlspecialchars($r['requester_name']) ?></dd></div>
              <div><dt>Correo</dt><dd><?= htmlspecialchars($r['requester_email']) ?></dd></div>
              <div><dt>Teléfono</dt><dd><?= htmlspecialchars($r['requester_phone']?:'—') ?></dd></div>
              <div><dt>Ubicación</dt><dd><?= htmlspecialchars($r['park_name']??'Sin parque') ?><?= !empty($r['area_name'])?' · '.htmlspecialchars($r['area_name']):'' ?></dd></div>
              <div><dt>Categoría</dt><dd><?= htmlspecialchars($r['category_name']??'—') ?></dd></div>
            </dl>
          </section>

          <section class="report-block report-times">
            <span class="report-label">Tiempos de ejecución</span>
            <div class="report-time-grid">
              <div><span>Hasta asignación</span><strong><?= htmlspecialchars($fmtMinutes($life['assignment_minutes']??null)) ?></strong></div>
              <div><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMinutes($life['first_response_minutes']??null)) ?></strong></div>
              <div><span>En cola</span><strong><?= htmlspecialchars($fmtMinutes($life['queue_minutes']??0)) ?></strong></div>
              <div><span>Trabajando</span><strong><?= htmlspecialchars($fmtMinutes($life['work_minutes']??0)) ?></strong></div>
              <div><span>En espera</span><strong><?= htmlspecialchars($fmtMinutes($life['pending_minutes']??0)) ?></strong></div>
              <div><span>Hasta resolución</span><strong><?= htmlspecialchars($fmtMinutes($life['resolution_minutes']??null)) ?></strong></div>
            </div>
            <div class="report-dates">
              <span><b>Asignado:</b> <?= !empty($r['assigned_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['assigned_at']))):'—' ?></span>
              <span><b>Primera respuesta:</b> <?= !empty($r['first_response_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['first_response_at']))):'—' ?></span>
              <span><b>Resuelto:</b> <?= !empty($r['resolved_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['resolved_at']))):'—' ?></span>
              <span><b>Cerrado:</b> <?= !empty($r['closed_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['closed_at']))):'—' ?></span>
            </div>
          </section>

          <section class="report-block report-status-history">
            <span class="report-label">Cambios de estado</span>
            <?php if(!empty($life['transitions'])): ?>
              <ol class="report-timeline">
                <?php foreach($life['transitions'] as $t): ?>
                  <li>
                    <span class="report-timeline-dot"></span>
                    <div><strong><?= htmlspecialchars($t['from_label']) ?> → <?= htmlspecialchars($t['to_label']) ?></strong><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['at']))) ?> · <?= htmlspecialchars($t['actor']) ?></small></div>
                  </li>
                <?php endforeach; ?>
              </ol>
              <details class="report-status-durations"><summary>Ver tiempo acumulado por estado</summary><div>
                <?php foreach(($life['durations']??[]) as $code=>$minutes): ?><span><b><?= htmlspecialchars($statusLabels[$code]??$code) ?>:</b> <?= htmlspecialchars($fmtMinutes($minutes)) ?></span><?php endforeach; ?>
              </div></details>
            <?php else: ?>
              <div class="report-empty-info">Sin cambios de estado registrados todavía.</div>
            <?php endif; ?>
          </section>

          <section class="report-block report-resolution-panel <?= empty($r['solution_applied'])?'is-missing':'' ?>">
            <span class="report-label">Resolución y aprendizaje</span>
            <?php if(!empty($r['solution_applied'])): ?>
              <strong class="report-resolution-type"><?= htmlspecialchars($resolutionLabels[$r['resolution_type']]??'Solución documentada') ?></strong>
              <div class="report-resolution-content"><b>Causa encontrada</b><p><?= nl2br(htmlspecialchars($r['root_cause']??'No indicada')) ?></p><b>Solución aplicada</b><p><?= nl2br(htmlspecialchars($r['solution_applied'])) ?></p><?php if(!empty($r['preventive_action'])):?><b>Prevención / seguimiento</b><p><?= nl2br(htmlspecialchars($r['preventive_action'])) ?></p><?php endif; ?></div>
              <small>Documentado por <?= htmlspecialchars($r['resolution_author']??'equipo de soporte') ?></small>
            <?php else: ?>
              <strong>Falta documentar la resolución</strong>
              <p class="muted">Cuando se resuelva el caso deben quedar registrados causa, solución aplicada y prevención para alimentar los informes y casos similares.</p>
            <?php endif; ?>
          </section>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<div class="processing-overlay" data-processing-overlay hidden><div class="processing-box"><div class="processing-spinner"></div><h2>Procesando información</h2><p>Aplicando filtros al informe…</p><div class="processing-line"><i></i></div><small>Preparando métricas, tiempos e historial de los casos.</small></div></div>
<script>document.querySelectorAll('[data-processing-form]').forEach(f=>f.addEventListener('submit',()=>{const x=document.querySelector('[data-processing-overlay]');if(x)x.hidden=false;}));</script>

<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
