<?php
use App\Core\{Auth,Csrf,Database};

$statusLabels=$statusLabels??[];
$status=(string)$ticket['status'];
$comments=[];$attachmentsByComment=[];$looseAttachments=[];$canComment=false;$canUpload=false;$resolution=null;
$templateLabels=[
    'GENERAL_SUPPORT'=>'Soporte general',
    'SOFTWARE_SUPPORT'=>'Soporte de software',
    'SOFTWARE_DEVELOPMENT'=>'Desarrollo de software',
    'AUDIT_ADVISORY'=>'Auditoría / asesoría',
];
$workStatusLabels=[
    'ANALYSIS'=>'En análisis / diagnóstico',
    'WAITING_CARROUSEL'=>'Esperando información de Carrousel',
    'WAITING_THIRD_PARTY'=>'Esperando tercero / fabricante',
    'IN_PROGRESS'=>'En atención / trabajando',
    'VALIDATING'=>'En validación',
    'READY_FOR_REVIEW'=>'Listo para revisión de Carrousel',
];
$reportTemplate=(string)($ticket['report_template']??'GENERAL_SUPPORT');
if(!isset($templateLabels[$reportTemplate]))$reportTemplate='GENERAL_SUPPORT';
try{
    $pdo=Database::pdo();
    $aq=$pdo->prepare("SELECT can_comment,can_upload FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1");
    $aq->execute([(int)$ticket['id'],(int)Auth::id()]);
    $access=$aq->fetch();
    $canComment=(bool)($access['can_comment']??false);
    $canUpload=(bool)($access['can_upload']??false);

    $cq=$pdo->prepare("SELECT tc.*,COALESCE(NULLIF(u.full_name,''),NULLIF(tc.author_name,''),'Usuario') author_display_name,u.access_type author_access_type,r.code author_role,ep.organization_name author_organization
        FROM ticket_comments tc
        LEFT JOIN users u ON u.id=tc.author_user_id
        LEFT JOIN roles r ON r.id=u.role_id
        LEFT JOIN external_profiles ep ON ep.user_id=u.id
        WHERE tc.ticket_id=? AND tc.deleted_at IS NULL AND tc.visibility IN('PUBLIC','EXTERNAL')
        ORDER BY tc.created_at,tc.id");
    $cq->execute([(int)$ticket['id']]);
    $comments=$cq->fetchAll();

    $at=$pdo->prepare("SELECT ta.* FROM ticket_attachments ta WHERE ta.ticket_id=? AND ta.visibility IN('PUBLIC','EXTERNAL') ORDER BY ta.created_at,ta.id");
    $at->execute([(int)$ticket['id']]);
    foreach($at->fetchAll() as $file){
        if(!empty($file['comment_id']))$attachmentsByComment[(int)$file['comment_id']][]=$file;
        else $looseAttachments[]=$file;
    }

    $rq=$pdo->prepare("SELECT tr.solution_applied,tr.preventive_action,tr.updated_at,u.full_name resolved_by_name
        FROM ticket_resolutions tr LEFT JOIN users u ON u.id=tr.resolved_by WHERE tr.ticket_id=? LIMIT 1");
    $rq->execute([(int)$ticket['id']]);
    $resolution=$rq->fetch()?:null;
}catch(\Throwable $e){}

$pageTitle=$ticket['ticket_number'];$pageSection='Mis casos';$activeNav='mine';$helpContext='ticket';require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<style nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
.external-work-grid{grid-template-columns:1fr}
.external-report-intro{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}.external-report-template{display:inline-flex;align-items:center;padding:6px 10px;border:1px solid var(--border);border-radius:999px;font-size:11px;font-weight:800;color:var(--brand-dark);background:var(--surface-soft)}
.external-report-form{display:grid;gap:14px}.external-report-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.external-report-grid .external-full{grid-column:1/-1}.external-report-block{display:grid;gap:10px;padding:14px;border:1px solid var(--border);border-radius:12px;background:var(--surface-soft)}.external-report-block[hidden]{display:none!important}.external-report-block h3{margin:0;font-size:14px}.external-report-block p{margin:0;color:var(--muted);font-size:11px}.external-report-block textarea{min-height:88px}.external-report-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:4px}.external-report-help{font-size:11px;color:var(--muted);max-width:680px}
@media(max-width:760px){.external-report-intro{flex-direction:column}.external-report-grid{grid-template-columns:1fr}.external-report-grid .external-full{grid-column:auto}.external-report-actions{align-items:stretch;flex-direction:column}.external-report-actions .btn{width:100%}}
</style>
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
    <a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/mis-tickets">Mis casos</a>
  </div>
  <?php if(!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars((string)($flash['type']??'success')) ?>"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <div class="external-work-grid"><main class="external-work-main">
    <section class="card external-problem-card"><div class="card-body">
      <div class="external-section-head"><div><span class="ticket-kicker">Problema</span><h2>Qué está pasando</h2></div><span class="external-responsible"><?= !empty($ticket['assigned_name'])?'Responsable: '.htmlspecialchars($ticket['assigned_name']):'Pendiente de atención' ?></span></div>
      <div class="external-problem-primary"><span>Problema reportado</span><p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p></div>
      <div class="external-problem-facts external-problem-facts-secondary"><div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div><div><span>Tipo</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div></div>
    </div></section>

    <section class="card external-conversation-card" id="conversacion"><div class="card-body">
      <div class="external-section-head"><div><span class="ticket-kicker">Seguimiento</span><h2>Conversación con soporte</h2></div><span class="external-message-count"><?= count($comments)+count($looseAttachments) ?> actualización(es)</span></div>
      <div class="conversation-list external-conversation-list">
      <?php foreach($comments as $c):
        $mine=(int)($c['author_user_id']??0)===(int)Auth::id();
        $fromExternal=(($c['author_access_type']??'')==='EXTERNAL');
        $fromRequester=((int)($c['author_user_id']??0)>0&&(int)($c['author_user_id']??0)===(int)($ticket['requester_user_id']??0))||(!empty($c['author_email'])&&strtolower((string)$c['author_email'])===strtolower((string)($ticket['requester_email']??'')));
        $externalChannel=(string)$c['visibility']==='EXTERNAL';
        $authorName=trim((string)($c['author_display_name']??$c['author_name']??''));
        if($authorName==='')$authorName=$mine?'Tú':($fromExternal?'Colaborador':($fromRequester?'Solicitante':'Equipo de soporte'));
        if($fromExternal){$authorRole='Colaborador'.(!empty($c['author_organization'])?' · '.trim((string)$c['author_organization']):'');}
        elseif($fromRequester){$authorRole='Solicitante';}
        else{$authorRole='Equipo de soporte';}
        if($mine)$authorRole='Tú · '.$authorRole;
        $channel=$externalChannel?'Colaboración':'Seguimiento';
      ?>
        <article class="conversation-message <?= $mine?'is-mine ':'' ?><?= $externalChannel?'is-external':'is-requester' ?>">
          <div class="conversation-channel-label <?= $externalChannel?'external':'support' ?>"><?= htmlspecialchars($channel) ?></div>
          <div class="conversation-meta"><strong><?= htmlspecialchars($authorName) ?></strong><span class="conversation-author-role"><?= htmlspecialchars($authorRole) ?></span><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($c['created_at']))) ?></span></div>
          <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
          <?php foreach($attachmentsByComment[(int)$c['id']]??[] as $f): ?><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?> <small><?= number_format(((int)$f['size_bytes'])/1024,0) ?> KB</small></a><?php endforeach; ?>
        </article>
      <?php endforeach; ?>
      <?php foreach($looseAttachments as $f): ?><article class="conversation-message"><div class="conversation-meta"><strong>Archivo compartido</strong><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($f['created_at']))) ?></span></div><a class="conversation-file" href="<?= APP_BASE_URL ?>/tickets/attachment?id=<?= (int)$f['id'] ?>">📎 <?= htmlspecialchars($f['original_name']) ?></a></article><?php endforeach; ?>
      <?php if(!$comments&&!$looseAttachments): ?><div class="external-empty-conversation"><strong>Aún no hay actualizaciones.</strong></div><?php endif; ?>
      </div>
    </div></section>

    <?php if($canComment): ?>
    <section class="card external-reply-card external-work-report" id="informe-tecnico" data-report-template="<?= htmlspecialchars($reportTemplate) ?>"><div class="card-body">
      <div class="external-report-intro">
        <div><span class="ticket-kicker">Documentación obligatoria</span><h2>Informe técnico requerido</h2><p class="subtle">Completa la información que corresponde al estado actual de la atención. Carrousel utilizará este registro como trazabilidad del caso.</p></div>
        <span class="external-report-template"><?= htmlspecialchars($templateLabels[$reportTemplate]) ?></span>
      </div>

      <form class="external-report-form" method="post" action="<?= APP_BASE_URL ?>/tickets/work-report" enctype="multipart/form-data" data-single-submit data-action-message="Guardando documentación…" data-external-work-report data-report-template="<?= htmlspecialchars($reportTemplate) ?>">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">

        <div class="external-report-grid">
          <label>Estado actual del trabajo *<select class="form-control" name="work_status" data-work-status required><?php foreach($workStatusLabels as $code=>$label): ?><option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
          <label>Referencia / caso del proveedor<input class="form-control" name="provider_reference" placeholder="Ej. ticket, folio o referencia interna"></label>
          <label>Tiempo invertido <span class="optional">Opcional</span><input class="form-control" type="number" min="0" name="time_spent_minutes" placeholder="Minutos"></label>
          <label>Próxima fecha compromiso <span class="optional">Cuando aplique</span><input class="form-control" type="datetime-local" name="commitment_at"></label>
        </div>

        <section class="external-report-block" data-work-status-block="ANALYSIS IN_PROGRESS VALIDATING READY_FOR_REVIEW">
          <h3>Diagnóstico y situación encontrada</h3><p>Documenta qué está ocurriendo y qué determinaste hasta este punto.</p>
          <label>Diagnóstico / situación encontrada<textarea class="form-control" name="diagnosis"></textarea></label>
          <label>Causa raíz <span class="optional">Si ya fue identificada</span><textarea class="form-control" name="root_cause"></textarea></label>
        </section>

        <section class="external-report-block" data-work-status-block="IN_PROGRESS VALIDATING READY_FOR_REVIEW">
          <h3>Trabajo realizado</h3><p>Describe las acciones ejecutadas sobre el caso.</p>
          <label>Acciones realizadas<textarea class="form-control" name="actions_performed"></textarea></label>
        </section>

        <section class="external-report-block" data-work-status-block="WAITING_CARROUSEL WAITING_THIRD_PARTY IN_PROGRESS VALIDATING READY_FOR_REVIEW">
          <h3>Pendientes y siguiente paso</h3>
          <label>Pendientes / información requerida<textarea class="form-control" name="pending_items" placeholder="Indica qué falta, de quién depende y cuál es el siguiente paso."></textarea></label>
        </section>

        <section class="external-report-block" data-work-status-block="VALIDATING READY_FOR_REVIEW">
          <h3>Validación y resultado</h3>
          <label>Pruebas realizadas<textarea class="form-control" name="tests_performed"></textarea></label>
          <label>Resultado obtenido<textarea class="form-control" name="result_summary"></textarea></label>
        </section>

        <section class="external-report-block" data-work-status-block="READY_FOR_REVIEW">
          <h3>Cierre técnico para revisión</h3>
          <label>Recomendación preventiva / seguimiento<textarea class="form-control" name="preventive_recommendation"></textarea></label>
        </section>

        <section class="external-report-block" data-report-template-block="SOFTWARE_SUPPORT SOFTWARE_DEVELOPMENT">
          <h3>Contexto del sistema</h3>
          <div class="external-report-grid"><label>Sistema / módulo<input class="form-control" name="system_module" placeholder="Ej. facturación, POS, backoffice, kiosco..."></label><label>Ambiente<input class="form-control" name="environment" placeholder="Ej. Producción, QA, pruebas"></label></div>
        </section>

        <section class="external-report-block" data-report-template-block="SOFTWARE_SUPPORT">
          <h3>Soporte de software</h3><p>Información técnica para reproducir, aplicar y validar la atención.</p>
          <label>Error o síntoma<textarea class="form-control" name="error_symptom"></textarea></label>
          <label>Cómo reproducir el problema<textarea class="form-control" name="reproduction_steps"></textarea></label>
          <label>Configuración / query / archivo / versión modificada<textarea class="form-control" name="configuration_changes"></textarea></label>
          <label>Procedimiento realizado paso a paso<textarea class="form-control" name="procedure_steps"></textarea></label>
          <label>Herramientas o accesos utilizados<textarea class="form-control" name="tools_access_used"></textarea></label>
          <label>Cómo revertir el cambio <span class="optional">Si aplica</span><textarea class="form-control" name="rollback_steps"></textarea></label>
          <label>Criterio recomendado para escalar nuevamente<textarea class="form-control" name="escalation_criteria"></textarea></label>
        </section>

        <section class="external-report-block" data-report-template-block="SOFTWARE_DEVELOPMENT">
          <h3>Desarrollo de software</h3><p>Documenta exactamente qué cambió en la solución entregada.</p>
          <label>Cambios de código / módulos<textarea class="form-control" name="code_changes"></textarea></label>
          <label>Cambios en base de datos<textarea class="form-control" name="database_changes"></textarea></label>
          <div class="external-report-grid"><label>Versión / build liberada<input class="form-control" name="release_version"></label><label>Cómo revertir el cambio <span class="optional">Si aplica</span><textarea class="form-control" name="rollback_steps"></textarea></label></div>
          <label>Notas de despliegue<textarea class="form-control" name="deployment_notes"></textarea></label>
        </section>

        <section class="external-report-block" data-report-template-block="AUDIT_ADVISORY">
          <h3>Auditoría / asesoría</h3><p>Documenta el hallazgo, riesgo, impacto y recomendación para Carrousel.</p>
          <label>Alcance de revisión<textarea class="form-control" name="review_scope"></textarea></label>
          <label>Hallazgo<textarea class="form-control" name="finding"></textarea></label>
          <label>Evidencia / sustento<textarea class="form-control" name="evidence_summary"></textarea></label>
          <div class="external-report-grid"><label>Nivel de riesgo<select class="form-control" name="risk_level"><option value="">Selecciona</option><option value="LOW">Bajo</option><option value="MEDIUM">Medio</option><option value="HIGH">Alto</option><option value="CRITICAL">Crítico</option></select></label><label>Prioridad de recomendación<select class="form-control" name="recommendation_priority"><option value="">Selecciona</option><option value="LOW">Baja</option><option value="MEDIUM">Media</option><option value="HIGH">Alta</option><option value="CRITICAL">Crítica</option></select></label></div>
          <label>Impacto para Carrousel<textarea class="form-control" name="business_impact"></textarea></label>
          <label>Recomendación<textarea class="form-control" name="recommendation"></textarea></label>
          <label>Responsable sugerido<input class="form-control" name="suggested_owner"></label>
          <label>Seguimiento sugerido<textarea class="form-control" name="follow_up"></textarea></label>
          <label>Conclusión<textarea class="form-control" name="conclusion"></textarea></label>
        </section>

        <?php if($canUpload): ?><label class="external-upload-label">Evidencia / archivo <span class="subtle">Opcional · máximo 10 MB</span><input class="external-file-input" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><?php endif; ?>

        <div class="external-report-actions"><span class="external-report-help">Los campos visibles cambian según el tipo de servicio y el estado de la atención. Cuando el estado sea “Listo para revisión”, Carrousel recibirá el informe como entrega técnica para revisión.</span><button class="btn btn-primary" type="submit">Guardar documentación</button></div>
      </form>
    </div></section>
    <?php endif; ?>

    <?php if($resolution): ?><section class="card external-solution-card"><div class="card-body"><span class="ticket-kicker">Resultado</span><h2>Solución final</h2><div class="external-problem-primary"><p><?= nl2br(htmlspecialchars((string)$resolution['solution_applied'])) ?></p></div><?php if(!empty($resolution['preventive_action'])): ?><div class="dashboard-scope-note"><strong>Recomendación</strong><span><?= nl2br(htmlspecialchars((string)$resolution['preventive_action'])) ?></span></div><?php endif; ?><div class="subtle" style="margin-top:10px"><?= !empty($resolution['resolved_by_name'])?htmlspecialchars((string)$resolution['resolved_by_name']).' · ':'' ?><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$resolution['updated_at']))) ?></div></div></section><?php endif; ?>
  </main>
  </div>
</div>
<script src="<?= APP_BASE_URL ?>/assets/js/external-work-report.js?v=1" defer></script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>