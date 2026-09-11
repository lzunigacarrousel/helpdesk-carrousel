<?php
use App\Core\Csrf;
$pageTitle=$article?'Editar artículo':'Nuevo artículo';$pageSection='Base de conocimiento';$activeNav='knowledge';$helpContext='knowledge';
require APP_ROOT.'/app/Views/shared/app_start.php';
$src=$article?:$prefill;
?>
<div class="itsm-page itsm-editor-page">
  <div class="page-heading"><div><span class="ticket-kicker">Knowledge Management</span><h1 class="page-title"><?= $article?'Editar artículo':'Crear artículo' ?></h1><p class="page-subtitle">Los artículos nuevos siempre se guardan como borrador. Revísalos antes de publicar.</p></div><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/knowledge">← Volver</a></div>
  <?php if(!empty($flash)): ?><div class="alert alert-info knowledge-validation-message"><?= htmlspecialchars((string)$flash['message']) ?></div><?php endif; ?>
  <section class="card"><div class="card-body"><form method="post" action="<?= APP_BASE_URL ?><?= $article?'/knowledge/update':'/knowledge/create' ?>" data-single-submit class="itsm-editor-form">
    <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><?php if($article): ?><input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>"><?php else: ?><input type="hidden" name="source_ticket_id" value="<?= (int)($prefill['source_ticket_id']??0) ?>"><input type="hidden" name="source_problem_id" value="<?= (int)($prefill['source_problem_id']??0) ?>"><?php endif; ?>
    <label>Título<input class="form-control" name="title" maxlength="220" minlength="5" required value="<?= htmlspecialchars((string)($src['title']??'')) ?>" placeholder="Ej. Restablecer conexión POS después de caída de enlace"></label>
    <label>Resumen<textarea class="form-control" name="summary" rows="2" placeholder="Una explicación breve para saber cuándo usar este artículo."><?= htmlspecialchars((string)($src['summary']??'')) ?></textarea></label>
    <div class="grid grid-2"><label>Categoría<select class="form-control" name="category_id"><option value="0">Sin categoría</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($src['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></label><label>Visibilidad<select class="form-control" name="visibility"><?php foreach($visibility as $code=>$label): ?><option value="<?= $code ?>" <?= (($src['visibility']??'INTERNAL')===$code)?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><small class="field-help">Público: visible para solicitantes autorizados cuando esté publicado. Interno: solo personal Carrousel autorizado.</small></label></div>
    <label>Contenido<textarea class="form-control knowledge-content-editor" name="content" rows="18" minlength="20" required placeholder="Problema\nSíntomas\nCausa\nSolución\nPasos\nValidación\nNotas"><?= htmlspecialchars((string)($src['content']??'')) ?></textarea></label>
    <div class="knowledge-editor-note"><strong>Estructura recomendada</strong><span>Problema · Síntomas · Causa · Solución · Pasos · Validación · Notas. No incluyas credenciales, teléfonos, correos personales ni información sensible.</span></div>
    <button class="btn btn-primary" type="submit"><?= $article?'Guardar cambios':'Guardar borrador' ?></button>
  </form></div></section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
