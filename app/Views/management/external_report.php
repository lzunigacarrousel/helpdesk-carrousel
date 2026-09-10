<?php
$pageTitle='Historial de proveedores';$pageSection='Proveedores';$activeNav='external-report';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$rows=$rows??[];$summary=$summary??[];$filters=$filters??['q'=>'','provider'=>0,'state'=>'','from'=>'','to'=>''];$providers=$providers??[];
$fmtDuration=static function(int $minutes):string{if($minutes<60)return $minutes.' min';$hours=intdiv($minutes,60);$mins=$minutes%60;if($hours<24)return $hours.' h'.($mins?' '.$mins.' min':'');$days=intdiv($hours,24);$rem=$hours%24;return $days.' d'.($rem?' '.$rem.' h':'');};
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Disponible','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$exportQuery=http_build_query(array_filter(['q'=>$filters['q'],'provider'=>$filters['provider']?:null,'state'=>$filters['state'],'from'=>$filters['from'],'to'=>$filters['to']],static fn($v)=>$v!==null&&$v!==''));
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-report-page{max-width:1500px;margin:0 auto}.external-report-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}.external-report-summary article{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:15px 16px}.external-report-summary span{display:block;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.external-report-summary strong{display:block;margin-top:5px;font-size:24px;color:var(--brand-dark)}.external-history-list{display:grid;gap:10px}.external-history-row{background:var(--card);border:1px solid var(--border);border-radius:15px;padding:14px 16px;display:grid;grid-template-columns:minmax(220px,1.3fr) minmax(230px,1.4fr) repeat(4,minmax(100px,.65fr));gap:14px;align-items:center}.external-history-row span,.external-history-row small{display:block;color:var(--muted);font-size:11px}.external-history-row strong{display:block;margin-top:2px}.external-history-status{display:inline-flex!important;width:max-content;padding:4px 8px;border:1px solid var(--border);border-radius:999px;color:var(--text)!important;font-size:10px!important;font-weight:800}.external-history-status.active{background:color-mix(in srgb,#22c55e 12%,var(--card))}.external-report-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}.external-report-filters{display:grid;grid-template-columns:1.4fr 1fr .75fr .8fr .8fr auto;gap:10px;align-items:end;background:var(--card);border:1px solid var(--border);border-radius:15px;padding:14px;margin-bottom:16px}.external-report-filters label{font-size:11px;font-weight:800;color:var(--muted)}.external-report-filters .form-control{margin-top:5px;min-height:42px}.external-report-filter-actions{display:flex;gap:8px;white-space:nowrap}@media(max-width:1180px){.external-report-filters{grid-template-columns:1fr 1fr 1fr}.external-report-filter-actions{grid-column:1/-1}.external-report-summary{grid-template-columns:repeat(3,1fr)}.external-history-row{grid-template-columns:1fr 1fr 1fr}.external-history-row>div:nth-child(1),.external-history-row>div:nth-child(2){grid-column:span 3}}@media(max-width:700px){.external-report-filters{grid-template-columns:1fr}.external-report-filter-actions{grid-column:auto}.external-report-summary{grid-template-columns:1fr 1fr}.external-history-row{grid-template-columns:1fr 1fr}.external-history-row>div:nth-child(1),.external-history-row>div:nth-child(2){grid-column:1/-1}.external-report-head{align-items:stretch}.external-report-head .mgmt-head-actions{width:100%}.external-report-head .btn{flex:1}}
</style>
<div class="external-report-page">
  <div class="mgmt-head external-report-head">
    <div><span class="mgmt-kicker">Historial operativo</span><h1>Historial de proveedores</h1><p>Consulta quién participó, en qué caso, durante cuánto tiempo y qué actividad realizó.</p></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos">Administrar proveedores</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar<?= $exportQuery?'?'.htmlspecialchars($exportQuery):'' ?>" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a></div>
  </div>

  <form class="external-report-filters" method="get" action="<?= APP_BASE_URL ?>/admin/externos/informe">
    <label>Buscar<input class="form-control" type="search" name="q" value="<?= htmlspecialchars((string)$filters['q']) ?>" placeholder="Proveedor, contacto, correo o ticket"></label>
    <label>Proveedor<select class="form-control" name="provider"><option value="">Todos</option><?php foreach($providers as $id=>$name): ?><option value="<?= (int)$id ?>" <?= (int)$filters['provider']===(int)$id?'selected':'' ?>><?= htmlspecialchars($name) ?></option><?php endforeach; ?></select></label>
    <label>Estado<select class="form-control" name="state"><option value="">Todos</option><option value="active" <?= $filters['state']==='active'?'selected':'' ?>>Activas</option><option value="closed" <?= $filters['state']==='closed'?'selected':'' ?>>Finalizadas</option></select></label>
    <label>Desde<input class="form-control" type="date" name="from" value="<?= htmlspecialchars((string)$filters['from']) ?>"></label>
    <label>Hasta<input class="form-control" type="date" name="to" value="<?= htmlspecialchars((string)$filters['to']) ?>"></label>
    <div class="external-report-filter-actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe">Limpiar</a></div>
  </form>

  <section class="external-report-summary" aria-label="Resumen de proveedores">
    <article><span>Participaciones</span><strong><?= count($rows) ?></strong></article>
    <article><span>Activas</span><strong><?= (int)($summary['active']??0) ?></strong></article>
    <article><span>Finalizadas</span><strong><?= (int)($summary['closed']??0) ?></strong></article>
    <article><span>Proveedores</span><strong><?= (int)($summary['providers']??0) ?></strong></article>
    <article><span>Respuestas</span><strong><?= (int)($summary['responses']??0) ?></strong></article>
    <article><span>Archivos</span><strong><?= (int)($summary['attachments']??0) ?></strong></article>
  </section>

  <section class="external-history-list" aria-label="Historial de participaciones">
    <?php foreach($rows as $r): ?>
      <article class="external-history-row">
        <div><span>Proveedor</span><strong><?= htmlspecialchars($r['organization']) ?></strong><small><?= htmlspecialchars($r['contact']) ?> · <?= htmlspecialchars($r['email']) ?></small></div>
        <div><span>Ticket</span><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['ticket_id'] ?>"><strong><?= htmlspecialchars($r['ticket_number']) ?></strong></a><small><?= htmlspecialchars($r['subject']) ?></small></div>
        <div><span>Asignado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['granted_at']))) ?></strong><small><?= htmlspecialchars($r['granted_by']) ?></small></div>
        <div><span>Estado</span><strong class="external-history-status <?= empty($r['revoked_at'])?'active':'' ?>"><?= empty($r['revoked_at'])?'Activo':'Finalizado' ?></strong><?php if(!empty($r['revoked_at'])): ?><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['revoked_at']))) ?> · <?= htmlspecialchars($r['revoked_by']?:'Sistema') ?></small><?php endif; ?></div>
        <div><span>Participación</span><strong><?= htmlspecialchars($fmtDuration((int)$r['duration_minutes'])) ?></strong><small><?= (int)$r['responses'] ?> <?= (int)$r['responses']===1?'respuesta':'respuestas' ?> · <?= (int)$r['attachments'] ?> <?= (int)$r['attachments']===1?'archivo':'archivos' ?></small></div>
        <div><span>Permisos</span><strong><?= $r['can_comment']?'Responder':'Solo lectura' ?><?= $r['can_upload']?' · Adjuntar':'' ?></strong><small>Ticket: <?= htmlspecialchars($statusLabels[$r['ticket_status']]??$r['ticket_status']) ?></small></div>
      </article>
    <?php endforeach; ?>
    <?php if(!$rows): ?><div class="card"><div class="empty-state"><strong>No hay resultados con estos filtros.</strong><span>Ajusta los criterios o limpia los filtros para ver todo el historial.</span></div></div><?php endif; ?>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
