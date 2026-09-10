<?php
use App\Core\Csrf;
$pageTitle='Correo y notificaciones';$pageSection='Administración';$activeNav='mail';$helpContext='mail';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['SENT'=>'Enviado','FAILED'=>'Falló','PENDING'=>'Pendiente','SKIPPED'=>'Modo prueba'];
$statusClasses=['SENT'=>'status-resolved','FAILED'=>'status-cancelled','PENDING'=>'status-pending','SKIPPED'=>''];
?>
<div class="audit-page mail-admin-page">
  <div class="page-heading audit-heading" style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
    <div><div class="ticket-kicker">Entregas</div><h1 class="page-title">Correo y notificaciones</h1></div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/audit">← Auditoría</a>
  </div>

  <?php if($flash): ?><div class="alert alert-<?= htmlspecialchars((string)$flash['type']) ?>"><?= htmlspecialchars((string)$flash['message']) ?></div><?php endif; ?>

  <div class="audit-stats">
    <section class="card stat"><span class="stat-label">Canal</span><b style="font-size:22px"><?= $health['mode']==='smtp'?($health['ready']?'SMTP activo':'Revisar configuración'):'Modo prueba' ?></b><div class="stat-note"><?= $health['mode']==='smtp'?'Envío por correo':'Sin envío real' ?></div></section>
    <section class="card stat"><span class="stat-label">Enviados hoy</span><b><?= (int)$stats['sent_today'] ?></b><div class="stat-note">Confirmados por SMTP.</div></section>
    <section class="card stat"><span class="stat-label">Fallidos</span><b><?= (int)$stats['failed'] ?></b><div class="stat-note">Requieren revisión.</div></section>
    <section class="card stat"><span class="stat-label">Pendientes</span><b><?= (int)$stats['pending'] ?></b><div class="stat-note">Aún no finalizados.</div></section>
  </div>

  <section class="card" style="margin-bottom:18px"><div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr));gap:18px;align-items:start">
      <div>
        <div class="ticket-kicker">Estado general</div>
        <h2 style="margin:5px 0 8px;font-size:20px"><?= $health['ready']?'Configuración lista':'Hay algo que revisar' ?></h2>
        <p class="subtle" style="margin:0 0 12px">Remitente: <strong><?= htmlspecialchars((string)$health['from_name']) ?></strong> · Enlaces: <strong><?= htmlspecialchars($canonicalHost) ?></strong> · Avisos de cola: <strong><?= htmlspecialchars((string)$health['support_group']) ?></strong></p>
        <?php foreach($health['issues'] as $issue): ?><div class="alert alert-danger" style="margin:8px 0"><?= htmlspecialchars($issue) ?></div><?php endforeach; ?>
        <?php foreach($health['warnings'] as $warning): ?><div class="alert alert-info" style="margin:8px 0"><?= htmlspecialchars($warning) ?></div><?php endforeach; ?>
        <?php if(!$health['issues']&&!$health['warnings']): ?><div class="alert alert-success" style="margin:8px 0">Configuración básica lista. Envía una prueba para confirmar la entrega.</div><?php endif; ?>
      </div>
      <form method="post" action="<?= APP_BASE_URL ?>/admin/correo/probar" data-single-submit>
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <label class="form-label" for="mail-test-email">Enviar una prueba a</label>
        <input class="form-control" id="mail-test-email" type="email" name="email" required value="<?= htmlspecialchars((string)$user['email']) ?>">
        <button class="btn btn-primary" type="submit" style="width:100%;margin-top:12px">Enviar correo de prueba</button>
      </form>
    </div>
  </div></section>

  <section class="card audit-log-card data-table-shell">
    <div class="card-header audit-log-head"><div><strong>Entregas recientes</strong><span><?= count($deliveries) ?> registros · <?= (int)$stats['test_today'] ?> en modo prueba hoy</span></div></div>
    <div class="table-wrap data-table-wrap"><table class="table data-table mail-delivery-table"><thead><tr><th>Fecha</th><th>Evento</th><th>Destinatario</th><th>Caso</th><th>Estado</th><th>Intentos</th><th>Acción</th></tr></thead><tbody>
    <?php foreach($deliveries as $d): $status=(string)$d['status'];$canRetry=in_array($status,['FAILED','PENDING'],true)&&(string)$d['event_key']!=='OTP_REQUESTED'&&$health['mode']==='smtp'; ?>
      <tr>
        <td data-label="Fecha" class="data-table-secondary"><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$d['created_at']))) ?></td>
        <td data-label="Evento"><strong><?= htmlspecialchars($eventLabels[(string)$d['event_key']]??'Actualización') ?></strong><?php if(!empty($d['title'])): ?><small><?= htmlspecialchars((string)$d['title']) ?></small><?php endif; ?></td>
        <td data-label="Destinatario"><?= htmlspecialchars((string)$d['recipient_email']) ?></td>
        <td data-label="Caso"><?php if(!empty($d['ticket_number'])): ?><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$d['ticket_id'] ?>"><?= htmlspecialchars((string)$d['ticket_number']) ?></a><?php else: ?><span class="data-table-muted">—</span><?php endif; ?></td>
        <td data-label="Estado"><span class="ticket-status-pill <?= htmlspecialchars($statusClasses[$status]??'') ?>"><?= htmlspecialchars($statusLabels[$status]??$status) ?></span><?php if($status==='FAILED'&&!empty($d['last_error'])): ?><details class="data-table-details"><summary>Ver motivo</summary><div><?= htmlspecialchars((string)$d['last_error']) ?></div></details><?php endif; ?></td>
        <td data-label="Intentos" class="data-table-number data-table-secondary"><?= (int)$d['attempts'] ?></td>
        <td data-label="Acción" class="data-table-actions"><?php if($canRetry): ?><form method="post" action="<?= APP_BASE_URL ?>/admin/correo/reintentar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="delivery_id" value="<?= (int)$d['id'] ?>"><button class="btn btn-outline-secondary btn-sm" type="submit">Reintentar</button></form><?php elseif($status==='SENT'): ?><span class="data-table-muted"><?= $d['sent_at']?htmlspecialchars(date('H:i',strtotime((string)$d['sent_at']))):'Enviado' ?></span><?php else: ?><span class="data-table-muted">—</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$deliveries): ?><tr><td class="data-table-empty" data-label="" colspan="7">Todavía no hay correos registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>