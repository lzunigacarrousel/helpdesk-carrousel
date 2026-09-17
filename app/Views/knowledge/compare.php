<?php
$pageTitle='Comparar '.$article['article_number'];$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';

$fields=[
  'Título'=>['title','text'],
  'Resumen'=>['summary','multiline'],
  'Contenido'=>['content','multiline'],
  'Categoría'=>['category_name','text'],
];
?>
<div class="itsm-page">
  <div class="page-heading">
    <div>
      <span class="ticket-kicker"><?= htmlspecialchars($article['article_number']) ?></span>
      <h1 class="page-title">Comparar versiones</h1>
      <p class="page-subtitle">Revisa exactamente qué cambió antes de restaurar o publicar.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge/history?id=<?= (int)$article['id'] ?>">← Volver al historial</a>
  </div>

  <section class="card">
    <div class="card-body">
      <div class="grid grid-2">
        <div>
          <span class="ticket-kicker">Versión <?= (int)$from['revision_number'] ?></span>
          <strong><?= htmlspecialchars($statuses[$from['state']]??$from['state']) ?></strong>
        </div>
        <div>
          <span class="ticket-kicker">Versión <?= (int)$to['revision_number'] ?></span>
          <strong><?= htmlspecialchars($statuses[$to['state']]??$to['state']) ?></strong>
        </div>
      </div>
    </div>
  </section>

  <?php foreach($fields as $label=>[$key,$mode]): ?>
    <?php $left=(string)($from[$key]??'');$right=(string)($to[$key]??'');$same=$left===$right; ?>
    <section class="card">
      <div class="card-body">
        <div class="search-knowledge-kicker">
          <strong><?= htmlspecialchars($label) ?></strong>
          <span><?= $same?'Sin cambios':'Cambió' ?></span>
        </div>
        <div class="grid grid-2">
          <div>
            <small>Versión <?= (int)$from['revision_number'] ?></small>
            <div><?= $mode==='multiline'?nl2br(htmlspecialchars($left)):htmlspecialchars($left?:'Sin dato') ?></div>
          </div>
          <div>
            <small>Versión <?= (int)$to['revision_number'] ?></small>
            <div><?= $mode==='multiline'?nl2br(htmlspecialchars($right)):htmlspecialchars($right?:'Sin dato') ?></div>
          </div>
        </div>
      </div>
    </section>
  <?php endforeach; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
