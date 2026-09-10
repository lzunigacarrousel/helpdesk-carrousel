<?php
$pageTitle='Historial de proveedores';$pageSection='Proveedores';$activeNav='external-report';$helpContext='reports';
require APP_ROOT.'/app/Views/shared/app_start.php';
$rows=$rows??[];$summary=$summary??[];$filters=$filters??['q'=>'','provider'=>0,'state'=>'','from'=>'','to'=>''];$providers=$providers??[];
$fmtDuration=static function(int $minutes):string{if($minutes<60)return $minutes.' min';$hours=intdiv($minutes,60);$mins=$minutes%60;if($hours<24)return $hours.' h'.($mins?' '.$mins.' min':'');$days=intdiv($hours,24);$rem=$hours%24;return $days.' d'.($rem?' '.$rem.' h':'');};
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Disponible','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$exportQuery=http_build_query(array_filter(['q'=>$filters['q'],'provider'=>$filters['provider']?:null,'state'=>$filters['state'],'from'=>$filters['from'],'to'=>$filters['to']],static fn($v)=>$v!==null&&$v!==''));
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-report-page{width:100%;margin:0}.external-report-summary{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:14px}.external-report-summary article{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:13px 14px}.external-report-summary span{display:block;color:var(--muted);font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.external-report-summary strong{display:block;margin-top:4px;font-size:22px;color:var(--brand-dark)}.external-report-head .mgmt-head-actions{display:flex;gap:8px;flex-wrap:wrap}.external-report-filters{display:grid;grid-template-columns:1.4fr 1fr .75fr .8fr .8fr auto;gap:10px;align-items:end;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:12px;margin-bottom:14px}.external-report-filters label{font-size:11px;font-weight:800;color:var(--muted)}.external-report-filters .form-control{margin-top:4px;min-height:40px}.external-report-filter-actions{display:flex;gap:8px;white-space:nowrap}.external-history-status{display:inline-flex;width:max-content;padding:4px 8px;border:1px solid var(--border);border-radius:999px;font-size:10px;font-weight:800}.external-history-status.active{background:color-mix(in srgb,#22c55e 12%,var(--card))}@media(max-width:1180px){.external-report-filters{grid-template-columns:1fr 1fr 1fr}.external-report-filter-actions{grid-column:1/-1}.external-report-summary{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.external-report-filters{grid-template-columns:1fr}.external-report-filter-actions{grid-column:auto}.external-report-summary{grid-template-columns:1fr 1fr}.external-report-head{align-items:stretch}.external-report-head .mgmt-head-actions{width:100%}.external-report-head .btn{flex:1}}
</style>
<div class="external-report-page">
  <div class="mgmt-head external-report-head">
    <div><span class="mgmt-kicker">Historial operativo</span><h1>Historial de proveedores</h1></div>
    <div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos">Administrar proveedores</a><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar<?= $exportQuery?'?'.htmlspecialchars($exportQuery):'' ?>" data-no-loading="1">Descargar Excel (.xlsx)</a></div>
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

  <section class="data-table-shell" aria-label="Historial de participaciones">
    <div class="data-table-wrap"><table class="data-table">
      <thead><tr><th>Proveedor</th><th>Ticket</th><th>Asignado</th><th>Estado</th><th>Participación</th><th>Actividad</th><th>Permisos</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td data-label="Proveedor"><strong><?= htmlspecialchars($r['organization']) ?></strong><small><?= htmlspecialchars($r['contact']) ?> · <?= htmlspecialchars($r['email']) ?></small></td>
          <td data-label="Ticket"><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$r['ticket_id'] ?>"><strong><?= htmlspecialchars($r['ticket_number']) ?></strong></a><small><?= htmlspecialchars($r['subject']) ?></small></td>
          <td data-label="Asignado" class="data-table-nowrap"><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['granted_at']))) ?></strong><small><?= htmlspecialchars($r['granted_by']) ?></small></td>
          <td data-label="Estado"><span class="external-history-status <?= empty($r['revoked_at'])?'active':'' ?>"><?= empty($r['revoked_at'])?'Activo':'Finalizado' ?></span><?php if(!empty($r['revoked_at'])): ?><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$r['revoked_at']))) ?> · <?= htmlspecialchars($r['revoked_by']?:'Sistema') ?></small><?php endif; ?></td>
          <td data-label="Participación"><strong><?= htmlspecialchars($fmtDuration((int)$r['duration_minutes'])) ?></strong></td>
          <td data-label="Actividad"><?= (int)$r['responses'] ?> <?= (int)$r['responses']===1?'respuesta':'respuestas' ?> · <?= (int)$r['attachments'] ?> <?= (int)$r['attachments']===1?'archivo':'archivos' ?></td>
          <td data-label="Permisos"><?= $r['can_comment']?'Responder':'Solo lectura' ?><?= $r['can_upload']?' · Adjuntar':'' ?><small>Ticket: <?= htmlspecialchars($statusLabels[$r['ticket_status']]??$r['ticket_status']) ?></small></td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?><tr><td class="data-table-empty" data-label="" colspan="7">No hay resultados con estos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>