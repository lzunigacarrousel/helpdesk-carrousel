<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controllerPath=$root.'/app/Controllers/TicketController.php';
$viewPath=$root.'/app/Views/tickets/show.php';

function replaceOnceNormalized(string $path,string $old,string $new,string $present,string $label): void
{
    $raw=(string)file_get_contents($path);
    $eol=str_contains($raw,"\r\n")?"\r\n":"\n";
    $body=str_replace("\r\n","\n",$raw);

    if($present!==''&&str_contains($body,$present)){
        echo "[OK] {$label}: ya aplicado.".PHP_EOL;
        return;
    }

    $count=substr_count($body,$old);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba 1 coincidencia y encontro {$count}.".PHP_EOL);
        exit(1);
    }

    $body=str_replace($old,$new,$body);
    if($eol==="\r\n")$body=str_replace("\n","\r\n",$body);
    file_put_contents($path,$body);
    echo "[OK] {$label}.".PHP_EOL;
}

$oldImport="use App\\Services\\{NotificationService,ScopeService,SlaPresentationService,TicketClassificationService,RequesterLocationPolicyService,TicketActivityService};";
$newImport="use App\\Services\\{NotificationService,ScopeService,SlaPresentationService,TicketClassificationService,RequesterLocationPolicyService,TicketActivityService,ProviderParticipationService,ProviderRatingService};";
replaceOnceNormalized(
    $controllerPath,
    $oldImport,
    $newImport,
    'ProviderParticipationService,ProviderRatingService',
    'TicketController importa servicios de calidad'
);

$controllerAnchor="        \$activityParks=\$isSupport?\$pdo->query(\"SELECT id,name FROM parks WHERE is_active=1 ORDER BY name\")->fetchAll():[];\n\n        View::render('tickets/show',[";
$controllerInsert="        \$activityParks=\$isSupport?\$pdo->query(\"SELECT id,name FROM parks WHERE is_active=1 ORDER BY name\")->fetchAll():[];\n\n        \$providerCycles=[];\n        \$providerRatingLabels=ProviderRatingService::SCORE_LABELS;\n        \$canRateProviders=\$isSupport\n            &&in_array((string)Auth::role(),['ADMIN','SEMIADMIN','TECHNICIAN'],true)\n            &&(new ScopeService())->userCanAccessTicket((int)Auth::id(),\$id);\n        if(\$isSupport){\n            \$participationService=new ProviderParticipationService(\$pdo);\n            \$ratingService=new ProviderRatingService(\$pdo);\n            \$providerCycles=\$ratingService->enrichRows(\$participationService->rowsForTicket(\$id));\n        }\n\n        View::render('tickets/show',[";
replaceOnceNormalized(
    $controllerPath,
    $controllerAnchor,
    $controllerInsert,
    '$providerCycles=$ratingService->enrichRows($participationService->rowsForTicket($id));',
    'TicketController carga ciclos y valoraciones'
);

$renderAnchor="            'canCancelActivities'=>\$canCancelActivities,\n        ]);";
$renderInsert="            'canCancelActivities'=>\$canCancelActivities,\n            'providerCycles'=>\$providerCycles,\n            'providerRatingLabels'=>\$providerRatingLabels,\n            'canRateProviders'=>\$canRateProviders,\n        ]);";
replaceOnceNormalized(
    $controllerPath,
    $renderAnchor,
    $renderInsert,
    "'providerCycles'=>\$providerCycles",
    'TicketController expone calidad a la vista'
);

$viewAnchor='  <section class="card conversation-card case-conversation-card" id="conversacion">';
$viewBlock=<<<'PHP'
  <?php if($isSupport&&!empty($providerCycles)): ?>
  <section class="card ticket-classification-card" id="provider-quality"><div class="card-body">
    <div class="case-section-head"><div><span class="ticket-kicker">Control interno</span><h2>Calidad del proveedor</h2></div></div>
    <p class="field-help">Valoración interna de IT por cada participación finalizada. No es visible para el proveedor ni para el solicitante.</p>

    <?php foreach($providerCycles as $cycle):
      $closed=!empty($cycle['revoke_event_id']);
      $rated=($cycle['provider_rating_score']??null)!==null;
      $organization=(string)($cycle['organization']??$cycle['contact']??'Proveedor');
    ?>
      <div class="ticket-classification-summary">
        <div><span>Proveedor</span><strong><?= htmlspecialchars($organization) ?></strong><small><?= htmlspecialchars((string)($cycle['contact']??'')) ?></small></div>
        <div><span>Asignado</span><strong><?= !empty($cycle['granted_at'])?htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['granted_at']))):'Sin fecha' ?></strong></div>
        <div><span>Participación</span><strong><?= $closed?'Finalizada':'Activa' ?></strong><?php if($closed&&!empty($cycle['revoked_at'])): ?><small>Finalizó <?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['revoked_at']))) ?></small><?php endif; ?></div>
        <div><span>Valoración vigente</span><strong><?= $rated?((int)$cycle['provider_rating_score'].'★ · '.htmlspecialchars((string)$cycle['provider_rating_label'])):'Sin evaluar' ?></strong><?php if($rated&&!empty($cycle['provider_rating_revisions'])): ?><small><?= (int)$cycle['provider_rating_revisions'] ?> corrección(es)</small><?php endif; ?></div>
      </div>

      <?php if(!$closed): ?>
        <div class="dashboard-scope-note"><span>Podrás evaluar cuando finalice la participación.</span></div>
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
          <div><span>Registró</span><strong><?= htmlspecialchars((string)($cycle['provider_rating_actor']?:'Equipo IT')) ?></strong><?php if(!empty($cycle['provider_rating_at'])): ?><small><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$cycle['provider_rating_at']))) ?></small><?php endif; ?></div>
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

PHP;
replaceOnceNormalized(
    $viewPath,
    $viewAnchor,
    $viewBlock.$viewAnchor,
    'id="provider-quality"',
    'Vista interna incorpora calidad del proveedor'
);

foreach([$controllerPath,$viewPath] as $path){
    $cmd='"'.PHP_BINARY.'" -l '.escapeshellarg($path);
    passthru($cmd,$code);
    if($code!==0)exit($code);
}

echo '[OK] UI Fase 8 aplicada. No se modifico la BD.'.PHP_EOL;
