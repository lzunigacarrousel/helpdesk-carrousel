<?php
use App\Core\Csrf;
$pageTitle='Proveedores externos';$pageSection='Administración';$activeNav='externals';$helpContext='externals';
$typeLabels=['PROVIDER'=>'Proveedor','PARTNER'=>'Socio / aliado','OTHER'=>'Otro'];
$providerCount=count($users);$accessCount=count($access);$shareableCount=count($tickets);
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="external-admin-page">
<div class="mgmt-head external-admin-head"><div><span class="mgmt-kicker">Colaboración controlada</span><h1>Proveedores y casos compartidos</h1><p>Crea la cuenta una sola vez y comparte únicamente los tickets donde necesites participación externa. El proveedor entra por OTP y solo ve sus casos habilitados.</p></div></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="external-admin-kpis">
  <article><span>Proveedores</span><strong><?= $providerCount ?></strong><small>Cuentas externas registradas</small></article>
  <article><span>Casos compartidos</span><strong><?= $accessCount ?></strong><small>Accesos vigentes ahora</small></article>
  <article><span>Disponibles para compartir</span><strong><?= $shareableCount ?></strong><small>Tickets que puedes habilitar</small></article>
</section>

<section class="external-security external-security-compact"><strong>Qué puede hacer un externo</strong><span>Ver el caso que compartiste, responder y adjuntar evidencia según los permisos. No ve Centro de soporte, usuarios, auditoría, métricas ni otros tickets.</span></section>

<div class="external-layout external-ops-layout">
<section class="mgmt-card external-operation-card">
  <div class="mgmt-card-head"><div><span>Paso 1 · Solo si es nuevo</span><h2>Registrar proveedor</h2></div><small class="muted">La cuenta recibirá acceso por correo.</small></div>
  <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/crear" class="external-form" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
    <label>Proveedor / empresa<input class="form-control" name="organization_name" required placeholder="Ej. Proveedor de Internet"></label>
    <label>Tipo<select class="form-control" name="external_type"><option value="PROVIDER">Proveedor</option><option value="PARTNER">Socio / aliado</option><option value="OTHER">Otro</option></select></label>
    <label>Nombre del contacto<input class="form-control" name="name" required placeholder="Nombre completo"></label>
    <label>Correo<input class="form-control" type="email" name="email" required placeholder="correo@proveedor.com"></label>
    <label>Teléfono<input class="form-control" name="phone" placeholder="Opcional"></label>
    <label>Servicio / referencia<input class="form-control" name="notes" placeholder="Ej. Internet, CCTV, soporte POS..."></label>
    <div class="external-full"><button class="btn btn-primary">Crear proveedor</button></div>
  </form>
</section>

<section class="mgmt-card external-operation-card external-share-card">
  <div class="mgmt-card-head"><div><span>Paso 2 · Trabajo diario</span><h2>Compartir un caso</h2></div><small class="muted">El acceso se puede revocar cuando termine el apoyo.</small></div>
  <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/asignar" class="external-form" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
    <label class="external-full">Proveedor<select class="form-control" name="user_id" required><option value="">Selecciona proveedor</option><?php foreach($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars(($u['organization_name']?:$u['full_name']).' · '.$u['full_name']) ?></option><?php endforeach; ?></select></label>
    <label class="external-full">Caso<select class="form-control" name="ticket_id" required><option value="">Selecciona un ticket</option><?php foreach($tickets as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['ticket_number'].' · '.$t['subject'].($t['park_name']?' · '.$t['park_name']:'')) ?></option><?php endforeach; ?></select></label>
    <div class="external-permission-box external-full"><strong>Permisos dentro de este caso</strong><label class="external-check"><input type="checkbox" name="can_comment" value="1" checked> Puede responder a Carrousel</label><label class="external-check"><input type="checkbox" name="can_upload" value="1" checked> Puede adjuntar evidencias</label></div>
    <div class="external-full"><button class="btn btn-primary" <?= !$users||!$tickets?'disabled':'' ?>>Compartir caso</button><?php if(!$users): ?><small class="external-form-note">Primero registra un proveedor.</small><?php elseif(!$tickets): ?><small class="external-form-note">No hay tickets disponibles para compartir.</small><?php endif; ?></div>
  </form>
</section>
</div>

<section class="mgmt-card external-directory-card" data-external-directory>
  <div class="mgmt-card-head external-directory-head"><div><span>Directorio</span><h2>Proveedores registrados</h2></div><div class="external-directory-tools"><input class="form-control" type="search" placeholder="Buscar empresa, contacto o correo" data-external-search><b><?= $providerCount ?></b></div></div>
  <div class="external-users">
    <?php foreach($users as $u): $searchText=mb_strtolower(trim(($u['organization_name']??'').' '.($u['full_name']??'').' '.($u['email']??'').' '.($u['phone']??''))); ?>
      <article data-external-user data-search="<?= htmlspecialchars($searchText) ?>"><div class="external-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['organization_name']?:$u['full_name'],0,1))) ?></div><div><strong><?= htmlspecialchars($u['organization_name']?:$u['full_name']) ?></strong><span><?= htmlspecialchars($u['full_name']) ?></span><small><?= htmlspecialchars($u['email']) ?><?= !empty($u['phone'])?' · '.htmlspecialchars($u['phone']):'' ?></small><div class="external-user-meta"><span><?= htmlspecialchars($typeLabels[$u['external_type']]??'Externo') ?></span><span><?= (int)$u['active_cases'] ?> caso(s) activo(s)</span></div></div></article>
    <?php endforeach; ?>
    <?php if(!$users): ?><div class="empty-state">Todavía no hay proveedores registrados.</div><?php endif; ?>
    <div class="empty-state" data-external-empty hidden>No encontramos proveedores con esa búsqueda.</div>
  </div>
</section>

<section class="mgmt-card external-access-card">
  <div class="mgmt-card-head"><div><span>Control de acceso</span><h2>Casos compartidos actualmente</h2></div><b><?= $accessCount ?></b></div>
  <?php if($access): ?><div class="external-access-list">
    <?php foreach($access as $x): ?><article class="external-access-row"><div class="external-access-case"><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$x['ticket_id'] ?>"><strong><?= htmlspecialchars($x['ticket_number']) ?></strong></a><span><?= htmlspecialchars($x['subject']) ?></span></div><div><span>Proveedor</span><strong><?= htmlspecialchars($x['organization_name']??$x['full_name']) ?></strong><small><?= htmlspecialchars($x['full_name']) ?></small></div><div><span>Permisos</span><strong><?= $x['can_comment']?'Responder':'' ?><?= $x['can_comment']&&$x['can_upload']?' · ':'' ?><?= $x['can_upload']?'Adjuntar':'' ?></strong><small>Desde <?= htmlspecialchars(date('d/m/Y H:i',strtotime($x['granted_at']))) ?></small></div><form method="post" action="<?= APP_BASE_URL ?>/admin/externos/revocar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$x['ticket_id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$x['user_id'] ?>"><button class="btn btn-outline-secondary btn-sm">Revocar acceso</button></form></article><?php endforeach; ?>
  </div><?php else: ?><div class="empty-state">No hay casos compartidos con proveedores en este momento.</div><?php endif; ?>
</section>
</div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){const root=document.querySelector('[data-external-directory]');if(!root)return;const q=root.querySelector('[data-external-search]');const rows=[...root.querySelectorAll('[data-external-user]')];const empty=root.querySelector('[data-external-empty]');const norm=v=>String(v||'').toLocaleLowerCase('es-GT').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();q?.addEventListener('input',()=>{const term=norm(q.value);let visible=0;rows.forEach(r=>{const show=!term||norm(r.dataset.search).includes(term);r.hidden=!show;if(show)visible++});if(empty)empty.hidden=visible!==0;});})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>