<?php
$pageTitle='Informe de proveedores';$pageSection='Proveedores';$activeNav='external-report';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$rows=$rows??[];
$summary=$summary??[];
$filters=$filters??['q'=>'','provider'=>0,'state'=>'','activity'=>'','from'=>'','to'=>''];
$providers=$providers??[];
$activityOptions=$activityOptions??[];
$fmtDuration=static function(?int $minutes):string{
    if($minutes===null)return '—';
    if($minutes<60)return $minutes.' min';
    $hours=intdiv($minutes,60);$mins=$minutes%60;
    if($hours<24)return $hours.' h'.($mins?' '.$mins.' min':'');
    $days=intdiv($hours,24);$rem=$hours%24;
    return $days.' d'.($rem?' '.$rem.' h':'');
};
$fmtDate=static function(?string $value):string{
    $ts=$value?strtotime($value):false;
    return $ts?date('d/m/Y H:i',$ts):'—';
};
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Disponible','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$exportQuery=http_build_query(array_filter([
    'q'=>$filters['q'],
    'provider'=>$filters['provider']?:null,
    'state'=>$filters['state'],
    'activity'=>$filters['activity'],
    'from'=>$filters['from'],
    'to'=>$filters['to'],
],static fn($v)=>$v!==null&&$v!==''));
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-report-page{width:100%;margin:0}.external-report-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}.external-report-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:14px}.external-report-summary article{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:13px 14px;min-width:0}.external-report-summary span{display:block;color:var(--muted);font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.external-report-summary strong{display:block;margin-top:4px;font-size:22px;color:var(--brand-dark);overflow-wrap:anywhere}.external-report-filters{display:grid;grid-template-columns:1.35fr 1fr .8fr 1fr .8fr .8fr auto;gap:10px;align-items:end;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:12px;margin-bottom:14px}.external-report-filters label{font-size:11px;font-weight:800;color:var(--muted)}.external-report-filters .form-control{margin-top:4px;min-height:40px}.external-report-filter-actions{display:flex;gap:8px;white-space:nowrap}.external-cycle-status,.external-activity-status{display:inline-flex;width:max-content;max-width:100%;padding:4px 8px;border:1px solid var(--border);border-radius:999px;font-size:10px;font-weight:800;line-height:1.25}.external-cycle-status.active{background:color-mix(in srgb,#22c55e 12%,var(--card))}.external-activity-status.ready{background:color-mix(in srgb,var(--brand) 10%,var(--card));color:var(--brand-dark)}.external-metric-stack{display:grid;gap:4px}.external-metric-stack small{display:block;color:var(--muted);line-height:1.35}.external-work-line{font-size:12px;font-weight:750}.external-result-line{display:flex;gap:6px;flex-wrap:wrap;align-items:center}.external-report-page .data-table td{vertical-align:top}.external-report-page .data-table strong{overflow-wrap:anywhere}@media(max-width:1280px){.external-report-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.external-report-filter-actions{grid-column:1/-1}.external-report-summary{grid-template-columns:repeat(3,1fr)}}@media(max-width:900px){.external-report-summary{grid-template-columns:repeat(2,1fr)}}@media(max-width:700px){.external-report-filters{grid-template-columns:1fr}.external-report-filter-actions{grid-column:auto}.external-report-summary{grid-template-columns:1fr 1fr}.external-report-head{align-items:stretch}.external-report-head .mgmt-head-actions{width:100%}.external-report-head .btn{flex:1}.external-report-page .data-table td{min-height:auto}}@media(max-width:430px){.external-report-summary{grid-template-columns:1fr}}
</style>
<div class="external-report-page">
  <div class="mgmt-head external-report-head">
    <div><span class="mgmt-kicker">Seguimiento operativo</span><h1>Informe de proveedores</h1><p>Participación, tiempos de respuesta, actividad técnica y devoluciones por ciclo de acceso.</p></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos">Administrar proveedores</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar<?= $exportQuery?'?'.htmlspecialchars($exportQuery):'' ?>" data-no-loading="1">Descargar Excel (.xlsx)</a></div>
  </div>

  <form class="external-report-filters" method="get" action="<?= APP_BASE_URL ?>/admin/externos/informe">
    <label>Buscar<input class="form-control" type="search" name="q" value="<?= htmlspecialchars((string)$filters['q']) ?>" placeholder="Proveedor, contacto, correo, ticket o asunto"></label>
    <label>Proveedor<select class="form-control" name="provider"><option value="">Todos</option><?php foreach($providers as $id=>$name): ?><option value="<?= (int)$id ?>" <?= (int)$filters['provider']===(int)$id?'selected':'' ?>><?= htmlspecialchars($name) ?></option><?php endforeach; ?></select></label>
    <label>Estado<select class="form-control" name="state"><option value="">Todos</option><option value="active" <?= $filters['state']==='active'?'selected':'' ?>>Activas</option><option value="closed" <?= $filters['state']==='closed'?'selected':'' ?>>Finalizadas</option></select></label>
    <label>Actividad actual<select class="form-control" name="activity"><option value="">Todas</option><?php foreach($activityOptions as $value=>$label): ?><option value="<?= htmlspecialchars((string)$value) ?>" <?= $filters['activity']===$value?'selected':'' ?>><?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select></label>
    <label>Desde<input class="form-control" type="date" name="from" value="<?= htmlspecialchars((string)$filters['from']) ?>"></label>
    <label>Hasta<input class="form-control" type="date" name="to" value="<?= htmlspecialchars((string)$filters['to']) ?>"></label>
    <div class="external-report-filter-actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe">Limpiar</a></div>
  </form>

  <section class="external-report-summary" aria-label="Resumen de proveedores">
    <article><span>Participaciones</span><strong><?= (int)($summary['participations']??count($rows)) ?></strong></article>
    <article><span>Activas</span><strong><?= (int)($summary['active']??0) ?></strong></article>
    <article><span>Sin respuesta</span><strong><?= (int)($summary['no_response']??0) ?></strong></article>
    <article><span>T. primera respuesta</span><strong><?= htmlspecialchars($fmtDuration(isset($summary['avg_first_response_minutes'])&&$summary['avg_first_response_minutes']!==null?(int)$summary['avg_first_response_minutes']:null)) ?></strong></article>
    <article><span>Devoluciones</span><strong><?= (int)($summary['returns']??0) ?></strong></article>
  </section>

  <section class="data-table-shell" aria-label="Participación operativa de proveedores">
    <div class="data-table-wrap"><table class="data-table">
      <thead><tr><th>Proveedor / Ticket</th><th>Asignación</th><th>Primera respuesta</th><th>Participación</th><th>Actividad actual</th><th>Trabajo</th><th>Resultado</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <?php $isActive=empty($r['revoked_at']);$firstResponse=$r['first_response_at']??null;$activityLabel=(string)($r['activity_label']??'Sin actualización');$workStatus=(string)($r['work_status']??''); ?>
        <tr>
          <td data-label="Proveedor / Ticket">
            <div class="external-metric-stack"><strong><?= htmlspecialchars((string)$r['organization']) ?></strong><small><?= htmlspecialchars((string)$r['contact']) ?> · <?= htmlspecialchars((string)$r['email']) ?></small><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['ticket_id'] ?>"><strong><?= htmlspecialchars((string)$r['ticket_number']) ?></strong></a><small><?= htmlspecialchars((string)$r['subject']) ?></small></div>
          </td>
          <td data-label="Asignación">
            <div class="external-metric-stack"><strong><?= htmlspecialchars($fmtDate((string)$r['granted_at'])) ?></strong><small>Asignado por <?= htmlspecialchars((string)$r['granted_by']) ?></small><?php if(!$isActive): ?><small>Finalizó <?= htmlspecialchars($fmtDate((string)$r['revoked_at'])) ?> · <?= htmlspecialchars((string)($r['revoked_by']?:'Sistema')) ?></small><?php endif; ?></div>
          </td>
          <td data-label="Primera respuesta">
            <?php if(!$firstResponse): ?>
              <div class="external-metric-stack"><strong>Sin respuesta</strong><small>Sin actividad registrada en este ciclo</small></div>
            <?php else: ?>
              <div class="external-metric-stack"><strong><?= htmlspecialchars($fmtDate((string)$firstResponse)) ?></strong><small><?= htmlspecialchars($fmtDuration(isset($r['first_response_minutes'])?(int)$r['first_response_minutes']:null)) ?> · <?= htmlspecialchars((string)($r['first_response_origin']??'')) ?></small></div>
            <?php endif; ?>
          </td>
          <td data-label="Participación">
            <div class="external-metric-stack"><strong><?= htmlspecialchars($fmtDuration((int)($r['duration_minutes']??0))) ?></strong><span class="external-cycle-status <?= $isActive?'active':'' ?>"><?= $isActive?'Activo':'Finalizado' ?></span></div>
          </td>
          <td data-label="Actividad actual">
            <div class="external-metric-stack"><span class="external-activity-status <?= $workStatus==='READY_FOR_REVIEW'?'ready':'' ?>"><?= htmlspecialchars($activityLabel) ?></span><small><?= !empty($r['last_activity_at'])?'Última actualización '.$fmtDate((string)$r['last_activity_at']):'Sin informe técnico' ?></small></div>
          </td>
          <td data-label="Trabajo">
            <div class="external-metric-stack"><strong><?= htmlspecialchars($fmtDuration((int)($r['declared_minutes']??0))) ?> declarados</strong><span class="external-work-line"><?= (int)($r['reports']??0) ?> informes · <?= (int)($r['responses']??0) ?> respuestas · <?= (int)($r['attachments']??0) ?> archivos</span></div>
          </td>
          <td data-label="Resultado">
            <div class="external-metric-stack"><div class="external-result-line"><strong><?= (int)($r['deliveries']??0) ?> entregas</strong><strong>· <?= (int)($r['returns']??0) ?> devoluciones</strong></div><small>Ticket: <?= htmlspecialchars($statusLabels[$r['ticket_status']]??(string)$r['ticket_status']) ?></small><span class="external-cycle-status <?= $isActive?'active':'' ?>"><?= $isActive?'Ciclo activo':'Ciclo finalizado' ?></span></div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?><tr><td class="data-table-empty" data-label="" colspan="7">No hay resultados con estos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>