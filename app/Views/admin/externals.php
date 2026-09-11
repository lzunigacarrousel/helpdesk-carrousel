<?php
use App\Core\Csrf;
$pageTitle='Proveedores externos';$pageSection='Administración';$activeNav='externals';$helpContext='externals';
$typeLabels=['PROVIDER'=>'Proveedor','PARTNER'=>'Socio / aliado','OTHER'=>'Otro'];
$statusLabels=['ACTIVE'=>'Activo','PENDING'=>'Pendiente','BLOCKED'=>'Bloqueado','DISABLED'=>'Desactivado'];
$shareUsers=$shareUsers??array_values(array_filter($users,static fn(array $row):bool=>($row['status']??'')==='ACTIVE'));
$providerCount=count($users);$activeProviderCount=count($shareUsers);$accessCount=count($access);$shareableCount=count($tickets);
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-admin-page{display:grid;gap:12px}.external-admin-page>.mgmt-head,.external-admin-page>.external-admin-kpis,.external-admin-page>.mgmt-card{margin-bottom:0}.external-provider-create{margin:0}.external-provider-create>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 16px;font-weight:850}.external-provider-create>summary::-webkit-details-marker{display:none}.external-provider-create>summary:after{content:'+';display:grid;place-items:center;width:28px;height:28px;border:1px solid var(--border);border-radius:8px;color:var(--brand);font-size:18px}.external-provider-create[open]>summary:after{content:'−'}.external-provider-create[open]>summary{border-bottom:1px solid var(--border)}.external-share-primary{height:auto!important;min-height:0!important;border:1px solid color-mix(in srgb,var(--brand) 28%,var(--border) 72%)}.external-share-primary .external-form{grid-template-columns:1fr 1fr}.external-share-primary .mgmt-card-head{align-items:center}.external-directory-tools{display:flex;align-items:center;gap:8px}.external-directory-tools .form-control{min-width:280px}.external-provider-name strong{display:block}.external-provider-name small{display:block}.external-admin-kpis article small{display:none}.external-provider-actions details{min-width:240px}.external-provider-actions summary{cursor:pointer;color:var(--brand);font-weight:850}.external-provider-edit-form{display:grid;gap:7px;padding:9px 0}.external-provider-edit-form label{font-size:10.5px;font-weight:800;color:var(--muted)}.external-provider-edit-form .form-control{margin-top:3px}.external-provider-origin{font-size:10.5px;color:var(--muted)}.external-provider-disabled{opacity:.72}.external-provider-danger{margin-top:7px;padding-top:7px;border-top:1px solid var(--border)}@media(max-width:760px){.external-share-primary .external-form{grid-template-columns:1fr}.external-share-primary .external-full{grid-column:1}.external-directory-tools{width:100%}.external-directory-tools .form-control{min-width:0;width:100%}}
</style>
<div class="external-admin-page">
<div class="mgmt-head external-admin-head"><div><span class="mgmt-kicker">Colaboración externa</span><h1>Proveedores</h1></div><div class="mgmt-head-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe">Ver historial</a></div></div>
<?php if(!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flash['type']??'info')) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="external-admin-kpis">
  <article><span>Proveedores activos</span><strong><?= $activeProviderCount ?></strong></article>
  <article><span>Casos con proveedor</span><strong><?= $accessCount ?></strong></article>
  <article><span>Casos disponibles</span><strong><?= $shareableCount ?></strong></article>
</section>

<section class="mgmt-card external-operation-card external-share-card external-share-primary">
  <div class="mgmt-card-head"><div><span>Trabajo diario</span><h2>Compartir un caso</h2></div></div>
  <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/asignar" class="external-form" data-single-submit>
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
    <label>Proveedor<select class="form-control" name="user_id" required><option value="">Selecciona proveedor</option><?php foreach($shareUsers as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars(($u['organization_name']?:$u['full_name']).' · '.$u['full_name']) ?></option><?php endforeach; ?></select></label>
    <label>Caso<select class="form-control" name="ticket_id" required><option value="">Selecciona un ticket</option><?php foreach($tickets as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['ticket_number'].' · '.$t['subject'].($t['park_name']?' · '.$t['park_name']:'')) ?></option><?php endforeach; ?></select></label>
    <div class="external-permission-box external-full"><strong>Permisos</strong><label class="external-check"><input type="checkbox" name="can_comment" value="1" checked> Puede responder</label><label class="external-check"><input type="checkbox" name="can_upload" value="1" checked> Puede adjuntar evidencias</label></div>
    <div class="external-full"><button class="btn btn-primary" <?= !$shareUsers||!$tickets?'disabled':'' ?>>Compartir caso</button><?php if(!$shareUsers): ?><small class="external-form-note">Primero registra o activa un proveedor.</small><?php elseif(!$tickets): ?><small class="external-form-note">No hay tickets disponibles para compartir.</small><?php endif; ?></div>
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
  <div class="data-table-toolbar"><div><strong>Proveedores registrados</strong><span class="data-table-muted"><?= $providerCount ?> cuenta<?= $providerCount===1?'':'s' ?> · <?= $activeProviderCount ?> activa<?= $activeProviderCount===1?'':'s' ?></span></div><div class="external-directory-tools"><input class="form-control" type="search" placeholder="Buscar empresa, contacto o correo" data-external-search></div></div>
  <div class="data-table-wrap"><table class="data-table">
    <thead><tr><th>Proveedor</th><th>Tipo</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Origen</th><th>Casos activos</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach($users as $u):
      $searchText=mb_strtolower(trim(($u['organization_name']??'').' '.($u['full_name']??'').' '.($u['email']??'').' '.($u['phone']??'').' '.($statusLabels[$u['status']]??'')));
      $converted=!empty($u['converted_from_internal']);
    ?>
      <tr data-external-user data-search="<?= htmlspecialchars($searchText) ?>" class="<?= ($u['status']??'')==='DISABLED'?'external-provider-disabled':'' ?>">
        <td data-label="Proveedor" class="external-provider-name"><strong><?= htmlspecialchars($u['organization_name']?:$u['full_name']) ?></strong></td>
        <td data-label="Tipo"><?= htmlspecialchars($typeLabels[$u['external_type']]??'Externo') ?></td>
        <td data-label="Contacto"><?= htmlspecialchars($u['full_name']) ?></td>
        <td data-label="Correo"><?= htmlspecialchars($u['email']) ?></td>
        <td data-label="Teléfono"><?= !empty($u['phone'])?htmlspecialchars($u['phone']):'—' ?></td>
        <td data-label="Estado"><span class="badge"><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span></td>
        <td data-label="Origen"><span class="external-provider-origin"><?= $converted?'Convertido desde usuario interno':'Creado como proveedor' ?></span></td>
        <td data-label="Casos activos" class="data-table-number"><?= (int)$u['active_cases'] ?></td>
        <td data-label="Acciones" class="external-provider-actions">
          <details><summary>Editar</summary>
            <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/actualizar" class="external-provider-edit-form" data-single-submit>
              <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <label>Proveedor / empresa<input class="form-control" name="organization_name" required value="<?= htmlspecialchars((string)($u['organization_name']??'')) ?>"></label>
              <label>Tipo<select class="form-control" name="external_type"><?php foreach($typeLabels as $code=>$label): ?><option value="<?= $code ?>" <?= ($u['external_type']??'PROVIDER')===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
              <label>Contacto<input class="form-control" name="name" required value="<?= htmlspecialchars($u['full_name']) ?>"></label>
              <label>Correo<input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($u['email']) ?>"></label>
              <label>Teléfono<input class="form-control" name="phone" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>" placeholder="Opcional"></label>
              <label>Servicio / referencia<input class="form-control" name="notes" value="<?= htmlspecialchars((string)($u['notes']??'')) ?>" placeholder="Ej. Internet, CCTV, POS..."></label>
              <button class="btn btn-primary btn-sm" type="submit">Guardar cambios</button>
            </form>
            <?php if(($u['status']??'')!=='DISABLED'): ?><div class="external-provider-danger">
              <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/desactivar" data-single-submit>
                <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="btn btn-outline-secondary btn-sm" type="submit">Desactivar proveedor</button>
              </form>
              <small class="external-form-note">Desactivar revoca todos sus casos compartidos y sesiones vigentes.</small>
            </div><?php else: ?><small class="external-form-note">Proveedor desactivado. Ya no puede recibir casos nuevos.</small><?php endif; ?>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$users): ?><tr><td class="data-table-empty" data-label="" colspan="9">Todavía no hay proveedores registrados.</td></tr><?php endif; ?>
    <tr data-external-empty hidden><td class="data-table-empty" data-label="" colspan="9">No encontramos proveedores con esa búsqueda.</td></tr>
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