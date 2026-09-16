<?php
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$pageTitle=$isExternal?'Casos':'Solicitudes';$pageSection=$pageTitle;$activeNav='mine';$helpContext='my_tickets';
$openCount=0;$waitingCount=0;$doneCount=0;$reviewCount=0;$totalCount=count($tickets);
foreach($tickets as $t){
  $st=(string)$t['status'];
  if($isExternal){
    if(!empty($t['external_revoked_at'])){$doneCount++;continue;}
    if($st==='PENDING')$waitingCount++;
    else $openCount++;
    continue;
  }
  if(in_array($st,['NEW','AVAILABLE','IN_PROGRESS','REOPENED'],true))$openCount++;
  elseif($st==='PENDING')$waitingCount++;
  elseif($st==='RESOLVED'){$doneCount++;$reviewCount++;}
  elseif($st==='CLOSED')$doneCount++;
}
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="tickets-page <?= $isExternal?'external-cases-page':'' ?>">
<div class="page-heading tickets-heading"><div><h1 class="page-title"><?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isExternal?'Consulta tus casos activos y el historial de participaciones finalizadas.':'Revisa el estado de tus casos y abre solo el que necesites continuar.' ?></p></div><?php if($isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/mis-tickets/exportar" data-no-loading="1">Descargar Excel</a></div><?php else: ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<?php if(!$isExternal&&$reviewCount>0): ?>
<section class="ticket-review-notice"><div><strong><?= $reviewCount ?> <?= $reviewCount===1?'solicitud necesita':'solicitudes necesitan' ?> tu confirmación</strong><span>Revisa la solución, califica la atención o devuelve el caso si aún falta algo.</span></div></section>
<?php endif; ?>

<?php if($isExternal): ?>
<section class="external-case-overview external-case-overview-compact">
  <div class="external-case-stats"><div><span>Activos</span><strong><?= $openCount ?></strong></div><div><span>En espera</span><strong><?= $waitingCount ?></strong></div><div><span>Finalizados</span><strong><?= $doneCount ?></strong></div><div><span>Total</span><strong><?= $totalCount ?></strong></div></div>
</section>
<?php endif; ?>

<?php if($tickets): ?><section class="ticket-list <?= $isExternal?'external-ticket-list':'' ?>" aria-label="<?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?>"><?php foreach($tickets as $t):
  $needsReview=!$isExternal&&(string)$t['status']==='RESOLVED';
  $isExternalHistory=$isExternal&&!empty($t['external_revoked_at']);
  $target=$needsReview?APP_BASE_URL.'/tickets/feedback?id='.(int)$t['id']:APP_BASE_URL.'/tickets/view?id='.(int)$t['id'];
  $openText=$isExternal?($isExternalHistory?'Participación finalizada':'Ver caso →'):($needsReview?'Revisar y cerrar →':'Ver detalle →');
  $subject=trim((string)($t['subject']??''));
  $description=trim((string)($t['description']??''));
  $normalize=static function(string $value):string{
    $value=mb_strtolower(trim((string)preg_replace('/\s+/u',' ',$value)),'UTF-8');
    return trim((string)preg_replace('/[^\p{L}\p{N}]+/u',' ',$value));
  };
  $showDescription=$description!==''&&$normalize($description)!==$normalize($subject);
?>
<article class="ticket-list-card <?= $isExternal?'external-ticket-card ':'' ?><?= $needsReview?'ticket-awaiting-confirmation ':'' ?><?= $isExternalHistory?'external-ticket-history':'' ?>">
<?php if(!$isExternalHistory): ?><a class="ticket-card-link" href="<?= $target ?>" aria-label="Abrir <?= htmlspecialchars($t['ticket_number']) ?>"></a><?php endif; ?>
<div class="ticket-list-top"><div><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($subject) ?></h2></div><span class="ticket-status-pill status-<?= $isExternalHistory?'closed':strtolower((string)$t['status']) ?>"><?= htmlspecialchars($isExternalHistory?'Participación finalizada':($statusLabels[$t['status']]??$t['status'])) ?></span></div>
<?php if($showDescription): ?><p class="ticket-list-problem"><?= htmlspecialchars(mb_strimwidth($description,0,220,'…')) ?></p><?php endif; ?>
<div class="ticket-list-meta"><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><?php if(!$isExternal): ?><span><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span><?php endif; ?><?php if($isExternal&&!empty($t['external_granted_at'])): ?><span>Asignado <?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['external_granted_at']))) ?></span><?php else: ?><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span><?php endif; ?><?php if($isExternalHistory&&!empty($t['external_revoked_at'])): ?><span>Finalizó <?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['external_revoked_at']))) ?></span><?php endif; ?></div>
<div class="ticket-list-bottom"><span><?= $isExternal?($isExternalHistory?'Historial de participación':(!empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Sin asignar')):'Seguimiento por equipo de soporte' ?></span><span class="ticket-open-text"><?= $openText ?></span></div>
</article><?php endforeach; ?></section><?php else: ?><section class="card"><div class="empty-state"><h2><?= $isExternal?'Aún no tienes casos compartidos':'Aún no tienes solicitudes' ?></h2><p><?= $isExternal?'Cuando Carrousel requiera tu participación en un caso, aparecerá aquí y permanecerá en tu historial al finalizar.':'Cuando necesites ayuda, usa Nueva solicitud en la parte superior.' ?></p></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>