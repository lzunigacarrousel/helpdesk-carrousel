<?php
$pageTitle='Informes';$pageSection='Informes';$activeNav='reports';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=\App\Services\TicketReportFilterService::STATUS_LABELS;
$priorityLabels=\App\Services\TicketReportFilterService::PRIORITY_LABELS;
$resolutionLabels=['CONFIGURATION'=>'Configuración','RESTART'=>'Reinicio','REPLACEMENT'=>'Cambio / reemplazo','PROVIDER'=>'Gestión con proveedor','USER_GUIDANCE'=>'Orientación al usuario','SOFTWARE'=>'Software','NETWORK'=>'Red / conectividad','HARDWARE'=>'Hardware','PERMISSION'=>'Acceso / permisos','MAINTENANCE'=>'Mantenimiento','OTHER'=>'Otro'];
$pendingReasons=$pendingReasons??[];
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
$role=(string)\App\Core\Auth::role();
$canAgenda=(bool)($canActivities??false);
$canProviders=(bool)($canProviders??false);
$canTeam=(bool)($canTeam??false);
$canKnowledge=(bool)($canKnowledge??false);
$a=$activityReport??null;
$p=$providerReport??null;
$t=$teamReport??null;
$k=$knowledgeReport??null;
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.report-catalog-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin-bottom:14px}.report-catalog-card{display:flex;flex-direction:column;gap:5px;min-height:112px;padding:14px 15px;border:1px solid var(--border);border-radius:13px;background:var(--card);color:var(--ink);text-decoration:none;transition:border-color .15s ease,transform .15s ease,box-shadow .15s ease}.report-catalog-card:hover{border-color:color-mix(in srgb,var(--brand) 42%,var(--border) 58%);box-shadow:var(--shadow-sm);transform:translateY(-1px)}.report-catalog-card span{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:850}.report-catalog-card strong{font-size:15px;color:var(--brand-dark)}.report-catalog-card small{font-size:12px;line-height:1.4;color:var(--muted)}.report-catalog-card.is-current{border-color:color-mix(in srgb,var(--brand) 36%,var(--border) 64%);background:color-mix(in srgb,var(--brand) 5%,var(--card) 95%)}.report-knowledge-panel,.report-activity-panel,.report-provider-panel,.report-team-panel{scroll-margin-top:92px;border:1px solid color-mix(in srgb,var(--brand) 24%,var(--border) 76%);border-radius:14px;background:linear-gradient(135deg,color-mix(in srgb,var(--brand) 4%,var(--card) 96%),var(--card));padding:15px 16px;margin-bottom:14px}.report-knowledge-head,.report-activity-head,.report-provider-head,.report-team-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:12px}.report-knowledge-head h2,.report-activity-head h2,.report-provider-head h2,.report-team-head h2{margin:2px 0 3px;font-size:18px}.report-knowledge-head p,.report-activity-head p,.report-provider-head p,.report-team-head p{margin:0;color:var(--muted);font-size:12px}.report-knowledge-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.report-activity-grid,.report-provider-grid,.report-team-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px}.report-knowledge-metric,.report-activity-metric,.report-provider-metric,.report-team-metric{border:1px solid var(--border);border-radius:11px;background:var(--card);padding:11px 12px}.report-knowledge-metric span,.report-knowledge-metric strong,.report-knowledge-metric small,.report-activity-metric span,.report-activity-metric strong,.report-activity-metric small,.report-provider-metric span,.report-provider-metric strong,.report-provider-metric small,.report-team-metric span,.report-team-metric strong,.report-team-metric small{display:block}.report-knowledge-metric span,.report-activity-metric span,.report-provider-metric span,.report-team-metric span{font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-weight:850}.report-knowledge-metric strong,.report-activity-metric strong,.report-provider-metric strong,.report-team-metric strong{font-size:21px;color:var(--brand);margin-top:3px}.report-knowledge-metric small,.report-activity-metric small,.report-provider-metric small,.report-team-metric small{font-size:11px;color:var(--muted);margin-top:2px;line-height:1.35}.report-knowledge-usage,.report-activity-usage,.report-provider-usage,.report-team-usage{display:flex;gap:8px 16px;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}.report-knowledge-usage b,.report-activity-usage b,.report-provider-usage b,.report-team-usage b{color:var(--ink)}.report-knowledge-actions,.report-activity-actions,.report-provider-actions,.report-team-actions{flex:0 0 auto}.report-module-hero{display:flex;align-items:center;justify-content:space-between;gap:22px;border:1px solid color-mix(in srgb,var(--brand) 34%,var(--border) 66%);border-left:6px solid var(--brand);border-radius:16px;padding:20px 22px;margin-bottom:14px;background:linear-gradient(135deg,color-mix(in srgb,var(--brand) 7%,var(--card) 93%),var(--card));box-shadow:0 2px 8px rgba(16,24,40,.05)}.report-module-hero h1{margin:3px 0 5px;font-size:30px}.report-module-actions{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}.report-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}.report-summary-grid article{border:1px solid var(--border);border-radius:13px;background:var(--card);padding:13px 15px;min-height:92px}.report-summary-grid article span,.report-summary-grid article strong,.report-summary-grid article small{display:block}.report-summary-grid article span{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:850}.report-summary-grid article strong{font-size:23px;color:var(--brand);margin-top:5px}.report-summary-grid article small{font-size:10.5px;color:var(--muted);margin-top:3px}.report-summary-grid .report-summary-alert{border-color:color-mix(in srgb,var(--warning) 30%,var(--border) 70%)}.report-filter-shell{border:1px solid var(--border);border-radius:14px;background:var(--card);padding:14px 16px;margin-bottom:14px}.report-filter-title{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:10px}.report-filter-title strong{font-size:15px}.report-filterbar{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px;align-items:end}.report-filterbar label{margin:0}.report-filterbar .form-control{min-height:43px}.report-filterbar .mgmt-filter-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px}.report-table-card{overflow:hidden}.report-table-head{display:flex;justify-content:space-between;gap:18px;align-items:center;padding:15px 17px;border-bottom:1px solid var(--border)}.report-table-head h2{margin:2px 0 0;font-size:19px}.report-table-tools{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}.report-table-search{min-width:300px;min-height:40px!important}.report-page-size{min-height:40px!important;width:auto}.report-table-wrap{width:100%;overflow:visible}.report-table{width:100%;table-layout:auto}.report-table .ticket-cell a{font-weight:850;color:var(--brand);text-decoration:none}.report-status-cell .badge{display:inline-flex;margin-bottom:3px}.report-doc-ok{color:var(--success);font-weight:800}.report-doc-missing{color:var(--warning);font-weight:800}.report-row-actions{display:flex;gap:6px;justify-content:flex-end}.report-row-actions .btn{min-height:34px;padding:6px 9px;font-size:10.5px}.report-detail-row td{padding:0!important;background:color-mix(in srgb,var(--card) 96%,var(--bg) 4%)}.report-detail-panel{padding:14px 16px 16px;border-bottom:2px solid color-mix(in srgb,var(--brand) 24%,var(--border) 76%)}.report-detail-grid{display:grid;grid-template-columns:1.1fr .9fr 1fr 1fr;gap:12px}.report-detail-block{border:1px solid var(--border);border-radius:11px;background:var(--card);padding:12px}.report-detail-block>span{display:block;font-size:9.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--brand);font-weight:850;margin-bottom:7px}.report-detail-block h3{font-size:14px;margin:0 0 6px}.report-detail-block p{margin:0;color:var(--ink);font-size:11.5px;line-height:1.5}.report-detail-facts{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:9px}.report-detail-facts div{font-size:10.5px;color:var(--muted)}.report-detail-facts b{display:block;color:var(--ink);font-size:10px}.report-time-list{display:grid;grid-template-columns:1fr 1fr;gap:7px}.report-time-list div{border:1px solid var(--border);border-radius:9px;padding:8px}.report-time-list span,.report-time-list strong{display:block}.report-time-list span{font-size:9.5px;color:var(--muted)}.report-time-list strong{font-size:12px;margin-top:2px}.report-mini-timeline{display:grid;gap:7px;margin:0;padding:0;list-style:none}.report-mini-timeline li{padding-left:10px;border-left:2px solid color-mix(in srgb,var(--brand) 35%,var(--border) 65%)}.report-mini-timeline strong,.report-mini-timeline small{display:block}.report-mini-timeline strong{font-size:10.5px}.report-mini-timeline small{font-size:9.5px;color:var(--muted);margin-top:2px}.report-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px}.report-pagination-info{font-size:11px;color:var(--muted)}.report-pagination-actions{display:flex;gap:7px}.report-pagination-actions .btn{min-height:36px;padding:7px 11px}.report-no-local-results{padding:24px;text-align:center;color:var(--muted)}.report-pending-summary{display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid var(--border);border-radius:13px;padding:12px 15px;margin-bottom:14px;background:var(--card)}.report-pending-summary strong,.report-pending-summary span{display:block}.report-pending-chips{display:flex;gap:7px;flex-wrap:wrap}.report-pending-chips span{display:inline-flex;gap:5px;align-items:center;border:1px solid var(--border);border-radius:999px;padding:6px 9px;font-size:10.5px}
@media(max-width:1180px){.report-catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-knowledge-grid,.report-activity-grid,.report-provider-grid,.report-team-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-filterbar{grid-template-columns:repeat(2,minmax(0,1fr))}.report-detail-grid{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.report-catalog-grid{grid-template-columns:1fr}.report-knowledge-head,.report-activity-head,.report-provider-head,.report-team-head{display:block}.report-knowledge-actions,.report-activity-actions,.report-provider-actions,.report-team-actions{margin-top:10px}.report-knowledge-grid,.report-activity-grid,.report-provider-grid,.report-team-grid{grid-template-columns:1fr 1fr}.report-module-hero{display:block;padding:16px}.report-module-actions{justify-content:flex-start;margin-top:12px}.report-summary-grid{grid-template-columns:1fr 1fr}.report-filterbar{grid-template-columns:1fr}.report-table-head{align-items:flex-start;flex-direction:column}.report-table-tools{width:100%;justify-content:flex-start}.report-table-search{min-width:0;width:100%}.report-detail-grid{grid-template-columns:1fr}.report-pending-summary{display:block}.report-pending-chips{margin-top:9px}.report-detail-row{border:0!important;box-shadow:none!important;background:transparent!important}.report-detail-row td{border:0!important;padding:0!important}.report-detail-panel{padding:10px 0 14px}}
@media(max-width:430px){.report-knowledge-grid,.report-activity-grid,.report-provider-grid,.report-team-grid,.report-summary-grid{grid-template-columns:1fr}.report-module-actions,.report-filterbar .mgmt-filter-actions,.report-table-tools,.report-pagination{display:grid;grid-template-columns:1fr;width:100%}.report-module-actions .btn,.report-filterbar .mgmt-filter-actions .btn,.report-table-tools>*{width:100%}.report-pagination-actions{display:grid;grid-template-columns:1fr 1fr}.report-page-size{width:100%}.report-knowledge-actions .btn,.report-activity-actions .btn,.report-provider-actions .btn,.report-team-actions .btn{width:100%}}
</style>

<section class="report-module-hero">
  <div>
    <span class="mgmt-kicker">Centro de reportes</span>
    <h1>Informes de tickets</h1>
  </div>
  <div class="report-module-actions">
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion?<?= htmlspecialchars($q) ?>">← Dashboard</a>
    <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/gestion/informes/exportar?<?= htmlspecialchars($q) ?>" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a>
  </div>
</section>

<section class="report-catalog-grid" aria-label="Informes disponibles">
  <a class="report-catalog-card is-current" href="#informe-tickets">
    <span>Informe principal</span>
    <strong>Tickets y SLA</strong>
    <small>Volumen, tiempos, esperas, documentación y ciclo completo de los casos.</small>
  </a>
  <?php if($canAgenda): ?>
  <a class="report-catalog-card" href="#informe-actividades">
    <span>Operación programada</span>
    <strong>Agenda y actividades</strong>
    <small>Programadas, en curso, finalizadas, canceladas y atrasadas del período.</small>
  </a>
  <?php endif; ?>
  <?php if($canProviders): ?>
  <a class="report-catalog-card" href="#informe-proveedores">
    <span>Colaboración</span>
    <strong>Proveedores</strong>
    <small>Participación, respuesta, entregas, devoluciones y calidad registrada.</small>
  </a>
  <?php endif; ?>
  <?php if($canTeam): ?>
  <a class="report-catalog-card" href="#informe-equipo">
    <span>Equipo de soporte</span>
    <strong>Carga y desempeño</strong>
    <small>Volumen, resolución, tiempos y satisfacción del equipo en el período.</small>
  </a>
  <?php endif; ?>
  <?php if($canKnowledge): ?>
  <a class="report-catalog-card" href="#informe-conocimiento">
    <span>Reutilización</span>
    <strong>Conocimiento</strong>
    <small>Publicación, uso como referencia y actividad del período seleccionado.</small>
  </a>
  <?php endif; ?>
</section>

<?php if($canTeam && is_array($t)): ?>
<section class="report-team-panel" id="informe-equipo" aria-label="Resumen del equipo de soporte">
  <div class="report-team-head">
    <div>
      <span class="mgmt-kicker">Equipo de soporte</span>
      <h2>Carga y desempeño</h2>
      <p>Tickets creados entre <?= htmlspecialchars($filters['from']) ?> y <?= htmlspecialchars($filters['to']) ?> dentro de tu alcance y filtros actuales.</p>
    </div>
    <div class="report-team-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/equipo?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>">Abrir equipo de soporte</a></div>
  </div>
  <div class="report-team-grid">
    <article class="report-team-metric">
      <span>Integrantes</span>
      <strong><?= (int)($t['members']??0) ?></strong>
      <small>Equipo activo configurado</small>
    </article>
    <article class="report-team-metric">
      <span>Tickets del período</span>
      <strong><?= (int)($t['tickets_period']??0) ?></strong>
      <small>Asignados al equipo</small>
    </article>
    <article class="report-team-metric">
      <span>Resueltos</span>
      <strong><?= (int)($t['resolved_period']??0) ?></strong>
      <small>Con resolución registrada</small>
    </article>
    <article class="report-team-metric">
      <span>Primera respuesta</span>
      <strong><?= htmlspecialchars($fmtMinutes($t['avg_first_response_min']??null)) ?></strong>
      <small>Promedio del período</small>
    </article>
    <article class="report-team-metric">
      <span>NPS</span>
      <strong><?= ($t['nps_value']??null)!==null?htmlspecialchars((((int)$t['nps_value']>0?'+':'').(int)$t['nps_value'])):'—' ?></strong>
      <small><?= (int)($t['nps_responses']??0) ?> respuesta(s)</small>
    </article>
  </div>
  <div class="report-team-usage">
    <span><b><?= (int)($t['active_cases']??0) ?></b> activo(s) del filtro</span>
    <span><b><?= (int)($t['in_progress']??0) ?></b> en proceso</span>
    <span><b><?= (int)($t['pending_cases']??0) ?></b> en espera</span>
    <span><b><?= ($t['avg_resolution_hours']??null)!==null?htmlspecialchars(round((float)$t['avg_resolution_hours'],1).' h'):'—' ?></b> resolución promedio</span>
    <span><b><?= ($t['avg_rating']??null)!==null?htmlspecialchars(round((float)$t['avg_rating'],1).'/10'):'—' ?></b> calificación promedio</span>
  </div>
</section>
<?php endif; ?>

<?php if($canProviders && is_array($p)): ?>
<section class="report-provider-panel" id="informe-proveedores" aria-label="Resumen de proveedores">
  <div class="report-provider-head">
    <div>
      <span class="mgmt-kicker">Proveedores</span>
      <h2>Participación y calidad</h2>
      <p>Participaciones iniciadas entre <?= htmlspecialchars($filters['from']) ?> y <?= htmlspecialchars($filters['to']) ?> dentro de tu alcance.</p>
    </div>
    <div class="report-provider-actions">
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>">Abrir informe de proveedores</a>
    </div>
  </div>
  <div class="report-provider-grid">
    <article class="report-provider-metric">
      <span>Proveedores</span>
      <strong><?= (int)($p['providers']??0) ?></strong>
      <small>Con participación en el período</small>
    </article>
    <article class="report-provider-metric">
      <span>Participaciones</span>
      <strong><?= (int)($p['participations']??0) ?></strong>
      <small><?= (int)($p['active']??0) ?> activa(s)</small>
    </article>
    <article class="report-provider-metric">
      <span>Sin respuesta</span>
      <strong><?= (int)($p['no_response']??0) ?></strong>
      <small>Ciclos sin primera respuesta</small>
    </article>
    <article class="report-provider-metric">
      <span>Calidad promedio</span>
      <strong><?= ($p['average_quality']??null)!==null?htmlspecialchars(number_format((float)$p['average_quality'],2)).'/5':'—' ?></strong>
      <small><?= (int)($p['rated_cycles']??0) ?> ciclo(s) evaluado(s)</small>
    </article>
    <article class="report-provider-metric">
      <span>Devoluciones</span>
      <strong><?= (int)($p['returns']??0) ?></strong>
      <small>Después de una entrega</small>
    </article>
  </div>
  <div class="report-provider-usage">
    <span><b><?= (int)($p['deliveries']??0) ?></b> entrega(s) listas</span>
    <span><b><?= (int)($p['responses']??0) ?></b> respuesta(s)</span>
    <span><b><?= (int)($p['attachments']??0) ?></b> archivo(s)</span>
    <span><b><?= $fmtMinutes($p['avg_first_response_minutes']??null) ?></b> primera respuesta promedio</span>
  </div>
</section>
<?php endif; ?>

<?php if($canAgenda && is_array($a)): ?>
<section class="report-activity-panel" id="informe-actividades" aria-label="Resumen de actividades">
  <div class="report-activity-head">
    <div>
      <span class="mgmt-kicker">Agenda y actividades</span>
      <h2>Operación programada</h2>
      <p>Actividades que coinciden con el período <?= htmlspecialchars($filters['from']) ?> a <?= htmlspecialchars($filters['to']) ?> dentro de tu alcance.</p>
    </div>
    <div class="report-activity-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/agenda?from=<?= urlencode($filters['from']) ?>&to=<?= urlencode($filters['to']) ?>&status=all">Abrir agenda</a></div>
  </div>
  <div class="report-activity-grid">
    <article class="report-activity-metric">
      <span>Programadas</span>
      <strong><?= (int)($a['scheduled']??0) ?></strong>
      <small>Pendientes de ejecutar</small>
    </article>
    <article class="report-activity-metric">
      <span>En curso</span>
      <strong><?= (int)($a['in_progress']??0) ?></strong>
      <small>Trabajo activo</small>
    </article>
    <article class="report-activity-metric">
      <span>Finalizadas</span>
      <strong><?= (int)($a['completed']??0) ?></strong>
      <small>Completadas en el rango</small>
    </article>
    <article class="report-activity-metric">
      <span>Canceladas</span>
      <strong><?= (int)($a['cancelled']??0) ?></strong>
      <small>No ejecutadas</small>
    </article>
    <article class="report-activity-metric">
      <span>Atrasadas</span>
      <strong><?= (int)($a['overdue']??0) ?></strong>
      <small>Programadas fuera de tiempo</small>
    </article>
  </div>
  <div class="report-activity-usage">
    <span><b><?= (int)($a['total']??0) ?></b> actividad(es) en el período</span>
    <span><b><?= (int)($a['conflicts']??0) ?></b> con conflicto de horario</span>
    <?php if((int)($filters['park_id']??0)>0): ?><span>Filtro de parque aplicado</span><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if($canKnowledge && is_array($k)): ?>
<section class="report-knowledge-panel" id="informe-conocimiento" aria-label="Resumen de conocimiento">
  <div class="report-knowledge-head">
    <div>
      <span class="mgmt-kicker">Conocimiento</span>
      <h2>Uso y publicación</h2>
      <p>Estado actual del catálogo y actividad entre <?= htmlspecialchars($filters['from']) ?> y <?= htmlspecialchars($filters['to']) ?>.</p>
    </div>
    <div class="report-knowledge-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge">Abrir conocimiento</a></div>
  </div>
  <div class="report-knowledge-grid">
    <article class="report-knowledge-metric">
      <span>Artículos activos</span>
      <strong><?= (int)($k['active_articles']??0) ?></strong>
      <small><?= (int)($k['archived_articles']??0) ?> archivado(s)</small>
    </article>
    <article class="report-knowledge-metric">
      <span>Para soporte</span>
      <strong><?= (int)($k['internal_published']??0) ?></strong>
      <small>Publicados internamente</small>
    </article>
    <article class="report-knowledge-metric">
      <span>Para solicitantes</span>
      <strong><?= (int)($k['public_available']??0) ?></strong>
      <small>Disponibles en autoservicio</small>
    </article>
    <article class="report-knowledge-metric">
      <span>Trabajo editorial</span>
      <strong><?= (int)($k['drafts']??0)+(int)($k['in_review']??0) ?></strong>
      <small><?= (int)($k['drafts']??0) ?> borrador(es) · <?= (int)($k['in_review']??0) ?> en revisión</small>
    </article>
  </div>
  <div class="report-knowledge-usage">
    <span><b><?= (int)($k['suggested']??0) ?></b> sugerencias</span>
    <span><b><?= (int)($k['opened']??0) ?></b> aperturas</span>
    <span><b><?= (int)($k['used_reference']??0) ?></b> usos como referencia</span>
    <span><b><?= (int)($k['tickets_with_reference']??0) ?></b> ticket(s) con referencia</span>
  </div>
</section>
<?php endif; ?>

<section class="report-summary-grid" id="informe-tickets" aria-label="Resumen del período">
  <article><span>Tickets</span><strong><?= (int)($s['total']??0) ?></strong></article>
  <article><span>Documentados</span><strong><?= (int)($s['documented']??0) ?></strong><small><?= htmlspecialchars((string)($s['documented_pct']??0)) ?>%</small></article>
  <article class="report-summary-alert"><span>Sin documentar</span><strong><?= (int)($s['undocumented']??0) ?></strong></article>
  <article><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_first_response_minutes']??null)) ?></strong></article>
  <article><span>Hasta resolución</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_resolution_minutes']??null)) ?></strong></article>
  <article><span>Trabajo efectivo</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_work_minutes']??null)) ?></strong></article>
  <article><span>En espera</span><strong><?= htmlspecialchars($fmtMinutes($s['avg_pending_minutes']??null)) ?></strong></article>
  <article><span>Cambios de estado</span><strong><?= (int)($s['status_changes']??0) ?></strong></article>
</section>

<?php if(!empty($s['pending_reasons'])): ?>
<section class="report-pending-summary" aria-label="Motivos de espera actuales">
  <div><span class="mgmt-kicker">Casos en espera</span><strong>Motivos activos</strong></div>
  <div class="report-pending-chips"><?php foreach($s['pending_reasons'] as $code=>$count): ?><span><b><?= (int)$count ?></b><?= htmlspecialchars($pendingReasons[$code]??$code) ?></span><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section class="report-filter-shell">
  <div class="report-filter-title"><strong>Filtros</strong></div>
  <form class="report-filterbar" method="get" action="<?= APP_BASE_URL ?>/gestion/informes" data-processing-form>
    <label>Desde<input class="form-control" type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></label>
    <label>Hasta<input class="form-control" type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></label>
    <label>Parque<select class="form-control" name="park_id"><option value="0">Todos</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['park_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
    <label>Categoría<select class="form-control" name="category_id"><option value="0">Todas</option><?php foreach($categories as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['category_id']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
    <label>Responsable<select class="form-control" name="assigned_to"><option value="0">Todos</option><?php foreach($supportUsers as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$filters['assigned_to']===(int)$x['id']?'selected':'' ?>><?= htmlspecialchars($x['full_name']) ?></option><?php endforeach; ?></select></label>
    <label>Estado<select class="form-control" name="status"><option value="">Todos</option><?php foreach($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    <label>Prioridad<select class="form-control" name="priority"><option value="">Todas</option><?php foreach($priorityLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filters['priority']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    <div class="mgmt-filter-actions"><button class="btn btn-primary">Aplicar filtros</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/gestion/informes">Limpiar</a></div>
  </form>
</section>

<section class="report-table-card data-table-shell" data-report-table>
  <div class="report-table-head">
    <div>
      <span class="mgmt-kicker">Detalle</span>
      <h2><?= (int)($s['detail_total']??count($rows)) ?> tickets</h2>
      <?php if((int)($s['detail_total']??count($rows))>(int)($s['detail_visible']??count($rows))): ?>
        <small>La pantalla muestra los <?= (int)($s['detail_visible']??count($rows)) ?> más recientes; Excel incluye todo el filtro.</small>
      <?php endif; ?>
    </div>
    <?php if($rows): ?><div class="report-table-tools"><input class="form-control report-table-search" type="search" placeholder="Buscar dentro del informe…" data-report-search><select class="form-control report-page-size" data-report-page-size aria-label="Filas por página"><option value="10">10 filas</option><option value="25" selected>25 filas</option><option value="50">50 filas</option><option value="100">100 filas</option></select></div><?php endif; ?>
  </div>

  <?php if(!$rows): ?>
    <div class="empty-state"><strong>No hay tickets para este filtro.</strong></div>
  <?php else: ?>
    <div class="report-table-wrap data-table-wrap">
      <table class="report-table data-table">
        <thead><tr><th>Ticket</th><th>Solicitante</th><th>Ubicación / categoría</th><th>Estado</th><th>Responsable</th><th>1ª respuesta</th><th>Trabajo</th><th>Resolución</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach($rows as $i=>$r): $life=$r['lifecycle']??[];$searchText=strtolower(implode(' ',[(string)$r['ticket_number'],(string)$r['subject'],(string)$r['description'],(string)$r['requester_name'],(string)$r['requester_email'],(string)($r['park_name']??''),(string)($r['area_name']??''),(string)($r['category_name']??''),(string)($r['assigned_name']??''),(string)($r['solution_applied']??''),(string)($r['root_cause']??'')])); ?>
          <tr class="report-main-row" data-report-main data-report-index="<?= (int)$i ?>" data-report-searchtext="<?= htmlspecialchars($searchText) ?>">
            <td data-label="Ticket" class="ticket-cell"><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['ticket_number']) ?></a><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime($r['created_at']))) ?></small></td>
            <td data-label="Solicitante"><strong><?= htmlspecialchars($r['requester_name']) ?></strong><small><?= htmlspecialchars($r['requester_email']) ?></small></td>
            <td data-label="Ubicación / categoría"><strong><?= htmlspecialchars($r['park_name']??'Sin ubicación') ?><?= !empty($r['area_name'])?' · '.htmlspecialchars($r['area_name']):'' ?></strong><small><?= htmlspecialchars($r['category_name']??'Sin categoría') ?></small></td>
            <td data-label="Estado" class="report-status-cell"><span class="badge badge-primary"><?= htmlspecialchars($statusLabels[$r['status']]??$r['status']) ?></span><small><?= htmlspecialchars($priorityLabels[$r['priority']]??$r['priority']) ?></small></td>
            <td data-label="Responsable"><strong><?= htmlspecialchars($r['assigned_name']??'Sin asignar') ?></strong><?php if($r['status']==='PENDING'&&!empty($r['pending_reason_code'])): ?><small><?= htmlspecialchars($pendingReasons[$r['pending_reason_code']]??$r['pending_reason_code']) ?></small><?php endif; ?></td>
            <td data-label="1ª respuesta" class="data-table-secondary"><strong><?= htmlspecialchars($fmtMinutes($life['first_response_minutes']??null)) ?></strong></td>
            <td data-label="Trabajo"><strong><?= htmlspecialchars($fmtMinutes($life['work_minutes']??0)) ?></strong><small>Espera <?= htmlspecialchars($fmtMinutes($life['pending_minutes']??0)) ?></small></td>
            <td data-label="Resolución"><?php if(!empty($r['solution_applied'])): ?><span class="report-doc-ok">Documentada</span><small><?= htmlspecialchars($fmtMinutes($life['resolution_minutes']??null)) ?></small><?php else: ?><span class="report-doc-missing">Pendiente</span><small><?= htmlspecialchars($fmtMinutes($life['resolution_minutes']??null)) ?></small><?php endif; ?></td>
            <td data-label="Acciones" class="data-table-actions"><button class="btn btn-outline-secondary" type="button" data-report-toggle="<?= (int)$i ?>">Detalle</button></td>
          </tr>
          <tr class="report-detail-row" data-report-detail data-report-index="<?= (int)$i ?>" hidden><td data-label="" colspan="9"><div class="report-detail-panel"><div class="report-detail-grid">
            <section class="report-detail-block"><span>Qué ocurrió</span><h3><?= htmlspecialchars($r['subject']) ?></h3><p><?= nl2br(htmlspecialchars($r['description'])) ?></p><div class="report-detail-facts"><div><b>Teléfono</b><?= htmlspecialchars($r['requester_phone']?:'—') ?></div><div><b>Prioridad</b><?= htmlspecialchars($priorityLabels[$r['priority']]??$r['priority']) ?></div><div><b>Asignado</b><?= !empty($r['assigned_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['assigned_at']))):'—' ?></div><div><b>Cerrado</b><?= !empty($r['closed_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime($r['closed_at']))):'—' ?></div></div></section>
            <section class="report-detail-block"><span>Tiempos</span><div class="report-time-list"><div><span>Hasta asignación</span><strong><?= htmlspecialchars($fmtMinutes($life['assignment_minutes']??null)) ?></strong></div><div><span>En cola</span><strong><?= htmlspecialchars($fmtMinutes($life['queue_minutes']??0)) ?></strong></div><div><span>Primera respuesta</span><strong><?= htmlspecialchars($fmtMinutes($life['first_response_minutes']??null)) ?></strong></div><div><span>Trabajando</span><strong><?= htmlspecialchars($fmtMinutes($life['work_minutes']??0)) ?></strong></div><div><span>En espera</span><strong><?= htmlspecialchars($fmtMinutes($life['pending_minutes']??0)) ?></strong></div><div><span>Hasta resolución</span><strong><?= htmlspecialchars($fmtMinutes($life['resolution_minutes']??null)) ?></strong></div></div></section>
            <section class="report-detail-block"><span>Historial</span><?php if(!empty($life['transitions'])): ?><ol class="report-mini-timeline"><?php foreach($life['transitions'] as $t): ?><li><strong><?= htmlspecialchars($t['from_label']) ?> → <?= htmlspecialchars($t['to_label']) ?></strong><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['at']))) ?> · <?= htmlspecialchars($t['actor']) ?><?= !empty($t['pending_reason'])?' · '.htmlspecialchars($pendingReasons[$t['pending_reason']]??$t['pending_reason']):'' ?></small></li><?php endforeach; ?></ol><?php else: ?><p>Sin cambios de estado.</p><?php endif; ?></section>
            <section class="report-detail-block"><span>Resolución / aprendizaje</span><?php if(!empty($r['solution_applied'])): ?><h3><?= htmlspecialchars($resolutionLabels[$r['resolution_type']]??'Solución documentada') ?></h3><?php if(!empty($r['root_cause'])): ?><p><strong>Causa:</strong> <?= nl2br(htmlspecialchars($r['root_cause'])) ?></p><?php endif; ?><p><strong>Solución:</strong> <?= nl2br(htmlspecialchars($r['solution_applied'])) ?></p><?php if(!empty($r['preventive_action'])): ?><p style="margin-top:7px"><strong>Prevención:</strong> <?= nl2br(htmlspecialchars($r['preventive_action'])) ?></p><?php endif; ?><div class="report-row-actions" style="margin-top:10px;justify-content:flex-start"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['id'] ?>">Abrir caso</a></div><?php else: ?><p class="report-doc-missing">Falta documentar la resolución.</p><div class="report-row-actions" style="margin-top:10px;justify-content:flex-start"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['id'] ?>">Abrir caso</a></div><?php endif; ?></section>
          </div></div></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="report-no-local-results" data-report-empty hidden>No hay filas que coincidan.</div>
    <div class="report-pagination"><div class="report-pagination-info" data-report-page-info></div><div class="report-pagination-actions"><button class="btn btn-outline-secondary" type="button" data-report-prev>← Anterior</button><button class="btn btn-outline-secondary" type="button" data-report-next>Siguiente →</button></div></div>
  <?php endif; ?>
</section>

<div class="processing-overlay" data-processing-overlay hidden><div class="processing-box"><div class="processing-spinner"></div><h2>Procesando información</h2><div class="processing-line"><i></i></div></div></div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(()=>{
  document.querySelectorAll('[data-processing-form]').forEach(f=>f.addEventListener('submit',()=>{const x=document.querySelector('[data-processing-overlay]');if(x)x.hidden=false;}));
  const root=document.querySelector('[data-report-table]');if(!root)return;
  const mains=[...root.querySelectorAll('[data-report-main]')];const details=[...root.querySelectorAll('[data-report-detail]')];
  const search=root.querySelector('[data-report-search]');const size=root.querySelector('[data-report-page-size]');const info=root.querySelector('[data-report-page-info]');const prev=root.querySelector('[data-report-prev]');const next=root.querySelector('[data-report-next]');const empty=root.querySelector('[data-report-empty]');let page=1;
  const norm=v=>String(v||'').toLocaleLowerCase('es-GT').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim();
  function visibleRows(){const q=norm(search?.value||'');return mains.filter(row=>!q||norm(row.dataset.reportSearchtext||'').includes(q));}
  function closeDetails(){details.forEach(row=>row.hidden=true);root.querySelectorAll('[data-report-toggle]').forEach(btn=>btn.textContent='Detalle');}
  function render(){closeDetails();const rows=visibleRows();const per=Math.max(1,parseInt(size?.value||'25',10));const pages=Math.max(1,Math.ceil(rows.length/per));if(page>pages)page=pages;const start=(page-1)*per;const shown=new Set(rows.slice(start,start+per));mains.forEach(row=>row.hidden=!shown.has(row));details.forEach(row=>row.hidden=true);if(empty)empty.hidden=rows.length!==0;if(info)info.textContent=rows.length?`${start+1}-${Math.min(start+per,rows.length)} de ${rows.length} tickets`:'0 tickets';if(prev)prev.disabled=page<=1;if(next)next.disabled=page>=pages;}
  root.addEventListener('click',e=>{const btn=e.target.closest('[data-report-toggle]');if(!btn)return;const index=btn.dataset.reportToggle;const row=details.find(x=>x.dataset.reportIndex===index);if(!row)return;const open=row.hidden;closeDetails();row.hidden=!open;btn.textContent=open?'Ocultar':'Detalle';});
  search?.addEventListener('input',()=>{page=1;render();});size?.addEventListener('change',()=>{page=1;render();});prev?.addEventListener('click',()=>{if(page>1){page--;render();}});next?.addEventListener('click',()=>{page++;render();});render();
})();
</script>

<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>