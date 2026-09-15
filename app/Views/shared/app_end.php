<footer class="app-corporate-footer">
  <div>© <?= date('Y') ?> <strong>Carrousel Guatemala ✨🎠</strong> · Desarrollado por <strong>Luis Fernando Zuniga</strong></div>
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

<?php $navigationGuardVersion=(string)(@filemtime(APP_ROOT.'/public/assets/js/navigation-guards.js')?:($assetVersion??'1')); ?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-density-v25.css?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/data-tables.css?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>">
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/navigation-guards.js?v=<?= htmlspecialchars($navigationGuardVersion) ?>"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>"></script>
<?php if($activeNav==='agenda'): ?><script defer src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/agenda.js?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>"></script><?php endif; ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/ticket-workspace.js?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/table-normalization.js?v=1"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/notifications.js?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/help-tour.js?v=<?= htmlspecialchars($assetVersion??'20260908-1710') ?>"></script>
</body></html>