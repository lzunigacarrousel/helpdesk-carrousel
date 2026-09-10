<?php use App\Core\Csrf; $assetVersion='20260909-042'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#173d75">
<title>Acceso | Helpdesk Carrousel</title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/auth-v2.css?v=<?= $assetVersion ?>">
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body class="auth-body-v2">
<div class="brand-strip"></div>
<main class="auth-layout-v2">
  <section class="auth-brand-v2" aria-label="Helpdesk Carrousel">
    <div class="auth-brand-content">
      <img class="auth-brand-logo" src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Corporación Carrousel">
      <span class="auth-product-pill">Helpdesk Carrousel</span>
      <h1>Soporte claro, seguimiento real y soluciones documentadas.</h1>
      <p>Registra solicitudes, consulta avances y conserva el historial de cada solución desde un solo lugar.</p>
      <div class="auth-points-v2"><span>Seguimiento</span><span>Conocimiento</span><span>Auditoría</span></div>
    </div>
  </section>

  <section class="auth-panel-v2">
    <div class="auth-card-v2">
      <div class="auth-mobile-brand-v2"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Corporación Carrousel"><strong>Helpdesk Carrousel</strong></div>
      <span class="auth-eyebrow">Acceso seguro</span>
      <h2>Ingresa al Helpdesk</h2>
      <p class="auth-lead">Escribe tu correo. Te enviaremos un código temporal de seis dígitos.</p>

      <?php if(!empty($flash)): ?><div class="alert <?= htmlspecialchars(($flash['type']??'')==='success'?'alert-success':'alert-info') ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

      <form class="auth-form-v2" method="post" action="<?= APP_BASE_URL ?>/auth/request" data-single-submit data-action-message="Enviando código de acceso…">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <label for="email">Correo electrónico
          <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars((string)($email??'')) ?>" required autocomplete="email" autofocus placeholder="nombre@correo.com">
        </label>
        <button class="btn btn-primary auth-primary" type="submit">Continuar</button>
      </form>

      <div class="auth-helper-v2"><span>i</span><div><strong>¿Es tu primer ingreso?</strong> Si el correo todavía no existe, podrás completar tu perfil antes de recibir el código.</div></div>
      <p class="auth-security-v2">El Helpdesk utiliza OTP. No necesitas una contraseña permanente y nunca debes compartir el código recibido.</p>

      <div class="auth-actions-v2">
        <div class="auth-action-group"><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/">← Volver</a></div>
        <button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button>
      </div>
    </div>
  </section>
</main>
<div id="global-action-status" class="global-action-status" role="status" aria-live="polite" aria-hidden="true"><div class="global-action-card"><span class="global-action-spinner" aria-hidden="true"></span><strong>Procesando información</strong><span data-action-message>Espera un momento…</span></div></div>
<div id="app-toast-container" class="app-toast-container" aria-live="polite" aria-atomic="false"></div>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= $assetVersion ?>"></script>
</body></html>