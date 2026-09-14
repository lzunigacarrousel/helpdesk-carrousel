$ErrorActionPreference = 'Stop'

$repo = Split-Path -Parent $PSScriptRoot
$path = Join-Path $repo 'app\Views\tickets\show.php'

if (-not (Test-Path $path)) {
    throw "No existe $path"
}

$content = [System.IO.File]::ReadAllText($path)

if (-not $content.Contains('ticket-activities.css')) {
    $workspaceMarker = '<div class="ticket-workspace case-focus-workspace">'
    if (-not $content.Contains($workspaceMarker)) {
        throw 'No se encontro el inicio del workspace del ticket.'
    }
    $assetLink = @'
<?php $ticketActivitiesAsset=(string)(@filemtime(APP_ROOT.'/public/assets/css/ticket-activities.css')?:'20260914-F5'); ?>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ticket-activities.css?v=<?= htmlspecialchars($ticketActivitiesAsset) ?>">
'@
    $content = $content.Replace($workspaceMarker, $assetLink + "`r`n" + $workspaceMarker)
}

$conversationMarker = '  <section class="card conversation-card case-conversation-card" id="conversacion">'
if (-not $content.Contains('id="actividades"')) {
    if (-not $content.Contains($conversationMarker)) {
        throw 'No se encontro el bloque de conversacion para insertar Actividades.'
    }

    $activityBlock = @'
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
            <label><span class="form-label">Responsable</span><select class="form-control" name="responsible_user_id" required><option value="">Selecciona responsable</option><?php foreach(($activityResponsibleUsers??[]) as $person): ?><option value="<?= (int)$person['id'] ?>"><?= htmlspecialchars((string)$person['full_name']) ?> · <?= htmlspecialchars((string)$person['email']) ?></option><?php endforeach; ?></select></label>
            <label><span class="form-label">Inicio programado</span><input class="form-control" type="datetime-local" name="scheduled_start_at" required></label>
            <label><span class="form-label">Fin estimado</span><input class="form-control" type="datetime-local" name="scheduled_end_at" required></label>

            <label data-activity-panel="VISITA_EN_SITIO" hidden><span class="form-label">Parque de la visita</span><select class="form-control" name="park_id" data-activity-required><option value="">Selecciona parque</option><?php foreach(($activityParks??[]) as $park): ?><option value="<?= (int)$park['id'] ?>" <?= (int)($ticket['park_id']??0)===(int)$park['id']?'selected':'' ?>><?= htmlspecialchars((string)$park['name']) ?></option><?php endforeach; ?></select></label>
            <label data-activity-panel="INTERVENCION_PROVEEDOR" hidden><span class="form-label">Proveedor que intervendrá</span><select class="form-control" name="provider_user_id" data-activity-required><option value="">Selecciona proveedor</option><?php foreach(($activityProviderUsers??[]) as $provider): ?><option value="<?= (int)$provider['id'] ?>"><?= htmlspecialchars((string)($provider['organization_name']?:$provider['full_name'])) ?> · <?= htmlspecialchars((string)$provider['email']) ?></option><?php endforeach; ?></select></label>
            <div data-activity-panel="SOPORTE_REMOTO" hidden class="activity-span-2 ticket-activity-visibility"><span>Esta actividad se registrará como soporte remoto.</span></div>

            <label class="activity-span-2"><span class="form-label">Objetivo</span><textarea class="form-control" name="objective" rows="3" required placeholder="Ej. Revisar comunicación del kiosco con el servidor y validar impresión."></textarea></label>
            <label class="activity-span-2"><span class="form-label">Preparación interna <span class="optional">Opcional</span></span><textarea class="form-control" name="internal_preparation_notes" rows="2" placeholder="Accesos, herramientas o puntos que el equipo debe preparar antes de atender."></textarea></label>
            <label class="activity-span-2"><span class="form-label">Participantes adicionales <span class="optional">Opcional</span></span><select class="form-control" name="participant_user_ids[]" multiple size="3"><?php foreach(($activityResponsibleUsers??[]) as $person): ?><option value="<?= (int)$person['id'] ?>"><?= htmlspecialchars((string)$person['full_name']) ?></option><?php endforeach; ?></select><span class="field-help">El responsable principal no necesita seleccionarse nuevamente.</span></label>
            <label class="activity-span-2 ticket-activity-visibility"><input type="checkbox" name="requester_visible" value="1" data-requester-visible><span>Mostrar esta actividad al solicitante</span></label>
            <label class="activity-span-2 ticket-activity-requester-summary" hidden><span class="form-label">Resumen visible al solicitante</span><textarea class="form-control" name="requester_summary" rows="2" maxlength="500" placeholder="Ej. Se programó una visita para revisar el equipo reportado."></textarea><span class="field-help">No incluyas notas internas, accesos ni información técnica sensible.</span></label>
          </div>
          <div class="ticket-activity-submit"><button type="button" class="btn btn-outline-secondary" data-activity-close>Cancelar</button><button class="btn btn-primary" type="submit">Programar actividad</button></div>
        </form>
      </div>
      <?php endif; ?>

      <div class="ticket-activity-sections">
        <div class="ticket-activity-section">
          <div class="ticket-activity-section-head"><h3>Próximas / activas</h3><span><?= count($activeActivities) ?> actividad(es)</span></div>
          <?php if($activeActivities): ?><div class="ticket-activity-grid">
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
                  <details><summary class="btn btn-outline-secondary btn-sm">Reprogramar</summary><form class="ticket-activity-transition two-columns" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/reschedule" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Nuevo inicio</span><input class="form-control" type="datetime-local" name="scheduled_start_at" required></label><label><span class="form-label">Nuevo fin</span><input class="form-control" type="datetime-local" name="scheduled_end_at" required></label><label class="activity-span-2"><span class="form-label">Motivo</span><input class="form-control" type="text" name="reason" minlength="5" required placeholder="Motivo de la reprogramación"></label><div class="activity-span-2"><button class="btn btn-primary btn-sm" type="submit">Guardar nueva fecha</button></div></form></details>
                  <form method="post" action="<?= APP_BASE_URL ?>/tickets/activities/start" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><button class="btn btn-primary btn-sm" type="submit">Iniciar</button></form>
                <?php endif; ?>

                <?php if(!empty($canManageActivities)&&$activityStatus==='EN_CURSO'): ?>
                  <details><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace('_',' ',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></form></details>
                <?php endif; ?>

                <?php if(!empty($canCancelActivities)&&in_array($activityStatus,['PROGRAMADA','EN_CURSO'],true)): ?>
                  <details><summary class="btn btn-outline-secondary btn-sm">Cancelar</summary><form class="ticket-activity-transition" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/cancel" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Motivo de cancelación</span><textarea class="form-control" name="cancel_reason" minlength="5" required placeholder="Explica por qué se cancela esta actividad"></textarea></label><button class="btn btn-outline-secondary btn-sm" type="submit" data-activity-confirm="¿Cancelar esta actividad?">Confirmar cancelación</button></form></details>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          </div><?php else: ?><div class="ticket-activity-empty"><strong>No hay actividades activas.</strong>Programa una actividad cuando el caso requiera una visita, soporte remoto, seguimiento o intervención.</div><?php endif; ?>
        </div>

        <div class="ticket-activity-section">
          <div class="ticket-activity-section-head"><h3>Historial</h3><span><?= count($historyActivities) ?> actividad(es)</span></div>
          <?php if($historyActivities): ?><div class="ticket-activity-grid">
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

'@
    $content = $content.Replace($conversationMarker, $activityBlock + $conversationMarker)
}

if (-not $content.Contains('ticket-activities.js')) {
    $endMarker = "<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>"
    if (-not $content.Contains($endMarker)) {
        throw 'No se encontro app_end.php para cargar el JS de actividades.'
    }
    $scriptTag = @'
<?php $ticketActivitiesJsVersion=(string)(@filemtime(APP_ROOT.'/public/assets/js/ticket-activities.js')?:'20260914-F5'); ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/ticket-activities.js?v=<?= htmlspecialchars($ticketActivitiesJsVersion) ?>"></script>
'@
    $content = $content.Replace($endMarker, $scriptTag + "`r`n" + $endMarker)
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $utf8NoBom)

Write-Host '[OK] UI de actividades insertada en app/Views/tickets/show.php.'
Write-Host '[OK] CSS y JS de Task 7 quedan cargados solo en el detalle del ticket.'
Write-Host '[OK] No se modifico la base de datos.'
