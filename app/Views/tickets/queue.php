<?php use App\Core\Csrf; $priorityLabels=$priorityLabels??['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica']; $statusLabels=$statusLabels??[]; $myTickets=$myTickets??[]; ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><title>Casos de soporte | Helpdesk Carrousel</title><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css"></head>
<body><div class="brand-strip"></div><main class="content support-page">
<div class="page-heading"><div><div class="ticket-kicker">Equipo de soporte</div><h1 class="page-title">Centro de soporte</h1><p class="page-subtitle">Continúa tus casos activos o toma uno nuevo desde la misma pantalla.</p></div><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/dashboard">Inicio</a></div>
<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="support-section"><div class="support-section-heading"><div><h2>Mis casos activos</h2><p>Lo que ya estás atendiendo.</p></div><span class="support-count"><?= count($myTickets) ?></span></div>
<?php if($myTickets): ?><div class="support-case-list"><?php foreach($myTickets as $t): ?><article class="support-case-card support-case-mine">
<div class="support-case-main"><div class="support-case-title"><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($t['subject']) ?></h2></div><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></div>
<div class="support-case-meta"><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span>Prioridad <?= htmlspecialchars(strtolower($priorityLabels[$t['priority']]??$t['priority'])) ?></span></div>
<div class="support-case-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Continuar caso</a></div>
</article><?php endforeach; ?></div><?php else: ?><div class="support-inline-empty">No tienes casos activos en este momento.</div><?php endif; ?>
</section>

<section class="support-section"><div class="support-section-heading"><div><h2>Casos por atender</h2><p>Disponibles para cualquier técnico libre.</p></div><span class="support-count"><?= count($tickets) ?></span></div>
<?php if($tickets): ?><div class="support-case-list"><?php foreach($tickets as $t): ?><article class="support-case-card">
<div class="support-case-main"><div class="support-case-title"><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($t['subject']) ?></h2></div><span class="priority-chip priority-<?= strtolower((string)$t['priority']) ?>"><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span></div>
<div class="support-case-meta"><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span></div>
<div class="support-case-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Ver detalle</a><form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>"><button class="btn btn-primary" type="submit">Tomar y atender</button></form></div>
</article><?php endforeach; ?></div><?php else: ?><div class="support-inline-empty">No hay casos esperando atención.</div><?php endif; ?>
</section>
</main><script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script></body></html>