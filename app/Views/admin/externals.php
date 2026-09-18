<?php
use App\Core\{Auth,Csrf,Database};
$pageTitle='Proveedores externos';$pageSection='Administración';$activeNav='externals';$helpContext='externals';
$typeLabels=['PROVIDER'=>'Proveedor','PARTNER'=>'Socio / aliado','OTHER'=>'Otro'];
$statusLabels=['ACTIVE'=>'Activo','PENDING'=>'Pendiente','BLOCKED'=>'Bloqueado','DISABLED'=>'Desactivado'];
$templateLabels=[
  'GENERAL_SUPPORT'=>'Soporte general',
  'SOFTWARE_SUPPORT'=>'Soporte de software',
  'SOFTWARE_DEVELOPMENT'=>'Desarrollo de software',
  'AUDIT_ADVISORY'=>'Auditoría / asesoría',
];
$templateByUser=[];$templateByAccess=[];
try{
  $pdo=Database::pdo();
  foreach($pdo->query("SELECT user_id,COALESCE(report_template,'GENERAL_SUPPORT') report_template FROM external_profiles")->fetchAll() as $row){$templateByUser[(int)$row['user_id']]=$row['report_template'];}
  foreach($pdo->query("SELECT ticket_id,user_id,COALESCE(report_template,'GENERAL_SUPPORT') report_template FROM external_ticket_access WHERE revoked_at IS NULL")->fetchAll() as $row){$templateByAccess[(int)$row['ticket_id'].':'.(int)$row['user_id']]=$row['report_template'];}
}catch(\Throwable $e){}
$shareUsers=$shareUsers??array_values(array_filter($users,static fn(array $row):bool=>($row['status']??'')==='ACTIVE'));
$canConvertToInternal=Auth::can('users.manage');$isFullAdmin=Auth::role()==='ADMIN';
$providerCount=count($users);$activeProviderCount=count($shareUsers);$accessCount=count($access);$shareableCount=count($tickets);
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-admin-page{display:grid;gap:12px}.external-admin-page>.mgmt-head,.external-admin-page>.external-admin-kpis,.external-admin-page>.mgmt-card{margin-bottom:0}
.external-provider-create{margin:0}.external-provider-create>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 16px;font-weight:850}.external-provider-create>summary::-webkit-details-marker{display:none}.external-provider-create>summary:after{content:'+';display:grid;place-items:center;width:28px;height:28px;border:1px solid var(--border);border-radius:8px;color:var(--brand);font-size:18px}.external-provider-create[open]>summary:after{content:'−'}.external-provider-create[open]>summary{border-bottom:1px solid var(--border)}
.external-share-primary{height:auto!important;min-height:0!important;border:1px solid color-mix(in srgb,var(--brand) 28%,var(--border) 72%)}.external-share-primary .external-form{grid-template-columns:repeat(3,minmax(0,1fr))}.external-share-primary .mgmt-card-head{align-items:center}
.external-directory-tools{display:flex;align-items:center;gap:8px}.external-directory-tools .form-control{min-width:280px}.external-provider-name strong{display:block}.external-provider-name small{display:block;margin-top:3px}.external-admin-kpis article small{display:none}.external-provider-origin{font-size:10.5px;color:var(--muted)}.external-provider-disabled{opacity:.72}.external-provider-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}.external-provider-actions .btn{white-space:nowrap}.external-provider-edit-toggle{white-space:nowrap}
.external-provider-edit-row[hidden]{display:none!important}.external-provider-edit-row>td{padding:0 10px 14px!important;background:transparent!important}.external-provider-edit-shell{padding:16px;border:1px solid var(--border);border-radius:14px;background:var(--card);box-shadow:var(--shadow-sm)}.external-provider-edit-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid var(--border)}.external-provider-edit-head strong{display:block;font-size:16px;color:var(--brand-dark)}.external-provider-edit-head small{display:block;margin-top:2px;color:var(--muted)}.external-provider-edit-panel{padding:0}
.external-provider-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 12px}.external-provider-edit-grid label{font-size:10.5px;font-weight:800;color:var(--muted)}.external-provider-edit-grid .form-control{margin-top:3px}.external-provider-edit-grid .external-full{grid-column:1/-1}.external-provider-edit-grid .external-save{grid-column:1/-1;display:flex;justify-content:flex-end}.external-provider-edit-grid .external-save .btn{min-width:220px}
.external-template-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end;margin-top:12px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--surface-soft)}.external-template-form label{font-size:10.5px;font-weight:800;color:var(--muted)}.external-template-form small{display:block;margin-top:4px;color:var(--muted);font-weight:500}.external-template-pill{display:inline-flex;padding:3px 7px;border-radius:999px;background:var(--surface-soft);border:1px solid var(--border);font-size:10px;color:var(--muted)}
.external-provider-danger{margin-top:10px;padding-top:8px;border-top:1px solid var(--border)}.external-convert-internal{margin-top:10px;padding-top:8px;border-top:1px solid var(--border)}.external-convert-internal>summary{cursor:pointer;color:var(--brand);font-weight:850}.external-convert-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 10px;margin-top:8px}.external-convert-grid .external-full{grid-column:1/-1}.external-convert-grid small{display:block;margin-top:3px;color:var(--muted);font-size:10px}
@media(max-width:1180px){.external-share-primary .external-form{grid-template-columns:repeat(2,minmax(0,1fr))}.external-provider-edit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.external-provider-edit-grid .external-full{grid-column:1/-1}}
@media(max-width:760px){.external-share-primary .external-form{grid-template-columns:1fr}.external-share-primary .external-full{grid-column:1}.external-directory-tools{width:100%}.external-directory-tools .form-control{min-width:0;width:100%}.external-provider-edit-row>td{display:block!important;width:100%!important;padding:8px 0 14px!important}.external-provider-edit-shell{padding:14px}.external-provider-edit-head{align-items:flex-start;flex-direction:column}.external-provider-edit-head .btn{width:100%}.external-provider-edit-grid{grid-template-columns:1fr}.external-provider-edit-grid .external-full,.external-provider-edit-grid .external-save{grid-column:auto}.external-provider-edit-grid .external-save .btn{width:100%;min-width:0}.external-template-form{grid-template-columns:1fr}.external-template-form .btn{width:100%}.external-convert-grid{grid-template-columns:1fr}}
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
    <label>Proveedor<select class="form-control" name="user_id" required><option value="">Selecciona proveedor</option><?php foreach($shareUsers as $u): ?><option value="<?= (int)$u['id'] ?>" data-default-template="<?= htmlspecialchars($templateByUser[(int)$u['id']]??'GENERAL_SUPPORT') ?>"><?= htmlspecialchars(($u['organization_name']?:$u['full_name']).' · '.$u['full_name']) ?></option><?php endforeach; ?></select></label>
    <label>Caso<select class="form-control" name="ticket_id" required><option value="">Selecciona un ticket</option><?php foreach($tickets as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['ticket_number'].' · '.$t['subject'].($t['park_name']?' · '.$t['park_name']:'')) ?></option><?php endforeach; ?></select></label>
    <label>Tipo de documentación<select class="form-control" name="report_template"><option value="">Usar la predeterminada del proveedor</option><?php foreach($templateLabels as $code=>$label): ?><option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><small class="external-form-note">Puedes cambiarla para este caso sin modificar la plantilla habitual del proveedor.</small></label>
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
    <label>Servicio / referencia<input class="form-control" name="notes" placeholder="Ej. soporte de software, desarrollo, asesoría..."></label>
    <div class="external-full"><small class="external-form-note">Los proveedores nuevos usan Soporte general por defecto. Puedes asignar su plantilla de documentación desde Editar.</small></div>
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
      $converted=!empty($u['converted_from_internal']);$currentTemplate=$templateByUser[(int)$u['id']]??'GENERAL_SUPPORT';
    ?>
      <tr id="external-provider-<?= (int)$u['id'] ?>" data-external-user data-search="<?= htmlspecialchars($searchText) ?>" class="<?= ($u['status']??'')==='DISABLED'?'external-provider-disabled':'' ?>">
        <td data-label="Proveedor" class="external-provider-name"><strong><?= htmlspecialchars($u['organization_name']?:$u['full_name']) ?></strong><small class="external-template-pill"><?= htmlspecialchars($templateLabels[$currentTemplate]??'Soporte general') ?></small></td>
        <td data-label="Tipo"><?= htmlspecialchars($typeLabels[$u['external_type']]??'Externo') ?></td>
        <td data-label="Contacto"><?= htmlspecialchars($u['full_name']) ?></td>
        <td data-label="Correo"><?= htmlspecialchars($u['email']) ?></td>
        <td data-label="Teléfono"><?= !empty($u['phone'])?htmlspecialchars($u['phone']):'—' ?></td>
        <td data-label="Estado"><span class="badge"><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span></td>
        <td data-label="Origen"><span class="external-provider-origin"><?= $converted?'Convertido desde usuario interno':'Creado como proveedor' ?></span></td>
        <td data-label="Casos activos" class="data-table-number"><?= (int)$u['active_cases'] ?></td>
        <td data-label="Acciones"><div class="external-provider-actions"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe?provider=<?= (int)$u['id'] ?>">Historial</a><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/externos/informe/exportar?provider=<?= (int)$u['id'] ?>" data-no-loading="1">Excel</a><button class="btn btn-outline-secondary external-provider-edit-toggle" type="button" data-external-edit-toggle aria-expanded="false" aria-controls="external-edit-<?= (int)$u['id'] ?>">Editar</button></div></td>
      </tr>
      <tr class="external-provider-edit-row" data-external-edit-row hidden id="external-edit-<?= (int)$u['id'] ?>">
        <td colspan="9">
          <div class="external-provider-edit-shell" data-external-edit-panel>
            <div class="external-provider-edit-head">
              <div><strong>Editar proveedor · <?= htmlspecialchars($u['organization_name']?:$u['full_name']) ?></strong><small><?= htmlspecialchars($u['email']) ?></small></div>
              <button class="btn btn-outline-secondary" type="button" data-external-edit-close>Cancelar edición</button>
            </div>
            <div class="external-provider-edit-panel">
              <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/actualizar" class="external-provider-edit-grid" data-single-submit>
                <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <label>Proveedor / empresa<input class="form-control" name="organization_name" required value="<?= htmlspecialchars((string)($u['organization_name']??'')) ?>"></label>
                <label>Tipo<select class="form-control" name="external_type"><?php foreach($typeLabels as $code=>$label): ?><option value="<?= $code ?>" <?= ($u['external_type']??'PROVIDER')===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
                <label>Contacto<input class="form-control" name="name" required value="<?= htmlspecialchars($u['full_name']) ?>"></label>
                <label>Correo<input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($u['email']) ?>"></label>
                <label>Teléfono<input class="form-control" name="phone" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>" placeholder="Opcional"></label>
                <label>Servicio / referencia<input class="form-control" name="notes" value="<?= htmlspecialchars((string)($u['notes']??'')) ?>" placeholder="Ej. soporte, desarrollo, asesoría..."></label>
                <div class="external-save"><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
              </form>

              <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/plantilla" class="external-template-form" data-single-submit>
                <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <label>Plantilla predeterminada de documentación<select class="form-control" name="report_template" required><?php foreach($templateLabels as $code=>$label): ?><option value="<?= $code ?>" <?= $currentTemplate===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><small>Define qué información se solicita normalmente a este proveedor. Puedes sobrescribirla al compartir un caso.</small></label>
                <button class="btn btn-outline-secondary" type="submit">Guardar plantilla</button>
              </form>

              <?php if($canConvertToInternal): ?>
              <details class="external-convert-internal"><summary>Convertir a usuario interno</summary>
                <form method="post" action="<?= APP_BASE_URL ?>/admin/externos/convertir-interno" class="external-convert-grid" data-single-submit>
                  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <div class="external-full"><small>Conserva la misma identidad e historial. Se revocarán sus casos compartidos como proveedor y deberá iniciar sesión nuevamente.</small></div>
                  <label>Perfil interno<select class="form-control" name="role_id" required><option value="">Selecciona</option><?php foreach(($internalRoles??[]) as $r): if(!$isFullAdmin&&($r['code']??'')==='ADMIN')continue; ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></label>
                  <label>Estado<select class="form-control" name="status"><option value="ACTIVE">Activo</option><option value="PENDING">Pendiente</option><option value="BLOCKED">Bloqueado</option><option value="DISABLED">Deshabilitado</option></select></label>
                  <label>Puesto o función<select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach(($positions??[]) as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
                  <label>Dónde trabaja<select class="form-control" name="assignment_type" required><option value="">Selecciona</option><option value="PARK">Parque / ubicación</option><option value="CORPORATE">Área corporativa</option><option value="OTHER">Otro</option></select></label>
                  <label>Parque<select class="form-control" name="park_id"><option value="">No aplica</option><?php foreach(($parks??[]) as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
                  <label>Área corporativa<select class="form-control" name="area_id"><option value="">No aplica</option><?php foreach(($areas??[]) as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
                  <label>Región <span class="optional">Opcional</span><select class="form-control" name="region_id"><option value="">No aplica / automática por parque</option><?php foreach(($regions??[]) as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></label>
                  <label>Responsable directo <span class="optional">Opcional</span><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach(($managers??[]) as $m): if((int)$m['id']===(int)$u['id'])continue; ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select><small>Referencia organizacional: persona a la que reporta. No asigna tickets y no cambia permisos ni alcance.</small></label>
                  <label class="external-full"><input type="checkbox" name="confirm_convert_internal" value="1" required> Confirmo que esta cuenta dejará de ser proveedor externo y volverá a tener acceso interno según el perfil seleccionado.</label>
                  <div class="external-full"><button class="btn btn-primary btn-sm" type="submit">Convertir a usuario interno</button></div>
                </form>
              </details>
              <?php endif; ?>

              <?php if(($u['status']??'')!=='DISABLED'): ?><div class="external-provider-danger"><form method="post" action="<?= APP_BASE_URL ?>/admin/externos/desactivar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Desactivar proveedor</button></form><small class="external-form-note">Desactivar revoca todos sus casos compartidos y sesiones vigentes.</small></div><?php else: ?><small class="external-form-note">Proveedor desactivado. Ya no puede recibir casos nuevos.</small><?php endif; ?>
            </div>
          </div>
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
    <thead><tr><th>Ticket</th><th>Proveedor</th><th>Documentación</th><th>Permisos</th><th>Desde</th><th>Acción</th></tr></thead>
    <tbody>
    <?php foreach($access as $x): $accessTemplate=$templateByAccess[(int)$x['ticket_id'].':'.(int)$x['user_id']]??($templateByUser[(int)$x['user_id']]??'GENERAL_SUPPORT'); ?>
      <tr>
        <td data-label="Ticket"><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$x['ticket_id'] ?>"><strong><?= htmlspecialchars($x['ticket_number']) ?></strong></a><small><?= htmlspecialchars($x['subject']) ?></small></td>
        <td data-label="Proveedor"><strong><?= htmlspecialchars($x['organization_name']??$x['full_name']) ?></strong><small><?= htmlspecialchars($x['full_name']) ?></small></td>
        <td data-label="Documentación"><span class="external-template-pill"><?= htmlspecialchars($templateLabels[$accessTemplate]??'Soporte general') ?></span></td>
        <td data-label="Permisos"><?= $x['can_comment']?'Responder':'' ?><?= $x['can_comment']&&$x['can_upload']?' · ':'' ?><?= $x['can_upload']?'Adjuntar':'' ?></td>
        <td data-label="Desde" class="data-table-nowrap"><?= htmlspecialchars(date('d/m/Y H:i',strtotime($x['granted_at']))) ?></td>
        <td data-label="Acción"><div class="data-table-actions"><form method="post" action="<?= APP_BASE_URL ?>/admin/externos/revocar" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$x['ticket_id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$x['user_id'] ?>"><button class="btn btn-outline-secondary btn-sm">Revocar acceso</button></form></div></td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$access): ?><tr><td class="data-table-empty" data-label="" colspan="6">No hay casos compartidos con proveedores.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
</div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const root=document.querySelector('[data-external-directory]');
  if(!root)return;
  const q=root.querySelector('[data-external-search]');
  const rows=[...root.querySelectorAll('[data-external-user]')];
  const empty=root.querySelector('[data-external-empty]');
  const editToggles=root.querySelectorAll('[data-external-edit-toggle]');
  const editRows=root.querySelectorAll('[data-external-edit-row]');
  const norm=v=>String(v||'').toLocaleLowerCase('es-GT').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();

  const closeAllExternalEditors=function(exceptId=''){
    editRows.forEach(function(row){
      if(exceptId&&row.id===exceptId)return;
      row.hidden=true;
      const button=root.querySelector('[data-external-edit-toggle][aria-controls="'+row.id+'"]');
      button?.setAttribute('aria-expanded','false');
    });
  };

  editToggles.forEach(function(button){
    button.addEventListener('click',function(){
      const row=document.getElementById(button.getAttribute('aria-controls')||'');
      if(!row)return;
      const opening=row.hidden;
      closeAllExternalEditors(opening?row.id:'');
      row.hidden=!opening;
      button.setAttribute('aria-expanded',opening?'true':'false');
      if(opening)row.querySelector('input[name="organization_name"]')?.focus();
    });
  });

  root.querySelectorAll('[data-external-edit-close]').forEach(function(button){
    button.addEventListener('click',function(){
      const row=button.closest('[data-external-edit-row]');
      if(!row)return;
      row.hidden=true;
      root.querySelector('[data-external-edit-toggle][aria-controls="'+row.id+'"]')?.setAttribute('aria-expanded','false');
    });
  });

  q?.addEventListener('input',()=>{
    closeAllExternalEditors();
    const term=norm(q.value);let visible=0;
    rows.forEach(r=>{const show=!term||norm(r.dataset.search).includes(term);r.hidden=!show;if(show)visible++});
    if(empty)empty.hidden=visible!==0;
  });
})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
