<?php
use App\Core\Csrf;
$statusLabels=$statusLabels??[];
$priorityLabels=$priorityLabels??[];
$status=(string)$ticket['status'];
$eventLabels=[
  'CREATED'=>'Solicitud creada','CLAIMED'=>'Caso tomado','REASSIGNED'=>'Responsable cambiado',
  'RELEASED'=>'Devuelto a disponibles','STATUS_CHANGED'=>'Estado actualizado','COMMENTED'=>'Nueva respuesta',
  'RESOLVED'=>'Caso resuelto','CLOSED'=>'Caso cerrado','REOPENED'=>'Caso reabierto'
];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><title><?= htmlspecialchars($ticket['ticket_number']) ?> | Helpdesk Carrousel</title><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css"></head>
<body><div class="brand-strip"></div><main class="content ticket-workspace">
<header class="ticket-topline">
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?><?= $isSupport?'/tickets/queue':'/mis-tickets' ?>">← <?= $isSupport?'Casos de soporte':'Mis solicitudes' ?></a>
  <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/dashboard">Inicio</a>
</header>
<section class="ticket-heading">
  <div><div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div><h1><?= htmlspecialchars($ticket['subject']) ?></h1><p><?= htmlspecialchars($ticket['category_name']??'Solicitud de soporte') ?><?= !empty($ticket['park_name'])?' · '.htmlspecialchars($ticket['park_name']):'' ?></p></div>
  <span class="ticket-status-pill status-<?= strtolower($status) ?>"><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></span>
</section>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<div class="ticket-summary-grid">
  <section class="ticket-summary-item"><span>Estado</span><strong><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></strong></section>
  <section class="ticket-summary-item"><span>Prioridad</span><strong><?= htmlspecialchars($priorityLabels[$ticket['priority']]??$ticket['priority']) ?></strong></section>
  <section class="ticket-summary-item"><span>Responsable</span><strong><?= htmlspecialchars($ticket['assigned_name']??'Aún sin asignar') ?></strong></section>
</div>

<?php if($isSupport): ?>
<section class="card ticket-actions-card"><div class="card-body">
  <div class="ticket-actions-head"><div><h2>Atender este caso</h2><p>Las acciones principales están aquí para evitar pasos innecesarios.</p></div></div>
  <div class="ticket-action-bar">
    <?php if($canClaim): ?>
      <form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-primary ticket-primary-action" type="submit">Tomar y atender</button></form>
    <?php endif; ?>

    <?php if($canChangeStatus): ?>
      <?php if(in_array($status,['PENDING','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="IN_PROGRESS"><button class="btn btn-primary" type="submit">Continuar atención</button></form><?php endif; ?>
      <?php if(in_array($status,['IN_PROGRESS','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="PENDING"><button class="btn btn-outline-secondary" type="submit">Poner en espera</button></form><?php endif; ?>
      <?php if(in_array($status,['IN_PROGRESS','PENDING','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="RESOLVED"><button class="btn btn-primary" type="submit">Marcar como resuelto</button></form><?php endif; ?>
      <?php if($status==='RESOLVED'): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="CLOSED"><button class="btn btn-primary" type="submit">Cerrar caso</button></form><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="REOPENED"><button class="btn btn-outline-secondary" type="submit">Reabrir</button></form><?php endif; ?>
    <?php endif; ?>

    <?php if($canRelease): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/release" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-outline-secondary" type="submit">Devolver a disponibles</button></form><?php endif; ?>
  </div>

  <?php if($canReassign && !empty($supportUsers)): ?><details class="ticket-more-actions"><summary>Asignar a otra persona</summary><form class="ticket-assign-form" method="post" action="<?= APP_BASE_URL ?>/tickets/assign" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><select class="form-control" name="assigned_to" required><option value="">Selecciona responsable</option><?php foreach($supportUsers as $su): ?><option value="<?= (int)$su['id'] ?>" <?= (int)($ticket['assigned_to']??0)===(int)$su['id']?'selected':'' ?>><?= htmlspecialchars($su['full_name']) ?> · <?= htmlspecialchars($su['role_name']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Asignar</button></form></details><?php endif; ?>
</div></section>
<?php else: ?>
<section class="card requester-action-card"><div class="card-body"><div><h2>Seguimiento de tu solicitud</h2><p>Aquí puedes revisar el estado y los movimientos del caso.</p></div><div class="requester-action-buttons"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/mis-tickets">Ver mis solicitudes</a><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/crear-ticket">Reportar otro problema</a></div></div></section>
<?php endif; ?>

<div class="ticket-content-grid">
<section class="card"><div class="card-body"><h2>Información del caso</h2><div class="ticket-info-list">
  <div><span>Solicitante</span><strong><?= htmlspecialchars($ticket['requester_name']) ?></strong><small><?= htmlspecialchars($ticket['requester_email']) ?><?= !empty($ticket['requester_phone'])?' · '.htmlspecialchars($ticket['requester_phone']):'' ?></small></div>
  <div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div>
  <div><span>Tipo de solicitud</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div>
</div><div class="ticket-description"><span>Descripción</span><p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p></div></div></section>

<section class="card"><div class="card-body"><h2>Actividad</h2><div class="ticket-timeline">
<?php foreach($events as $e): ?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?= htmlspecialchars($eventLabels[$e['event_type']]??ucfirst(strtolower(str_replace('_',' ',$e['event_type'])))) ?></strong><p><?= htmlspecialchars(date('d/m/Y H:i',strtotime($e['created_at']))) ?><?= !empty($e['actor_name'])?' · '.htmlspecialchars($e['actor_name']):'' ?></p></div></div><?php endforeach; ?>
<?php if(!$events): ?><div class="empty-state">Aún no hay movimientos registrados.</div><?php endif; ?>
</div></div></section>
</div>
</main><script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script></body></html>