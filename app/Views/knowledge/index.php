<?php
$pageTitle='Base de conocimiento';$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="itsm-page">
  <div class="page-heading"><div><span class="ticket-kicker">Knowledge Management</span><h1 class="page-title">Base de conocimiento</h1></div><?php if($canManage): ?><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/knowledge/new">+ Nuevo artículo</a><?php endif; ?></div>
  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
  <section class="card"><div class="card-body"><form class="itsm-filter-grid" method="get" action="<?= APP_BASE_URL ?>/knowledge">
    <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Buscar número, título, resumen o contenido">
    <?php if($canManage): ?><select class="form-control" name="status"><option value="">Todos los estados</option><?php foreach($statuses as $code=>$label): ?><option value="<?= $code ?>" <?= $filters['status']===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><select class="form-control" name="visibility"><option value="">Toda visibilidad</option><?php foreach($visibility as $code=>$label): ?><option value="<?= $code ?>" <?= $filters['visibility']===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><?php endif; ?>
    <select class="form-control" name="category_id"><option value="0">Todas las categorías</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$filters['category']===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary" type="submit">Aplicar</button>
  </form></div></section>

  <?php if($articles): ?><div class="knowledge-grid"><?php foreach($articles as $a): ?><a class="card knowledge-card" href="<?= APP_BASE_URL ?>/knowledge/view?id=<?= (int)$a['id'] ?>"><div class="card-body"><div class="search-knowledge-kicker"><strong><?= htmlspecialchars($a['article_number']) ?></strong><span><?= htmlspecialchars($statuses[$a['status']]??$a['status']) ?></span></div><h2><?= htmlspecialchars($a['title']) ?></h2><p><?= htmlspecialchars(mb_strimwidth((string)($a['summary']?:strip_tags((string)$a['content'])),0,200,'…')) ?></p><div class="ticket-list-meta"><span><?= htmlspecialchars($a['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars($visibility[$a['visibility']]??$a['visibility']) ?></span><span><?= htmlspecialchars($a['author_name']??'Autor no disponible') ?></span><span><?= htmlspecialchars(date('d/m/Y',strtotime($a['updated_at']))) ?></span></div></div></a><?php endforeach; ?></div><?php else: ?><section class="card"><div class="empty-state"><strong>No encontramos artículos.</strong><span><?= $canManage?'Usa Nuevo artículo en la parte superior para crear el primero.':'Prueba con otros filtros o términos de búsqueda.' ?></span></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>