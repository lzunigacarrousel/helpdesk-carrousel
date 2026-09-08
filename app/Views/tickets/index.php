<?php
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
$priorityLabels=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$pageTitle=$isExternal?'Mis casos':'Mis solicitudes';$pageSection=$pageTitle;$activeNav='mine';$helpContext='my_tickets';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="tickets-page <?= $isExternal?'external-cases-page':'' ?>">
<div class="page-heading tickets-heading"><div><div class="ticket-kicker"><?= $isExternal?'Colaboración externa':'Seguimiento' ?></div><h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1><p class="page-subtitle"><?= $isExternal?'Aquí verás únicamente los casos que Carrousel comparta contigo. Abre un caso para revisar el problema, responder y adjuntar evidencias cuando tengas permiso.':'Revisa el estado de los casos asociados a '.htmlspecialchars($user['email']).'.' ?></p></div><?php if(!$isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<?php if($tickets): ?><section class="ticket-list" aria-label="<?= htmlspecialchars($pageTitle) ?>"><?php foreach($tickets as $t): ?><article class="ticket-list-card">
<a class="ticket-card-link" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>" aria-label="Abrir <?= htmlspecialchars($t['ticket_number']) ?>"></a>
<div class="ticket-list-top"><div><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($t['subject']) ?></h2></div><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></div>
<div class="ticket-list-meta"><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span>Prioridad <?= htmlspecialchars(strtolower($priorityLabels[$t['priority']]??$t['priority'])) ?></span></div>
<div class="ticket-list-bottom"><span><?= !empty($t['assigned_name'])?'Responsable Carrousel: '.htmlspecialchars($t['assigned_name']):'Aún sin responsable interno' ?></span><span class="ticket-open-text"><?= $isExternal?'Abrir y colaborar →':'Abrir seguimiento →' ?></span></div>
</article><?php endforeach; ?></section><?php else: ?><section class="card"><div class="empty-state"><h2><?= $isExternal?'No tienes casos compartidos':'Aún no tienes solicitudes' ?></h2><p><?= $isExternal?'Cuando Carrousel necesite tu apoyo en un caso, aparecerá aquí automáticamente.':'Cuando necesites ayuda, registra una nueva solicitud.' ?></p><?php if(!$isExternal): ?><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">Nueva solicitud</a><?php endif; ?></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>