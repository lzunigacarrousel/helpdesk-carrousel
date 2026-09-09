<?php
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$pageTitle=$isExternal?'Mis casos':'Mis solicitudes';$pageSection=$pageTitle;$activeNav='mine';$helpContext='my_tickets';
$openCount=0;$waitingCount=0;$doneCount=0;
foreach($tickets as $t){
  $st=(string)$t['status'];
  if(in_array($st,['NEW','AVAILABLE','IN_PROGRESS','REOPENED'],true))$openCount++;
  elseif($st==='PENDING')$waitingCount++;
  elseif(in_array($st,['RESOLVED','CLOSED'],true))$doneCount++;
}
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="tickets-page <?= $isExternal?'external-cases-page':'' ?>">
<div class="page-heading tickets-heading"><div><div class="ticket-kicker"><?= $isExternal?'Seguimiento':'Seguimiento' ?></div><h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1><p class="page-subtitle"><?= $isExternal?'Consulta los casos asignados a tu cuenta y abre cada uno para revisar el detalle o enviar una actualización.':'Revisa primero qué reportaste y después consulta estado, responsable y seguimiento.' ?></p></div><?php if(!$isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<?php if($isExternal): ?>
<section class="external-case-overview">
  <div class="external-case-guide"><span class="ticket-kicker">Tu espacio</span><strong>Revisa el problema, continúa la conversación y adjunta evidencia cuando el caso lo permita.</strong><small>Esta vista concentra únicamente la información necesaria para colaborar en tus casos.</small></div>
  <div class="external-case-stats"><div><span>Activos</span><strong><?= $openCount ?></strong></div><div><span>En espera</span><strong><?= $waitingCount ?></strong></div><div><span>Finalizados</span><strong><?= $doneCount ?></strong></div></div>
</section>
<?php endif; ?>

<?php if($tickets): ?><section class="ticket-list <?= $isExternal?'external-ticket-list':'' ?>" aria-label="<?= htmlspecialchars($pageTitle) ?>"><?php foreach($tickets as $t): ?>
<article class="ticket-list-card <?= $isExternal?'external-ticket-card':'' ?>">
<a class="ticket-card-link" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>" aria-label="Abrir <?= htmlspecialchars($t['ticket_number']) ?>"></a>
<div class="ticket-list-top"><div><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($t['subject']) ?></h2></div><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></div>
<p class="ticket-list-problem"><?= htmlspecialchars(mb_strimwidth(trim((string)($t['description']??'')),0,220,'…')) ?></p>
<div class="ticket-list-meta"><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><?php if(!$isExternal): ?><span>Prioridad <?= htmlspecialchars(strtolower($priorityLabels[$t['priority']]??$t['priority'])) ?></span><?php endif; ?><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span></div>
<div class="ticket-list-bottom"><span><?= !empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Aún sin responsable' ?></span><span class="ticket-open-text"><?= $isExternal?'Abrir caso →':'Abrir seguimiento →' ?></span></div>
</article><?php endforeach; ?></section><?php else: ?><section class="card"><div class="empty-state"><h2><?= $isExternal?'No tienes casos asignados':'Aún no tienes solicitudes' ?></h2><p><?= $isExternal?'Cuando un caso requiera tu participación aparecerá aquí y recibirás una notificación.':'Cuando necesites ayuda, registra una nueva solicitud.' ?></p><?php if(!$isExternal): ?><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">Nueva solicitud</a><?php endif; ?></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>