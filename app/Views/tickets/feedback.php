<?php
use App\Core\Csrf;
$pageTitle='Revisar solución';$pageSection='Mis solicitudes';$activeNav='mine';$helpContext='my_tickets';
require APP_ROOT.'/app/Views/shared/app_start.php';
$closed=(string)$ticket['status']==='CLOSED';
?>
<div class="ticket-feedback-page">
  <div class="page-heading ticket-feedback-heading">
    <div>
      <span class="ticket-kicker"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
      <h1 class="page-title">¿Quedó resuelto?</h1>
      <p class="page-subtitle">Revisa la solución. Puedes confirmarla o devolver el caso a soporte.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$ticket['id'] ?>">Ver caso</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <section class="card ticket-feedback-solution">
    <div class="card-body">
      <span class="ticket-kicker">Solución registrada</span>
      <h2><?= htmlspecialchars($ticket['subject']) ?></h2>
      <?php if(!empty($ticket['solution_applied'])): ?>
        <div class="ticket-feedback-solution-text"><?= nl2br(htmlspecialchars((string)$ticket['solution_applied'])) ?></div>
      <?php else: ?>
        <div class="itsm-inline-empty">El equipo marcó el caso como resuelto. Revisa el seguimiento para ver el detalle.</div>
      <?php endif; ?>
      <div class="ticket-feedback-meta">
        <?php if(!empty($ticket['resolved_by_name'])): ?><span>Atendido por <strong><?= htmlspecialchars($ticket['resolved_by_name']) ?></strong></span><?php endif; ?>
        <?php if(!empty($ticket['resolved_at'])): ?><span>Resuelto <?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$ticket['resolved_at']))) ?></span><?php endif; ?>
      </div>
    </div>
  </section>

  <?php if($closed&&$feedback): ?>
    <section class="card ticket-feedback-thanks">
      <div class="card-body">
        <div class="ticket-feedback-check">✓</div>
        <div><h2>Gracias por tu opinión</h2><p>Esta solicitud ya está cerrada.</p><strong>Tu calificación: <?= (int)$feedback['nps_score'] ?>/10</strong></div>
      </div>
    </section>
  <?php elseif(!$closed): ?>
    <div class="ticket-feedback-options">
      <section class="card ticket-feedback-positive">
        <div class="card-body">
          <span class="ticket-kicker">Sí, quedó resuelto</span>
          <h2>Califica la atención</h2>
          <p>Tu respuesta nos ayuda a mejorar el servicio.</p>
          <form method="post" action="<?= APP_BASE_URL ?>/tickets/feedback" data-single-submit>
            <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
            <fieldset class="nps-fieldset">
              <legend>¿Qué tan probable es que recomiendes la atención de soporte?</legend>
              <div class="nps-scale" role="radiogroup" aria-label="Calificación de 0 a 10">
                <?php for($i=0;$i<=10;$i++): ?><label><input type="radio" name="nps_score" value="<?= $i ?>" required><span><?= $i ?></span></label><?php endfor; ?>
              </div>
              <div class="nps-scale-labels"><span>Nada probable</span><span>Muy probable</span></div>
            </fieldset>
            <label class="form-label" for="feedback-comment">Comentario <span class="optional">Opcional</span></label>
            <textarea class="form-control" id="feedback-comment" name="comment" rows="3" maxlength="1000" placeholder="Si quieres, cuéntanos qué salió bien o qué podríamos mejorar."></textarea>
            <button class="btn btn-primary ticket-feedback-primary" type="submit">Calificar y cerrar</button>
          </form>
        </div>
      </section>

      <section class="card ticket-feedback-return">
        <div class="card-body">
          <span class="ticket-kicker">No, todavía falta algo</span>
          <h2>Devolver a soporte</h2>
          <p>Indica brevemente qué sigue ocurriendo. El mismo caso volverá a atención.</p>
          <form method="post" action="<?= APP_BASE_URL ?>/tickets/feedback/reopen" data-single-submit>
            <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
            <label class="form-label" for="reopen-reason">¿Qué falta resolver?</label>
            <textarea class="form-control" id="reopen-reason" name="reason" rows="4" maxlength="1000" required placeholder="Ejemplo: El error volvió a aparecer al intentar facturar."></textarea>
            <button class="btn btn-outline-secondary" type="submit">Devolver a soporte</button>
          </form>
        </div>
      </section>
    </div>
  <?php else: ?>
    <section class="card ticket-feedback-thanks"><div class="card-body"><div class="ticket-feedback-check">✓</div><div><h2>Solicitud cerrada</h2><p>La solución ya fue confirmada.</p></div></div></section>
  <?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
