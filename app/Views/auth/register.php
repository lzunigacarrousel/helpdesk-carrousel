<?php use App\Core\Csrf; $assetVersion='20260909-001'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#173d75">
<title>Primer ingreso | Helpdesk Carrousel</title>
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
      <h1>Completa tu perfil una sola vez.</h1>
      <p>Estos datos permiten dirigir mejor tus solicitudes y saber a qué parque, área o función perteneces. Después ingresarás únicamente con OTP.</p>
      <div class="auth-points-v2"><span>Registro único</span><span>Asignación organizacional</span><span>OTP seguro</span></div>
    </div>
  </section>

  <section class="auth-panel-v2">
    <div class="auth-card-v2 auth-register-shell">
      <span class="auth-eyebrow">Primer ingreso</span>
      <h2>Completa tus datos</h2>
      <p class="auth-lead">Correo identificado: <strong><?= htmlspecialchars((string)$email) ?></strong></p>
      <?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

      <form method="post" action="<?= APP_BASE_URL ?>/auth/register" id="registerForm" data-single-submit data-action-message="Guardando tus datos y enviando código…">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="email" value="<?= htmlspecialchars((string)$email) ?>">

        <section class="auth-register-section">
          <h3>1. Tus datos</h3><p>Información básica para identificarte.</p>
          <div class="auth-register-grid">
            <label>Nombre completo<input class="form-control" id="name" name="name" autocomplete="name" maxlength="160" required autofocus placeholder="Escribe tu nombre completo"></label>
            <label>Teléfono<input class="form-control" id="phone" name="phone" autocomplete="tel" inputmode="tel" maxlength="30" required placeholder="Número de contacto"></label>
          </div>
        </section>

        <section class="auth-register-section">
          <h3>2. ¿Dónde trabajas?</h3><p>Selecciona la opción que mejor describe tu ubicación habitual.</p>
          <div class="auth-work-options">
            <div class="auth-work-option"><input type="radio" id="workPark" name="assignment_type" value="PARK" required><label for="workPark">Parque / ubicación</label></div>
            <div class="auth-work-option"><input type="radio" id="workCorporate" name="assignment_type" value="CORPORATE" required><label for="workCorporate">Área corporativa</label></div>
            <div class="auth-work-option"><input type="radio" id="workOther" name="assignment_type" value="OTHER" required><label for="workOther">Otro</label></div>
          </div>
          <div class="auth-register-grid" style="margin-top:12px">
            <label id="parkField" class="auth-field-hidden">Parque o ubicación<select class="form-control" id="park_id" name="park_id"><option value="">Selecciona</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></label>
            <label id="areaField" class="auth-field-hidden">Área<select class="form-control" id="area_id" name="area_id"><option value="">Selecciona</option><?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></label>
            <label>Puesto o función<select class="form-control" id="position_id" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></label>
          </div>
          <div class="auth-register-note">El responsable o supervisor se relacionará con tu estructura cuando exista una asignación configurada para tu parque o área.</div>
        </section>

        <button class="btn btn-primary auth-primary" style="width:100%;min-height:52px;margin-top:16px" type="submit">Guardar y enviar código</button>
      </form>

      <p class="auth-security-v2">Tus datos se utilizan para identificar y dirigir correctamente tus solicitudes dentro del Helpdesk.</p>
      <div class="auth-actions-v2"><div class="auth-action-group"><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/login">← Cambiar correo</a></div><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button></div>
    </div>
  </section>
</main>
<div id="global-action-status" class="global-action-status" role="status" aria-live="polite" aria-hidden="true"><div class="global-action-card"><span class="global-action-spinner" aria-hidden="true"></span><strong>Procesando información</strong><span data-action-message>Espera un momento…</span></div></div>
<div id="app-toast-container" class="app-toast-container" aria-live="polite" aria-atomic="false"></div>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= $assetVersion ?>"></script>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(()=>{const park=document.getElementById('parkField'),area=document.getElementById('areaField'),parkSelect=document.getElementById('park_id'),areaSelect=document.getElementById('area_id');function refresh(){const v=document.querySelector('input[name="assignment_type"]:checked')?.value||'';park?.classList.toggle('auth-field-hidden',v!=='PARK');area?.classList.toggle('auth-field-hidden',v==='OTHER');if(parkSelect){parkSelect.required=v==='PARK';if(v!=='PARK')parkSelect.value='';}if(areaSelect){areaSelect.required=v==='CORPORATE';if(v==='OTHER')areaSelect.value='';}}document.querySelectorAll('input[name="assignment_type"]').forEach(x=>x.addEventListener('change',refresh));refresh();})();
</script>
</body></html>