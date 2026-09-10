<?php
$pageTitle='Reporte de proveedores';$pageSection='Proveedores';$activeNav='external-report';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$rows=$rows??[];$summary=$summary??[];
$fmtDuration=static function(int $minutes):string{if($minutes<60)return $minutes.' min';$hours=intdiv($minutes,60);$mins=$minutes%60;if($hours<24)return $hours.' h'.($mins?' '.$mins.' min':'');$days=intdiv($hours,24);$rem=$hours%24;return $days.' d'.($rem?' '.$rem.' h':'');};
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-report-page{max-width:1500px;margin:0 auto}.external-report-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px}.external-report-summary article{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:15px 16px}.external-report-summary span{display:block;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.external-report-summary strong{display:block;margin-top:5px;font-size:24px;color:var(--brand-dark)}.external-history-list{display:grid;gap:10px}.external-history-row{background:var(--card);border:1px solid var(--border);border-radius:15px;padding:14px 16px;display:grid;grid-template-columns:minmax(220px,1.3fr) minmax(230px,1.4fr) repeat(4,minmax(100px,.65fr));gap:14px;align-items:center}.external-history-row span,.external-history-row small{display:block;color:var(--muted);font-size:11px}.external-history-row strong{display:block;margin-top:2px}.external-history-status{display:inline-flex!important;width:max-content;padding:4px 8px;border:1px solid var(--border);border-radius:999px;color:var(--text)!important;font-size:10px!important;font-weight:800}.external-history-status.active{background:color-mix(in srgb,#22c55e 12%,var(--card))}.external-report-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:1100px){.external-report-summary{grid-template-columns:repeat(3,1fr)}.external-history-row{grid-template-columns:1fr 1fr 1fr}.external-history-row>div:nth-child(1),.external-history-row>div:nth-child(2){grid-column:span 3}}@media(max-width:700px){.external-report-summary{grid-template-columns:1fr 1fr}.external-history-row{grid-template-columns:1fr 1fr}.external-history-row>div:nth-child(1),.external-history-row>div:nth-child(2){grid-column:1/-1}.external-report-head{align-items:stretch}.external-report-head .mgmt-head-actions{width:100%}.external-report-head .btn{flex:1}}
</style>
<div class="external-report-page">
  <div class="mgmt-head external-report-head">
    <div><span class="mgmt-kicker">Historial operativo</span><h1>Proveedores</h1><p>Cada asignación y revocación queda como un ciclo independiente, incluso si el mismo proveedor vuelve a participar en el mismo ticket.</p></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos">Administrar proveedores</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar" data-action-message="Preparando archivo Excel…">Descargar Excel (.xlsx)</a></div>
  </div>

  <section class="external-report-summary" aria-label="Resumen de proveedores">
    <article><span>Participaciones</span><strong><?= count($rows) ?></strong></article>
    <article><span>Activas</span><strong><?= (int)($summary['active']??0) ?></strong></article>
    <article><span>Finalizadas</span><strong><?= (int)($summary['closed']??0) ?></strong></article>
    <article><span>Proveedores</span><strong><?= (int)($summary['providers']??0) ?></strong></article>
    <article><span>Respuestas</span><strong><?= (int)($summary['responses']??0) ?></strong></article>
  </section>

  <section class="external-history-list" aria-label="Historial de participaciones">
    <?php foreach($rows as $r): ?>
      <article class="external-history-row">
        <div><span>Proveedor</span><strong><?= htmlspecialchars($r['organization']) ?></strong><small><?= htmlspecialchars($r['contact']) ?> · <?= htmlspecialchars($r['email']) ?></small></div>
        <div><span>Ticket</span><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['ticket_id'] ?>"><strong><?= htmlspecialchars($r['ticket_number']) ?></strong></a><small><?= htmlspecialchars($r['subject']) ?></small></div>
        <div><span>Asignado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['granted_at']))) ?></strong><small><?= htmlspecialchars($r['granted_by']) ?></small></div>
        <div><span>Estado</span><strong class="external-history-status <?= empty($r['revoked_at'])?'active':'' ?>"><?= empty($r['revoked_at'])?'Activo':'Finalizado' ?></strong><?php if(!empty($r['revoked_at'])): ?><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['revoked_at']))) ?> · <?= htmlspecialchars($r['revoked_by']?:'Sistema') ?></small><?php endif; ?></div>
        <div><span>Participación</span><strong><?= htmlspecialchars($fmtDuration((int)$r['duration_minutes'])) ?></strong><small><?= (int)$r['responses'] ?> respuesta(s) · <?= (int)$r['attachments'] ?> archivo(s)</small></div>
        <div><span>Permisos</span><strong><?= $r['can_comment']?'Responder':'Solo lectura' ?><?= $r['can_upload']?' · Adjuntar':'' ?></strong><small>Ticket: <?= htmlspecialchars($r['ticket_status']) ?></small></div>
      </article>
    <?php endforeach; ?>
    <?php if(!$rows): ?><div class="card"><div class="empty-state"><strong>Aún no hay historial de proveedores.</strong><span>Cuando compartas un caso, su participación aparecerá aquí.</span></div></div><?php endif; ?>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
