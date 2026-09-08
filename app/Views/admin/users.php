<?php
use App\Core\Csrf;
$pageTitle='Usuarios';$pageSection='Administración';$activeNav='users';$helpContext='users';
require APP_ROOT.'/app/Views/shared/app_start.php';
$statusLabels=['PENDING'=>'Pendiente de validar','ACTIVE'=>'Activo','BLOCKED'=>'Bloqueado','DISABLED'=>'Deshabilitado'];
?>
<div class="page-heading"><div><h1 class="page-title">Usuarios y estructura</h1><p class="page-subtitle">El rol define lo que puede hacer en Helpdesk. La asignación define dónde trabaja y quién es su responsable.</p></div></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

<section class="organization-guide">
  <div><strong>Rol Helpdesk</strong><span>Solicitante, Técnico, Semiadmin, Administrador o Externo.</span></div>
  <div><strong>Asignación organizacional</strong><span>Parque/área, puesto y responsable. Son conceptos separados.</span></div>
</section>

<div class="admin-user-toolbar"><input class="form-control" id="userSearch" placeholder="Buscar por nombre o correo" autocomplete="off"><span class="muted"><?= count($users) ?> usuarios</span></div>

<section class="admin-user-list" id="userList">
<?php foreach($users as $u):
  $assignmentText=$u['park_name']??$u['area_name']??'Sin ubicación definida';
?>
<article class="admin-user-card" data-user-search="<?= htmlspecialchars(strtolower($u['full_name'].' '.$u['email'].' '.$assignmentText)) ?>">
  <div class="admin-user-summary">
    <div class="admin-user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></div>
    <div class="admin-user-main"><h2><?= htmlspecialchars($u['full_name']) ?></h2><p><?= htmlspecialchars($u['email']) ?><?= !empty($u['phone'])?' · '.htmlspecialchars($u['phone']):'' ?></p><div class="admin-user-badges"><span class="badge badge-primary"><?= htmlspecialchars($u['role_name']) ?></span><span class="badge <?= $u['status']==='ACTIVE'?'badge-success':($u['status']==='PENDING'?'badge-warning':'badge-secondary') ?>"><?= htmlspecialchars($statusLabels[$u['status']]??$u['status']) ?></span></div></div>
    <div class="admin-user-org"><strong><?= htmlspecialchars($assignmentText) ?></strong><span><?= htmlspecialchars($u['position_name']??'Puesto pendiente') ?></span><small><?= !empty($u['manager_name'])?'Responsable: '.htmlspecialchars($u['manager_name']):'Responsable pendiente de validar' ?></small></div>
  </div>

  <details class="admin-user-edit"><summary>Editar usuario y asignación</summary>
  <form method="post" action="<?= APP_BASE_URL ?>/admin/users/assign" data-single-submit class="admin-user-form">
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
    <div><label class="form-label">Rol en Helpdesk</label><select class="form-control" name="role_id" required><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id']===(int)$u['role_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?></select></div>
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

<script>
(()=>{const q=document.getElementById('userSearch');if(q)q.addEventListener('input',()=>{const v=q.value.trim().toLowerCase();document.querySelectorAll('.admin-user-card').forEach(c=>c.hidden=v&&!c.dataset.userSearch.includes(v));});})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>