<?php
use App\Core\Csrf;
$priorityLabels=$priorityLabels??['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$statusLabels=$statusLabels??[];$myTickets=$myTickets??[];
$pageTitle='Centro de soporte';$pageSection='Centro de soporte';$activeNav='support';$helpContext='support_center';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="support-page support-workboard">
<div class="support-hero">
    <div>
        <div class="ticket-kicker">Equipo de soporte</div>
        <h1 class="page-title">Centro de soporte</h1>
        <p class="page-subtitle">Aquí ves de inmediato qué estás atendiendo y qué casos siguen disponibles.</p>
    </div>
    <div class="support-hero-stats">
        <div><strong><?= count($myTickets) ?></strong><span>Mis activos</span></div>
        <div><strong><?= count($tickets) ?></strong><span>Por atender</span></div>
    </div>
</div>

<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<div class="support-board">
<section class="support-lane support-lane-mine">
    <div class="support-lane-head">
        <div>
            <span class="support-lane-kicker">En curso</span>
            <h2>Mis casos activos</h2>
            <p>Continúa primero los casos que ya están bajo tu responsabilidad.</p>
        </div>
        <span class="support-count"><?= count($myTickets) ?></span>
    </div>

    <div class="support-lane-body">
    <?php if($myTickets): ?>
        <?php foreach($myTickets as $t): ?>
        <article class="support-ticket-row support-ticket-active">
            <div class="support-ticket-content">
                <div class="support-ticket-topline">
                    <span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span>
                    <span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span>
                </div>
                <h3><?= htmlspecialchars($t['subject']) ?></h3>
                <div class="support-ticket-meta">
                    <span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span>
                    <span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span>
                    <span>Prioridad <?= htmlspecialchars(strtolower($priorityLabels[$t['priority']]??$t['priority'])) ?></span>
                </div>
            </div>
            <div class="support-ticket-action">
                <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Continuar</a>
            </div>
        </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="support-lane-empty"><strong>Sin casos activos</strong><span>Cuando tomes un caso aparecerá aquí.</span></div>
    <?php endif; ?>
    </div>
</section>

<section class="support-lane support-lane-pending">
    <div class="support-lane-head">
        <div>
            <span class="support-lane-kicker">Disponibles</span>
            <h2>Casos por atender</h2>
            <p>Revisa la prioridad y toma únicamente el caso que puedas comenzar.</p>
        </div>
        <span class="support-count"><?= count($tickets) ?></span>
    </div>

    <div class="support-lane-body">
    <?php if($tickets): ?>
        <?php foreach($tickets as $t): ?>
        <article class="support-ticket-row">
            <div class="support-ticket-content">
                <div class="support-ticket-topline">
                    <span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span>
                    <span class="priority-chip priority-<?= strtolower((string)$t['priority']) ?>"><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span>
                </div>
                <h3><?= htmlspecialchars($t['subject']) ?></h3>
                <div class="support-ticket-meta">
                    <span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span>
                    <span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span>
                    <span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span>
                </div>
            </div>
            <div class="support-ticket-action support-ticket-action-pending">
                <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Revisar</a>
                <form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit>
                    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                    <button class="btn btn-primary" type="submit">Tomar caso</button>
                </form>
            </div>
        </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="support-lane-empty"><strong>Todo al día</strong><span>No hay casos esperando atención.</span></div>
    <?php endif; ?>
    </div>
</section>
</div>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>