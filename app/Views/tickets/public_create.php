<?php use App\Core\Csrf; $u=$user??null; $helpContext='public_create'; $assetVersion='20260909-027'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark">
<title>Reportar un problema | Helpdesk Carrousel</title>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/components.css?v=<?= $assetVersion ?>">
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head><body><div class="brand-strip"></div>
<main class="auth-page public-form-page"><div class="auth-shell public-form-shell"><section class="auth-card public-form-card">
<div class="public-form-head public-form-head-compact"><img class="auth-logo public-form-logo" src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><h1 class="auth-title">Reportar un problema</h1><p class="auth-subtitle">Cuéntanos lo que sucede. Te avisaremos cuando haya novedades.</p></div></div>
<form method="post" action="<?= APP_BASE_URL ?>/crear-ticket" class="public-ticket-form" id="publicTicketForm" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="subject" id="subject" value="">
<div class="public-form-upper">
<section class="form-step form-step-requester" data-public-step="requester">
<div class="form-step-title"><span>1</span><div><strong>Tus datos</strong><small><?= $u?'Usaremos los datos de tu sesión.':'Necesitamos saber quién reporta el caso.' ?></small></div></div>
<?php if($u): ?>
<input type="hidden" name="name" value="<?= htmlspecialchars((string)$u['full_name']) ?>"><input type="hidden" name="email" value="<?= htmlspecialchars((string)$u['email']) ?>">
<div class="signed-requester"><div class="signed-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)$u['full_name'],0,1))) ?></div><div><strong><?= htmlspecialchars((string)$u['full_name']) ?></strong><span><?= htmlspecialchars((string)$u['email']) ?></span></div></div>
<label class="form-label" for="phone">Teléfono <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Número de contacto" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>">
<?php else: ?>
<div class="grid grid-2 public-form-grid"><div><label class="form-label" for="name">Nombre completo</label><input class="form-control" id="name" name="name" required maxlength="180" autocomplete="name" placeholder="Escribe tu nombre completo"></div><div><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="nombre@carrousel.com.gt"><div class="field-help">Aquí recibirás el seguimiento.</div></div></div>
<label class="form-label" for="phone">Teléfono <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Número de contacto">
<?php endif; ?>
</section>
<section class="form-step form-step-location" data-public-step="location"><div class="form-step-title"><span>2</span><div><strong>Ubicación</strong><small>Selecciona lo que corresponda. Si no lo sabes, déjalo sin especificar.</small></div></div><div class="grid grid-2 public-form-grid">
<div><label class="form-label" for="park_id">Parque o ubicación</label><select class="form-control" id="park_id" name="park_id"><option value="">No especificar</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="area_id">Área</label><select class="form-control" id="area_id" name="area_id"><option value="">No especificar</option><?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div></div></section>
</div>
<section class="form-step form-step-problem" data-public-step="problem"><div class="form-step-title"><span>3</span><div><strong>Problema</strong><small>Selecciona el tipo y describe lo que observas.</small></div></div>
<div class="public-problem-main">
<div class="public-classification-row" data-public-step="classification"><label class="form-label" for="category_id">Tipo de solicitud</label><select class="form-control" id="category_id" name="category_id" required><option value="">Selecciona una opción</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<label class="form-label public-description-label" for="description">Describe lo que sucede</label><textarea class="form-control public-description" id="description" name="description" rows="6" required aria-describedby="description-help" placeholder="Ejemplo: Desde esta mañana la caja no permite facturar y aparece un mensaje al intentar cobrar."></textarea>
<details class="public-inline-help" id="description-help" data-public-step="guidance">
  <summary>¿Necesitas ayuda para explicarlo?</summary>
  <div class="public-inline-help-body">
    <div><strong>Qué observas</strong><span>Mensaje, falla o comportamiento inesperado.</span></div>
    <div><strong>Desde cuándo</strong><span>Cuándo empezó o si ocurre varias veces.</span></div>
    <div><strong>Dónde afecta</strong><span>Servicio, equipo, parque o proceso involucrado.</span></div>
    <div><strong>Qué intentaste</strong><span>Solo si ya realizaste alguna prueba.</span></div>
    <div class="public-after-submit"><strong>Después de enviarlo</strong><ol><li>Recibirás un número de caso.</li><li>Soporte revisará la solicitud.</li><li>Las novedades llegarán aquí y por correo.</li></ol></div>
  </div>
</details>
</div>
</section>
<div class="public-submit-box" data-public-step="submit"><button class="btn btn-primary public-submit" id="publicSubmitBtn" type="submit">Enviar solicitud</button></div></form>
<div class="auth-actions public-form-actions"><a class="btn btn-outline-secondary btn-sm" href="<?= $u?APP_BASE_URL.'/dashboard':APP_BASE_URL.'/' ?>">Inicio</a><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/mis-tickets">Mis solicitudes</a><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar tema"><span data-theme-icon>◐</span></button></div>
</section></div></main>
<?php require APP_ROOT.'/app/Views/shared/help_widget.php'; ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= $assetVersion ?>"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/help-tour.js?v=<?= $assetVersion ?>"></script>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const form=document.getElementById('publicTicketForm');
  const description=document.getElementById('description');
  const subject=document.getElementById('subject');
  if(!form||!description||!subject)return;
  const buildSubject=()=>{
    const clean=(description.value||'').replace(/\s+/g,' ').trim();
    if(!clean){subject.value='';return;}
    let short=clean.split(/[.!?]\s/)[0]||clean;
    if(short.length<5)short=clean;
    subject.value=short.slice(0,180).trim();
  };
  description.addEventListener('input',buildSubject);
  form.addEventListener('submit',buildSubject,{capture:true});
})();
</script>
</body></html>