<?php
use App\Core\Csrf;
$pageTitle='Revisar solución';$pageSection='Mis solicitudes';$activeNav='mine';$helpContext='my_tickets';
require APP_ROOT.'/app/Views/shared/app_start.php';
$closed=(string)$ticket['status']==='CLOSED';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.ticket-feedback-page{width:min(980px,100%)!important;display:grid;gap:14px}.ticket-feedback-heading{margin:0!important}.ticket-feedback-solution,.ticket-feedback-decision,.ticket-feedback-panel,.ticket-feedback-thanks{border-radius:15px;overflow:hidden}.ticket-feedback-solution .card-body,.ticket-feedback-decision .card-body,.ticket-feedback-panel .card-body{padding:18px 20px}.ticket-feedback-solution h2{margin:5px 0 10px;font-size:22px}.ticket-feedback-solution-text{padding:14px 15px;border:1px solid var(--border);border-radius:11px;background:color-mix(in srgb,var(--brand) 4%,var(--card) 96%);font-size:15px;line-height:1.6}.ticket-feedback-meta{display:flex;gap:10px 18px;flex-wrap:wrap;margin-top:10px;color:var(--muted);font-size:13px}
.ticket-feedback-decision{text-align:center}.ticket-feedback-decision h2{margin:2px 0 6px;font-size:24px}.ticket-feedback-decision p{margin:0;color:var(--muted)}.ticket-feedback-choices{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px}.ticket-feedback-choice{min-height:50px;font-size:15px;font-weight:800}.ticket-feedback-choice[aria-pressed="true"]{box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 18%,transparent)}
.ticket-feedback-panel h2{margin:4px 0 12px;font-size:21px}.ticket-feedback-positive{border-color:color-mix(in srgb,#159447 25%,var(--border) 75%)!important}.ticket-feedback-return{background:color-mix(in srgb,#d99000 4%,var(--card) 96%)!important;border-color:color-mix(in srgb,#d99000 20%,var(--border) 80%)!important}.ticket-feedback-panel textarea{resize:vertical}.ticket-feedback-panel form{display:grid;gap:10px}
.nps-fieldset{border:0;padding:0;margin:0 0 16px}.nps-fieldset legend{font-weight:800;font-size:14px;margin-bottom:11px}.nps-scale{display:grid;grid-template-columns:repeat(11,minmax(36px,1fr));gap:5px}.nps-scale label{cursor:pointer}.nps-scale input{position:absolute;opacity:0;pointer-events:none}.nps-scale span{display:grid;place-items:center;min-height:42px;border:1px solid var(--border);border-radius:9px;background:var(--card);font-weight:850;transition:.12s ease}.nps-scale label:hover span{border-color:var(--brand);transform:translateY(-1px)}.nps-scale input:focus-visible+span{outline:3px solid color-mix(in srgb,var(--brand) 22%,transparent)}.nps-scale input:checked+span{background:var(--brand);border-color:var(--brand);color:#fff;box-shadow:0 4px 12px color-mix(in srgb,var(--brand) 22%,transparent)}.nps-scale-labels{display:flex;justify-content:space-between;color:var(--muted);font-size:11.5px;margin-top:6px}.ticket-feedback-primary{margin-top:2px}
.ticket-feedback-thanks .card-body{display:flex;align-items:center;gap:14px;padding:18px}.ticket-feedback-check{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:50%;background:color-mix(in srgb,#159447 12%,var(--card) 88%);color:#087c37;font-size:22px;font-weight:900}.ticket-feedback-thanks h2{margin:0 0 4px}
@media(max-width:760px){.ticket-feedback-choices{grid-template-columns:1fr}.nps-scale{grid-template-columns:repeat(6,minmax(38px,1fr))}.nps-scale-labels{display:none}}
@media(max-width:520px){.ticket-feedback-page{gap:12px}.ticket-feedback-heading{align-items:flex-start}.ticket-feedback-solution .card-body,.ticket-feedback-decision .card-body,.ticket-feedback-panel .card-body{padding:16px}.nps-scale{grid-template-columns:repeat(4,minmax(42px,1fr))}.nps-scale span{min-height:44px}}
</style>
<div class="ticket-feedback-page" data-feedback-flow>
  <div class="page-heading ticket-feedback-heading">
    <div><span class="ticket-kicker"><?= htmlspecialchars($ticket['ticket_number']) ?></span><h1 class="page-title">Revisar solución</h1></div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$ticket['id'] ?>">Ver caso</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <section class="card ticket-feedback-solution">
    <div class="card-body">
      <span class="ticket-kicker">Solución registrada</span>
      <h2><?= htmlspecialchars($ticket['subject']) ?></h2>
      <?php if(!empty($ticket['solution_applied'])): ?><div class="ticket-feedback-solution-text"><?= nl2br(htmlspecialchars((string)$ticket['solution_applied'])) ?></div><?php else: ?><div class="itsm-inline-empty">Revisa el seguimiento para ver el detalle de la solución.</div><?php endif; ?>
      <div class="ticket-feedback-meta"><?php if(!empty($ticket['resolved_by_name'])): ?><span>Atendido por <strong><?= htmlspecialchars($ticket['resolved_by_name']) ?></strong></span><?php endif; ?><?php if(!empty($ticket['resolved_at'])): ?><span>Resuelto <?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$ticket['resolved_at']))) ?></span><?php endif; ?></div>
    </div>
  </section>

  <?php if($closed&&$feedback): ?>
    <section class="card ticket-feedback-thanks"><div class="card-body"><div class="ticket-feedback-check">✓</div><div><h2>Gracias por tu opinión</h2><strong>Calificación: <?= (int)$feedback['nps_score'] ?>/10</strong></div></div></section>
  <?php elseif(!$closed): ?>
    <section class="card ticket-feedback-decision">
      <div class="card-body">
        <span class="ticket-kicker">Confirma la solución</span>
        <h2>¿Tu problema quedó resuelto?</h2>
        <p>Elige una opción y te mostraremos únicamente el siguiente paso necesario.</p>
        <div class="ticket-feedback-choices" role="group" aria-label="¿Tu problema quedó resuelto?">
          <button class="btn btn-primary ticket-feedback-choice" type="button" data-feedback-choice="yes" aria-controls="feedback-yes" aria-expanded="false" aria-pressed="false">Sí, ya quedó</button>
          <button class="btn btn-outline-secondary ticket-feedback-choice" type="button" data-feedback-choice="no" aria-controls="feedback-no" aria-expanded="false" aria-pressed="false">No, todavía falta algo</button>
        </div>
      </div>
    </section>

    <section id="feedback-yes" class="card ticket-feedback-panel ticket-feedback-positive" data-feedback-panel="yes" hidden>
      <div class="card-body">
        <span class="ticket-kicker">Sí, quedó resuelto</span><h2>Califica la atención</h2>
        <form method="post" action="<?= APP_BASE_URL ?>/tickets/feedback" data-single-submit>
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
          <fieldset class="nps-fieldset"><legend>¿Qué tan probable es que recomiendes la atención de soporte?</legend><div class="nps-scale" role="radiogroup" aria-label="Calificación de 0 a 10"><?php for($i=0;$i<=10;$i++): ?><label><input type="radio" name="nps_score" value="<?= $i ?>" required><span><?= $i ?></span></label><?php endfor; ?></div><div class="nps-scale-labels"><span>Nada probable</span><span>Muy probable</span></div></fieldset>
          <label class="form-label" for="feedback-comment">Comentario <span class="optional">Opcional</span></label><textarea class="form-control" id="feedback-comment" name="comment" rows="3" maxlength="1000" placeholder="Qué salió bien o qué podríamos mejorar"></textarea>
          <button class="btn btn-primary ticket-feedback-primary" type="submit">Calificar y cerrar</button>
        </form>
      </div>
    </section>

    <section id="feedback-no" class="card ticket-feedback-panel ticket-feedback-return" data-feedback-panel="no" hidden>
      <div class="card-body">
        <span class="ticket-kicker">No, todavía falta algo</span><h2>Devolver a soporte</h2>
        <form method="post" action="<?= APP_BASE_URL ?>/tickets/feedback/reopen" data-single-submit>
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
          <label class="form-label" for="reopen-reason">¿Qué falta resolver?</label><textarea class="form-control" id="reopen-reason" name="reason" rows="4" maxlength="1000" required placeholder="Ejemplo: El error volvió a aparecer al intentar facturar."></textarea>
          <button class="btn btn-outline-secondary" type="submit">Devolver a soporte</button>
        </form>
      </div>
    </section>
  <?php else: ?>
    <section class="card ticket-feedback-thanks"><div class="card-body"><div class="ticket-feedback-check">✓</div><div><h2>Solicitud cerrada</h2></div></div></section>
  <?php endif; ?>
</div>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/ticket-feedback.js?v=<?= htmlspecialchars($assetVersion??'1') ?>"></script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>