<?php
use App\Core\{Csrf,Database,Auth};
use App\Controllers\WorkflowController;
use App\Services\{ProviderRatingService,SolutionSuggestionService,TicketClassificationService};
$statusLabels=$statusLabels??[];$priorityLabels=$priorityLabels??[];$status=(string)$ticket['status'];
$pendingReasons=WorkflowController::PENDING_REASONS;$slaSummary=$ticket['sla_summary']??null;
$requestTypeOptions=TicketClassificationService::requestTypeOptions();$impactOptions=TicketClassificationService::impactOptions();$urgencyOptions=TicketClassificationService::urgencyOptions();$classificationPriorities=TicketClassificationService::priorityOptions();
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$isRequester=!$isSupport&&!$isExternal;
$eventLabels=['CREATED'=>'Solicitud creada','CLAIMED'=>'Caso tomado','REASSIGNED'=>'Responsable cambiado','RELEASED'=>'Devuelto a disponibles','STATUS_CHANGED'=>'Estado actualizado','PENDING_REASON_CHANGED'=>'Motivo de espera actualizado','COMMENTED'=>'Nueva respuesta','RESOLUTION_RECORDED'=>'Solución documentada','RESOLVED'=>'Caso resuelto','CLOSED'=>'Caso cerrado','REOPENED'=>'Caso reabierto','PROBLEM_LINKED'=>'Problema conocido relacionado','PROBLEM_UNLINKED'=>'Problema conocido desvinculado','KNOWLEDGE_CREATED'=>'Artículo creado desde el caso','LOCATION_CHANGED'=>'Ubicación del caso actualizada','CLASSIFICATION_CHANGED'=>'Clasificación actualizada','ACTIVITY_CREATED'=>'Actividad programada','ACTIVITY_RESCHEDULED'=>'Actividad reprogramada','ACTIVITY_STARTED'=>'Actividad iniciada','ACTIVITY_COMPLETED'=>'Actividad finalizada','ACTIVITY_CANCELLED'=>'Actividad cancelada'];
$resolution=null;$similar=[];$comments=[];$attachmentsByComment=[];$looseAttachments=[];$relatedProblems=[];$problemOptions=[];$suggestions=[];$externalParticipants=[];
$knowledgeCandidate=$knowledgeCandidate??['eligible'=>false,'reasons'=>[],'documentation_ok'=>false];
$resolutionPrefill=$_SESSION['resolution_prefill_'.(int)$ticket['id']]??[];
unset($_SESSION['resolution_prefill_'.(int)$ticket['id']]);
try{
    $pdo=Database::pdo();
    $rq=$pdo->prepare("SELECT tr.*,u.full_name resolved_by_name FROM ticket_resolutions tr LEFT JOIN users u ON u.id=tr.resolved_by WHERE tr.ticket_id=? LIMIT 1");$rq->execute([(int)$ticket['id']]);$resolution=$rq->fetch()?:null;
    if($isSupport){
        $sq=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.resolved_at,tr.resolution_type,tr.solution_applied,p.name park_name FROM tickets t JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN parks p ON p.id=t.park_id WHERE t.id<>? AND t.deleted_at IS NULL AND t.status IN('RESOLVED','CLOSED') AND tr.is_reusable=1 AND (t.category_id=? OR (? IS NOT NULL AND t.park_id=?)) ORDER BY (t.category_id=? ) DESC,(t.park_id=? ) DESC,t.resolved_at DESC LIMIT 5");$park=$ticket['park_id']??null;$cat=(int)($ticket['category_id']??0);$sq->execute([(int)$ticket['id'],$cat,$park,$park,$cat,$park]);$similar=$sq->fetchAll();
        if(Auth::can('problems.view')){$pq=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,kp.status,kp.workaround,kp.permanent_solution,kp.occurrence_count FROM problem_occurrences po JOIN known_problems kp ON kp.id=po.problem_id WHERE po.ticket_id=? ORDER BY po.created_at DESC");$pq->execute([(int)$ticket['id']]);$relatedProblems=$pq->fetchAll();}
        if(Auth::can('problems.manage')){$po=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,kp.status FROM known_problems kp WHERE kp.status<>'CLOSED' AND NOT EXISTS(SELECT 1 FROM problem_occurrences x WHERE x.problem_id=kp.id AND x.ticket_id=?) ORDER BY (kp.category_id=? ) DESC,(kp.park_id=? ) DESC,kp.updated_at DESC LIMIT 80");$po->execute([(int)$ticket['id'],(int)($ticket['category_id']??0),(int)($ticket['park_id']??0)]);$problemOptions=$po->fetchAll();}
        if(Auth::can('knowledge.view')||Auth::can('problems.view'))$suggestions=(new SolutionSuggestionService())->forTicket($ticket,5);
        $eq=$pdo->prepare("SELECT u.full_name,ep.organization_name FROM external_ticket_access eta JOIN users u ON u.id=eta.user_id LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE eta.ticket_id=? AND eta.revoked_at IS NULL ORDER BY COALESCE(ep.organization_name,u.full_name)");$eq->execute([(int)$ticket['id']]);$externalParticipants=$eq->fetchAll();
    }
    $visibilityWhere=$isSupport?'':" AND tc.visibility='PUBLIC'";$cq=$pdo->prepare("SELECT tc.*,u.access_type author_access_type,r.code author_role FROM ticket_comments tc LEFT JOIN users u ON u.id=tc.author_user_id LEFT JOIN roles r ON r.id=u.role_id WHERE tc.ticket_id=? AND tc.deleted_at IS NULL {$visibilityWhere} ORDER BY tc.created_at,tc.id");$cq->execute([(int)$ticket['id']]);$comments=$cq->fetchAll();
    $attachmentWhere=$isSupport?'':" AND ta.visibility='PUBLIC'";$at=$pdo->prepare("SELECT ta.* FROM ticket_attachments ta WHERE ta.ticket_id=? {$attachmentWhere} ORDER BY ta.created_at,ta.id");$at->execute([(int)$ticket['id']]);foreach($at->fetchAll() as $file){if(!empty($file['comment_id']))$attachmentsByComment[(int)$file['comment_id']][]=$file;else $looseAttachments[]=$file;}
}catch(\Throwable $e){}
$pageTitle=$ticket['ticket_number'];$pageSection=$isSupport?'Centro de soporte':'Mis solicitudes';$activeNav=$isSupport?'support':'mine';$helpContext='ticket';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<?php $ticketActivitiesAsset=(string)(@filemtime(APP_ROOT.'/public/assets/css/ticket-activities.css')?:'20260914-F5'); ?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ticket-activities.css?v=<?= htmlspecialchars($ticketActivitiesAsset) ?>">
<div class="ticket-workspace case-focus-workspace">
  <div class="case-focus-head"><div><div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div><h1 class="page-title"><?= htmlspecialchars($ticket['subject']) ?></h1><div class="case-head-meta"><?php if($isSupport): ?><span class="case-kind-chip"><?= htmlspecialchars(TicketClassificationService::requestTypeLabel($ticket['request_type']??null)) ?></span><?php endif; ?><span class="ticket-status-pill status-<?= strtolower($status) ?>"><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></span><span class="priority-chip priority-<?= strtolower((string)$ticket['priority']) ?>"><?= htmlspecialchars($priorityLabels[$ticket['priority']]??$ticket['priority']) ?></span><span><?= htmlspecialchars($ticket['park_name']??'Ubicación no especificada') ?></span><span><?= htmlspecialchars($ticket['category_name']??'Sin categoría') ?></span></div></div><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?><?= $isSupport?'/tickets/queue':'/mis-tickets' ?>">Volver</a></div>
  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <div class="case-focus-grid"><section class="card case-problem-card"><div class="card-body"><span class="ticket-kicker">Solicitud</span><h2>Lo que se reportó</h2><div class="case-problem-description"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div><div class="case-problem-meta"><div><span>Dónde ocurrió</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div><div><span>Categoría</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div><?php if($isSupport): ?><div><span>Tipo de caso</span><strong><?= htmlspecialchars(TicketClassificationService::requestTypeLabel($ticket['request_type']??null)) ?></strong></div><?php endif; ?><?php if($isSupport): ?><div><span>Solicitante actual</span><strong><?= htmlspecialchars($ticket['current_requester_name']??$ticket['requester_name']) ?></strong><small><?= htmlspecialchars($ticket['current_requester_email']??$ticket['requester_email']) ?><?= !empty($ticket['current_requester_phone']??$ticket['requester_phone'])?' · '.htmlspecialchars((string)($ticket['current_requester_phone']??$ticket['requester_phone'])):'' ?></small><?php $currentLocation=array_values(array_filter([(string)($ticket['current_requester_park_name']??''),(string)($ticket['current_requester_area_name']??'')])); if($currentLocation): ?><small>Ubicación actual: <?= htmlspecialchars(implode(' · ',$currentLocation)) ?></small><?php endif; ?></div><?php endif; ?><div><span>Reportado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['created_at']))) ?></strong></div></div></div></section>
  <aside class="card case-status-card"><div class="card-body"><span class="ticket-kicker">Contexto</span><div class="case-status-main"><strong><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></strong><small>Actualizado <?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['updated_at']??$ticket['created_at']))) ?></small></div><dl class="case-status-list"><div><dt>Prioridad</dt><dd><?= htmlspecialchars($priorityLabels[$ticket['priority']]??$ticket['priority']) ?></dd></div><div><dt><?= $isSupport?'Responsable':'Atención' ?></dt><dd><?= $isSupport?htmlspecialchars($ticket['assigned_name']??'Aún sin asignar'):'Equipo de soporte' ?></dd></div><div><dt>Solicitante</dt><dd><?= htmlspecialchars($ticket['requester_name']??'No indicado') ?></dd></div><div><dt>Creado</dt><dd><?= htmlspecialchars(date('d/m/Y H:i',strtotime($ticket['created_at']))) ?></dd></div><?php if($status==='PENDING'&&!empty($ticket['pending_reason_code'])): ?><div class="case-pending-status"><dt>En espera por</dt><dd><?= htmlspecialchars($pendingReasons[$ticket['pending_reason_code']]??$ticket['pending_reason_code']) ?><?php if(!empty($ticket['pending_note'])): ?><small><?= htmlspecialchars($ticket['pending_note']) ?></small><?php endif; ?></dd></div><?php endif; ?><?php if($isSupport&&is_array($slaSummary)&&($slaSummary['state']??'none')!=='none'): ?><div class="case-sla-status"><dt>Resolución objetivo</dt><dd><strong><?= htmlspecialchars((string)($slaSummary['remaining_label']??'Sin SLA')) ?></strong><small><?= htmlspecialchars((string)($slaSummary['state_label']??'')) ?><?= ($slaSummary['utilization_percent']??null)!==null?' · '.(int)$slaSummary['utilization_percent'].'% del tiempo utilizado':'' ?></small><?php if(!empty($slaSummary['due_at'])): ?><small>Objetivo <?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$slaSummary['due_at']))) ?></small><?php endif; ?></dd></div><?php endif; ?></dl></div></aside></div>
  <?php if($isSupport&&!empty($canEditLocation)): ?><section class="card ticket-classification-card"><div class="card-body">

    <div class="case-section-head"><div><span class="ticket-kicker">Ubicación histórica</span><h2>Dónde ocurrió este caso</h2></div></div>

    <div class="ticket-classification-summary">

      <div><span>Parque actual del caso</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificado') ?></strong></div>

      <div><span>Área del caso</span><strong><?= htmlspecialchars($ticket['area_name']??'No especificada') ?></strong></div>

    </div>

    <details class="ticket-classification-edit"><summary>Corregir ubicación del caso</summary>

      <form class="ticket-classification-form" method="post" action="<?= APP_BASE_URL ?>/tickets/location" data-single-submit>

        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">

        <label><span class="form-label">Parque <span class="optional">Puede quedar sin especificar</span></span><select class="form-control" name="park_id"><option value="">Sin parque</option><?php foreach($locationParks as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)($ticket['park_id']??0)===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></label>

        <label><span class="form-label">Área <span class="optional">Opcional</span></span><select class="form-control" name="area_id"><option value="">Sin área</option><?php foreach($locationAreas as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)($ticket['area_id']??0)===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select></label>

        <label class="classification-note"><span class="form-label">Motivo del cambio</span><input class="form-control" type="text" name="location_change_reason" required minlength="8" maxlength="500" placeholder="Ej. Se confirmó posteriormente con el solicitante."></label>

        <div class="classification-submit"><button class="btn btn-primary" type="submit">Guardar ubicación histórica</button></div>

      </form>

      <span class="field-help">Este dato alimenta Dashboard e informes por parque. El cambio queda registrado con antes, después, usuario, fecha y motivo.</span>

    </details>

  </div></section><?php endif; ?>



  <?php if($isSupport): ?><section class="card ticket-classification-card"><div class="card-body">
    <div class="case-section-head"><div><span class="ticket-kicker">ITSM 2.1</span><h2>Clasificación del caso</h2></div></div>
    <div class="ticket-classification-summary">
      <div><span>Tipo</span><strong><?= htmlspecialchars(TicketClassificationService::requestTypeLabel($ticket['request_type']??null)) ?></strong></div>
      <div><span>Impacto</span><strong><?= htmlspecialchars(TicketClassificationService::impactLabel($ticket['impact']??null)) ?></strong></div>
      <div><span>Urgencia</span><strong><?= htmlspecialchars(TicketClassificationService::urgencyLabel($ticket['urgency']??null)) ?></strong></div>
      <div><span>Prioridad</span><strong><?= htmlspecialchars(TicketClassificationService::priorityLabel($ticket['priority']??null)) ?></strong><small><?= htmlspecialchars(TicketClassificationService::prioritySourceLabel($ticket['priority_source']??'LEGACY')) ?></small></div>
    </div>
    <?php if(!empty($canClassify)): ?><details class="ticket-classification-edit"><summary>Ajustar clasificación</summary>
      <form class="ticket-classification-form" method="post" action="<?= APP_BASE_URL ?>/tickets/classification" data-single-submit>
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <label><span class="form-label">Tipo</span><select class="form-control" name="request_type" required><?php foreach($requestTypeOptions as $code=>$meta): ?><option value="<?= htmlspecialchars((string)$code) ?>" <?= ($ticket['request_type']??'')===$code?'selected':'' ?>><?= htmlspecialchars((string)$meta['label']) ?></option><?php endforeach; ?></select></label>
        <label><span class="form-label">Impacto</span><select class="form-control" name="impact" required><option value="">Selecciona</option><?php foreach($impactOptions as $code=>$label): ?><option value="<?= htmlspecialchars((string)$code) ?>" <?= ($ticket['impact']??'')===$code?'selected':'' ?>><?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select></label>
        <label><span class="form-label">Urgencia</span><select class="form-control" name="urgency" required><option value="">Selecciona</option><?php foreach($urgencyOptions as $code=>$label): ?><option value="<?= htmlspecialchars((string)$code) ?>" <?= ($ticket['urgency']??'')===$code?'selected':'' ?>><?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select></label>
        <label><span class="form-label">Prioridad manual <span class="optional">Opcional</span></span><select class="form-control" name="priority_override"><option value="">Usar la calculada</option><?php foreach($classificationPriorities as $code=>$label): ?><option value="<?= htmlspecialchars((string)$code) ?>" <?= (($ticket['priority_source']??'')==='MANUAL'&&($ticket['priority']??'')===$code)?'selected':'' ?>><?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select><span class="field-help">Si la dejas vacía, se usa Impacto + Urgencia.</span></label>
        <label class="classification-note"><span class="form-label">Motivo del ajuste <span class="optional">Solo si cambias la prioridad</span></span><input class="form-control" type="text" name="classification_note" maxlength="500" placeholder="Ejemplo: apertura de parque detenida por este caso"></label>
        <div class="classification-submit"><button class="btn btn-primary" type="submit">Guardar clasificación</button></div>
      </form>
    </details><?php endif; ?>
  </div></section><?php endif; ?>

  <?php if($isSupport&&Auth::can('problems.view')): ?><section class="card ticket-problem-link"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Recurrencias</span><h2>Problema conocido</h2></div><?php if(Auth::can('problems.manage')): ?><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/problems/new?ticket_id=<?= (int)$ticket['id'] ?>">Crear desde este caso</a><?php endif; ?></div><?php if($relatedProblems): ?><div class="ticket-related-problems"><?php foreach($relatedProblems as $p): ?><a href="<?= APP_BASE_URL ?>/problems/view?id=<?= (int)$p['id'] ?>" class="ticket-related-problem"><div><strong><?= htmlspecialchars($p['problem_number'].' · '.$p['title']) ?></strong><small><?= htmlspecialchars($p['status']) ?> · <?= (int)$p['occurrence_count'] ?> caso(s)</small></div><?php if(!empty($p['workaround'])): ?><p><span>Solución temporal</span><?= htmlspecialchars(mb_strimwidth((string)$p['workaround'],0,240,'…')) ?></p><?php elseif(!empty($p['permanent_solution'])): ?><p><span>Solución documentada</span><?= htmlspecialchars(mb_strimwidth((string)$p['permanent_solution'],0,240,'…')) ?></p><?php endif; ?></a><?php endforeach; ?></div><?php else: ?><div class="itsm-inline-empty">Sin problema conocido relacionado.</div><?php endif; ?><?php if(Auth::can('problems.manage')&&$problemOptions): ?><form class="itsm-inline-form" method="post" action="<?= APP_BASE_URL ?>/problems/link-ticket" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><select class="form-control" name="problem_id" required><option value="">Relacionar con problema existente…</option><?php foreach($problemOptions as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['problem_number'].' · '.$p['title']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Relacionar</button></form><?php endif; ?></div></section><?php endif; ?>

  <?php if($isSupport&&!empty($knowledgeCandidate['eligible'])&&Auth::can('knowledge.draft_manage')): ?>
    <section class="card knowledge-candidate-card">
      <div class="card-body">
        <div class="case-section-head">
          <div>
            <span class="ticket-kicker">Conocimiento reutilizable</span>
            <h2>Este caso puede convertirse en conocimiento reutilizable.</h2>
            <p>La solución está documentada y contiene señales que pueden ayudar a resolver casos similares.</p>
          </div>
          <a class="btn btn-primary" href="<?= APP_BASE_URL ?>/knowledge/new?ticket_id=<?= (int)$ticket['id'] ?>">Crear borrador</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if($isSupport&&$suggestions): ?>
    <section class="card suggested-solutions">
      <div class="card-body">
        <div class="case-section-head">
          <div>
            <span class="ticket-kicker">Experiencia previa</span>
            <h2>Posibles soluciones</h2>
          </div>
        </div>
        <div class="suggestion-list">
          <?php foreach($suggestions as $s):
            $referenceType=$s['type']==='ARTICLE'?'KNOWLEDGE':$s['type'];
          ?>
            <article class="suggestion-item">
              <a href="<?= htmlspecialchars($s['url']) ?>"
                 data-suggestion-open
                 data-ticket-id="<?= (int)$ticket['id'] ?>"
                 data-reference-type="<?= htmlspecialchars($referenceType) ?>"
                 data-reference-id="<?= (int)$s['id'] ?>"
                 data-revision-id="<?= (int)($s['revision_id']??0) ?>">
                <div class="suggestion-type"><span><?= $s['type']==='ARTICLE'?'Artículo':($s['type']==='PROBLEM'?'Problema conocido':'Caso resuelto') ?></span></div>
                <strong><?= htmlspecialchars($s['number'].' · '.$s['title']) ?></strong>
                <p><?= htmlspecialchars((string)$s['summary']) ?></p>
                <small>Ver detalle →</small>
              </a>
              <?php if(Auth::can('tickets.resolve')&&in_array($status,['IN_PROGRESS','PENDING','REOPENED'],true)): ?>
                <form method="post" action="<?= APP_BASE_URL ?>/tickets/reference/use" data-single-submit>
                  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                  <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                  <input type="hidden" name="reference_type" value="<?= htmlspecialchars($referenceType) ?>">
                  <input type="hidden" name="reference_id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-outline-secondary" type="submit">Usar como referencia</button>
                </form>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if($isSupport): ?><section class="card case-action-card"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Acciones</span><h2>Atención del caso</h2></div></div><div class="ticket-action-bar case-action-bar"><?php if($canClaim): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-primary ticket-primary-action" type="submit">Tomar y atender</button></form><?php endif; ?><?php if($canChangeStatus&&in_array($status,['PENDING','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="IN_PROGRESS"><button class="btn btn-primary" type="submit">Continuar atención</button></form><?php endif; ?><?php if($canChangeStatus&&$status==='RESOLVED'): ?><div class="itsm-inline-empty"><strong>Solución registrada.</strong> El cierre final queda pendiente de confirmación del solicitante.</div><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="REOPENED"><button class="btn btn-outline-secondary" type="submit">Reabrir</button></form><?php endif; ?><?php if($canRelease): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/release" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-outline-secondary" type="submit">Devolver a la cola</button></form><?php endif; ?></div>
    <?php if($canChangeStatus&&in_array($status,['IN_PROGRESS','REOPENED'],true)): ?><div class="case-pending-control"><div><strong>Poner en espera</strong><span>Selecciona el motivo de la pausa.</span></div><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="PENDING"><select class="form-control" name="pending_reason_code" required><option value="">Motivo de espera</option><?php foreach($pendingReasons as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select><input class="form-control" type="text" name="pending_note" maxlength="500" placeholder="Detalle opcional: proveedor, compra, fecha acordada…"><button class="btn btn-outline-secondary" type="submit">Poner en espera</button></form></div><?php endif; ?>
    <?php if($canReassign&&!empty($supportUsers)): ?><details class="ticket-more-actions case-assign-details"><summary>Más acciones</summary><form class="ticket-assign-form" method="post" action="<?= APP_BASE_URL ?>/tickets/assign" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><select class="form-control" name="assigned_to" required><option value="">Selecciona responsable</option><?php foreach($supportUsers as $su): ?><option value="<?= (int)$su['id'] ?>" <?= (int)($ticket['assigned_to']??0)===(int)$su['id']?'selected':'' ?>><?= htmlspecialchars($su['full_name']) ?> · <?= htmlspecialchars($su['role_name']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Reasignar</button></form></details><?php endif; ?>
    <?php if($canChangeStatus&&in_array($status,['IN_PROGRESS','PENDING','REOPENED'],true)): ?><div class="resolution-capture case-resolution-capture"><div class="case-section-head"><div><span class="ticket-kicker">Documentar solución</span><h2>Qué resolvió el caso</h2></div></div><form method="post" action="<?= APP_BASE_URL ?>/tickets/resolve" data-single-submit class="resolution-form"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><div class="resolution-grid"><label>Tipo de solución<select class="form-control" name="resolution_type" required><option value="">Selecciona</option><option value="CONFIGURATION">Configuración</option><option value="RESTART">Reinicio / restablecimiento</option><option value="REPLACEMENT">Cambio o reemplazo</option><option value="PROVIDER">Gestión con proveedor</option><option value="USER_GUIDANCE">Orientación al usuario</option><option value="SOFTWARE">Software / aplicación</option><option value="NETWORK">Red / conectividad</option><option value="HARDWARE">Hardware / equipo</option><option value="PERMISSION">Acceso / permisos</option><option value="MAINTENANCE">Mantenimiento</option><option value="OTHER">Otro</option></select></label><label>Qué encontramos<input class="form-control" name="root_cause" required value="<?= htmlspecialchars((string)($resolutionPrefill['root_cause']??'')) ?>" placeholder="Causa o condición encontrada"></label><label class="resolution-full">Qué hicimos<textarea class="form-control" name="solution_applied" rows="3" required placeholder="Describe la acción o los pasos que resolvieron el problema"><?= htmlspecialchars((string)($resolutionPrefill['solution']??'')) ?></textarea></label><label class="resolution-full">Cómo evitarlo <span class="subtle">(opcional)</span><textarea class="form-control" name="preventive_action" rows="2" placeholder="Recomendación, mantenimiento o seguimiento"><?= htmlspecialchars((string)($resolutionPrefill['prevention']??'')) ?></textarea></label></div><button class="btn btn-primary" type="submit">Guardar solución y resolver</button></form></div><?php endif; ?>
  </div></section><?php endif; ?>

  <?php if($isRequester&&!empty($requesterActivities)):

    $requesterActivityTypeLabels=[

      'VISITA_EN_SITIO'=>'Visita en sitio',

      'SOPORTE_REMOTO'=>'Soporte remoto',

      'SEGUIMIENTO'=>'Seguimiento',

      'INTERVENCION_PROVEEDOR'=>'Intervención programada',

      'OTRA'=>'Atención programada',

    ];

    $requesterActivityStatusLabels=[

      'PROGRAMADA'=>'Programada',

      'EN_CURSO'=>'En curso',

      'FINALIZADA'=>'Finalizada',

      'CANCELADA'=>'Cancelada',

    ];

  ?>

  <section class="card ticket-activities-card ticket-requester-activities">

    <div class="card-body">

      <div class="case-section-head ticket-activities-head">

        <div>

          <span class="ticket-kicker">Seguimiento</span>

          <h2>Próxima atención</h2>

          <p class="ticket-activities-intro">Aquí verás únicamente la información de atención que el equipo de soporte publicó para ti.</p>

        </div>

      </div>

      <div class="ticket-activity-grid <?= count($requesterActivities)===1?'is-single':'' ?>">

        <?php foreach($requesterActivities as $activity):

          $requesterType=(string)($activity['activity_type']??'OTRA');

          $requesterStatus=(string)($activity['status']??'PROGRAMADA');

        ?>

          <article class="ticket-activity-item <?= in_array($requesterStatus,['PROGRAMADA','EN_CURSO'],true)?'is-active':'' ?>">

            <div class="ticket-activity-item-head">

              <div class="ticket-activity-kind">

                <strong><?= htmlspecialchars($requesterActivityTypeLabels[$requesterType]??'Atención programada') ?></strong>

                <small>Actualización publicada por soporte</small>

              </div>

              <span class="ticket-activity-status status-<?= strtolower($requesterStatus) ?>"><?= htmlspecialchars($requesterActivityStatusLabels[$requesterStatus]??$requesterStatus) ?></span>

            </div>

            <div class="ticket-activity-meta">

              <div><span>Fecha programada</span><strong><?= !empty($activity['scheduled_start_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_start_at']))):'Por confirmar' ?></strong></div>

              <?php if(!empty($activity['scheduled_end_at'])): ?><div><span>Fin estimado</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_end_at']))) ?></strong></div><?php endif; ?>

              <?php if(!empty($activity['park_name'])): ?><div><span>Ubicación</span><strong><?= htmlspecialchars((string)$activity['park_name']) ?></strong></div><?php endif; ?>

            </div>

            <p class="ticket-activity-objective"><?= nl2br(htmlspecialchars((string)$activity['requester_summary'])) ?></p>

          </article>

        <?php endforeach; ?>

      </div>

    </div>

  </section>

  <?php endif; ?>

  <?php if($isSupport):
    $activityTypeLabels=[
      'VISITA_EN_SITIO'=>'Visita en sitio',
      'SOPORTE_REMOTO'=>'Soporte remoto',
      'SEGUIMIENTO'=>'Seguimiento',
      'INTERVENCION_PROVEEDOR'=>'Intervención de proveedor',
      'OTRA'=>'Otra actividad',
    ];
    $activityStatusLabels=['PROGRAMADA'=>'Programada','EN_CURSO'=>'En curso','FINALIZADA'=>'Finalizada','CANCELADA'=>'Cancelada'];
    $activityResultLabels=['RESUELTA'=>'Resuelta','PARCIAL'=>'Parcial','SIN_RESOLVER'=>'Sin resolver','REQUIERE_SEGUIMIENTO'=>'Requiere seguimiento'];
    $activeActivities=array_values(array_filter($activities??[],static fn(array $a):bool=>in_array((string)($a['status']??''),['PROGRAMADA','EN_CURSO'],true)));
    $historyActivities=array_values(array_filter($activities??[],static fn(array $a):bool=>in_array((string)($a['status']??''),['FINALIZADA','CANCELADA'],true)));
  ?>
  <section class="card ticket-activities-card" id="actividades">
    <div class="card-body">
      <div class="case-section-head ticket-activities-head">
        <div>
          <span class="ticket-kicker">Trabajo programado</span>
          <h2>Actividades del caso</h2>
          <p class="ticket-activities-intro">Programa visitas, soporte remoto, seguimientos o intervenciones sin cambiar automáticamente el estado del ticket.</p>
        </div>
        <?php if(!empty($canCreateActivities)): ?>
          <div class="ticket-activities-toolbar"><button type="button" class="btn btn-primary" data-activity-open>+ Programar actividad</button></div>
        <?php endif; ?>
      </div>

      <?php if(!empty($canCreateActivities)): ?>
      <div class="ticket-activity-create" data-activity-create-panel hidden>
        <form class="ticket-activity-form" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/create" data-activity-create-form data-single-submit>
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
          <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
          <input type="hidden" name="is_remote" value="0">
          <div class="ticket-activity-form-head">
            <div><span class="ticket-kicker">Nueva actividad</span><h3>Programar trabajo</h3><p>Define qué se hará, quién será responsable y cuándo está previsto atenderlo.</p></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-activity-close>Cerrar</button>
          </div>
          <div class="ticket-activity-form-grid">
            <label><span class="form-label">Tipo de actividad</span><select class="form-control" name="activity_type" data-activity-type required><option value="">Selecciona</option><?php foreach(($activityTypes??[]) as $type): ?><option value="<?= htmlspecialchars((string)$type) ?>"><?= htmlspecialchars($activityTypeLabels[$type]??str_replace('_',' ',(string)$type)) ?></option><?php endforeach; ?></select></label>
            <label><span class="form-label">Responsable</span><select class="form-control" name="responsible_user_id" required data-activity-responsible><option value="">Selecciona responsable</option><?php foreach(($activityResponsibleUsers??[]) as $person): ?><option value="<?= (int)$person['id'] ?>"><?= htmlspecialchars((string)$person['full_name']) ?> · <?= htmlspecialchars((string)$person['email']) ?></option><?php endforeach; ?></select></label>
            <label><span class="form-label">Inicio programado</span><input class="form-control" type="datetime-local" name="scheduled_start_at" required></label>
            <label><span class="form-label">Fin estimado</span><input class="form-control" type="datetime-local" name="scheduled_end_at" required></label>

            <label data-activity-panel="VISITA_EN_SITIO" hidden><span class="form-label">Parque de la visita</span><select class="form-control" name="park_id" data-activity-required><option value="">Selecciona parque</option><?php foreach(($activityParks??[]) as $park): ?><option value="<?= (int)$park['id'] ?>" <?= (int)($ticket['park_id']??0)===(int)$park['id']?'selected':'' ?>><?= htmlspecialchars((string)$park['name']) ?></option><?php endforeach; ?></select></label>
            <label data-activity-panel="INTERVENCION_PROVEEDOR" hidden><span class="form-label">Proveedor que intervendrá</span><select class="form-control" name="provider_user_id" data-activity-required><option value="">Selecciona proveedor</option><?php foreach(($activityProviderUsers??[]) as $provider): ?><option value="<?= (int)$provider['id'] ?>"><?= htmlspecialchars((string)($provider['organization_name']?:$provider['full_name'])) ?> · <?= htmlspecialchars((string)$provider['email']) ?></option><?php endforeach; ?></select></label>
            <div data-activity-panel="SOPORTE_REMOTO" hidden class="activity-span-2 ticket-activity-visibility"><span>Esta actividad se registrará como soporte remoto.</span></div>

            <label class="activity-span-2"><span class="form-label">Objetivo</span><textarea class="form-control" name="objective" rows="3" required placeholder="Ej. Revisar comunicación del kiosco con el servidor y validar impresión."></textarea></label>
            <details class="ticket-activity-more-options activity-span-2"><summary>Más opciones</summary><div class="ticket-activity-more-options-body">
<label class="activity-span-2"><span class="form-label">Preparación interna <span class="optional">Opcional</span></span><textarea class="form-control" name="internal_preparation_notes" rows="2" placeholder="Accesos, herramientas o puntos que el equipo debe preparar antes de atender."></textarea></label>
            <div class="activity-span-2 ticket-activity-participant-field" data-activity-participants>
              <div class="ticket-activity-participant-head">
                <span class="form-label">Participantes adicionales <span class="optional">Opcional</span></span>
                <span class="ticket-activity-participant-count" data-participant-count>0 seleccionados</span>
              </div>
              <div class="ticket-activity-participant-picker">
                <div class="ticket-activity-participant-search">
                  <span aria-hidden="true">⌕</span>
                  <input type="text" data-participant-search placeholder="Buscar participante…" autocomplete="off" aria-label="Buscar participante">
                </div>
                <div class="ticket-activity-participant-options" data-participant-options>
                  <?php foreach(($activityResponsibleUsers??[]) as $person): ?>
                    <label class="ticket-activity-participant-option" data-participant-option data-search="<?= htmlspecialchars(mb_strtolower((string)$person['full_name'].' '.(string)$person['email']),ENT_QUOTES,'UTF-8') ?>">
                      <input type="checkbox" name="participant_user_ids[]" value="<?= (int)$person['id'] ?>" data-participant-checkbox>
                      <span><strong><?= htmlspecialchars((string)$person['full_name']) ?></strong><small><?= htmlspecialchars((string)$person['email']) ?></small></span>
                    </label>
                  <?php endforeach; ?>
                  <div class="ticket-activity-participant-empty" data-participant-empty hidden>Sin coincidencias.</div>
                </div>
              </div>
              <span class="field-help">Marca una o varias personas. El responsable principal se excluye automáticamente.</span>
            </div>
            <label class="activity-span-2 ticket-activity-visibility"><input type="checkbox" name="requester_visible" value="1" data-requester-visible><span>Mostrar esta actividad al solicitante</span></label>
            <label class="activity-span-2 ticket-activity-requester-summary" hidden><span class="form-label">Resumen visible al solicitante</span><textarea class="form-control" name="requester_summary" rows="2" maxlength="500" placeholder="Ej. Se programó una visita para revisar el equipo reportado."></textarea><span class="field-help">No incluyas notas internas, accesos ni información técnica sensible.</span></label>
</div></details>
          </div>
          <div class="ticket-activity-submit"><button type="button" class="btn btn-outline-secondary" data-activity-close>Cancelar</button><button class="btn btn-primary" type="submit">Programar actividad</button></div>
        </form>
      </div>
      <?php endif; ?>

      <div class="ticket-activity-sections">
        <div class="ticket-activity-section">
          <div class="ticket-activity-section-head"><h3>Próximas / activas</h3><span><?= count($activeActivities) ?> actividad(es)</span></div>
          <?php if($activeActivities): ?><div class="ticket-activity-grid <?= count($activeActivities)===1?'is-single':'' ?>">
          <?php foreach($activeActivities as $activity):
            $activityId=(int)$activity['id'];$activityStatus=(string)$activity['status'];$activityType=(string)$activity['activity_type'];
          ?>
            <article class="ticket-activity-item is-active">
              <div class="ticket-activity-item-head"><div class="ticket-activity-kind"><strong><?= htmlspecialchars($activityTypeLabels[$activityType]??str_replace('_',' ',$activityType)) ?></strong><small>#<?= $activityId ?> · <?= htmlspecialchars((string)($activity['responsible_name']??'Sin responsable')) ?></small></div><span class="ticket-activity-status status-<?= strtolower($activityStatus) ?>"><?= htmlspecialchars($activityStatusLabels[$activityStatus]??$activityStatus) ?></span></div>
              <div class="ticket-activity-meta">
                <div><span>Inicio</span><strong><?= !empty($activity['scheduled_start_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_start_at']))):'Sin fecha' ?></strong></div>
                <div><span>Fin estimado</span><strong><?= !empty($activity['scheduled_end_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_end_at']))):'Sin fecha' ?></strong></div>
                <?php if(!empty($activity['park_name'])): ?><div><span>Parque</span><strong><?= htmlspecialchars((string)$activity['park_name']) ?></strong></div><?php endif; ?>
                <?php if(!empty($activity['provider_organization'])||!empty($activity['provider_name'])): ?><div><span>Proveedor</span><strong><?= htmlspecialchars((string)($activity['provider_organization']?:$activity['provider_name'])) ?></strong></div><?php endif; ?>
              </div>
              <p class="ticket-activity-objective"><?= nl2br(htmlspecialchars((string)$activity['objective'])) ?></p>
              <?php if(!empty($activity['participants'])): ?><div class="ticket-activity-participants"><?php foreach($activity['participants'] as $participant): ?><span class="ticket-activity-person"><?= htmlspecialchars((string)$participant['full_name']) ?></span><?php endforeach; ?></div><?php endif; ?>

              <div class="ticket-activity-actions">
                <?php if(!empty($canManageActivities)&&$activityStatus==='PROGRAMADA'): ?>
                  <details class="ticket-activity-transition-details ticket-activity-reschedule-details"><summary class="btn btn-outline-secondary btn-sm">Reprogramar</summary><form class="ticket-activity-transition ticket-activity-transition--reschedule two-columns" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/reschedule" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Nuevo inicio</span><input class="form-control" type="datetime-local" name="scheduled_start_at" required></label><label><span class="form-label">Nuevo fin</span><input class="form-control" type="datetime-local" name="scheduled_end_at" required></label><label class="activity-span-2"><span class="form-label">Motivo</span><input class="form-control" type="text" name="reason" minlength="5" required placeholder="Motivo de la reprogramación"></label><div class="activity-span-2 ticket-activity-transition-actions"><button class="btn btn-primary btn-sm" type="submit">Guardar nueva fecha</button></div></form></details>
                  <form method="post" action="<?= APP_BASE_URL ?>/tickets/activities/start" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><button class="btn btn-primary btn-sm" type="submit">Iniciar</button></form>
                <?php endif; ?>

                <?php if(!empty($canManageActivities)&&$activityStatus==='EN_CURSO'): ?>
                  <details class="ticket-activity-transition-details ticket-activity-complete-details"><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition ticket-activity-transition--complete" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><div class="ticket-activity-complete-grid"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace('_',' ',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label class="activity-span-2"><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><div class="activity-span-2 ticket-activity-complete-actions"><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></div></div></form></details>
                <?php endif; ?>

                <?php if(!empty($canCancelActivities)&&in_array($activityStatus,['PROGRAMADA','EN_CURSO'],true)): ?>
                  <details class="ticket-activity-transition-details ticket-activity-cancel-details"><summary class="btn btn-outline-secondary btn-sm">Cancelar</summary><form class="ticket-activity-transition ticket-activity-transition--cancel" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/cancel" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Motivo de cancelación</span><textarea class="form-control" name="cancel_reason" minlength="5" required placeholder="Explica por qué se cancela esta actividad"></textarea></label><button class="btn btn-outline-secondary btn-sm" type="submit" data-activity-confirm="¿Cancelar esta actividad?">Confirmar cancelación</button></form></details>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          </div><?php else: ?><div class="ticket-activity-empty"><strong>No hay actividades activas.</strong>Programa una actividad cuando el caso requiera una visita, soporte remoto, seguimiento o intervención.</div><?php endif; ?>
        </div>

        <div class="ticket-activity-section">
          <div class="ticket-activity-section-head"><h3>Historial</h3><span><?= count($historyActivities) ?> actividad(es)</span></div>
          <?php if($historyActivities): ?><div class="ticket-activity-grid <?= count($historyActivities)===1?'is-single':'' ?>">
          <?php foreach($historyActivities as $activity): $activityStatus=(string)$activity['status'];$activityType=(string)$activity['activity_type']; ?>
            <article class="ticket-activity-item">
              <div class="ticket-activity-item-head"><div class="ticket-activity-kind"><strong><?= htmlspecialchars($activityTypeLabels[$activityType]??str_replace('_',' ',$activityType)) ?></strong><small>#<?= (int)$activity['id'] ?> · <?= htmlspecialchars((string)($activity['responsible_name']??'Sin responsable')) ?></small></div><span class="ticket-activity-status status-<?= strtolower($activityStatus) ?>"><?= htmlspecialchars($activityStatusLabels[$activityStatus]??$activityStatus) ?></span></div>
              <div class="ticket-activity-meta"><div><span>Programada</span><strong><?= !empty($activity['scheduled_start_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['scheduled_start_at']))):'Sin fecha' ?></strong></div><?php if(!empty($activity['finished_at'])): ?><div><span>Finalizada</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['finished_at']))) ?></strong></div><?php elseif(!empty($activity['cancelled_at'])): ?><div><span>Cancelada</span><strong><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$activity['cancelled_at']))) ?></strong></div><?php endif; ?></div>
              <p class="ticket-activity-objective"><?= nl2br(htmlspecialchars((string)$activity['objective'])) ?></p>
              <?php if($activityStatus==='FINALIZADA'): ?><div class="ticket-activity-result"><strong><?= htmlspecialchars($activityResultLabels[$activity['result_code']??'']??str_replace('_',' ',(string)($activity['result_code']??'Resultado'))) ?></strong><p><?= nl2br(htmlspecialchars((string)($activity['result_summary']??''))) ?></p></div><?php elseif($activityStatus==='CANCELADA'&&!empty($activity['cancel_reason'])): ?><div class="ticket-activity-result"><strong>Motivo de cancelación</strong><p><?= nl2br(htmlspecialchars((string)$activity['cancel_reason'])) ?></p></div><?php endif; ?>
            </article>
          <?php endforeach; ?>
          </div><?php else: ?><div class="ticket-activity-empty"><strong>Aún no hay historial.</strong>Las actividades finalizadas o canceladas aparecerán aquí.</div><?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>
  <?php if($isSupport&&!empty($providerCycles)): ?>

  <section class="card ticket-classification-card" id="provider-quality"><div class="card-body">

    <div class="case-section-head"><div><span class="ticket-kicker">Control interno</span><h2>Calidad del proveedor</h2></div></div>

    <p class="field-help">Valoración interna de IT por cada participación finalizada. No es visible para el proveedor ni para el solicitante.</p>



    <?php foreach($providerCycles as $cycle):

      $closed=!empty($cycle['revoked_at']);

      $cycleEvaluable=ProviderRatingService::isCycleEvaluable($cycle);

      $rated=($cycle['provider_rating_score']??null)!==null;

      $organization=(string)($cycle['organization']??$cycle['contact']??'Proveedor');

    ?>

      <div class="ticket-classification-summary">

        <div><span>Proveedor</span><strong><?= htmlspecialchars($organization) ?></strong><small><?= htmlspecialchars((string)($cycle['contact']??'')) ?></small></div>

        <div><span>Asignado</span><strong><?= !empty($cycle['granted_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['granted_at']))):'Sin fecha' ?></strong></div>

        <div><span>Participación</span><strong><?= $closed?'Finalizada':'Activa' ?></strong><?php if($closed&&!empty($cycle['revoked_at'])): ?><small>Finalizó <?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['revoked_at']))) ?></small><?php endif; ?></div>

        <div><span>Valoración vigente</span><strong><?= $rated?((int)$cycle['provider_rating_score'].'★ · '.htmlspecialchars((string)$cycle['provider_rating_label'])):'Sin evaluar' ?></strong><?php if($rated&&!empty($cycle['provider_rating_revisions'])): ?><small><?= (int)$cycle['provider_rating_revisions'] ?> corrección(es)</small><?php endif; ?></div>

      </div>



      <?php if(!$cycleEvaluable): ?>

        <div class="dashboard-scope-note"><span><?= $closed?'Esta participación no puede evaluarse porque no finalizó mediante revocación explícita.':'Podrás evaluar cuando finalice la participación.' ?></span></div>

      <?php elseif(!$rated&&$canRateProviders): ?>

        <details class="ticket-classification-edit"><summary>Evaluar proveedor</summary>

          <form class="ticket-classification-form" method="post" action="<?= APP_BASE_URL ?>/tickets/provider-rating" data-single-submit>

            <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">

            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">

            <input type="hidden" name="external_user_id" value="<?= (int)$cycle['user_id'] ?>">

            <input type="hidden" name="grant_event_id" value="<?= (int)$cycle['grant_event_id'] ?>">

            <label><span class="form-label">Valoración</span><select class="form-control" name="score" required><option value="">Selecciona</option><?php foreach(($providerRatingLabels??[]) as $score=>$label): ?><option value="<?= (int)$score ?>"><?= (int)$score ?>★ <?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select></label>

            <label><span class="form-label">Comentario <span class="optional">Según valoración</span></span><textarea class="form-control" name="comment" rows="3" maxlength="2000" placeholder="Contexto interno de la valoración"></textarea></label>

            <span class="field-help">Comentario obligatorio para 1–2 estrellas y para toda corrección.</span>

            <div class="classification-submit"><button class="btn btn-primary" type="submit">Guardar valoración</button></div>

          </form>

        </details>

      <?php elseif($rated): ?>

        <div class="resolution-read-grid">

          <div><span>Valoración</span><strong><?= (int)$cycle['provider_rating_score'] ?>★ · <?= htmlspecialchars((string)$cycle['provider_rating_label']) ?></strong></div>

          <div class="provider-rating-registration"><span>Registró</span><strong><?= htmlspecialchars((string)($cycle['provider_rating_actor']?:'Equipo IT')) ?></strong><?php if(!empty($cycle['provider_rating_at'])): ?><small class="provider-rating-registered-at"><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['provider_rating_at']))) ?></small><?php endif; ?></div>

          <div><span>Comentario interno</span><p><?= ($cycle['provider_rating_comment']??'')!==''?nl2br(htmlspecialchars((string)$cycle['provider_rating_comment'])):'Sin comentario.' ?></p></div>

        </div>

        <?php if($canRateProviders): ?>

          <details class="ticket-classification-edit"><summary>Registrar corrección</summary>

            <form class="ticket-classification-form" method="post" action="<?= APP_BASE_URL ?>/tickets/provider-rating/correct" data-single-submit>

              <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">

              <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">

              <input type="hidden" name="external_user_id" value="<?= (int)$cycle['user_id'] ?>">

              <input type="hidden" name="grant_event_id" value="<?= (int)$cycle['grant_event_id'] ?>">

              <input type="hidden" name="corrected_rating_event_id" value="<?= (int)$cycle['provider_rating_event_id'] ?>">

              <label><span class="form-label">Nueva valoración</span><select class="form-control" name="score" required><option value="">Selecciona</option><?php foreach(($providerRatingLabels??[]) as $score=>$label): ?><option value="<?= (int)$score ?>" <?= (int)$cycle['provider_rating_score']===(int)$score?'selected':'' ?>><?= (int)$score ?>★ <?= htmlspecialchars((string)$label) ?></option><?php endforeach; ?></select></label>

              <label><span class="form-label">Motivo de la corrección</span><textarea class="form-control" name="comment" rows="3" maxlength="2000" required placeholder="Explica por qué se corrige la valoración"></textarea></label>

              <span class="field-help">Comentario obligatorio para 1–2 estrellas y para toda corrección.</span>

              <div class="classification-submit"><button class="btn btn-primary" type="submit">Guardar corrección</button></div>

            </form>

          </details>

        <?php endif; ?>

      <?php else: ?>

        <div class="dashboard-scope-note"><span>Participación finalizada · Sin evaluar.</span></div>

      <?php endif; ?>

    <?php endforeach; ?>

  </div></section>

  <?php endif; ?>

  <section class="card conversation-card case-conversation-card" id="conversacion"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Comunicación</span><h2><?= $isSupport?'Conversaciones':'Seguimiento' ?></h2></div><?php if($isSupport&&$externalParticipants): ?><div class="dashboard-scope-note"><strong>Proveedor participando</strong><span><?= htmlspecialchars(implode(', ',array_map(static fn(array $x):string=>(string)($x['organization_name']?:$x['full_name']),$externalParticipants))) ?></span></div><?php endif; ?></div><div class="conversation-list"><?php foreach($comments as $c): $mine=(int)($c['author_user_id']??0)===(int)Auth::id();$internal=$c['visibility']==='INTERNAL';$externalChannel=$c['visibility']==='EXTERNAL';$fromExternal=(($c['author_access_type']??'')==='EXTERNAL');$fromSupport=in_array((string)($c['author_role']??''),['ADMIN','SEMIADMIN','TECHNICIAN'],true);$author=$c['author_name']?:'Usuario';$channel=$internal?'Solo equipo de soporte':($externalChannel?'Colaboración con proveedor':($fromSupport?'Equipo de soporte':'Solicitante'));$class=$internal?'is-internal':($externalChannel?'is-external':($fromSupport?'is-mine':'is-requester')); ?><article class="conversation-message <?= $mine?'is-mine ':'' ?><?= $class ?>"><div class="conversation-channel-label <?= $internal?'internal':($externalChannel?'external':($fromSupport?'support':'')) ?>"><?= $internal?'🔒 ':'' ?><?= htmlspecialchars($channel) ?></div><div class="conversation-meta"><strong><?= htmlspecialchars($author) ?></strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($c['created_at']))) ?></span></div><p><?= nl2br(htmlspecialchars($c['body'])) ?></p><?php foreach($attachmentsByComment[(int)$c['id']]??[] as $f): ?><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>"><span>📎</span><span><strong><?= htmlspecialchars($f['original_name']) ?></strong><small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB · Descargar</small></span></a><?php endforeach; ?></article><?php endforeach; ?><?php foreach($looseAttachments as $f): ?><article class="conversation-message"><div class="conversation-meta"><strong>Archivo adjunto</strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($f['created_at']))) ?></span></div><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>"><span>📎</span><span><strong><?= htmlspecialchars($f['original_name']) ?></strong><small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB · Descargar</small></span></a></article><?php endforeach; ?><?php if(!$comments&&!$looseAttachments): ?><div class="empty-state conversation-empty"><strong>Aún no hay mensajes.</strong></div><?php endif; ?></div>
  <?php if(!in_array($status,['CLOSED','CANCELLED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/respond" enctype="multipart/form-data" class="conversation-form" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><?php if($isSupport): ?><div class="conversation-mode" role="radiogroup" aria-label="Tipo de mensaje"><label><input type="radio" name="visibility" value="PUBLIC" checked><span>Respuesta al usuario</span></label><?php if($externalParticipants): ?><label class="external-mode"><input type="radio" name="visibility" value="EXTERNAL"><span>Colaboración con proveedor</span></label><?php endif; ?><label class="internal-mode"><input type="radio" name="visibility" value="INTERNAL"><span>🔒 Conversación interna</span></label></div><div class="conversation-mode-help">Usuario: visible para el solicitante. Proveedor: solo soporte y colaboradores del caso. Interna: solo equipo de soporte.</div><?php endif; ?><label>Mensaje<textarea class="form-control" name="body" rows="4" placeholder="<?= $isSupport?'Escribe el avance, consulta o coordinación necesaria...':'Agrega información o responde al equipo de soporte...' ?>"></textarea></label><label>Archivo <span class="subtle">(opcional · máximo 10 MB)</span><input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><button class="btn btn-primary" type="submit"><?= $isSupport?'Enviar mensaje':'Enviar actualización' ?></button></form><?php endif; ?></div></section>

  <div class="case-secondary-grid"><section class="card"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Historial</span><h2>Actividad</h2></div></div><div class="ticket-timeline"><?php foreach($events as $e): ?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?= htmlspecialchars($eventLabels[$e['event_type']]??ucfirst(strtolower(str_replace('_',' ',$e['event_type'])))) ?></strong><p><?= htmlspecialchars(date('d/m/Y H:i',strtotime($e['created_at']))) ?><?= !empty($e['actor_name'])?' · '.htmlspecialchars($e['actor_name']):'' ?></p></div></div><?php endforeach; ?><?php if(!$events): ?><div class="empty-state"><strong>Sin movimientos adicionales.</strong></div><?php endif; ?></div></div></section>
  <?php if($resolution): ?><section class="card resolution-summary case-solution-card"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Resultado</span><h2>Solución</h2></div><div class="topbar-actions"><?php if(!empty($resolution['resolved_by_name'])):?><small><?= htmlspecialchars($resolution['resolved_by_name']) ?> · <?= htmlspecialchars(date('d/m/Y H:i',strtotime($resolution['updated_at']))) ?></small><?php endif; ?><?php if($isSupport&&Auth::can('knowledge.manage')): ?><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/knowledge/new?ticket_id=<?= (int)$ticket['id'] ?>">Crear artículo</a><?php endif; ?></div></div><?php if($isSupport): ?><div class="resolution-read-grid"><div><span>Tipo</span><strong><?= htmlspecialchars(str_replace('_',' ',ucfirst(strtolower($resolution['resolution_type'])))) ?></strong></div><div><span>Causa encontrada</span><p><?= nl2br(htmlspecialchars($resolution['root_cause']??'No indicada')) ?></p></div><div class="resolution-full"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php if(!empty($resolution['preventive_action'])):?><div class="resolution-full"><span>Prevención / seguimiento</span><p><?= nl2br(htmlspecialchars($resolution['preventive_action'])) ?></p></div><?php endif; ?></div><?php else: ?><div class="case-user-resolution"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php endif; ?></div></section><?php else: ?><section class="card case-secondary-placeholder"><div class="card-body"><span class="ticket-kicker">Resolución</span><h2><?= $isSupport?'Pendiente de documentar':'Aún en seguimiento' ?></h2></div></section><?php endif; ?></div>

  <?php if($isSupport&&$similar): ?><section class="card similar-solutions"><div class="card-body"><div class="case-section-head"><div><span class="ticket-kicker">Experiencia previa</span><h2>Casos similares resueltos</h2></div></div><div class="similar-list"><?php foreach($similar as $s): ?><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$s['id'] ?>" class="similar-item"><div><strong><?= htmlspecialchars($s['ticket_number'].' · '.$s['subject']) ?></strong><small><?= htmlspecialchars($s['park_name']??'Sin ubicación') ?><?= !empty($s['resolved_at'])?' · '.htmlspecialchars(date('d/m/Y',strtotime($s['resolved_at']))):'' ?> · <?= htmlspecialchars(str_replace('_',' ',ucfirst(strtolower((string)$s['resolution_type'])))) ?></small></div><p><?= htmlspecialchars(mb_strimwidth((string)$s['solution_applied'],0,180,'…')) ?></p><span class="similar-open">Ver solución →</span></a><?php endforeach; ?></div></div></section><?php endif; ?>
</div>
<?php $ticketActivitiesJsVersion=(string)(@filemtime(APP_ROOT.'/public/assets/js/ticket-activities.js')?:'20260914-F5'); ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/ticket-activities.js?v=<?= htmlspecialchars($ticketActivitiesJsVersion) ?>"></script>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
document.addEventListener('click',function(event){
  const link=event.target.closest('[data-suggestion-open]');
  if(!link)return;
  try{
    const body=new FormData();
    body.append('_csrf','<?= htmlspecialchars(Csrf::token(),ENT_QUOTES,'UTF-8') ?>');
    body.append('ticket_id',link.dataset.ticketId||'');
    body.append('reference_type',link.dataset.referenceType||'');
    body.append('reference_id',link.dataset.referenceId||'');
    body.append('revision_id',link.dataset.revisionId||'');
    fetch('<?= APP_BASE_URL ?>/tickets/suggestion/open',{
      method:'POST',
      body,
      keepalive:true,
      credentials:'same-origin'
    }).catch(()=>{});
  }catch(e){}
});
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>