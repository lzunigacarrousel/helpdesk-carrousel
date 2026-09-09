<?php
use App\Core\{Auth,Csrf};
$pageTitle='Usuarios';$pageSection='Administración';$activeNav='users';$helpContext='users';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['PENDING'=>'Pendiente','ACTIVE'=>'Activo','BLOCKED'=>'Bloqueado','DISABLED'=>'Deshabilitado'];
$profileHelp=[
  'REQUESTER'=>['label'=>'Solicitante','support'=>false],
  'TECHNICIAN'=>['label'=>'Técnico','support'=>true],
  'SUPERVISOR'=>['label'=>'Supervisor','support'=>false],
  'MANAGEMENT'=>['label'=>'Gerencia','support'=>false],
  'SEMIADMIN'=>['label'=>'Semiadministrador','support'=>true],
  'ADMIN'=>['label'=>'Administrador','support'=>true],
];
$isFullAdmin=Auth::role()==='ADMIN';
$activeCount=count(array_filter($users,static fn(array $u):bool=>($u['status']??'')==='ACTIVE'));
$pendingCount=count(array_filter($users,static fn(array $u):bool=>($u['status']??'')==='PENDING'));
?>
<div class="page-heading admin-users-heading">
  <div>
    <h1 class="page-title">Usuarios</h1>
    <p class="page-subtitle">Crea, actualiza o retira accesos internos desde un solo lugar.</p>
  </div>
  <details class="admin-create-user" data-create-user>
    <summary class="btn btn-primary">+ Nuevo usuario</summary>
    <div class="admin-create-panel">
      <div class="admin-create-head"><div><span class="ticket-kicker">Nuevo acceso</span><h2>Crear usuario</h2></div></div>
      <form method="post" action="<?= APP_BASE_URL ?>/admin/users/create" data-single-submit class="admin-user-form admin-user-form-create">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <div><label class="form-label">Nombre completo</label><input class="form-control" type="text" name="full_name" required autocomplete="name"></div>
        <div><label class="form-label">Correo</label><input class="form-control" type="email" name="email" required autocomplete="email"></div>
        <div><label class="form-label">Teléfono</label><input class="form-control" type="text" name="phone" autocomplete="tel" placeholder="Opcional"></div>
        <div><label class="form-label">Estado</label><select class="form-control" name="status"><option value="ACTIVE">Activo</option><option value="PENDING">Pendiente</option><option value="BLOCKED">Bloqueado</option><option value="DISABLED">Deshabilitado</option></select></div>
        <div><label class="form-label">Perfil</label><select class="form-control" name="role_id" required><option value="">Selecciona</option><?php foreach($roles as $r): if(!$isFullAdmin&&$r['code']==='ADMIN')continue; ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Tipo de ubicación</label><select class="form-control" name="assignment_type" required><option value="PARK">Parque / ubicación</option><option value="CORPORATE">Área corporativa</option><option value="OTHER">Otro</option></select></div>
        <div><label class="form-label">Parque</label><select class="form-control" name="park_id"><option value="">No aplica</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Área</label><select class="form-control" name="area_id"><option value="">No aplica</option><?php foreach($areas as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Región</label><select class="form-control" name="region_id"><option value="">Automática / no aplica</option><?php foreach($regions as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Puesto o función</label><select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $x): ?><option value="<?= (int)$x['id'] ?>"><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div class="admin-manager-field"><label class="form-label">Responsable directo</label><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach($managers as $m): ?><option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select></div>
        <div class="admin-form-action"><button class="btn btn-primary" type="submit">Crear usuario</button></div>
      </form>
    </div>
  </details>
</div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="admin-users-shell" data-users-admin>
  <div class="admin-users-kpis" aria-label="Resumen de usuarios">
    <div><strong><?= count($users) ?></strong><span>Total</span></div>
    <div><strong><?= $activeCount ?></strong><span>Activos</span></div>
    <div><strong><?= $pendingCount ?></strong><span>Pendientes</span></div>
  </div>

  <div class="admin-user-toolbar">
    <label class="admin-user-search-field">
      <span class="form-label">Buscar</span>
      <input class="form-control" type="search" placeholder="Nombre, correo, parque, área o puesto…" autocomplete="off" data-users-search>
    </label>
    <label>
      <span class="form-label">Perfil</span>
      <select class="form-control" data-users-role-filter>
        <option value="">Todos</option>
        <?php foreach($roles as $r): ?><option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>
      <span class="form-label">Estado</span>
      <select class="form-control" data-users-status-filter>
        <option value="">Todos</option>
        <?php foreach($statusLabels as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn btn-outline-secondary admin-users-clear" type="button" data-users-clear>Limpiar</button>
  </div>

  <div class="admin-users-summary">
    <span data-users-summary>Mostrando <?= count($users) ?> de <?= count($users) ?> usuarios</span>
    <strong data-users-count><?= count($users) ?> visibles</strong>
  </div>

  <section class="admin-user-list" data-users-list>
  <?php foreach($users as $u):
    $assignmentText=$u['park_name']??$u['area_name']??'Sin ubicación definida';
    $preset=$profileHelp[$u['role_code']]??['label'=>$u['role_name'],'support'=>false];
    $scopeText=match($u['role_code']){
      'ADMIN','SEMIADMIN','MANAGEMENT'=>'Global',
      'SUPERVISOR'=>($u['region_name']?'Región · '.$u['region_name']:($u['park_name']?'Parque · '.$u['park_name']:($u['area_name']?'Área · '.$u['area_name']:'Pendiente'))),
      'TECHNICIAN'=>'Equipo de soporte',
      default=>'Información propia',
    };
    $searchText=implode(' ',array_filter([
      $u['full_name']??'', $u['email']??'', $u['phone']??'', $u['role_name']??'',
      $statusLabels[$u['status']]??($u['status']??''), $u['region_name']??'', $u['park_name']??'',
      $u['area_name']??'', $u['position_name']??'', $u['manager_name']??''
    ]));
    $canEditThis=!($u['role_code']==='ADMIN'&&!$isFullAdmin);
  ?>
  <article class="admin-user-card"
           data-user-row
           data-user-search="<?= htmlspecialchars($searchText) ?>"
           data-user-role="<?= htmlspecialchars($u['role_name']) ?>"
           data-user-status="<?= htmlspecialchars($u['status']) ?>">
    <div class="admin-user-summary">
      <div class="admin-user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></div>
      <div class="admin-user-main">
        <h2><?= htmlspecialchars($u['full_name']) ?></h2>
        <p><?= htmlspecialchars($u['email']) ?><?= !empty($u['phone'])?' · '.htmlspecialchars($u['phone']):'' ?></p>
        <div class="admin-user-meta"><span><?= htmlspecialchars($preset['label']) ?></span><span>·</span><span><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span><span>·</span><span><?= htmlspecialchars($scopeText) ?></span></div>
      </div>
      <div class="admin-user-org"><strong><?= htmlspecialchars($assignmentText) ?></strong><span><?= htmlspecialchars($u['position_name']??'Puesto pendiente') ?></span><?php if(!empty($u['manager_name'])): ?><small>Responsable: <?= htmlspecialchars($u['manager_name']) ?></small><?php endif; ?></div>
      <?php if($canEditThis): ?><div class="admin-user-card-action"><a href="#user-<?= (int)$u['id'] ?>" class="btn btn-outline-secondary btn-sm" data-no-loading="1" onclick="this.closest('article').querySelector('details').open=true">Editar</a></div><?php endif; ?>
    </div>

    <?php if($canEditThis): ?>
    <details class="admin-user-edit" id="user-<?= (int)$u['id'] ?>"><summary>Editar usuario</summary>
      <form method="post" action="<?= APP_BASE_URL ?>/admin/users/assign" data-single-submit class="admin-user-form">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
        <div><label class="form-label">Nombre completo</label><input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required></div>
        <div><label class="form-label">Correo</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
        <div><label class="form-label">Teléfono</label><input class="form-control" type="text" name="phone" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>" placeholder="Opcional"></div>
        <div><label class="form-label">Estado</label><select class="form-control" name="status"><?php foreach($statusLabels as $code=>$label): ?><option value="<?= $code ?>" <?= $u['status']===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Perfil</label><select class="form-control" name="role_id" required><?php foreach($roles as $r): if(!$isFullAdmin&&$r['code']==='ADMIN')continue; ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id']===(int)$u['role_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Tipo de ubicación</label><select class="form-control assignment-type" name="assignment_type" required><option value="PARK" <?= $u['assignment_type']==='PARK'?'selected':'' ?>>Parque / ubicación</option><option value="CORPORATE" <?= $u['assignment_type']==='CORPORATE'?'selected':'' ?>>Área corporativa</option><option value="OTHER" <?= $u['assignment_type']==='OTHER'||empty($u['assignment_type'])?'selected':'' ?>>Otro</option></select></div>
        <div><label class="form-label">Parque</label><select class="form-control" name="park_id"><option value="">No aplica</option><?php foreach($parks as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['park_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Área</label><select class="form-control" name="area_id"><option value="">No aplica</option><?php foreach($areas as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['area_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Región</label><select class="form-control" name="region_id"><option value="">Automática / no aplica</option><?php foreach($regions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['region_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">Puesto o función</label><select class="form-control" name="position_id" required><option value="">Selecciona</option><?php foreach($positions as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id']===(int)($u['position_id']??0)?'selected':'' ?>><?= htmlspecialchars($x['name']) ?></option><?php endforeach; ?></select></div>
        <div class="admin-manager-field"><label class="form-label">Responsable directo</label><select class="form-control" name="manager_user_id"><option value="">Sin responsable / nivel superior</option><?php foreach($managers as $m): if((int)$m['id']===(int)$u['id'])continue; ?><option value="<?= (int)$m['id'] ?>" <?= (int)$m['id']===(int)($u['manager_user_id']??0)?'selected':'' ?>><?= htmlspecialchars($m['full_name']) ?><?= !empty($m['position_name'])?' · '.htmlspecialchars($m['position_name']):'' ?></option><?php endforeach; ?></select></div>
        <div class="admin-form-action"><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
      </form>

      <?php if((int)$u['id']!==(int)Auth::id()): ?>
      <div class="admin-user-danger-zone">
        <div><strong>Eliminar usuario</strong><span>Retira el acceso, pero conserva tickets, historial y auditoría.</span></div>
        <?php if((int)($u['active_ticket_count']??0)>0): ?>
          <div class="admin-delete-blocked">Tiene <?= (int)$u['active_ticket_count'] ?> caso(s) activo(s). Reasígnalos antes de eliminarlo.</div>
        <?php else: ?>
          <form method="post" action="<?= APP_BASE_URL ?>/admin/users/delete" data-single-submit class="admin-delete-form">
            <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
            <label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo que quiero eliminar este usuario.</label>
            <button class="btn btn-danger" type="submit">Eliminar usuario</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </details>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
  </section>

  <div class="admin-users-empty" data-users-empty hidden><strong>No encontramos usuarios con esos filtros.</strong><span>Prueba con otro nombre, correo, ubicación, perfil o estado.</span></div>
</section>

<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>