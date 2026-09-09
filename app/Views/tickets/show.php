<?php
use App\Core\{Csrf,Database,Auth};
$statusLabels=$statusLabels??[];$priorityLabels=$priorityLabels??[];$status=(string)$ticket['status'];
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$isRequester=!$isSupport&&!$isExternal;
$eventLabels=['CREATED'=>'Solicitud creada','CLAIMED'=>'Caso tomado','REASSIGNED'=>'Responsable cambiado','RELEASED'=>'Devuelto a disponibles','STATUS_CHANGED'=>'Estado actualizado','COMMENTED'=>'Nueva respuesta','RESOLUTION_RECORDED'=>'Solución documentada','RESOLVED'=>'Caso resuelto','CLOSED'=>'Caso cerrado','REOPENED'=>'Caso reabierto'];
$resolution=null;$similar=[];$comments=[];$attachmentsByComment=[];$looseAttachments=[];$externalCanComment=true;$externalCanUpload=true;
try{
    $pdo=Database::pdo();
    $rq=$pdo->prepare("SELECT tr.*,u.full_name resolved_by_name FROM ticket_resolutions tr LEFT JOIN users u ON u.id=tr.resolved_by WHERE tr.ticket_id=? LIMIT 1");
    $rq->execute([(int)$ticket['id']]);$resolution=$rq->fetch()?:null;

    if($isSupport){
        $sq=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.resolved_at,tr.resolution_type,tr.solution_applied,p.name park_name
            FROM tickets t JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN parks p ON p.id=t.park_id
            WHERE t.id<>? AND t.deleted_at IS NULL AND t.status IN('RESOLVED','CLOSED') AND tr.is_reusable=1
              AND (t.category_id=? OR (? IS NOT NULL AND t.park_id=?))
            ORDER BY (t.category_id=? ) DESC,(t.park_id=? ) DESC,t.resolved_at DESC LIMIT 5");
        $park=$ticket['park_id']??null;$cat=(int)($ticket['category_id']??0);
        $sq->execute([(int)$ticket['id'],$cat,$park,$park,$cat,$park]);$similar=$sq->fetchAll();
    }

    $visibilityWhere=$isSupport?'':' AND tc.visibility=\'PUBLIC\'';
    $cq=$pdo->prepare("SELECT tc.*,u.access_type author_access_type,r.code author_role FROM ticket_comments tc LEFT JOIN users u ON u.id=tc.author_user_id LEFT JOIN roles r ON r.id=u.role_id WHERE tc.ticket_id=? AND tc.deleted_at IS NULL {$visibilityWhere} ORDER BY tc.created_at,tc.id");
    $cq->execute([(int)$ticket['id']]);$comments=$cq->fetchAll();

    $attachmentWhere=$isSupport?'':' AND ta.visibility=\'PUBLIC\'';
    $at=$pdo->prepare("SELECT ta.* FROM ticket_attachments ta WHERE ta.ticket_id=? {$attachmentWhere} ORDER BY ta.created_at,ta.id");
    $at->execute([(int)$ticket['id']]);
    foreach($at->fetchAll() as $file){if(!empty($file['comment_id']))$attachmentsByComment[(int)$file['comment_id']][]=$file;else $looseAttachments[]=$file;}
}catch(\Throwable $e){}
$pageTitle=$ticket['ticket_number'];$pageSection=$isSupport?'Centro de soporte':'Mis solicitudes';$activeNav=$isSupport?'support':'mine';$helpContext='ticket';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="ticket-workspace case-focus-workspace">
  <div class="case-focus-head">
    <div>
      <div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div>
      <h1 class="page-title"><?= htmlspecialchars($ticket['subject']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?><?= $isSupport?'/tickets/queue':'/mis-tickets' ?>">← Volver</a>
  </div>

  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <div class="case-focus-grid">
    <section class="card case-problem-card">
      <div class="card-body">
        <span class="ticket-kicker">1 · Qué está pasando</span>
        <h2><?= htmlspecialchars($ticket['subject']) ?></h2>
        <div class="case-problem-description"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div>
        <div class="case-problem-meta">
          <div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div>
          <div><span>Tipo de solicitud</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div>
          <?php if($isSupport): ?><div><span>Solicitante</span><strong><?= htmlspecialchars($ticket['requester_name']) ?></strong><small><?= htmlspecialchars($ticket['requester_email']) ?><?= !empty($ticket['requester_phone'])?' · '.htmlspecialchars($ticket['requester_phone']):'' ?></small></div><?php endif; ?>
          <?php if($isRequester): ?><div><span>Reportado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['created_at']))) ?></strong></div><?php endif; ?>
        </div>
      </div>
    </section>

    <aside class="card case-status-card">
      <div class="card-body">
        <span class="ticket-kicker">Estado del caso</span>
        <div class="case-status-main"><strong><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></strong><small>Actualizado <?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['updated_at']??$ticket['created_at']))) ?></small></div>
        <dl class="case-status-list">
          <div><dt>Prioridad</dt><dd><?= htmlspecialchars($priorityLabels[$ticket['priority']]??$ticket['priority']) ?></dd></div>
          <div><dt>Responsable</dt><dd><?= htmlspecialchars($ticket['assigned_name']??'Aún sin asignar') ?></dd></div>
          <div><dt>Creado</dt><dd><?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['created_at']))) ?></dd></div>
          <?php if($isSupport&&!empty($ticket['resolution_due_at'])): ?><div><dt>Límite de resolución</dt><dd><?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['resolution_due_at']))) ?></dd></div><?php endif; ?>
        </dl>
      </div>
    </aside>
  </div>

  <?php if($isSupport): ?>
  <section class="card case-action-card">
    <div class="card-body">
      <div class="case-section-head"><div><span class="ticket-kicker">2 · Qué debes hacer</span><h2>Atención del caso</h2><p>Actualiza el caso mientras trabajas y documenta la solución antes de resolverlo.</p></div></div>
      <div class="ticket-action-bar case-action-bar">
        <?php if($canClaim): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-primary ticket-primary-action" type="submit">Tomar y atender</button></form><?php endif; ?>
        <?php if($canChangeStatus): ?>
          <?php if(in_array($status,['PENDING','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="IN_PROGRESS"><button class="btn btn-primary" type="submit">Continuar atención</button></form><?php endif; ?>
          <?php if(in_array($status,['IN_PROGRESS','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="PENDING"><button class="btn btn-outline-secondary" type="submit">Poner en espera</button></form><?php endif; ?>
          <?php if($status==='RESOLVED'): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="CLOSED"><button class="btn btn-primary" type="submit">Cerrar caso</button></form><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="REOPENED"><button class="btn btn-outline-secondary" type="submit">Reabrir</button></form><?php endif; ?>
        <?php endif; ?>
        <?php if($canRelease): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/release" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-outline-secondary" type="submit">Devolver a la cola</button></form><?php endif; ?>
      </div>

      <?php if($canReassign&&!empty($supportUsers)): ?><details class="ticket-more-actions case-assign-details"><summary>Asignar a otra persona</summary><form class="ticket-assign-form" method="post" action="<?= APP_BASE_URL ?>/tickets/assign" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><select class="form-control" name="assigned_to" required><option value="">Selecciona responsable</option><?php foreach($supportUsers as $su): ?><option value="<?= (int)$su['id'] ?>" <?= (int)($ticket['assigned_to']??0)===(int)$su['id']?'selected':'' ?>><?= htmlspecialchars($su['full_name']) ?> · <?= htmlspecialchars($su['role_name']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Asignar</button></form></details><?php endif; ?>

      <?php if($canChangeStatus && in_array($status,['IN_PROGRESS','PENDING','REOPENED'],true)): ?>
      <div class="resolution-capture case-resolution-capture">
        <div class="case-section-head"><div><span class="ticket-kicker">Cerrar el aprendizaje</span><h2>¿Cómo se resolvió?</h2><p>Registra causa y solución. Esto alimenta automáticamente informes y casos similares.</p></div></div>
        <form method="post" action="<?= APP_BASE_URL ?>/tickets/resolve" data-single-submit class="resolution-form"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><div class="resolution-grid">
          <label>Tipo de solución<select class="form-control" name="resolution_type" required><option value="">Selecciona</option><option value="CONFIGURATION">Configuración</option><option value="RESTART">Reinicio / restablecimiento</option><option value="REPLACEMENT">Cambio o reemplazo</option><option value="PROVIDER">Gestión con proveedor</option><option value="USER_GUIDANCE">Orientación al usuario</option><option value="SOFTWARE">Software / aplicación</option><option value="NETWORK">Red / conectividad</option><option value="HARDWARE">Hardware / equipo</option><option value="PERMISSION">Acceso / permisos</option><option value="MAINTENANCE">Mantenimiento</option><option value="OTHER">Otro</option></select></label>
          <label>Causa encontrada<input class="form-control" name="root_cause" required placeholder="Ej. Configuración incorrecta del DNS"></label>
          <label class="resolution-full">Qué se hizo para resolverlo<textarea class="form-control" name="solution_applied" rows="3" required placeholder="Describe los pasos o la acción que solucionó el problema"></textarea></label>
          <label class="resolution-full">Qué hacer para evitar que se repita <span class="subtle">(opcional)</span><textarea class="form-control" name="preventive_action" rows="2" placeholder="Recomendación, mantenimiento, cambio de proceso, seguimiento..."></textarea></label>
        </div><button class="btn btn-primary" type="submit">Guardar solución y marcar resuelto</button></form>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="card conversation-card case-conversation-card" id="conversacion">
    <div class="card-body">
      <div class="case-section-head"><div><span class="ticket-kicker"><?= $isSupport?'3':'2' ?> · Qué ha pasado hasta ahora</span><h2>Seguimiento del caso</h2><p><?= $isSupport?'Las respuestas visibles las puede consultar el solicitante. Las notas internas quedan solo para Soporte.':'Aquí puedes revisar respuestas de Sistemas y agregar información a tu solicitud.' ?></p></div></div>
      <div class="conversation-list">
        <?php foreach($comments as $c): $mine=(int)($c['author_user_id']??0)===(int)Auth::id();$internal=$c['visibility']==='INTERNAL';$author=$c['author_name']?:'Usuario';if(($c['author_access_type']??'')==='EXTERNAL')$author.=' · Proveedor';elseif(($c['author_role']??'')==='ADMIN'||($c['author_role']??'')==='SEMIADMIN'||($c['author_role']??'')==='TECHNICIAN')$author.=' · Carrousel'; ?>
          <article class="conversation-message <?= $mine?'is-mine':'' ?> <?= $internal?'is-internal':'' ?>"><div class="conversation-meta"><strong><?= htmlspecialchars($author) ?></strong><span><?= $internal?'Nota interna · ':'' ?><?= htmlspecialchars(date('d/m/Y H:i',strtotime($c['created_at']))) ?></span></div><p><?= nl2br(htmlspecialchars($c['body'])) ?></p><?php foreach($attachmentsByComment[(int)$c['id']]??[] as $f): ?><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?> <small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB</small></a><?php endforeach; ?></article>
        <?php endforeach; ?>
        <?php foreach($looseAttachments as $f): ?><article class="conversation-message"><div class="conversation-meta"><strong>Archivo adjunto</strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($f['created_at']))) ?></span></div><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?></a></article><?php endforeach; ?>
        <?php if(!$comments&&!$looseAttachments): ?><div class="empty-state conversation-empty">Todavía no hay respuestas. Usa el espacio de abajo para iniciar el seguimiento.</div><?php endif; ?>
      </div>

      <?php if(!in_array($status,['CLOSED','CANCELLED'],true)): ?>
      <form method="post" action="<?= APP_BASE_URL ?>/tickets/respond" enctype="multipart/form-data" class="conversation-form" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <?php if($isSupport): ?><div class="conversation-mode"><label><input type="radio" name="visibility" value="PUBLIC" checked> Respuesta visible</label><label><input type="radio" name="visibility" value="INTERNAL"> Nota interna</label></div><?php endif; ?>
        <label>Escribe una actualización<textarea class="form-control" name="body" rows="4" placeholder="<?= $isSupport?'Explica avance, diagnóstico, solicitud de información o resultado...':'Agrega información o responde a Sistemas...' ?>"></textarea></label>
        <label>Adjuntar archivo <span class="subtle">(opcional · máximo 10 MB)</span><input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label>
        <button class="btn btn-primary" type="submit">Enviar actualización</button>
      </form>
      <?php endif; ?>
    </div>
  </section>

  <div class="case-secondary-grid">
    <section class="card"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Historial</span><h2>Actividad</h2></div></div><div class="ticket-timeline"><?php foreach($events as $e): ?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?= htmlspecialchars($eventLabels[$e['event_type']]??ucfirst(strtolower(str_replace('_',' ',$e['event_type'])))) ?></strong><p><?= htmlspecialchars(date('d/m/Y H:i',strtotime($e['created_at']))) ?><?= !empty($e['actor_name'])?' · '.htmlspecialchars($e['actor_name']):'' ?></p></div></div><?php endforeach; ?><?php if(!$events): ?><div class="empty-state">Aún no hay movimientos registrados.</div><?php endif; ?></div></div></section>

    <?php if($resolution): ?><section class="card resolution-summary"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Resolución registrada</span><h2>Cómo se resolvió</h2></div><?php if(!empty($resolution['resolved_by_name'])):?><small><?= htmlspecialchars($resolution['resolved_by_name']) ?> · <?= htmlspecialchars(date('d/m/Y H:i',strtotime($resolution['updated_at']))) ?></small><?php endif; ?></div>
      <?php if($isSupport): ?><div class="resolution-read-grid"><div><span>Tipo</span><strong><?= htmlspecialchars(str_replace('_',' ',ucfirst(strtolower($resolution['resolution_type'])))) ?></strong></div><div><span>Causa encontrada</span><p><?= nl2br(htmlspecialchars($resolution['root_cause']??'No indicada')) ?></p></div><div class="resolution-full"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php if(!empty($resolution['preventive_action'])):?><div class="resolution-full"><span>Prevención / seguimiento</span><p><?= nl2br(htmlspecialchars($resolution['preventive_action'])) ?></p></div><?php endif; ?></div><?php else: ?><div class="case-user-resolution"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php endif; ?>
    </div></section><?php else: ?><section class="card case-secondary-placeholder"><div class="card-body"><span class="ticket-kicker">Resolución</span><h2><?= $isSupport?'Pendiente de documentar':'Aún en seguimiento' ?></h2><p><?= $isSupport?'Cuando el problema quede resuelto, registra aquí la causa y la solución aplicada.':'Cuando Sistemas resuelva tu solicitud, aquí podrás consultar el resultado.' ?></p></div></section><?php endif; ?>
  </div>

  <?php if($isSupport && $similar): ?><section class="card similar-solutions"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Experiencia previa</span><h2>Casos similares que ya se resolvieron</h2><p>Antes de empezar desde cero, revisa qué funcionó anteriormente.</p></div></div><div class="similar-list"><?php foreach($similar as $s): ?><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$s['id'] ?>" class="similar-item"><div><strong><?= htmlspecialchars($s['ticket_number'].' · '.$s['subject']) ?></strong><small><?= htmlspecialchars($s['park_name']??'Sin ubicación') ?><?= !empty($s['resolved_at'])?' · '.htmlspecialchars(date('d/m/Y',strtotime($s['resolved_at']))):'' ?></small></div><p><?= htmlspecialchars(mb_strimwidth((string)$s['solution_applied'],0,180,'…')) ?></p></a><?php endforeach; ?></div></div></section><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
