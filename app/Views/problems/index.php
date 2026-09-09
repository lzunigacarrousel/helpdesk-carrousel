<?php
use App\Core\Auth;
$pageTitle='Problemas conocidos';$pageSection='Problemas conocidos';$activeNav='problems';$helpContext='problems';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="itsm-page">
  <div class="page-heading"><div><span class="ticket-kicker">Problem Management</span><h1 class="page-title">Problemas conocidos</h1><p class="page-subtitle">Agrupa incidentes recurrentes, documenta causa raíz y publica workarounds reutilizables.</p></div><?php if(Auth::can('problems.manage')): ?><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/problems/new">+ Nuevo problema</a><?php endif; ?></div>
  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
  <section class="card"><div class="card-body"><form class="itsm-filter-grid" method="get" action="<?= APP_BASE_URL ?>/problems">
    <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Buscar número, título, causa o workaround">
    <select class="form-control" name="status"><option value="">Todos los estados</option><?php foreach($statuses as $code=>$label): ?><option value="<?= $code ?>" <?= $filters['status']===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select>
    <select class="form-control" name="category_id"><option value="0">Todas las categorías</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$filters['category']===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
    <select class="form-control" name="park_id"><option value="0">Todos los parques</option><?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)$filters['park']===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select>
    <select class="form-control" name="owner_id"><option value="0">Todos los propietarios</option><?php foreach($owners as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (int)$filters['owner']===(int)$o['id']?'selected':'' ?>><?= htmlspecialchars($o['full_name']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary" type="submit">Aplicar</button>
  </form></div></section>

  <?php if($problems): ?><div class="table-responsive"><table class="table itsm-table"><thead><tr><th>Problema</th><th>Estado</th><th>Categoría</th><th>Parque</th><th>Propietario</th><th>Ocurrencias</th><th>Primera / última</th><th>Actualizado</th></tr></thead><tbody>
  <?php foreach($problems as $p): ?><tr><td><a class="itsm-primary-link" href="<?= APP_BASE_URL ?>/problems/view?id=<?= (int)$p['id'] ?>"><span><?= htmlspecialchars($p['problem_number']) ?></span><strong><?= htmlspecialchars($p['title']) ?></strong><small><?= htmlspecialchars(mb_strimwidth((string)$p['description'],0,130,'…')) ?></small></a></td><td><span class="badge badge-secondary"><?= htmlspecialchars($statuses[$p['status']]??$p['status']) ?></span></td><td><?= htmlspecialchars($p['category_name']??'Sin categoría') ?></td><td><?= htmlspecialchars($p['park_name']??'General') ?></td><td><?= htmlspecialchars($p['owner_name']??'Sin propietario') ?></td><td><strong><?= (int)$p['occurrence_count'] ?></strong></td><td><small><?= !empty($p['first_seen_at'])?htmlspecialchars(date('d/m/Y',strtotime($p['first_seen_at']))):'—' ?> / <?= !empty($p['last_seen_at'])?htmlspecialchars(date('d/m/Y',strtotime($p['last_seen_at']))):'—' ?></small></td><td><?= htmlspecialchars(date('d/m/Y H:i',strtotime($p['updated_at']))) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else: ?><section class="card"><div class="empty-state"><h2>No hay problemas con estos filtros</h2><p>Cuando identifiques recurrencia puedes crear un problema desde uno o varios tickets.</p></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
