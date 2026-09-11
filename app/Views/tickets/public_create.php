<?php
use App\Core\Csrf;
use App\Services\RequesterTopicService;
$u=$user??null;$assignment=$assignment??null;$defaultParkId=(int)($defaultParkId??0);$defaultAreaId=(int)($defaultAreaId??0);
$hasAssignedPark=$u&&$defaultParkId>0&&!empty($assignment['park_name']);$knownPhone=$u?trim((string)($u['phone']??'')):'';
$requesterTopics=RequesterTopicService::options($categories??[]);
$requesterTopicGroups=[];foreach($requesterTopics as $topic){$requesterTopicGroups[(string)$topic['group']][]=$topic;}
$helpContext='public_create';$assetVersion='20260911-UXHELP1';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#173d75">
<title>Solicitar ayuda | Helpdesk Carrousel</title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/components.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/dark-refinement.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/help-tour-contrast.css?v=20260912-UXHELP2">
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.public-request-body{min-height:100vh;background:var(--bg);color:var(--ink)}
.public-request-topbar{position:sticky;top:4px;z-index:20;height:66px;border-bottom:1px solid var(--border);background:var(--card);box-shadow:0 1px 3px rgba(16,24,40,.04)}
.public-request-topbar-inner{width:min(1480px,100%);height:100%;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between;gap:18px}
.public-request-brand{display:flex;align-items:center;gap:11px;text-decoration:none;color:var(--ink)}.public-request-brand img{width:48px;height:48px;object-fit:contain;background:#fff;border-radius:9px;padding:4px}.public-request-brand strong,.public-request-brand span{display:block}.public-request-brand strong{color:var(--brand-dark);font-size:15px}.public-request-brand span{color:var(--muted);font-size:11px}
.public-request-actions{display:flex;align-items:center;gap:8px}.public-request-user{margin-right:4px;text-align:right}.public-request-user strong,.public-request-user span{display:block}.public-request-user strong{font-size:13px}.public-request-user span{font-size:11px;color:var(--muted)}
.public-request-main{width:min(1180px,100%);margin:0 auto;padding:24px}.public-request-heading{margin-bottom:18px}.public-request-heading span{display:block;color:var(--brand);font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.08em}.public-request-heading h1{margin:3px 0 5px;font-size:29px;line-height:1.15;color:var(--ink)}.public-request-heading p{margin:0;color:var(--muted);font-size:14px}
.public-ticket-form{display:grid;gap:16px}.public-context-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.public-request-card{margin:0;overflow:visible}.public-request-card .card-body{padding:18px 20px}.public-section-title{margin:0 0 14px;font-size:17px;color:var(--ink)}
.public-request-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px 14px}.public-request-fields .field-full{grid-column:1/-1}.public-request-card .form-label{margin:0 0 6px;font-size:13px}.public-request-card .form-control{min-height:44px;font-size:14px}
.signed-requester{display:flex;align-items:center;gap:10px;min-height:52px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:color-mix(in srgb,var(--card) 94%,var(--bg) 6%)}.signed-avatar{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:var(--brand);color:#fff;font-weight:850}.signed-requester strong,.signed-requester span{display:block}.signed-requester strong{font-size:13px}.signed-requester span{color:var(--muted);font-size:11px}.requester-phone-missing{margin-top:12px}
.public-location-summary{display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:58px;padding:11px 12px;border:1px solid var(--border);border-radius:10px;background:color-mix(in srgb,var(--card) 94%,var(--bg) 6%)}.public-location-summary span,.public-location-summary strong{display:block}.public-location-summary span{font-size:11px;color:var(--muted)}.public-location-summary strong{margin-top:2px;font-size:14px;color:var(--ink)}.public-location-fields{margin-top:12px}.public-location-fields[hidden]{display:none!important}
.public-help-card .card-body{padding:22px}.public-help-grid{display:grid;grid-template-columns:minmax(250px,.34fr) minmax(0,1fr);gap:18px;align-items:start}.public-help-grid textarea{min-height:170px;resize:vertical}.public-category-help{margin:8px 2px 0;min-height:34px;color:var(--muted);font-size:12px;line-height:1.4}.public-description-help{margin:7px 2px 0;color:var(--muted);font-size:12px}.public-submit-bar{display:flex;align-items:center;justify-content:flex-end;gap:14px;margin-top:18px;padding-top:18px;border-top:1px solid var(--border)}.public-submit{min-width:190px;min-height:46px;background:var(--brand);border-color:var(--brand)}
html[data-theme="dark"] .public-request-brand img{background:#fff}
@media(max-width:900px){.public-request-main{padding:18px}.public-context-grid,.public-help-grid{grid-template-columns:1fr}.public-request-heading h1{font-size:25px}}
@media(max-width:650px){.public-request-topbar{height:60px}.public-request-topbar-inner{padding:0 12px}.public-request-brand img{width:42px;height:42px}.public-request-user{display:none}.public-request-actions .btn:not(.theme-btn){font-size:0;min-width:40px;width:40px;padding:0}.public-request-actions .btn:not(.theme-btn):first-of-type:before{content:"⌂";font-size:17px}.public-request-actions .btn:not(.theme-btn):nth-of-type(2):before{content:"▤";font-size:16px}.public-request-main{padding:14px 12px 28px}.public-request-heading{margin-bottom:14px}.public-request-heading h1{font-size:23px}.public-request-fields{grid-template-columns:1fr}.public-request-fields .field-full{grid-column:auto}.public-request-card .card-body,.public-help-card .card-body{padding:15px}.public-location-summary{align-items:flex-start;flex-direction:column}.public-location-summary .btn{width:100%}.public-submit-bar{align-items:stretch;flex-direction:column}.public-submit{width:100%;min-width:0}}
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
  <div class="public-request-heading"><span>Soporte</span><h1>Solicitar ayuda</h1><p>No necesitas conocer la causa técnica. Cuéntanos qué necesitas y qué está pasando.</p></div>

  <form method="post" action="<?= APP_BASE_URL ?>/crear-ticket" class="public-ticket-form" id="publicTicketForm" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="subject" id="subject" value=""><input type="hidden" name="category_id" id="category_id" value="">

    <div class="public-context-grid">
      <section class="card public-request-card" data-public-step="requester"><div class="card-body">
        <h2 class="public-section-title"><?= $u?'Tus datos':'¿Cómo podemos contactarte?' ?></h2>
        <?php if($u): ?>
          <div class="signed-requester"><div class="signed-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)$u['full_name'],0,1))) ?></div><div><strong><?= htmlspecialchars((string)$u['full_name']) ?></strong><span><?= htmlspecialchars((string)$u['email']) ?><?= $knownPhone!==''?' · '.htmlspecialchars($knownPhone):'' ?></span></div></div>
          <?php if($knownPhone===''): ?><div class="requester-phone-missing"><label class="form-label" for="phone">Número de contacto <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Para contactarte si hace falta"></div><?php endif; ?>
        <?php else: ?>
          <div class="public-request-fields"><div><label class="form-label" for="name">Nombre completo</label><input class="form-control" id="name" name="name" required maxlength="180" autocomplete="name" placeholder="Nombre completo"></div><div><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="nombre@correo.com"></div><div class="field-full"><label class="form-label" for="phone">Número de contacto <span class="optional">Opcional</span></label><input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Para contactarte si hace falta"></div></div>
        <?php endif; ?>
      </div></section>

      <section class="card public-request-card" data-public-step="location"><div class="card-body">
        <h2 class="public-section-title">¿Dónde ocurre?</h2>
        <?php if($hasAssignedPark): ?>
          <div class="public-location-summary" data-location-summary><div><span>Ubicación</span><strong><?= htmlspecialchars((string)$assignment['park_name']) ?><?= !empty($assignment['area_name'])?' · '.htmlspecialchars((string)$assignment['area_name']):'' ?></strong></div><button class="btn btn-outline-secondary btn-sm" type="button" data-location-toggle>Reportar en otro lugar</button></div>
        <?php endif; ?>
        <div class="public-request-fields public-location-fields" data-location-fields data-assigned-park="<?= $defaultParkId ?>" data-assigned-area="<?= $defaultAreaId ?>" <?= $hasAssignedPark?'hidden':'' ?>>
          <div><label class="form-label" for="park_id">Parque o ubicación</label><select class="form-control" id="park_id" name="park_id"><option value="">No especificar</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $defaultParkId===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
          <div><label class="form-label" for="area_id">Área</label><select class="form-control" id="area_id" name="area_id"><option value="">No especificar</option><?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $defaultAreaId===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></div>
        </div>
      </div></section>
    </div>

    <section class="card public-request-card public-help-card" data-public-step="problem"><div class="card-body">
      <div class="public-help-grid">
        <div>
          <label class="form-label" for="requester_topic"><strong>¿En qué necesitas ayuda?</strong></label>
          <select class="form-control" id="requester_topic" name="requester_topic" required><option value="">Selecciona una opción</option><?php foreach($requesterTopicGroups as $group=>$topics): ?><optgroup label="<?= htmlspecialchars((string)$group) ?>"><?php foreach($topics as $topic): ?><option value="<?= htmlspecialchars((string)$topic['key'],ENT_QUOTES,'UTF-8') ?>" data-category-id="<?= (int)$topic['category_id'] ?>" data-category-help="<?= htmlspecialchars((string)$topic['help'],ENT_QUOTES,'UTF-8') ?>" data-category-placeholder="<?= htmlspecialchars((string)$topic['placeholder'],ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars((string)$topic['label']) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select>
          <p class="public-category-help" data-category-help-text aria-live="polite">Selecciona una opción y te mostraremos qué información puede ayudarnos.</p>
        </div>
        <div>
          <label class="form-label" for="description"><strong>Cuéntanos qué está pasando</strong></label>
          <textarea class="form-control public-description" id="description" name="description" rows="6" required placeholder="Describe qué ocurre, desde cuándo y qué estabas intentando hacer."></textarea>
          <p class="public-description-help">No necesitas usar palabras técnicas. Incluye mensajes de error o el equipo afectado si los conoces.</p>
        </div>
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
  const categoryId=document.getElementById('category_id');
  const category=document.getElementById('requester_topic');
  const categoryHelp=document.querySelector('[data-category-help-text]');
  const locationFields=document.querySelector('[data-location-fields]');
  const locationToggle=document.querySelector('[data-location-toggle]');
  const defaultPlaceholder='Describe qué ocurre, desde cuándo y qué estabas intentando hacer.';
  if(!form||!description||!subject)return;
  const buildSubject=()=>{const clean=(description.value||'').replace(/\s+/g,' ').trim();if(!clean){subject.value='';return;}let short=clean.split(/[.!?]\s/)[0]||clean;if(short.length<5)short=clean;subject.value=short.slice(0,180).trim();};
  const syncCategoryContext=()=>{if(!category)return;const option=category.options[category.selectedIndex];if(categoryId)categoryId.value=(option&&option.dataset.categoryId)||'';if(categoryHelp)categoryHelp.textContent=(option&&option.dataset.categoryHelp)||'Selecciona una opción y te mostraremos qué información puede ayudarnos.';description.placeholder=(option&&option.dataset.categoryPlaceholder)||defaultPlaceholder;};
  description.addEventListener('input',buildSubject);
  form.addEventListener('submit',()=>{syncCategoryContext();buildSubject();},{capture:true});
  if(category){category.addEventListener('change',syncCategoryContext);syncCategoryContext();}
  if(locationFields&&locationToggle){
    locationToggle.addEventListener('click',()=>{
      const opening=locationFields.hidden;locationFields.hidden=!opening;
      if(opening){locationToggle.textContent='Usar mi ubicación asignada';return;}
      const park=document.getElementById('park_id'),area=document.getElementById('area_id');
      if(park){park.value=locationFields.dataset.assignedPark||'';park.dispatchEvent(new Event('change',{bubbles:true}));}
      if(area){area.value=locationFields.dataset.assignedArea||'';area.dispatchEvent(new Event('change',{bubbles:true}));}
      locationToggle.textContent='Reportar en otro lugar';
    });
  }
})();
</script>
</body></html>