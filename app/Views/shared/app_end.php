<footer class="app-corporate-footer">
  <div>© <?= date('Y') ?> <strong>Carrousel Guatemala ✨🎠</strong> · Desarrollado por <strong>Luis Fernando Zuniga</strong></div>
  <div class="app-footer-actions no-print"><span class="app-footer-chip">Helpdesk Carrousel</span></div>
</footer>
</main></div></div>

<?php require APP_ROOT.'/app/Views/shared/help_widget.php'; ?>

<div id="global-action-status" class="global-action-status" role="status" aria-live="polite" aria-hidden="true">
  <div class="global-action-card">
    <span class="global-action-spinner" aria-hidden="true"></span>
    <strong>Procesando información</strong>
    <span data-action-message>Espera un momento…</span>
  </div>
</div>
<div id="app-toast-container" class="app-toast-container" aria-live="polite" aria-atomic="false"></div>

<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script>
</body></html>
