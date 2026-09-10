<?php
use App\Core\Csrf;
$pageTitle='Proveedores externos';$pageSection='Administración';$activeNav='externals';$helpContext='externals';
$typeLabels=['PROVIDER'=>'Proveedor','PARTNER'=>'Socio / aliado','OTHER'=>'Otro'];
$providerCount=count($users);$accessCount=count($access);$shareableCount=count($tickets);
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-admin-page{display:grid;gap:12px}.external-admin-page>.mgmt-head,.external-admin-page>.external-admin-kpis,.external-admin-page>.mgmt-card{margin-bottom:0}.external-provider-create{margin:0}.external-provider-create>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 16px;font-weight:850}.external-provider-create>summary::-webkit-details-marker{display:none}.external-provider-create>summary:after{content:'+';display:grid;place-items:center;width:28px;height:28px;border:1px solid var(--border);border-radius:8px;color:var(--brand);font-size:18px}.external-provider-create[open]>summary:after{content:'−'}.external-provider-create[open]>summary{border-bottom:1px solid var(--border)}.external-share-primary{height:auto!important;min-height:0!important;border:1px solid color-mix(in srgb,var(--brand) 28%,var(--border) 72%)}.external-share-primary .external-form{grid-template-columns:1fr 1fr}.external-share-primary .mgmt-card-head{align-items:center}.external-directory-tools{display:flex;align-items:center;gap:8px}.external-directory-tools .form-control{min-width:280px}.external-provider-name strong{display:block}.external-provider-name small{display:block}.external-admin-kpis article small{display:none}@media(max-width:760px){.external-share-primary .external-form{grid-template-columns:1fr}.external-share-primary .external-full{grid-column:1}.external-directory-tools{width:100%}.external-directory-tools .form-control{min-width:0;width:100%}}
</style>
<div class="external-admin-page">
<div class="mgmt-head external-admin-head"><div><span class="mgmt-kicker">Colaboración externa</span><h1>Proveedores</h1></div><div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe">Ver historial</a></div></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="external-admin-kpis">
  <article><span>Proveedores</span><strong><?= $providerCount ?></strong></article>
  <article><span>Casos con proveedor</span><strong><?= $accessCount ?></strong></article>
  <article><span>Casos disponibles</span><strong><?= $shareableCount ?></strong></article>
</section>

<section class="mgmt-card external-operation-card external-share-card external-share-primary">
  <div class="mgmt-card-head"><div><span>Trabajo diario</span><h2>Compartir un caso</h2></div></div>
  <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/asignar" class="external-form" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
    <label>Proveedor<select class="form-control" name="user_id" required><option value="">Selecciona proveedor</option><?php foreach($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars(($u['organization_name']?:$u['full_name']).' · '.$u['full_name']) ?></option><?php endforeach; ?></select></label>
    <label>Caso<select class="form-control" name="ticket_id" required><option value="">Selecciona un ticket</option><?php foreach($tickets as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['ticket_number'].' · '.$t['subject'].($t['park_name']?' · '.$t['park_name']:'')) ?></option><?php endforeach; ?></select></label>
    <div class="external-permission-box external-full"><strong>Permisos</strong><label class="external-check"><input type="checkbox" name="can_comment" value="1" checked> Puede responder</label><label class="external-check"><input type="checkbox" name="can_upload" value="1" checked> Puede adjuntar evidencias</label></div>
    <div class="external-full"><button class="btn btn-primary" <?= !$users||!$tickets?'disabled':'' ?>>Compartir caso</button><?php if(!$users): ?><small class="external-form-note">Primero registra un proveedor.</small><?php elseif(!$tickets): ?><small class="external-form-note">No hay tickets disponibles para compartir.</small><?php endif; ?></div>
  </form>
</section>

<details class="mgmt-card external-provider-create">
  <summary>Registrar nuevo proveedor</summary>
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
</details>

<section class="data-table-shell" data-external-directory>
  <div class="data-table-toolbar"><div><strong>Proveedores registrados</strong><span class="data-table-muted"><?= $providerCount ?> cuenta<?= $providerCount===1?'':'s' ?></span></div><div class="external-directory-tools"><input class="form-control" type="search" placeholder="Buscar empresa, contacto o correo" data-external-search></div></div>
  <div class="data-table-wrap"><table class="data-table">
    <thead><tr><th>Proveedor</th><th>Tipo</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Casos activos</th></tr></thead>
    <tbody>
    <?php foreach($users as $u): $searchText=mb_strtolower(trim(($u['organization_name']??'').' '.($u['full_name']??'').' '.($u['email']??'').' '.($u['phone']??''))); ?>
      <tr data-external-user data-search="<?= htmlspecialchars($searchText) ?>">
        <td data-label="Proveedor" class="external-provider-name"><strong><?= htmlspecialchars($u['organization_name']?:$u['full_name']) ?></strong></td>
        <td data-label="Tipo"><?= htmlspecialchars($typeLabels[$u['external_type']]??'Externo') ?></td>
        <td data-label="Contacto"><?= htmlspecialchars($u['full_name']) ?></td>
        <td data-label="Correo"><?= htmlspecialchars($u['email']) ?></td>
        <td data-label="Teléfono"><?= !empty($u['phone'])?htmlspecialchars($u['phone']):'—' ?></td>
        <td data-label="Casos activos" class="data-table-number"><?= (int)$u['active_cases'] ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$users): ?><tr><td class="data-table-empty" data-label="" colspan="6">Todavía no hay proveedores registrados.</td></tr><?php endif; ?>
    <tr data-external-empty hidden><td class="data-table-empty" data-label="" colspan="6">No encontramos proveedores con esa búsqueda.</td></tr>
    </tbody>
  </table></div>
</section>

<section class="data-table-shell external-access-card">
  <div class="data-table-toolbar"><div><strong>Casos compartidos</strong><span class="data-table-muted"><?= $accessCount ?> acceso<?= $accessCount===1?'':'s' ?> vigente<?= $accessCount===1?'':'s' ?></span></div></div>
  <div class="data-table-wrap"><table class="data-table">
    <thead><tr><th>Ticket</th><th>Proveedor</th><th>Permisos</th><th>Desde</th><th>Acción</th></tr></thead>
    <tbody>
    <?php foreach($access as $x): ?>
      <tr>
        <td data-label="Ticket"><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$x['ticket_id'] ?>"><strong><?= htmlspecialchars($x['ticket_number']) ?></strong></a><small><?= htmlspecialchars($x['subject']) ?></small></td>
        <td data-label="Proveedor"><strong><?= htmlspecialchars($x['organization_name']??$x['full_name']) ?></strong><small><?= htmlspecialchars($x['full_name']) ?></small></td>
        <td data-label="Permisos"><?= $x['can_comment']?'Responder':'' ?><?= $x['can_comment']&&$x['can_upload']?' · ':'' ?><?= $x['can_upload']?'Adjuntar':'' ?></td>
        <td data-label="Desde" class="data-table-nowrap"><?= htmlspecialchars(date('d/m/Y H:i',strtotime($x['granted_at']))) ?></td>
        <td data-label="Acción"><div class="data-table-actions"><form method="post" action="<?= APP_BASE_URL ?>/admin/externos/revocar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$x['ticket_id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$x['user_id'] ?>"><button class="btn btn-outline-secondary btn-sm">Revocar acceso</button></form></div></td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$access): ?><tr><td class="data-table-empty" data-label="" colspan="5">No hay casos compartidos con proveedores.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
</div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){const root=document.querySelector('[data-external-directory]');if(!root)return;const q=root.querySelector('[data-external-search]');const rows=[...root.querySelectorAll('[data-external-user]')];const empty=root.querySelector('[data-external-empty]');const norm=v=>String(v||'').toLocaleLowerCase('es-GT').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();q?.addEventListener('input',()=>{const term=norm(q.value);let visible=0;rows.forEach(r=>{const show=!term||norm(r.dataset.search).includes(term);r.hidden=!show;if(show)visible++});if(empty)empty.hidden=visible!==0;});})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>