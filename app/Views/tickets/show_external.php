<?php
use App\Core\{Auth,Csrf,Database};

$statusLabels=$statusLabels??[];
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
$pageSection='Mis casos';
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
        <span><?= htmlspecialchars($ticket['park_name']??'Ubicación no especificada') ?></span>
        <span><?= htmlspecialchars($ticket['category_name']??'Sin categoría') ?></span>
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
            <div><span class="ticket-kicker">Información del caso</span><h2>Qué está pasando</h2><p>Revisa primero el problema y el contexto antes de enviar una actualización.</p></div>
            <span class="external-responsible"><?= !empty($ticket['assigned_name'])?'Responsable: '.htmlspecialchars($ticket['assigned_name']):'Responsable pendiente' ?></span>
          </div>

          <div class="external-problem-primary">
            <span>Problema reportado</span>
            <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
          </div>

          <div class="external-problem-facts external-problem-facts-secondary">
            <div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div>
            <div><span>Tipo de solicitud</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div>
          </div>
        </div>
      </section>

      <section class="card external-reply-card" id="responder">
        <div class="card-body">
          <div class="external-section-head">
            <div><span class="ticket-kicker">Tu actualización</span><h2>¿Qué necesitas informar?</h2><p>Envía un avance, una consulta, evidencia o el resultado de tu trabajo.</p></div>
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
            <label>Mensaje
              <textarea class="form-control" name="body" rows="5" data-external-message placeholder="Describe qué revisaste, qué encontraste, qué hiciste o qué información necesitas."></textarea>
            </label>
            <?php endif; ?>
            <?php if($canUpload): ?>
            <label class="external-upload-label">Evidencia o archivo <span class="subtle">Opcional · máximo 10 MB</span>
              <input class="external-file-input" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.doc,.docx,.xls,.xlsx">
            </label>
            <?php endif; ?>
            <div class="external-send-row">
              <span><?= $canComment&&$canUpload?'Puedes enviar un mensaje, un archivo o ambos.':($canComment?'Tu actualización llegará al responsable del caso.':'Puedes adjuntar evidencia para este caso.') ?></span>
              <button class="btn btn-primary" type="submit">Enviar actualización</button>
            </div>
          </form>
          <?php else: ?>
            <div class="external-readonly"><strong>Este caso está en modo consulta.</strong><span>Puedes revisar el seguimiento y los archivos disponibles.</span></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="card external-conversation-card" id="conversacion">
        <div class="card-body">
          <div class="external-section-head">
            <div><span class="ticket-kicker">Seguimiento</span><h2>Conversación</h2><p>Aquí queda el registro de las actualizaciones del equipo de soporte y de tu equipo.</p></div>
            <span class="external-message-count"><?= count($comments)+(count($looseAttachments)) ?> actualización(es)</span>
          </div>
          <div class="conversation-list external-conversation-list">
            <?php foreach($comments as $c):
              $mine=(int)($c['author_user_id']??0)===(int)Auth::id();
              $author=$mine?'Tu equipo':'Equipo de soporte';
              if(($c['author_access_type']??'')==='EXTERNAL'&&!$mine)$author='Colaborador';
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
            <?php if(!$comments&&!$looseAttachments): ?><div class="external-empty-conversation"><strong>Aún no hay actualizaciones.</strong><span>Cuando exista un avance aparecerá aquí.</span></div><?php endif; ?>
          </div>
        </div>
      </section>
    </main>

    <aside class="external-work-side">
      <section class="card external-side-card">
        <div class="card-body">
          <span class="ticket-kicker">Tu participación</span>
          <h2>Disponible en este caso</h2>
          <div class="external-capability-list">
            <div class="<?= $canComment?'is-enabled':'is-disabled' ?>"><span><?= $canComment?'✓':'—' ?></span><div><strong>Responder</strong><small><?= $canComment?'Puedes enviar avances y consultas.':'Modo consulta.' ?></small></div></div>
            <div class="<?= $canUpload?'is-enabled':'is-disabled' ?>"><span><?= $canUpload?'✓':'—' ?></span><div><strong>Adjuntar evidencia</strong><small><?= $canUpload?'Puedes enviar archivos de respaldo.':'Modo consulta.' ?></small></div></div>
          </div>
        </div>
      </section>

      <section class="card external-side-card">
        <div class="card-body">
          <span class="ticket-kicker">Flujo</span>
          <h2>Qué pasa después</h2>
          <ol class="external-next-steps">
            <li><span>1</span><div><strong>Envías una actualización</strong><small>La información queda registrada en el caso.</small></div></li>
            <li><span>2</span><div><strong>Soporte revisa</strong><small>El responsable continúa la gestión y puede responderte.</small></div></li>
            <li><span>3</span><div><strong>El caso se finaliza</strong><small>El estado y la solución quedan disponibles en el seguimiento.</small></div></li>
          </ol>
        </div>
      </section>
    </aside>
  </div>
</div>

<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
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