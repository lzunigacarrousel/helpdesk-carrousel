<?php use App\Core\Csrf; $assetVersion='20260909-004'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#173d75">
<title>Verificación | Helpdesk Carrousel</title>
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
      <span class="auth-product-pill">Helpdesk</span>
      <h1>Un paso más para entrar de forma segura.</h1>
      <p>El código confirma que tienes acceso al correo registrado, sin utilizar contraseñas permanentes.</p>
      <div class="auth-points-v2"><span>Código de 6 dígitos</span><span>Acceso temporal</span><span>Sin contraseña</span></div>
    </div>
  </section>

  <section class="auth-panel-v2">
    <div class="auth-card-v2">
      <span class="auth-eyebrow">Verificación</span>
      <h2>Escribe tu código</h2>
      <p class="auth-lead">Lo enviamos a <strong><?= htmlspecialchars((string)$email) ?></strong>.</p>
      <?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
      <form class="auth-form-v2" method="post" action="<?= APP_BASE_URL ?>/auth/verify" data-single-submit data-action-message="Validando código…">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars((string)$email) ?>">
        <label for="otp-code">Código OTP<input class="form-control otp-input-v2" id="otp-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus placeholder="000000"></label>
        <button class="btn btn-primary auth-primary" type="submit">Verificar e ingresar</button>
      </form>
      <div class="auth-helper-v2"><span>ℹ</span><div>El código es temporal. Si expiró o no lo recibiste, solicita uno nuevo.</div></div>
      <p class="auth-security-v2">Nunca compartas tu código OTP con otra persona.</p>
      <div class="auth-actions-v2"><div class="auth-action-group"><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/login">Cambiar correo</a><form method="post" action="<?= APP_BASE_URL ?>/auth/resend" data-single-submit data-action-message="Reenviando código…"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="email" value="<?= htmlspecialchars((string)$email) ?>"><button class="btn btn-outline-primary btn-sm" type="submit">Reenviar código</button></form></div><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button></div>
    </div>
  </section>
</main>
<div id="global-action-status" class="global-action-status" role="status" aria-live="polite" aria-hidden="true"><div class="global-action-card"><span class="global-action-spinner" aria-hidden="true"></span><strong>Procesando información</strong><span data-action-message>Espera un momento…</span></div></div>
<div id="app-toast-container" class="app-toast-container" aria-live="polite" aria-atomic="false"></div>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= $assetVersion ?>"></script>
</body></html>