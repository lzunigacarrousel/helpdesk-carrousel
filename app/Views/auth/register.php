<?php use App\Core\Csrf; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark">
<title>Primer ingreso | Helpdesk Carrousel</title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css">
<style>
.register-shell{width:min(900px,100%)}.register-card{padding:32px}.register-intro{text-align:center;margin-bottom:18px}.register-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}.register-section{border:1px solid var(--border);border-radius:14px;padding:20px;margin-top:16px}.register-section h2{font-size:19px;margin:0 0 4px}.register-section p{margin:0 0 12px;color:var(--muted);font-size:13px}.work-options{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.work-option{position:relative}.work-option input{position:absolute;opacity:0;pointer-events:none}.work-option label{display:block;border:1px solid var(--border);border-radius:12px;padding:15px;cursor:pointer;text-align:center;font-weight:750;background:var(--card)}.work-option input:checked+label{border:2px solid var(--blue);background:var(--info-bg);color:var(--brand)}.field-hidden{display:none}.register-note{font-size:13px;color:var(--muted);margin-top:10px;line-height:1.5}.register-submit{width:100%;min-height:54px;margin-top:18px;font-size:16px}@media(max-width:760px){.auth-page{display:block;padding:4px 0 0;background:var(--bg)}.auth-page:before,.auth-page:after{display:none}.register-shell{width:100%}.register-card{border:0;border-radius:0;box-shadow:none;min-height:calc(100vh - 4px);padding:24px 14px}.register-grid{grid-template-columns:1fr}.work-options{grid-template-columns:1fr}.work-option label{text-align:left}.register-section{padding:16px 13px}}
</style>
<script>try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body><div class="brand-strip"></div>
<main class="auth-page"><div class="auth-shell register-shell"><section class="auth-card register-card">
<div class="register-intro"><img class="auth-logo" src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Corporación Carrousel"><div class="auth-eyebrow">Primer ingreso</div><h1 class="auth-title">Completa tus datos</h1><p class="auth-subtitle">Esto se hace una sola vez. Así podremos identificar tu ubicación y dirigir mejor tus solicitudes.</p></div>
<?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<form method="post" action="<?= APP_BASE_URL ?>/auth/register" id="registerForm" data-single-submit>
<input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="email" value="<?= htmlspecialchars((string)$email) ?>">
<section class="register-section"><h2>1. Tus datos</h2><p>Información básica para identificarte.</p><div class="register-grid">
<div><label class="form-label">Correo</label><input class="form-control" value="<?= htmlspecialchars((string)$email) ?>" disabled></div>
<div><label class="form-label" for="name">Nombre completo</label><input class="form-control" id="name" name="name" autocomplete="name" maxlength="160" required autofocus placeholder="Escribe tu nombre completo"></div>
<div><label class="form-label" for="phone">Teléfono</label><input class="form-control" id="phone" name="phone" autocomplete="tel" inputmode="tel" maxlength="30" required placeholder="Número de contacto"></div>
</div></section>

<section class="register-section"><h2>2. ¿Dónde trabajas?</h2><p>Selecciona la opción que mejor describa tu ubicación habitual.</p>
<div class="work-options">
<div class="work-option"><input type="radio" id="workPark" name="assignment_type" value="PARK" required><label for="workPark">Parque / ubicación</label></div>
<div class="work-option"><input type="radio" id="workCorporate" name="assignment_type" value="CORPORATE" required><label for="workCorporate">Área corporativa</label></div>
<div class="work-option"><input type="radio" id="workOther" name="assignment_type" value="OTHER" required><label for="workOther">Otro</label></div>
</div>
<div class="register-grid" style="margin-top:10px">
<div id="parkField" class="field-hidden"><label class="form-label" for="park_id">Parque o ubicación</label><select class="form-control" id="park_id" name="park_id"><option value="">Selecciona</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
<div id="areaField" class="field-hidden"><label class="form-label" for="area_id">Área</label><select class="form-control" id="area_id" name="area_id"><option value="">Selecciona</option><?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="position_id">Puesto o función</label><select class="form-control" id="position_id" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="register-note">El responsable o supervisor se asignará automáticamente cuando exista una relación configurada para tu parque o área.</div>
</section>

<button class="btn btn-primary register-submit" type="submit">Guardar y enviar código de acceso</button>
</form>
<div class="auth-actions"><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/">← Volver</a><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle><span data-theme-icon>◐</span></button></div>
</section></div></main>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script>
<script>
(()=>{const park=document.getElementById('parkField'),area=document.getElementById('areaField'),parkSelect=document.getElementById('park_id'),areaSelect=document.getElementById('area_id');function refresh(){const v=document.querySelector('input[name="assignment_type"]:checked')?.value||'';park.classList.toggle('field-hidden',v!=='PARK');area.classList.toggle('field-hidden',v==='OTHER');parkSelect.required=v==='PARK';areaSelect.required=v==='CORPORATE';if(v!=='PARK')parkSelect.value='';if(v==='OTHER')areaSelect.value='';}document.querySelectorAll('input[name="assignment_type"]').forEach(x=>x.addEventListener('change',refresh));refresh();})();
</script>
</body></html>