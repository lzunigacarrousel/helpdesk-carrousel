<?php
use App\Core\Csrf;
$pageTitle=$article?'Editar borrador':'Nuevo artículo';$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';
$src=$article?:$prefill;
?>
<div class="itsm-page itsm-editor-page">
  <div class="page-heading">
    <div>
      <span class="ticket-kicker">Conocimiento</span>
      <h1 class="page-title"><?= $article?'Editar borrador':'Crear artículo' ?></h1>
      <p class="page-subtitle">Documenta una solución clara. Publicarla para soporte requiere una revisión posterior.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge">← Volver</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-info knowledge-validation-message"><?= htmlspecialchars((string)$flash['message']) ?></div><?php endif; ?>

  <section class="card">
    <div class="card-body">
      <form method="post" action="<?= APP_BASE_URL ?><?= $article?'/knowledge/update':'/knowledge/create' ?>" data-single-submit class="itsm-editor-form">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
        <?php if($article): ?>
          <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
          <input type="hidden" name="revision_id" value="<?= (int)($article['editing_revision_id']??0) ?>">
        <?php else: ?>
          <input type="hidden" name="source_ticket_id" value="<?= (int)($prefill['source_ticket_id']??0) ?>">
          <input type="hidden" name="source_problem_id" value="<?= (int)($prefill['source_problem_id']??0) ?>">
        <?php endif; ?>

        <label>
          Título
          <input class="form-control" name="title" maxlength="220" minlength="5" required value="<?= htmlspecialchars((string)($src['title']??'')) ?>" placeholder="Ej. Restablecer conexión POS después de una caída">
        </label>

        <label>
          Resumen
          <textarea class="form-control" name="summary" rows="2" placeholder="Cuándo usar esta solución y qué problema resuelve."><?= htmlspecialchars((string)($src['summary']??'')) ?></textarea>
        </label>

        <label>
          Categoría
          <select class="form-control" name="category_id">
            <option value="0">Sin categoría</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)($src['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Contenido
          <textarea class="form-control knowledge-content-editor" name="content" rows="18" minlength="20" required placeholder="Problema&#10;Síntomas&#10;Causa&#10;Solución&#10;Pasos&#10;Validación&#10;Prevención"><?= htmlspecialchars((string)($src['content']??'')) ?></textarea>
        </label>

        <?php if($article): ?>
          <label>
            Nota del cambio
            <textarea class="form-control" name="change_note" rows="2" placeholder="Resume qué mejoraste en esta versión."><?= htmlspecialchars((string)($src['change_note']??'')) ?></textarea>
          </label>
        <?php endif; ?>

        <div class="knowledge-editor-note">
          <strong>Estructura recomendada</strong>
          <span>Problema · Síntomas · Causa · Solución · Pasos · Validación · Prevención. No incluyas credenciales ni información sensible.</span>
        </div>

        <button class="btn btn-primary" type="submit">Guardar borrador</button>
      </form>
    </div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
