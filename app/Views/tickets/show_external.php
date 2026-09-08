<?php
use App\Core\{Auth,Csrf,Database};

$statusLabels=$statusLabels??[];
$priorityLabels=$priorityLabels??[];
$status=(string)$ticket['status'];
$comments=[];$attachmentsByComment=[];$looseAttachments=[];$canComment=false;$canUpload=false;

try{
    $pdo=Database::pdo();
    $aq=$pdo->prepare("SELECT can_comment,can_upload FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1");
    $aq->execute([(int)$ticket['id'],(int)Auth::id()]);
    $access=$aq->fetch();
    $canComment=(bool)($access['can_comment']??false);
    $canUpload=(bool)($access['can_upload']??false);

    $cq=$pdo->prepare("SELECT tc.*,u.access_type author_access_type,r.code author_role FROM ticket_comments tc LEFT JOIN users u ON u.id=tc.author_user_id LEFT JOIN roles r ON r.id=u.role_id WHERE tc.ticket_id=? AND tc.deleted_at IS NULL AND tc.visibility='PUBLIC' ORDER BY tc.created_at,tc.id");
    $cq->execute([(int)$ticket['id']]);
    $comments=$cq->fetchAll();

    $at=$pdo->prepare("SELECT ta.* FROM ticket_attachments ta WHERE ta.ticket_id=? AND ta.visibility='PUBLIC' ORDER BY ta.created_at,ta.id");
    $at->execute([(int)$ticket['id']]);
    foreach($at->fetchAll() as $file){
        if(!empty($file['comment_id']))$attachmentsByComment[(int)$file['comment_id']][]=$file;
        else $looseAttachments[]=$file;
    }
}catch(\Throwable $e){}

$pageTitle=$ticket['ticket_number'];
$pageSection='Caso compartido';
$activeNav='mine';
$helpContext='ticket';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="external-workspace" data-external-workspace>
  <div class="external-case-head">
    <div>
      <div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div>
      <h1 class="page-title"><?= htmlspecialchars($ticket['subject']) ?></h1>
      <div class="external-case-meta">
        <span class="external-state-chip"><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></span>
        <span>Prioridad <?= htmlspecialchars(strtolower($priorityLabels[$ticket['priority']]??$ticket['priority'])) ?></span>
        <span><?= htmlspecialchars($ticket['park_name']??'Ubicación no especificada') ?></span>
      </div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/mis-tickets">← Mis casos</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <div class="external-work-grid">
    <main class="external-work-main">
      <section class="card external-problem-card">
        <div class="card-body">
          <div class="external-section-head">
            <div><span class="ticket-kicker">1 · Revisa el caso</span><h2>Qué necesita Carrousel</h2></div>
            <span class="external-responsible"><?= !empty($ticket['assigned_name'])?'Responsable: '.htmlspecialchars($ticket['assigned_name']):'Pendiente de responsable interno' ?></span>
          </div>
          <div class="external-problem-facts">
            <div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div>
            <div><span>Tipo</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div>
          </div>
          <div class="external-problem-description"><span>Detalle reportado</span><p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p></div>
        </div>
      </section>

      <section class="card external-reply-card" id="responder">
        <div class="card-body">
          <div class="external-section-head">
            <div><span class="ticket-kicker">2 · Actualiza el caso</span><h2>¿Qué necesitas informar a Carrousel?</h2><p>Elige una opción para empezar y agrega el detalle necesario.</p></div>
          </div>

          <?php if($canComment||$canUpload): ?>
          <div class="external-quick-actions" aria-label="Tipos de actualización rápida">
            <?php if($canComment): ?>
            <button type="button" class="external-quick-btn" data-external-template="Avance del caso:">Enviar avance</button>
            <button type="button" class="external-quick-btn" data-external-template="Necesito información adicional:">Solicitar información</button>
            <button type="button" class="external-quick-btn is-success" data-external-template="Trabajo realizado / listo para revisión:">Trabajo realizado</button>
            <button type="button" class="external-quick-btn" data-external-template="Observación importante:">Agregar observación</button>
            <?php endif; ?>
          </div>

          <form class="external-response-form" method="post" action="<?= APP_BASE_URL ?>/tickets/respond" enctype="multipart/form-data" data-single-submit data-action-message="Enviando actualización…">
            <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
            <input type="hidden" name="visibility" value="PUBLIC">
            <?php if($canComment): ?>
            <label>Mensaje para Carrousel
              <textarea class="form-control" name="body" rows="5" data-external-message placeholder="Describe qué revisaste, qué encontraste, qué hiciste o qué necesitas de Carrousel."></textarea>
            </label>
            <?php endif; ?>
            <?php if($canUpload): ?>
            <label class="external-upload-label">Evidencia o archivo <span class="subtle">Opcional · máximo 10 MB</span>
              <input class="external-file-input" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx">
            </label>
            <?php endif; ?>
            <div class="external-send-row">
              <span><?= $canComment&&$canUpload?'Puedes enviar mensaje, archivo o ambos.':($canComment?'Envía tu actualización al equipo de Carrousel.':'Puedes adjuntar evidencia para este caso.') ?></span>
              <button class="btn btn-primary" type="submit">Enviar a Carrousel</button>
            </div>
          </form>
          <?php else: ?>
            <div class="external-readonly"><strong>Este acceso es solo de consulta.</strong><span>Si necesitas responder, comunícate con tu contacto de Carrousel para habilitar participación.</span></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="card external-conversation-card" id="conversacion">
        <div class="card-body">
          <div class="external-section-head">
            <div><span class="ticket-kicker">3 · Seguimiento</span><h2>Conversación</h2><p>Aquí queda el registro de lo que Carrousel y tu equipo han informado sobre este caso.</p></div>
            <span class="external-message-count"><?= count($comments)+(count($looseAttachments)) ?> actualización(es)</span>
          </div>
          <div class="conversation-list external-conversation-list">
            <?php foreach($comments as $c):
              $mine=(int)($c['author_user_id']??0)===(int)Auth::id();
              $author=$mine?'Tu equipo':'Carrousel';
              if(($c['author_access_type']??'')==='EXTERNAL'&&!$mine)$author='Proveedor externo';
            ?>
              <article class="conversation-message <?= $mine?'is-mine':'' ?>">
                <div class="conversation-meta"><strong><?= htmlspecialchars($author) ?></strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($c['created_at']))) ?></span></div>
                <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
                <?php foreach($attachmentsByComment[(int)$c['id']]??[] as $f): ?><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?> <small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB</small></a><?php endforeach; ?>
              </article>
            <?php endforeach; ?>
            <?php foreach($looseAttachments as $f): ?>
              <article class="conversation-message"><div class="conversation-meta"><strong>Archivo compartido</strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($f['created_at']))) ?></span></div><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?></a></article>
            <?php endforeach; ?>
            <?php if(!$comments&&!$looseAttachments): ?><div class="external-empty-conversation"><strong>Aún no hay actualizaciones.</strong><span>Cuando envíes el primer avance aparecerá aquí.</span></div><?php endif; ?>
          </div>
        </div>
      </section>
    </main>

    <aside class="external-work-side">
      <section class="card external-side-card">
        <div class="card-body">
          <span class="ticket-kicker">Tu participación</span>
          <h2>Qué puedes hacer</h2>
          <div class="external-capability-list">
            <div class="<?= $canComment?'is-enabled':'is-disabled' ?>"><span><?= $canComment?'✓':'—' ?></span><div><strong>Responder</strong><small><?= $canComment?'Puedes enviar avances y consultas.':'No habilitado en este caso.' ?></small></div></div>
            <div class="<?= $canUpload?'is-enabled':'is-disabled' ?>"><span><?= $canUpload?'✓':'—' ?></span><div><strong>Adjuntar evidencia</strong><small><?= $canUpload?'Puedes enviar archivos de respaldo.':'No habilitado en este caso.' ?></small></div></div>
          </div>
        </div>
      </section>

      <section class="card external-side-card">
        <div class="card-body">
          <span class="ticket-kicker">Flujo</span>
          <h2>Qué pasa después</h2>
          <ol class="external-next-steps">
            <li><span>1</span><div><strong>Envías tu actualización</strong><small>Carrousel recibe la información y el correo de aviso.</small></div></li>
            <li><span>2</span><div><strong>Sistemas revisa</strong><small>El responsable interno continúa la gestión del ticket.</small></div></li>
            <li><span>3</span><div><strong>Carrousel cierra el caso</strong><small>El estado final y la resolución quedan documentados internamente.</small></div></li>
          </ol>
        </div>
      </section>
    </aside>
  </div>
</div>

<script>
document.querySelectorAll('[data-external-template]').forEach(function(button){
  button.addEventListener('click',function(){
    const field=document.querySelector('[data-external-message]');
    if(!field)return;
    const template=(button.getAttribute('data-external-template')||'').trim();
    const prefix=template+'\n';
    if(!field.value.trim())field.value=prefix;
    else if(!field.value.startsWith(template))field.value=prefix+field.value;
    field.focus();
    field.setSelectionRange(field.value.length,field.value.length);
  });
});
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>