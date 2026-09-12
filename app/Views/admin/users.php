<?php
use App\Core\{Auth,Csrf};
$pageTitle='Usuarios';$pageSection='Administración';$activeNav='users';$helpContext='users';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['PENDING'=>'Pendiente','ACTIVE'=>'Activo','BLOCKED'=>'Bloqueado','DISABLED'=>'Deshabilitado'];
$profileHelp=[
  'REQUESTER'=>['label'=>'Solicitante'],
  'TECHNICIAN'=>['label'=>'Técnico'],
  'SUPERVISOR'=>['label'=>'Supervisor'],
  'MANAGEMENT'=>['label'=>'Gerencia'],
  'SEMIADMIN'=>['label'=>'Semiadministrador'],
  'ADMIN'=>['label'=>'Administrador'],
];
$isFullAdmin=Auth::role()==='ADMIN';
$activeCount=count(array_filter($users,static fn(array $u):bool=>($u['status']??'')==='ACTIVE'));
$pendingCount=count(array_filter($users,static fn(array $u):bool=>($u['status']??'')==='PENDING'));
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.admin-users-page{width:100%;margin:0}.admin-users-heading{align-items:center;margin-bottom:14px}.admin-access-toggle{white-space:nowrap}
.admin-access-panel{margin:0 0 14px;overflow:hidden}.admin-access-panel[hidden]{display:none!important}.admin-access-panel .card-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:13px 16px;background:color-mix(in srgb,var(--card) 96%,var(--bg) 4%)}.admin-access-panel .card-header strong{font-size:16px;color:var(--brand-dark)}.admin-access-close{border:0;background:transparent;color:var(--muted);font-size:22px;line-height:1;cursor:pointer;padding:4px 6px}
.admin-access-form{padding:16px;display:grid;gap:12px}.admin-access-grid,.admin-access-org{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px 12px;align-items:end}.admin-access-wide{grid-column:span 2}.admin-access-org{padding-top:12px;border-top:1px solid var(--border)}.admin-access-actions{display:flex;justify-content:flex-end;gap:8px}[data-location-park][hidden],[data-location-area][hidden],[data-location-region][hidden]{display:none!important}
.admin-users-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px}.admin-users-kpis>div{padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--card)}.admin-users-kpis strong,.admin-users-kpis span{display:block}.admin-users-kpis strong{font-size:22px;color:var(--brand)}.admin-users-kpis span{font-size:10px;color:var(--muted);text-transform:uppercase;font-weight:850}
.admin-user-toolbar{display:grid;grid-template-columns:minmax(260px,1.6fr) minmax(150px,.6fr) minmax(150px,.6fr) auto;gap:8px;align-items:end;margin-bottom:10px}.admin-user-toolbar .form-label{margin-bottom:4px}.admin-users-summary{display:flex;justify-content:space-between;gap:10px;margin:0 0 8px;color:var(--muted);font-size:11px}.admin-user-table td:first-child strong{font-size:13px}.admin-user-table .admin-user-edit summary{cursor:pointer;color:var(--brand);font-weight:850}.admin-user-edit-panel{min-width:min(760px,72vw);padding:10px}.admin-user-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.admin-form-action{display:flex;align-items:end}.admin-user-convert{margin-top:10px;border-top:1px solid var(--border);padding-top:8px}.admin-user-convert summary{cursor:pointer;color:var(--brand);font-weight:850}.admin-user-convert-zone{margin-top:8px;padding:10px;border:1px solid color-mix(in srgb,var(--brand) 24%,var(--border));border-radius:10px;background:color-mix(in srgb,var(--info-bg) 42%,var(--card) 58%)}.admin-user-convert-zone>p{margin:4px 0 10px;color:var(--muted);font-size:11px}.admin-user-convert-form{display:grid;grid-template-columns:1fr 1fr;gap:8px}.admin-user-convert-form .external-full{grid-column:1/-1}.admin-user-convert-confirm{display:flex;gap:7px;align-items:flex-start;font-size:11px;color:var(--text)}
.admin-user-remove{margin-top:10px;border-top:1px solid var(--border);padding-top:8px}.admin-user-remove summary{cursor:pointer;color:var(--danger);font-weight:800}.admin-user-danger-zone{margin-top:8px;padding:10px;border:1px solid color-mix(in srgb,var(--danger) 28%,var(--border));border-radius:10px}.admin-delete-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}
.admin-users-empty{margin-top:10px;padding:14px;border:1px dashed var(--border);border-radius:12px;color:var(--muted)}
@media(max-width:1180px){.admin-access-grid,.admin-access-org{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-access-wide{grid-column:span 2}.admin-user-form{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-user-toolbar{grid-template-columns:1fr 1fr 1fr auto}}
@media(max-width:760px){.admin-user-convert-form{grid-template-columns:1fr}.admin-users-heading{align-items:stretch}.admin-access-toggle{width:100%}.admin-access-grid,.admin-access-org,.admin-user-form,.admin-user-toolbar{grid-template-columns:1fr}.admin-access-wide{grid-column:auto}.admin-access-form{padding:14px}.admin-access-actions{display:grid;grid-template-columns:1fr}.admin-access-actions .btn{width:100%}.admin-users-kpis{grid-template-columns:repeat(3,1fr)}.admin-user-edit-panel{min-width:0;padding:4px 0}.admin-users-summary{align-items:center}}
</style>
<div class="admin-users-page">
  <div class="page-heading admin-users-heading">
    <div><h1 class="page-title">Usuarios</h1></div>
    <button class="btn btn-primary admin-access-toggle" type="button" data-access-toggle aria-expanded="false">+ Dar acceso</button>
  </div>

  <section class="card admin-access-panel" data-access-panel <?= isset($_GET['create'])?'':'hidden' ?>>
    <div class="card-header"><strong>Acceso interno</strong><button class="admin-access-close" type="button" data-access-close aria-label="Cerrar">×</button></div>
    <form method="post" action="<?= APP_BASE_URL ?>/admin/users/create" data-single-submit class="admin-access-form" data-user-admin-form>
      <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
      <div class="admin-access-grid">
        <div class="admin-access-wide"><label class="form-label">Nombre completo</label><input class="form-control" type="text" name="full_name" required autocomplete="name" placeholder="Nombre de la persona"></div>
        <div><label class="form-label">Correo</label><input class="form-control" type="email" name="email" required autocomplete="email" placeholder="nombre@correo.com"></div>
        <div><label class="form-label">Teléfono <span class="optional">Opcional</span></label><input class="form-control" type="text" name="phone" autocomplete="tel" placeholder="Número de contacto"></div>
        <div><label class="form-label">Perfil</label><select class="form-control" name="role_id" required data-role-select><option value="">Selecciona</option><?php foreach($roles as $r): if(!$isFullAdmin&&$r['code']==='ADMIN')continue; ?><option value="<?= (int)$r['id'] ?>" data-role-code="<?= htmlspecialchars($r['code']) ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Estado</label><select class="form-control" name="status"><option value="ACTIVE">Activo</option><option value="PENDING">Pendiente</option><option value="BLOCKED">Bloqueado</option><option value="DISABLED">Deshabilitado</option></select></div>
        <div><label class="form-label">Puesto o función</label><select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">¿Qué representa esta cuenta?</label><select class="form-control" name="requester_entity_type" required data-requester-entity><option value="PERSON">Persona</option><option value="DEPARTMENT">Área / departamento</option><option value="PARK">Parque</option></select><small class="field-help">Parque = correo oficial de un parque. Persona/Área puede reportar otras ubicaciones según su perfil.</small></div>
        <div><label class="form-label">Dónde trabaja</label><select class="form-control" name="assignment_type" required data-assignment-type><option value="">Selecciona</option><option value="PARK">Parque / ubicación</option><option value="CORPORATE">Área corporativa</option><option value="OTHER">Otro</option></select></div>
      </div>
      <div class="admin-access-org">
        <div data-location-park hidden><label class="form-label">Parque</label><select class="form-control" name="park_id" disabled><option value="">Selecciona</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div data-location-area hidden><label class="form-label">Área corporativa</label><select class="form-control" name="area_id" disabled><option value="">Selecciona</option><?php foreach($areas as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div data-location-region hidden><label class="form-label">Región <span class="optional">Si aplica</span></label><select class="form-control" name="region_id" disabled><option value="">Selecciona</option><?php foreach($regions as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div class="admin-manager-field"><label class="form-label">Responsable directo <span class="optional">Opcional</span></label><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach($managers as $m): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select><small class="field-help">Referencia organizacional: persona a la que reporta. No asigna tickets y no cambia permisos ni alcance.</small></div>
      </div>
      <div class="admin-access-actions"><button class="btn btn-outline-secondary" type="button" data-access-cancel>Cancelar</button><button class="btn btn-primary" type="submit">Dar acceso</button></div>
    </form>
  </section>

  <?php if(!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flash['type']??'info')) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <section data-users-admin>
    <div class="admin-users-kpis" aria-label="Resumen de usuarios"><div><strong><?= count($users) ?></strong><span>Total</span></div><div><strong><?= $activeCount ?></strong><span>Activos</span></div><div><strong><?= $pendingCount ?></strong><span>Pendientes</span></div></div>

    <div class="admin-user-toolbar">
      <label><span class="form-label">Buscar</span><input class="form-control" type="search" placeholder="Nombre, correo, ubicación o puesto…" autocomplete="off" data-users-search></label>
      <label><span class="form-label">Perfil</span><select class="form-control" data-users-role-filter><option value="">Todos</option><?php foreach($roles as $r): ?><option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></label>
      <label><span class="form-label">Estado</span><select class="form-control" data-users-status-filter><option value="">Todos</option><?php foreach($statusLabels as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
      <button class="btn btn-outline-secondary" type="button" data-users-clear>Limpiar</button>
    </div>
    <div class="admin-users-summary"><span data-users-summary>Mostrando <?= count($users) ?> de <?= count($users) ?> usuarios</span><strong data-users-count><?= count($users) ?> visibles</strong></div>

    <div class="data-table-shell"><div class="data-table-wrap"><table class="data-table admin-user-table">
      <thead><tr><th>Usuario</th><th>Perfil</th><th>Ubicación</th><th>Puesto</th><th>Estado</th><th>Alcance</th><th>Acciones</th></tr></thead>
      <tbody data-users-list>
      <?php foreach($users as $u):
        $assignmentText=$u['park_name']??$u['area_name']??'Sin ubicación definida';
        $preset=$profileHelp[$u['role_code']]??['label'=>$u['role_name']];
        $scopeText=match($u['role_code']){
          'ADMIN','SEMIADMIN','MANAGEMENT'=>'Global',
          'SUPERVISOR'=>($u['region_name']?'Región · '.$u['region_name']:($u['park_name']?'Parque · '.$u['park_name']:($u['area_name']?'Área · '.$u['area_name']:'Pendiente'))),
          'TECHNICIAN'=>'Equipo de soporte',
          default=>'Información propia',
        };
        $searchText=implode(' ',array_filter([$u['full_name']??'', $u['email']??'', $u['phone']??'', $u['role_name']??'', $statusLabels[$u['status']]??($u['status']??''), $u['region_name']??'', $u['park_name']??'', $u['area_name']??'', $u['position_name']??'', $u['manager_name']??'']));
        $canEditThis=!($u['role_code']==='ADMIN'&&!$isFullAdmin);
      ?>
        <tr data-user-row data-user-search="<?= htmlspecialchars($searchText) ?>" data-user-role="<?= htmlspecialchars($u['role_name']) ?>" data-user-status="<?= htmlspecialchars($u['status']) ?>">
          <td data-label="Usuario"><strong><?= htmlspecialchars($u['full_name']) ?></strong><small><?= htmlspecialchars($u['email']) ?><?= !empty($u['phone'])?' · '.htmlspecialchars($u['phone']):'' ?></small></td>
          <td data-label="Perfil"><?= htmlspecialchars($preset['label']) ?><small><?= htmlspecialchars(match($u['requester_entity_type']??'PERSON'){'PARK'=>'Cuenta de parque','DEPARTMENT'=>'Área / departamento',default=>'Persona'}) ?></small></td>
          <td data-label="Ubicación"><strong><?= htmlspecialchars($assignmentText) ?></strong><?php if(!empty($u['manager_name'])): ?><small>Responsable: <?= htmlspecialchars($u['manager_name']) ?></small><?php endif; ?></td>
          <td data-label="Puesto"><?= htmlspecialchars($u['position_name']??'Pendiente') ?></td>
          <td data-label="Estado"><span class="badge"><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span></td>
          <td data-label="Alcance" class="data-table-secondary"><?= htmlspecialchars($scopeText) ?></td>
          <td data-label="Acciones">
          <?php if($canEditThis): ?>
            <details class="admin-user-edit" id="user-<?= (int)$u['id'] ?>"><summary>Editar</summary><div class="admin-user-edit-panel">
              <form method="post" action="<?= APP_BASE_URL ?>/admin/users/assign" data-single-submit class="admin-user-form" data-user-admin-form>
                <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <div><label class="form-label">Nombre completo</label><input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required></div>
                <div><label class="form-label">Correo</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
                <div><label class="form-label">Teléfono</label><input class="form-control" type="text" name="phone" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>" placeholder="Opcional"></div>
                <div><label class="form-label">Estado</label><select class="form-control" name="status"><?php foreach($statusLabels as $code=>$label): ?><option value="<?= $code ?>" <?= $u['status']===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">Perfil</label><select class="form-control" name="role_id" required data-role-select><?php foreach($roles as $r): if(!$isFullAdmin&&$r['code']==='ADMIN')continue; ?><option value="<?= (int)$r['id'] ?>" data-role-code="<?= htmlspecialchars($r['code']) ?>" <?= (int)$r['id']===(int)$u['role_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">Puesto o función</label><select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['position_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">¿Qué representa esta cuenta?</label><select class="form-control" name="requester_entity_type" required data-requester-entity><option value="PERSON" <?= ($u['requester_entity_type']??'PERSON')==='PERSON'?'selected':'' ?>>Persona</option><option value="DEPARTMENT" <?= ($u['requester_entity_type']??'PERSON')==='DEPARTMENT'?'selected':'' ?>>Área / departamento</option><option value="PARK" <?= ($u['requester_entity_type']??'PERSON')==='PARK'?'selected':'' ?>>Parque</option></select></div>
                <div><label class="form-label">Dónde trabaja</label><select class="form-control" name="assignment_type" required data-assignment-type><option value="PARK" <?= $u['assignment_type']==='PARK'?'selected':'' ?>>En un parque</option><option value="CORPORATE" <?= $u['assignment_type']==='CORPORATE'?'selected':'' ?>>En un área corporativa</option><option value="OTHER" <?= $u['assignment_type']==='OTHER'||empty($u['assignment_type'])?'selected':'' ?>>Otro</option></select></div>
                <div data-location-park><label class="form-label">Parque</label><select class="form-control" name="park_id"><option value="">Selecciona</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['park_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
                <div data-location-area><label class="form-label">Área corporativa</label><select class="form-control" name="area_id"><option value="">Selecciona</option><?php foreach($areas as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['area_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
                <div data-location-region><label class="form-label">Región <span class="optional">Si aplica</span></label><select class="form-control" name="region_id"><option value="">Selecciona</option><?php foreach($regions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['region_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">Responsable directo <span class="optional">Opcional</span></label><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach($managers as $m): if((int)$m['id']===(int)$u['id'])continue; ?><option value="<?= (int)$m['id'] ?>" <?= (int)$m['id']===(int)($u['manager_user_id']??0)?'selected':'' ?>><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select><small class="field-help">Referencia organizacional: persona a la que reporta. No asigna tickets y no cambia permisos ni alcance.</small></div>
                <div class="admin-form-action"><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
              </form>
              <?php if(($u['requester_entity_type']??'PERSON')==='PARK' && !empty($u['park_id'])): ?>

                <?php if((int)($u['missing_park_ticket_count']??0)>0): ?>

                  <details class="admin-user-convert"><summary>Completar tickets históricos sin parque</summary><div class="admin-user-convert-zone">

                    <strong><?= (int)$u['missing_park_ticket_count'] ?> tickets anteriores sin parque</strong>

                    <p>Esta cuenta representa <?= htmlspecialchars((string)$u['park_name']) ?>. Puedes completar únicamente los tickets que todavía estén sin parque; nunca se sobrescribe un ticket que ya tenga otra ubicación.</p>

                    <form method="post" action="<?= APP_BASE_URL ?>/admin/users/backfill-park-tickets" data-single-submit class="admin-user-convert-form">

                      <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">

                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">

                      <label class="external-full"><span class="form-label">Motivo de la actualización histórica</span><input class="form-control" name="backfill_reason" required minlength="8" maxlength="500" placeholder="Ej. Se confirmó que este correo es la cuenta oficial del parque."></label>

                      <div class="external-full"><button class="btn btn-primary" type="submit">Asignar <?= htmlspecialchars((string)$u['park_name']) ?> a los <?= (int)$u['missing_park_ticket_count'] ?> tickets sin parque</button></div>

                    </form>

                  </div></details>

                <?php endif; ?>

                <?php if((int)($u['conflicting_park_ticket_count']??0)>0): ?>

                  <div class="alert alert-warning"><?= (int)$u['conflicting_park_ticket_count'] ?> ticket(s) ya tienen un parque distinto. No se modifican automáticamente y deben revisarse individualmente.</div>

                <?php endif; ?>

              <?php endif; ?>              <?php if((int)$u['id']!==(int)Auth::id()): ?>
              <details class="admin-user-convert"><summary>Convertir a proveedor externo</summary><div class="admin-user-convert-zone">
                <strong>Convertir a proveedor externo</strong>
                <p>Esta persona dejará de tener acceso interno, permisos, ubicación y participación en soporte. Después solo podrá consultar los casos que Carrousel comparta expresamente con su cuenta.</p>
                <?php if((int)($u['active_ticket_count']??0)>0): ?>
                  <div class="admin-delete-blocked">Tiene <?= (int)$u['active_ticket_count'] ?> caso(s) activo(s) asignado(s). Reasígnalos antes de convertir la cuenta.</div>
                <?php else: ?>
                  <form method="post" action="<?= APP_BASE_URL ?>/admin/users/convertir-externo" data-single-submit class="admin-user-convert-form">
                    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <label><span class="form-label">Proveedor / empresa</span><input class="form-control" name="organization_name" required placeholder="Ej. Empresa de Internet"></label>
                    <label><span class="form-label">Tipo</span><select class="form-control" name="external_type"><option value="PROVIDER">Proveedor</option><option value="PARTNER">Socio / aliado</option><option value="OTHER">Otro</option></select></label>
                    <label class="external-full"><span class="form-label">Servicio / referencia</span><input class="form-control" name="notes" placeholder="Ej. Internet, CCTV, POS, mantenimiento..."></label>
                    <label class="admin-user-convert-confirm external-full"><input type="checkbox" name="confirm_convert" value="1" required> Confirmo que esta cuenta dejará de ser interna y pasará a proveedor externo.</label>
                    <div class="external-full"><button class="btn btn-primary" type="submit">Convertir a proveedor externo</button></div>
                  </form>
                <?php endif; ?>
              </div></details>
              <details class="admin-user-remove"><summary>Retirar acceso</summary><div class="admin-user-danger-zone"><strong>Retirar acceso</strong><?php if((int)($u['active_ticket_count']??0)>0): ?><div class="admin-delete-blocked">Tiene <?= (int)$u['active_ticket_count'] ?> caso(s) activo(s). Reasígnalos antes.</div><?php else: ?><form method="post" action="<?= APP_BASE_URL ?>/admin/users/delete" data-single-submit class="admin-delete-form"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo el retiro.</label><button class="btn btn-danger" type="submit">Retirar acceso</button></form><?php endif; ?></div></details>
              <?php endif; ?>
            </div></details>
          <?php else: ?><span class="data-table-muted">Solo administrador</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$users): ?><tr><td class="data-table-empty" data-label="" colspan="7">No hay usuarios registrados.</td></tr><?php endif; ?>
      </tbody>
    </table></div></div>
    <div class="admin-users-empty" data-users-empty hidden><strong>No encontramos usuarios con esos filtros.</strong></div>
  </section>
</div>

<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const panel=document.querySelector('[data-access-panel]');
  const toggle=document.querySelector('[data-access-toggle]');
  const closeButtons=document.querySelectorAll('[data-access-close],[data-access-cancel]');
  const setPanel=function(open){if(!panel||!toggle)return;panel.hidden=!open;toggle.setAttribute('aria-expanded',open?'true':'false');toggle.textContent=open?'Cerrar':'+ Dar acceso';if(open){panel.querySelector('input[name="full_name"]')?.focus();}};
  toggle?.addEventListener('click',function(){setPanel(panel?.hidden??true)});
  closeButtons.forEach(function(button){button.addEventListener('click',function(){setPanel(false)})});

  const sync=function(form){
    const type=form.querySelector('[data-assignment-type]');
    const role=form.querySelector('[data-role-select]');
    const entity=form.querySelector('[data-requester-entity]');
    if(!type)return;
    let value=type.value;
    const roleCode=role?.selectedOptions?.[0]?.dataset?.roleCode||'';
    const entityValue=entity?.value||'PERSON';
    if(entityValue==='PARK'&&roleCode!=='SUPERVISOR'){type.value='PARK';value='PARK';}
    const park=form.querySelector('[data-location-park]');
    const area=form.querySelector('[data-location-area]');
    const region=form.querySelector('[data-location-region]');
    const set=function(wrap,show,required){if(!wrap)return;wrap.hidden=!show;const field=wrap.querySelector('select,input');if(!field)return;field.disabled=!show;field.required=!!(show&&required);if(!show)field.required=false;};
    set(park,value==='PARK',true);set(area,value==='CORPORATE',true);set(region,roleCode==='SUPERVISOR'||value==='OTHER',false);
  };
  document.querySelectorAll('[data-user-admin-form]').forEach(function(form){sync(form);form.querySelector('[data-assignment-type]')?.addEventListener('change',function(){sync(form)});form.querySelector('[data-role-select]')?.addEventListener('change',function(){sync(form)});form.querySelector('[data-requester-entity]')?.addEventListener('change',function(){sync(form)});});
})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>