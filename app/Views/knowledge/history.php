<?php
use App\Core\Csrf;
$pageTitle='Historial '.$article['article_number'];$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="itsm-page">
  <div class="page-heading">
    <div>
      <span class="ticket-kicker"><?= htmlspecialchars($article['article_number']) ?></span>
      <h1 class="page-title">Historial de versiones</h1>
      <p class="page-subtitle">Consulta cambios anteriores sin modificar la versión vigente.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge/view?id=<?= (int)$article['id'] ?>">← Volver al artículo</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars((string)$flash['message']) ?></div><?php endif; ?>

  <div class="knowledge-grid">
    <?php foreach($revisions as $index=>$revision): ?>
      <section class="card">
        <div class="card-body">
          <div class="search-knowledge-kicker">
            <strong>Versión <?= (int)$revision['revision_number'] ?></strong>
            <span><?= htmlspecialchars($statuses[$revision['state']]??$revision['state']) ?></span>
          </div>
          <h2><?= htmlspecialchars($revision['title']) ?></h2>
          <p><?= htmlspecialchars(mb_strimwidth((string)($revision['summary']?:strip_tags((string)$revision['content'])),0,220,'…')) ?></p>
          <div class="ticket-list-meta">
            <span><?= htmlspecialchars($revision['category_name']??'Sin categoría') ?></span>
            <span><?= htmlspecialchars($revision['created_by_name']??'Autor no disponible') ?></span>
            <span><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$revision['created_at']))) ?></span>
            <?php if((int)$article['current_internal_revision_id']===(int)$revision['id']): ?><span>Vigente para soporte</span><?php endif; ?>
            <?php if((int)$article['current_public_revision_id']===(int)$revision['id']): ?><span>Disponible para solicitantes</span><?php endif; ?>
          </div>

          <?php if(!empty($revision['change_note'])): ?><p><strong>Cambio:</strong> <?= nl2br(htmlspecialchars($revision['change_note'])) ?></p><?php endif; ?>
          <?php if(!empty($revision['review_note'])): ?><p><strong>Observación:</strong> <?= nl2br(htmlspecialchars($revision['review_note'])) ?></p><?php endif; ?>

          <div class="topbar-actions">
            <?php if((int)$article['current_internal_revision_id']>0 && (int)$article['current_internal_revision_id']!==(int)$revision['id']): ?>
              <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge/compare?id=<?= (int)$article['id'] ?>&from=<?= (int)$revision['id'] ?>&to=<?= (int)$article['current_internal_revision_id'] ?>">Comparar con vigente</a>
            <?php endif; ?>

            <?php if($canRestore && ($article['lifecycle_status']??'ACTIVE')!=='ARCHIVED'): ?>
              <details>
                <summary class="btn btn-outline-secondary">Restaurar como borrador</summary>
                <form method="post" action="<?= APP_BASE_URL ?>/knowledge/restore" data-single-submit class="itsm-editor-form">
                  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                  <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                  <input type="hidden" name="source_revision_id" value="<?= (int)$revision['id'] ?>">
                  <label>Motivo<textarea class="form-control" name="restore_note" rows="2" required placeholder="Explica por qué necesitas recuperar esta versión."></textarea></label>
                  <button class="btn btn-primary" type="submit">Crear borrador desde esta versión</button>
                </form>
              </details>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
