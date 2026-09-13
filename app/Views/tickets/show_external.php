<?php
use App\Core\{Auth,Csrf,Database};

$statusLabels=$statusLabels??[];$status=(string)$ticket['status'];
$comments=[];$attachmentsByComment=[];$looseAttachments=[];$canComment=false;$canUpload=false;$resolution=null;
try{
    $pdo=Database::pdo();
    $aq=$pdo->prepare("SELECT can_comment,can_upload FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1");$aq->execute([(int)$ticket['id'],(int)Auth::id()]);$access=$aq->fetch();$canComment=(bool)($access['can_comment']??false);$canUpload=(bool)($access['can_upload']??false);
    $cq=$pdo->prepare("SELECT tc.*,COALESCE(NULLIF(u.full_name,''),NULLIF(tc.author_name,''),'Usuario') author_display_name,u.access_type author_access_type,r.code author_role,ep.organization_name author_organization FROM ticket_comments tc LEFT JOIN users u ON u.id=tc.author_user_id LEFT JOIN roles r ON r.id=u.role_id LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE tc.ticket_id=? AND tc.deleted_at IS NULL AND tc.visibility IN('PUBLIC','EXTERNAL') ORDER BY tc.created_at,tc.id");$cq->execute([(int)$ticket['id']]);$comments=$cq->fetchAll();
    $at=$pdo->prepare("SELECT ta.* FROM ticket_attachments ta WHERE ta.ticket_id=? AND ta.visibility IN('PUBLIC','EXTERNAL') ORDER BY ta.created_at,ta.id");$at->execute([(int)$ticket['id']]);foreach($at->fetchAll() as $file){if(!empty($file['comment_id']))$attachmentsByComment[(int)$file['comment_id']][]=$file;else $looseAttachments[]=$file;}
    $rq=$pdo->prepare("SELECT tr.solution_applied,tr.preventive_action,tr.updated_at,u.full_name resolved_by_name FROM ticket_resolutions tr LEFT JOIN users u ON u.id=tr.resolved_by WHERE tr.ticket_id=? LIMIT 1");$rq->execute([(int)$ticket['id']]);$resolution=$rq->fetch()?:null;
}catch(\Throwable $e){}

$pageTitle=$ticket['ticket_number'];$pageSection='Mis casos';$activeNav='mine';$helpContext='ticket';require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-work-report .external-report-intro{margin:0 0 18px}
.external-report-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.external-report-grid label{display:flex;flex-direction:column;gap:7px;font-weight:700}
.external-report-grid .external-report-full{grid-column:1/-1}
.external-report-grid textarea{min-height:104px;resize:vertical}
.external-report-grid .external-report-short textarea{min-height:88px}
.external-report-review{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border:1px solid var(--border,#d6deeb);border-radius:12px;font-weight:600}
.external-report-review input{margin-top:3px}
.external-report-actions{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-top:18px;flex-wrap:wrap}
.external-report-actions .subtle{max-width:720px}
@media(max-width:900px){.external-report-grid{grid-template-columns:1fr}.external-report-grid .external-report-full{grid-column:auto}}
</style>
<div class="external-workspace" data-external-workspace>
  <div class="external-case-head"><div><div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div><h1 class="page-title"><?= htmlspecialchars($ticket['subject']) ?></h1><div class="external-case-meta"><span class="external-state-chip"><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></span><span><?= htmlspecialchars($ticket['park_name']??'Ubicación no especificada') ?></span><span><?= htmlspecialchars($ticket['category_name']??'Sin categoría') ?></span></div></div><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/mis-tickets">Mis casos</a></div>
  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <div class="external-work-grid"><main class="external-work-main">
    <section class="card external-problem-card"><div class="card-body"><div class="external-section-head"><div><span class="ticket-kicker">Problema</span><h2>Qué está pasando</h2></div><span class="external-responsible"><?= !empty($ticket['assigned_name'])?'Responsable: '.htmlspecialchars($ticket['assigned_name']):'Pendiente de atención' ?></span></div><div class="external-problem-primary"><span>Problema reportado</span><p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p></div><div class="external-problem-facts external-problem-facts-secondary"><div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div><div><span>Tipo</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div></div></div></section>

    <section class="card external-conversation-card" id="conversacion"><div class="card-body"><div class="external-section-head"><div><span class="ticket-kicker">Seguimiento</span><h2>Conversación con soporte</h2></div><span class="external-message-count"><?= count($comments)+count($looseAttachments) ?> actualización(es)</span></div><div class="conversation-list external-conversation-list">
      <?php foreach($comments as $c):
        $mine=(int)($c['author_user_id']??0)===(int)Auth::id();
        $fromExternal=(($c['author_access_type']??'')==='EXTERNAL');
        $fromRequester=((int)($c['author_user_id']??0)>0&&(int)($c['author_user_id']??0)===(int)($ticket['requester_user_id']??0))||(!empty($c['author_email'])&&strtolower((string)$c['author_email'])===strtolower((string)($ticket['requester_email']??'')));
        $externalChannel=(string)$c['visibility']==='EXTERNAL';
        $authorName=trim((string)($c['author_display_name']??$c['author_name']??''));
        if($authorName==='')$authorName=$mine?'Tú':($fromExternal?'Colaborador':($fromRequester?'Solicitante':'Equipo de soporte'));
        if($fromExternal){
            $authorRole='Colaborador';
            if(!empty($c['author_organization']))$authorRole.=' · '.trim((string)$c['author_organization']);
        }elseif($fromRequester){
            $authorRole='Solicitante';
        }else{
            $authorRole='Equipo de soporte';
        }
        if($mine)$authorRole='Tú · '.$authorRole;
        $channel=$externalChannel?'Colaboración':'Seguimiento';
      ?>
      <article class="conversation-message <?= $mine?'is-mine ':'' ?><?= $externalChannel?'is-external':'is-requester' ?>"><div class="conversation-channel-label <?= $externalChannel?'external':'support' ?>"><?= htmlspecialchars($channel) ?></div><div class="conversation-meta"><strong><?= htmlspecialchars($authorName) ?></strong><span class="conversation-author-role"><?= htmlspecialchars($authorRole) ?></span><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($c['created_at']))) ?></span></div><p><?= nl2br(htmlspecialchars($c['body'])) ?></p><?php foreach($attachmentsByComment[(int)$c['id']]??[] as $f): ?><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?> <small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB</small></a><?php endforeach; ?></article>
      <?php endforeach; ?>
      <?php foreach($looseAttachments as $f): ?><article class="conversation-message"><div class="conversation-meta"><strong>Archivo compartido</strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($f['created_at']))) ?></span></div><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?></a></article><?php endforeach; ?>
      <?php if(!$comments&&!$looseAttachments): ?><div class="external-empty-conversation"><strong>Aún no hay actualizaciones.</strong></div><?php endif; ?>
    </div></div></section>

    <?php if($canComment): ?><section class="card external-work-report" id="informe-tecnico"><div class="card-body">
      <div class="external-section-head"><div><span class="ticket-kicker">Documentación obligatoria</span><h2>Informe técnico requerido</h2></div></div>
      <div class="dashboard-scope-note external-report-intro"><strong>Carrousel necesita conocer exactamente qué ocurrió y qué se hizo.</strong><span>Completa todos los campos con información real. Si un campo no aplica, escribe “No aplica” y explica brevemente por qué.</span></div>
      <form method="post" action="<?= APP_BASE_URL ?>/tickets/work-report" enctype="multipart/form-data" data-single-submit data-action-message="Guardando informe técnico…">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <div class="external-report-grid">
          <label>Estado actual del trabajo<select class="form-control" name="report_type" required><option value="">Selecciona</option><option value="PROGRESS">En atención</option><option value="INFO_REQUEST">Esperando información</option><option value="WORK_COMPLETED">Atención terminada</option></select></label>
          <label>Porcentaje de avance<input class="form-control" type="number" name="progress_percent" required min="0" max="100" step="1" placeholder="0 a 100"></label>

          <label class="external-report-full">Diagnóstico técnico<textarea class="form-control" name="diagnosis" required rows="3" placeholder="Qué encontraron al revisar el caso, síntomas observados, mediciones, códigos o condiciones detectadas."></textarea></label>
          <label class="external-report-full">Causa raíz<textarea class="form-control" name="root_cause" required rows="3" placeholder="Qué originó realmente el problema. Si aún no está confirmada, indícalo y explica la hipótesis técnica."></textarea></label>
          <label class="external-report-full">Acciones realizadas<textarea class="form-control" name="actions_performed" required rows="4" placeholder="Detalla paso a paso lo que se realizó: ajustes, cambios, reinicios, reemplazos, configuraciones o intervenciones."></textarea></label>

          <label>Repuestos / materiales<textarea class="form-control" name="parts_materials" required rows="3" placeholder="Qué se utilizó, cantidad, modelo/parte o escribe No aplica con el motivo."></textarea></label>
          <label>Cambios de configuración<textarea class="form-control" name="configuration_changes" required rows="3" placeholder="Parámetros modificados, valores anteriores/nuevos o No aplica con el motivo."></textarea></label>
          <label>Pruebas realizadas<textarea class="form-control" name="tests_performed" required rows="3" placeholder="Qué pruebas se hicieron para validar el funcionamiento y con qué resultado."></textarea></label>
          <label>Resultado obtenido<textarea class="form-control" name="result_summary" required rows="3" placeholder="Estado final observado después de las acciones y pruebas."></textarea></label>
          <label>Pendientes<textarea class="form-control" name="pending_items" required rows="3" placeholder="Qué queda pendiente, dependencia, pieza, autorización o escribe No aplica si no queda nada."></textarea></label>
          <label>Recomendación preventiva<textarea class="form-control" name="preventive_recommendation" required rows="3" placeholder="Qué recomienda para evitar que vuelva a ocurrir o reducir el riesgo."></textarea></label>

          <label>Referencia del proveedor<input class="form-control" type="text" name="provider_reference" required maxlength="190" placeholder="Ticket, orden de trabajo, RMA, visita u otra referencia externa"></label>
          <label>Tiempo invertido (minutos)<input class="form-control" type="number" name="time_spent_minutes" required min="1" max="525600" step="1" placeholder="Ej. 90"></label>
          <label>Fecha compromiso <span class="subtle">Si la atención sigue abierta</span><input class="form-control" type="datetime-local" name="commitment_at"></label>
          <?php if($canUpload): ?><label>Evidencia / archivo <span class="subtle">Opcional · máximo 10 MB</span><input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.doc,.docx,.xls,.xlsx"></label><?php endif; ?>

          <label class="external-report-full external-report-review"><input type="checkbox" name="ready_for_review" value="1"><span>Atención terminada y lista para revisión de Carrousel. Marca esta opción únicamente cuando el trabajo esté realmente finalizado y el avance sea 100%.</span></label>
        </div>
        <div class="external-report-actions"><span class="subtle">Este informe queda registrado como evidencia técnica del caso y no cierra el ticket automáticamente.</span><button class="btn btn-primary" type="submit">Guardar informe técnico</button></div>
      </form>
    </div></section><?php endif; ?>

    <?php if($resolution): ?><section class="card external-solution-card"><div class="card-body"><span class="ticket-kicker">Resultado</span><h2>Solución final</h2><div class="external-problem-primary"><p><?= nl2br(htmlspecialchars((string)$resolution['solution_applied'])) ?></p></div><?php if(!empty($resolution['preventive_action'])): ?><div class="dashboard-scope-note"><strong>Recomendación</strong><span><?= nl2br(htmlspecialchars((string)$resolution['preventive_action'])) ?></span></div><?php endif; ?><div class="subtle" style="margin-top:10px"><?= !empty($resolution['resolved_by_name'])?htmlspecialchars((string)$resolution['resolved_by_name']).' · ':'' ?><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$resolution['updated_at']))) ?></div></div></section><?php endif; ?>
  </main>

  <aside class="external-work-side"><section class="card external-side-card"><div class="card-body"><span class="ticket-kicker">Flujo</span><h2>Qué sigue</h2><ol class="external-next-steps"><li><span>1</span><div><strong>Revisa</strong><small>Problema y seguimiento.</small></div></li><li><span>2</span><div><strong>Documenta</strong><small>Completa el informe técnico requerido.</small></div></li><li><span>3</span><div><strong>Revisión</strong><small>Carrousel valida el trabajo antes de cerrar.</small></div></li></ol></div></section></aside></div>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>