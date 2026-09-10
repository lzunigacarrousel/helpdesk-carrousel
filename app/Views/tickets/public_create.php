<?php use App\Core\Csrf; $u=$user??null; $helpContext='public_create'; $assetVersion='20260909-042'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#173d75">
<title>Reportar un problema | Helpdesk Carrousel</title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/components.css?v=<?= $assetVersion ?>">
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.public-request-body{min-height:100vh;background:var(--bg);color:var(--ink)}
.public-request-topbar{position:sticky;top:4px;z-index:20;height:66px;border-bottom:1px solid var(--border);background:var(--card);box-shadow:0 1px 3px rgba(16,24,40,.04)}
.public-request-topbar-inner{width:min(1480px,100%);height:100%;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:18px}
.public-request-brand{display:flex;align-items:center;gap:11px;text-decoration:none;color:var(--ink)}.public-request-brand img{width:48px;height:48px;object-fit:contain;background:#fff;border-radius:9px;padding:4px}.public-request-brand strong,.public-request-brand span{display:block}.public-request-brand strong{color:var(--brand-dark);font-size:15px}.public-request-brand span{color:var(--muted);font-size:11px}
.public-request-actions{display:flex;align-items:center;gap:8px}.public-request-user{margin-right:4px;text-align:right}.public-request-user strong,.public-request-user span{display:block}.public-request-user strong{font-size:13px}.public-request-user span{font-size:11px;color:var(--muted)}
.public-request-main{width:min(1480px,100%);margin:0 auto;padding:24px}.public-request-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:18px}.public-request-heading span{display:block;color:var(--brand);font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.08em}.public-request-heading h1{margin:3px 0 4px;font-size:28px;line-height:1.15;color:var(--ink)}
.public-ticket-form{display:grid;gap:16px}.public-request-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.public-request-card{margin:0;overflow:hidden}.public-request-card .card-body{padding:18px 20px}.public-request-step-head{display:flex;align-items:flex-start;gap:11px;margin-bottom:14px}.public-request-step-number{display:grid;place-items:center;flex:0 0 32px;width:32px;height:32px;border-radius:9px;background:var(--brand);color:#fff;font-size:13px;font-weight:900}.public-request-step-head strong{display:block;font-size:17px;color:var(--ink)}.public-request-card .form-label{margin:0 0 6px;font-size:13px}.public-request-card .form-control{min-height:44px;font-size:14px}.public-request-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px 14px}.public-request-fields .field-full{grid-column:1/-1}.signed-requester{display:flex;align-items:center;gap:10px;min-height:44px;margin-bottom:12px;padding:9px 11px;border:1px solid var(--border);border-radius:10px;background:color-mix(in srgb,var(--card) 94%,var(--bg) 6%)}.signed-avatar{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:50%;background:var(--brand);color:#fff;font-weight:850}.signed-requester strong,.signed-requester span{display:block}.signed-requester strong{font-size:13px}.signed-requester span{color:var(--muted);font-size:11px}
.public-problem-card .card-body{padding:20px}.public-problem-grid{display:grid;grid-template-columns:minmax(230px,.32fr) minmax(0,1fr);gap:16px;align-items:start}.public-description{min-height:150px;resize:vertical}
.public-submit-bar{display:flex;align-items:center;justify-content:flex-end;gap:14px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)}.public-submit{min-width:190px;min-height:46px;background:#173d75;border-color:#173d75}.public-submit:hover,.public-submit:focus{background:#102f5e;border-color:#102f5e}
html[data-theme="dark"] .public-request-brand img{background:#fff}
@media(max-width:900px){.public-request-user{display:none}.public-request-main{padding:18px}.public-request-grid,.public-problem-grid{grid-template-columns:1fr}.public-request-heading{align-items:flex-start}.public-request-heading h1{font-size:25px}}
@media(max-width:650px){.public-request-topbar{height:60px}.public-request-topbar-inner{padding:0 12px}.public-request-brand img{width:42px;height:42px}.public-request-actions .btn:not(.theme-btn){font-size:0;min-width:40px;width:40px;padding:0}.public-request-actions .btn:not(.theme-btn):first-of-type:before{content:"⌂";font-size:17px}.public-request-actions .btn:not(.theme-btn):nth-of-type(2):before{content:"▤";font-size:16px}.public-request-main{padding:14px 12px 28px}.public-request-heading{margin-bottom:14px}.public-request-heading h1{font-size:23px}.public-request-fields{grid-template-columns:1fr}.public-request-fields .field-full{grid-column:auto}.public-request-card .card-body,.public-problem-card .card-body{padding:15px}.public-submit-bar{align-items:stretch;flex-direction:column}.public-submit{width:100%;min-width:0}}
</style>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body class="public-request-body"><div class="brand-strip"></div>
<header class="public-request-topbar"><div class="public-request-topbar-inner">
  <a class="public-request-brand" href="<?= $u?APP_BASE_URL.'/dashboard':APP_BASE_URL.'/' ?>"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><strong>Helpdesk</strong><span>Corporación Carrousel</span></div></a>
  <div class="public-request-actions">
    <?php if($u): ?><div class="public-request-user"><strong><?= htmlspecialchars((string)$u['full_name']) ?></strong><span><?= htmlspecialchars((string)$u['email']) ?></span></div><?php endif; ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= $u?APP_BASE_URL.'/dashboard':APP_BASE_URL.'/' ?>">Inicio</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/mis-tickets">Mis solicitudes</a>
    <button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button>
  </div>
</div></header>

<main class="public-request-main">
  <div class="public-request-heading"><div><span>Soporte</span><h1>Reportar un problema</h1></div></div>

  <form method="post" action="<?= APP_BASE_URL ?>/crear-ticket" class="public-ticket-form" id="publicTicketForm" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="subject" id="subject" value="">

    <div class="public-request-grid">
      <section class="card public-request-card" data-public-step="requester"><div class="card-body">
        <div class="public-request-step-head"><span class="public-request-step-number">1</span><div><strong>Tus datos</strong></div></div>
        <?php if($u): ?>
          <input type="hidden" name="name" value="<?= htmlspecialchars((string)$u['full_name']) ?>"><input type="hidden" name="email" value="<?= htmlspecialchars((string)$u['email']) ?>">
          <div class="signed-requester"><div class="signed-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)$u['full_name'],0,1))) ?></div><div><strong><?= htmlspecialchars((string)$u['full_name']) ?></strong><span><?= htmlspecialchars((string)$u['email']) ?></span></div></div>
          <label class="form-label" for="phone">Teléfono <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Número de contacto" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>">
        <?php else: ?>
          <div class="public-request-fields"><div><label class="form-label" for="name">Nombre completo</label><input class="form-control" id="name" name="name" required maxlength="180" autocomplete="name" placeholder="Nombre completo"></div><div><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="nombre@correo.com"></div><div class="field-full"><label class="form-label" for="phone">Teléfono <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Número de contacto"></div></div>
        <?php endif; ?>
      </div></section>

      <section class="card public-request-card" data-public-step="location"><div class="card-body">
        <div class="public-request-step-head"><span class="public-request-step-number">2</span><div><strong>Ubicación</strong></div></div>
        <div class="public-request-fields"><div><label class="form-label" for="park_id">Parque o ubicación</label><select class="form-control" id="park_id" name="park_id"><option value="">No especificar</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div><div><label class="form-label" for="area_id">Área</label><select class="form-control" id="area_id" name="area_id"><option value="">No especificar</option><?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div></div>
      </div></section>
    </div>

    <section class="card public-request-card public-problem-card" data-public-step="problem"><div class="card-body">
      <div class="public-request-step-head"><span class="public-request-step-number">3</span><div><strong>Problema</strong></div></div>
      <div class="public-problem-grid">
        <div><label class="form-label" for="category_id">Tipo de solicitud</label><select class="form-control" id="category_id" name="category_id" required><option value="">Selecciona una opción</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="description">Describe lo que sucede</label><textarea class="form-control public-description" id="description" name="description" rows="5" required placeholder="Ejemplo: Desde esta mañana la caja no permite facturar y aparece un mensaje al intentar cobrar."></textarea></div>
      </div>
      <div class="public-submit-bar" data-public-step="submit"><button class="btn btn-primary public-submit" id="publicSubmitBtn" type="submit">Enviar solicitud</button></div>
    </div></section>
  </form>
</main>
<?php require APP_ROOT.'/app/Views/shared/help_widget.php'; ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js?v=<?= $assetVersion ?>"></script>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/help-tour.js?v=<?= $assetVersion ?>"></script>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const form=document.getElementById('publicTicketForm');
  const description=document.getElementById('description');
  const subject=document.getElementById('subject');
  if(!form||!description||!subject)return;
  const buildSubject=()=>{const clean=(description.value||'').replace(/\s+/g,' ').trim();if(!clean){subject.value='';return;}let short=clean.split(/[.!?]\s/)[0]||clean;if(short.length<5)short=clean;subject.value=short.slice(0,180).trim();};
  description.addEventListener('input',buildSubject);
  form.addEventListener('submit',buildSubject,{capture:true});
})();
</script>
</body></html>