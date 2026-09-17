<?php
use App\Core\Csrf;
$pageTitle=$article['article_number'];$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';

$stateLabel=$statuses[$article['status']]??$article['status'];
$workingState=$workingRevision['state']??null;
$internalVersion=$currentInternal['revision_number']??null;
$publicVersion=$currentPublic['revision_number']??null;
?>
<div class="itsm-page knowledge-article-page">
  <div class="page-heading">
    <div>
      <span class="ticket-kicker"><?= htmlspecialchars($article['article_number']) ?> · Versión <?= (int)$article['revision_number'] ?></span>
      <h1 class="page-title"><?= htmlspecialchars($article['title']) ?></h1>
      <p class="page-subtitle"><?= htmlspecialchars($stateLabel) ?> · <?= htmlspecialchars($article['category_name']??'Sin categoría') ?></p>
    </div>
    <div class="topbar-actions">
      <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge">← Volver</a>
      <?php if($canEdit && $workingState==='DRAFT'): ?><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/knowledge/edit?id=<?= (int)$article['id'] ?>">Editar borrador</a><?php endif; ?>
    </div>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars((string)$flash['message']) ?></div><?php endif; ?>

  <?php if($canManage): ?>
    <section class="card knowledge-state-bar">
      <div class="card-body">
        <div>
          <strong>Estado editorial</strong>
          <span>
            <?= htmlspecialchars($stateLabel) ?>
            <?php if($workingState==='IN_REVIEW'): ?> · En revisión<?php endif; ?>
          </span>
        </div>

        <div class="topbar-actions">
          <?php if($canEdit && $workingState==='DRAFT'): ?>
            <form method="post" action="<?= APP_BASE_URL ?>/knowledge/submit-review" data-single-submit>
              <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
              <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
              <input type="hidden" name="revision_id" value="<?= (int)$workingRevision['id'] ?>">
              <button class="btn btn-primary" type="submit">Enviar a revisión</button>
            </form>
          <?php elseif($canPublishInternal && $workingState==='IN_REVIEW'): ?>
            <form method="post" action="<?= APP_BASE_URL ?>/knowledge/publish-internal" data-single-submit>
              <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
              <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
              <input type="hidden" name="revision_id" value="<?= (int)$workingRevision['id'] ?>">
              <button class="btn btn-primary" type="submit">Publicar para soporte</button>
            </form>
          <?php endif; ?>

          <details class="knowledge-actions-menu">
            <summary class="btn btn-outline-secondary">Más acciones</summary>
            <div class="card">
              <div class="card-body">
                <?php if($canReview && $workingState==='IN_REVIEW'): ?>
                  <form method="post" action="<?= APP_BASE_URL ?>/knowledge/return-draft" data-single-submit>
                    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <input type="hidden" name="revision_id" value="<?= (int)$workingRevision['id'] ?>">
                    <label>Qué debe corregirse<textarea class="form-control" name="review_note" rows="2" required></textarea></label>
                    <button class="btn btn-outline-secondary" type="submit">Devolver a borrador</button>
                  </form>
                <?php endif; ?>

                <?php if($canPublishPublic && $currentInternal && (int)($currentPublic['id']??0)!==(int)$currentInternal['id']): ?>
                  <form method="post" action="<?= APP_BASE_URL ?>/knowledge/publish-public" data-single-submit>
                    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <input type="hidden" name="revision_id" value="<?= (int)$currentInternal['id'] ?>">
                    <button class="btn btn-outline-secondary" type="submit">Disponible para solicitantes</button>
                  </form>
                <?php endif; ?>

                <?php if($canPublishInternal && $article['status']!=='ARCHIVED'): ?>
                  <form method="post" action="<?= APP_BASE_URL ?>/knowledge/archive" data-single-submit>
                    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <button class="btn btn-outline-secondary" type="submit">Archivar artículo</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </details>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <div class="knowledge-layout">
    <article class="card">
      <div class="card-body">
        <span class="ticket-kicker">Resumen</span>
        <p class="knowledge-summary"><?= nl2br(htmlspecialchars($article['summary']?:'Sin resumen adicional.')) ?></p>
        <div class="knowledge-content"><?= nl2br(htmlspecialchars($article['content'])) ?></div>
      </div>
    </article>

    <aside class="card">
      <div class="card-body">
        <dl class="case-status-list">
          <div><dt>Autor</dt><dd><?= htmlspecialchars($article['author_name']??'No disponible') ?></dd></div>
          <div><dt>Actualizado</dt><dd><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$article['updated_at']))) ?></dd></div>
          <div><dt>Publicado para soporte</dt><dd><?= $internalVersion?'Versión '.(int)$internalVersion:'Aún no publicado' ?></dd></div>
          <div><dt>Disponible para solicitantes</dt><dd><?= $publicVersion?'Versión '.(int)$publicVersion:'No' ?></dd></div>
        </dl>

        <?php if($problems): ?>
          <div class="knowledge-related">
            <span class="ticket-kicker">Problemas relacionados</span>
            <?php foreach($problems as $p): ?>
              <a href="<?= APP_BASE_URL ?>/problems/view?id=<?= (int)$p['id'] ?>">
                <strong><?= htmlspecialchars($p['problem_number']) ?></strong>
                <small><?= htmlspecialchars($p['title']) ?><?= $p['is_primary']?' · Principal':'' ?></small>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if($canHistory && $revisions): ?>
          <div class="knowledge-related">
            <span class="ticket-kicker">Historial</span>
            <?php foreach(array_slice($revisions,0,5) as $revision): ?>
              <span><strong>Versión <?= (int)$revision['revision_number'] ?></strong> · <?= htmlspecialchars($statuses[$revision['state']]??$revision['state']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
