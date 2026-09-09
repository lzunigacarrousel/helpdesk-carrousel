<?php
use App\Core\Csrf;
$pageTitle='Usuarios';$pageSection='Administración';$activeNav='users';$helpContext='users';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['PENDING'=>'Pendiente de validar','ACTIVE'=>'Activo','BLOCKED'=>'Bloqueado','DISABLED'=>'Deshabilitado'];
$profileHelp=[
  'REQUESTER'=>['label'=>'Solicitante','can'=>'Crear y seguir sus solicitudes.','scope'=>'Solo información propia.','support'=>false],
  'TECHNICIAN'=>['label'=>'Técnico','can'=>'Atender, responder y resolver casos.','scope'=>'Alcance del equipo de soporte.','support'=>true],
  'SUPERVISOR'=>['label'=>'Supervisor','can'=>'Consultar seguimiento e informes.','scope'=>'Región, parque o área asignada.','support'=>false],
  'MANAGEMENT'=>['label'=>'Gerencia','can'=>'Consultar dashboard, informes y tendencias.','scope'=>'Global.','support'=>false],
  'SEMIADMIN'=>['label'=>'Semiadministrador','can'=>'Operación amplia de soporte y gestión.','scope'=>'Global.','support'=>true],
  'ADMIN'=>['label'=>'Administrador','can'=>'Control total del Helpdesk.','scope'=>'Global.','support'=>true],
];
?>
<div class="page-heading"><div><h1 class="page-title">Usuarios y estructura</h1><p class="page-subtitle">El perfil define qué puede hacer. El alcance define qué información puede consultar. Atender soporte es una capacidad separada.</p></div></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="organization-guide profile-guide-grid">
  <div><strong>Perfil</strong><span>Solicitante, Técnico, Supervisor, Gerencia, Semiadmin o Administrador.</span></div>
  <div><strong>Alcance</strong><span>Global, región, parque, área o solo información propia.</span></div>
  <div><strong>Atiende soporte</strong><span>Solo Administrador, Semiadmin y Técnico toman y gestionan tickets.</span></div>
</section>

<section class="profile-presets-card">
  <div class="dashboard-section-head compact"><div><span class="ticket-kicker">Perfiles predefinidos</span><h2>Qué hace cada perfil</h2><p>Selecciona el perfil según la responsabilidad real de la persona, no según cuánto quieres que vea en pantalla.</p></div></div>
  <div class="profile-preset-grid">
    <?php foreach($profileHelp as $code=>$preset): ?>
      <article><div class="profile-preset-head"><strong><?= htmlspecialchars($preset['label']) ?></strong><span class="badge <?= $preset['support']?'badge-primary':'badge-secondary' ?>"><?= $preset['support']?'Atiende soporte':'Solo consulta / solicitud' ?></span></div><p><?= htmlspecialchars($preset['can']) ?></p><small><?= htmlspecialchars($preset['scope']) ?></small></article>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-users-shell" data-users-admin>
  <div class="admin-user-toolbar">
    <label class="admin-user-search-field">
      <span class="form-label">Buscar usuario</span>
      <input class="form-control" type="search" placeholder="Nombre, correo, parque, área, puesto o responsable…" autocomplete="off" data-users-search>
    </label>
    <label>
      <span class="form-label">Perfil</span>
      <select class="form-control" data-users-role-filter>
        <option value="">Todos los perfiles</option>
        <?php foreach($roles as $r): ?><option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>
      <span class="form-label">Estado</span>
      <select class="form-control" data-users-status-filter>
        <option value="">Todos los estados</option>
        <?php foreach($statusLabels as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn btn-outline-secondary admin-users-clear" type="button" data-users-clear>Limpiar</button>
  </div>

  <div class="admin-users-summary">
    <span data-users-summary>Mostrando <?= count($users) ?> de <?= count($users) ?> usuarios</span>
    <strong data-users-count><?= count($users) ?> usuarios</strong>
  </div>

  <section class="admin-user-list" data-users-list>
  <?php foreach($users as $u):
    $assignmentText=$u['park_name']??$u['area_name']??'Sin ubicación definida';
    $preset=$profileHelp[$u['role_code']]??['label'=>$u['role_name'],'can'=>'Acceso según permisos configurados.','scope'=>'Alcance según asignación.','support'=>false];
    $scopeText=match($u['role_code']){
      'ADMIN','SEMIADMIN','MANAGEMENT'=>'Global',
      'SUPERVISOR'=>($u['region_name']?'Región · '.$u['region_name']:($u['park_name']?'Parque · '.$u['park_name']:($u['area_name']?'Área · '.$u['area_name']:'Alcance pendiente'))),
      'TECHNICIAN'=>'Equipo de soporte',
      default=>'Información propia',
    };
    $searchText=implode(' ',array_filter([
      $u['full_name']??'', $u['email']??'', $u['phone']??'', $u['role_name']??'',
      $statusLabels[$u['status']]??($u['status']??''), $u['region_name']??'', $u['park_name']??'',
      $u['area_name']??'', $u['position_name']??'', $u['manager_name']??''
    ]));
  ?>
  <article class="admin-user-card"
           data-user-row
           data-user-search="<?= htmlspecialchars($searchText) ?>"
           data-user-role="<?= htmlspecialchars($u['role_name']) ?>"
           data-user-status="<?= htmlspecialchars($u['status']) ?>">
    <div class="admin-user-summary">
      <div class="admin-user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></div>
      <div class="admin-user-main"><h2><?= htmlspecialchars($u['full_name']) ?></h2><p><?= htmlspecialchars($u['email']) ?><?= !empty($u['phone'])?' · '.htmlspecialchars($u['phone']):'' ?></p><div class="admin-user-badges"><span class="badge badge-primary"><?= htmlspecialchars($preset['label']) ?></span><span class="badge <?= $preset['support']?'badge-success':'badge-secondary' ?>"><?= $preset['support']?'Atiende soporte':'No atiende tickets' ?></span><span class="badge <?= $u['status']==='ACTIVE'?'badge-success':($u['status']==='PENDING'?'badge-warning':'badge-secondary') ?>"><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span></div><small class="admin-user-scope">Alcance: <?= htmlspecialchars($scopeText) ?></small></div>
      <div class="admin-user-org"><strong><?= htmlspecialchars($assignmentText) ?></strong><span><?= htmlspecialchars($u['position_name']??'Puesto pendiente') ?></span><small><?= !empty($u['manager_name'])?'Responsable: '.htmlspecialchars($u['manager_name']):'Responsable pendiente de validar' ?></small></div>
    </div>

    <details class="admin-user-edit"><summary>Editar perfil y asignación</summary>
    <form method="post" action="<?= APP_BASE_URL ?>/admin/users/assign" data-single-submit class="admin-user-form">
      <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
      <div><label class="form-label">Perfil en Helpdesk</label><select class="form-control" name="role_id" required><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id']===(int)$u['role_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label">Tipo de ubicación</label><select class="form-control assignment-type" name="assignment_type" required><option value="PARK" <?= $u['assignment_type']==='PARK'?'selected':'' ?>>Parque / ubicación</option><option value="CORPORATE" <?= $u['assignment_type']==='CORPORATE'?'selected':'' ?>>Área corporativa</option><option value="OTHER" <?= $u['assignment_type']==='OTHER'||empty($u['assignment_type'])?'selected':'' ?>>Otro</option></select></div>
      <div><label class="form-label">Parque</label><select class="form-control" name="park_id"><option value="">No aplica</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['park_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label">Área</label><select class="form-control" name="area_id"><option value="">No aplica</option><?php foreach($areas as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['area_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label">Región</label><select class="form-control" name="region_id"><option value="">Automática / no aplica</option><?php foreach($regions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['region_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label">Puesto o función</label><select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['position_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
      <div class="admin-manager-field"><label class="form-label">Responsable directo</label><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach($managers as $m): if((int)$m['id']===(int)$u['id'])continue; ?><option value="<?= (int)$m['id'] ?>" <?= (int)$m['id']===(int)($u['manager_user_id']??0)?'selected':'' ?>><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select></div>
      <div class="admin-form-action"><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
    </form></details>
  </article>
  <?php endforeach; ?>
  </section>

  <div class="admin-users-empty" data-users-empty hidden>
    <strong>No encontramos usuarios con esos filtros.</strong>
    <span>Prueba con otro nombre, correo, ubicación, perfil o estado.</span>
  </div>
</section>

<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>