<?php
use App\Core\{Csrf,Database};
$statusLabels=$statusLabels??[];$priorityLabels=$priorityLabels??[];$status=(string)$ticket['status'];
$eventLabels=['CREATED'=>'Solicitud creada','CLAIMED'=>'Caso tomado','REASSIGNED'=>'Responsable cambiado','RELEASED'=>'Devuelto a disponibles','STATUS_CHANGED'=>'Estado actualizado','COMMENTED'=>'Nueva respuesta','RESOLUTION_RECORDED'=>'Solución documentada','RESOLVED'=>'Caso resuelto','CLOSED'=>'Caso cerrado','REOPENED'=>'Caso reabierto'];
$resolution=null;$similar=[];
try{
    $rq=Database::pdo()->prepare("SELECT tr.*,u.full_name resolved_by_name FROM ticket_resolutions tr LEFT JOIN users u ON u.id=tr.resolved_by WHERE tr.ticket_id=? LIMIT 1");
    $rq->execute([(int)$ticket['id']]);$resolution=$rq->fetch()?:null;
    if($isSupport){
        $sq=Database::pdo()->prepare("SELECT t.id,t.ticket_number,t.subject,t.resolved_at,tr.resolution_type,tr.solution_applied,p.name park_name
            FROM tickets t JOIN ticket_resolutions tr ON tr.ticket_id=t.id LEFT JOIN parks p ON p.id=t.park_id
            WHERE t.id<>? AND t.deleted_at IS NULL AND t.status IN('RESOLVED','CLOSED') AND tr.is_reusable=1
              AND (t.category_id=? OR (? IS NOT NULL AND t.park_id=?))
            ORDER BY (t.category_id=? ) DESC,(t.park_id=? ) DESC,t.resolved_at DESC LIMIT 5");
        $park=$ticket['park_id']??null;$cat=(int)($ticket['category_id']??0);
        $sq->execute([(int)$ticket['id'],$cat,$park,$park,$cat,$park]);$similar=$sq->fetchAll();
    }
}catch(\Throwable $e){}
$pageTitle=$ticket['ticket_number'];$pageSection=$isSupport?'Centro de soporte':'Mis solicitudes';$activeNav=$isSupport?'support':'mine';$helpContext='ticket';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="ticket-workspace">
<div class="page-heading"><div><div class="ticket-kicker">Caso <?= htmlspecialchars($ticket['ticket_number']) ?></div><h1 class="page-title"><?= htmlspecialchars($ticket['subject']) ?></h1><p class="page-subtitle"><?= htmlspecialchars($ticket['category_name']??'Solicitud de soporte') ?><?= !empty($ticket['park_name'])?' · '.htmlspecialchars($ticket['park_name']):'' ?></p></div><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?><?= $isSupport?'/tickets/queue':'/mis-tickets' ?>">← Volver</a></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<div class="ticket-summary-grid"><section class="ticket-summary-item"><span>Estado</span><strong><?= htmlspecialchars($statusLabels[$status]??str_replace('_',' ',$status)) ?></strong></section><section class="ticket-summary-item"><span>Prioridad</span><strong><?= htmlspecialchars($priorityLabels[$ticket['priority']]??$ticket['priority']) ?></strong></section><section class="ticket-summary-item"><span>Responsable</span><strong><?= htmlspecialchars($ticket['assigned_name']??'Aún sin asignar') ?></strong></section></div>

<?php if($isSupport): ?>
<section class="card ticket-actions-card"><div class="card-body"><div class="ticket-actions-head"><div><h2>Atención del caso</h2><p>Trabaja el caso y documenta la solución aquí mismo.</p></div></div><div class="ticket-action-bar">
<?php if($canClaim): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-primary ticket-primary-action" type="submit">Tomar y atender</button></form><?php endif; ?>
<?php if($canChangeStatus): ?>
<?php if(in_array($status,['PENDING','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="IN_PROGRESS"><button class="btn btn-primary" type="submit">Continuar atención</button></form><?php endif; ?>
<?php if(in_array($status,['IN_PROGRESS','REOPENED'],true)): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="PENDING"><button class="btn btn-outline-secondary" type="submit">Poner en espera</button></form><?php endif; ?>
<?php if($status==='RESOLVED'): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="CLOSED"><button class="btn btn-primary" type="submit">Cerrar caso</button></form><form method="post" action="<?= APP_BASE_URL ?>/tickets/status" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><input type="hidden" name="status" value="REOPENED"><button class="btn btn-outline-secondary" type="submit">Reabrir</button></form><?php endif; ?>
<?php endif; ?>
<?php if($canRelease): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/release" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><button class="btn btn-outline-secondary" type="submit">Devolver a la cola</button></form><?php endif; ?>
</div>
<?php if($canReassign&&!empty($supportUsers)): ?><details class="ticket-more-actions"><summary>Asignar a otra persona</summary><form class="ticket-assign-form" method="post" action="<?= APP_BASE_URL ?>/tickets/assign" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><select class="form-control" name="assigned_to" required><option value="">Selecciona responsable</option><?php foreach($supportUsers as $su): ?><option value="<?= (int)$su['id'] ?>" <?= (int)($ticket['assigned_to']??0)===(int)$su['id']?'selected':'' ?>><?= htmlspecialchars($su['full_name']) ?> · <?= htmlspecialchars($su['role_name']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Asignar</button></form></details><?php endif; ?>

<?php if($canChangeStatus && in_array($status,['IN_PROGRESS','PENDING','REOPENED'],true)): ?>
<div class="resolution-capture">
<div class="ticket-actions-head"><div><span class="ticket-kicker">Cerrar el aprendizaje</span><h2>¿Cómo se resolvió?</h2><p>Esta información alimentará los informes y ayudará cuando el problema vuelva a repetirse.</p></div></div>
<form method="post" action="<?= APP_BASE_URL ?>/tickets/resolve" data-single-submit class="resolution-form">
<input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
<div class="resolution-grid">
<label>Tipo de solución<select class="form-control" name="resolution_type" required><option value="">Selecciona</option><option value="CONFIGURATION">Configuración</option><option value="RESTART">Reinicio / restablecimiento</option><option value="REPLACEMENT">Cambio o reemplazo</option><option value="PROVIDER">Gestión con proveedor</option><option value="USER_GUIDANCE">Orientación al usuario</option><option value="SOFTWARE">Software / aplicación</option><option value="NETWORK">Red / conectividad</option><option value="HARDWARE">Hardware / equipo</option><option value="PERMISSION">Acceso / permisos</option><option value="MAINTENANCE">Mantenimiento</option><option value="OTHER">Otro</option></select></label>
<label>Causa encontrada<input class="form-control" name="root_cause" required placeholder="Ej. Configuración incorrecta del DNS"></label>
<label class="resolution-full">Qué se hizo para resolverlo<textarea class="form-control" name="solution_applied" rows="3" required placeholder="Describe los pasos o la acción que solucionó el problema"></textarea></label>
<label class="resolution-full">Qué hacer para evitar que se repita <span class="subtle">(opcional)</span><textarea class="form-control" name="preventive_action" rows="2" placeholder="Recomendación, mantenimiento, cambio de proceso, seguimiento..."></textarea></label>
<label class="resolution-check"><input type="checkbox" name="is_reusable" value="1" checked> Guardar esta solución para sugerirla en casos similares</label>
</div><button class="btn btn-primary" type="submit">Guardar solución y marcar resuelto</button>
</form></div>
<?php endif; ?>
</div></section>
<?php endif; ?>

<?php if($resolution): ?>
<section class="card resolution-summary"><div class="card-body"><div class="ticket-actions-head"><div><span class="ticket-kicker">Resolución registrada</span><h2>Cómo se resolvió este caso</h2></div><?php if(!empty($resolution['resolved_by_name'])):?><small><?= htmlspecialchars($resolution['resolved_by_name']) ?> · <?= htmlspecialchars(date('d/m/Y H:i',strtotime($resolution['updated_at']))) ?></small><?php endif; ?></div>
<?php if($isSupport): ?><div class="resolution-read-grid"><div><span>Tipo</span><strong><?= htmlspecialchars(str_replace('_',' ',ucfirst(strtolower($resolution['resolution_type'])))) ?></strong></div><div><span>Causa encontrada</span><p><?= nl2br(htmlspecialchars($resolution['root_cause']??'No indicada')) ?></p></div><div class="resolution-full"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php if(!empty($resolution['preventive_action'])):?><div class="resolution-full"><span>Prevención / seguimiento</span><p><?= nl2br(htmlspecialchars($resolution['preventive_action'])) ?></p></div><?php endif; ?></div>
<?php else: ?><div class="ticket-description"><span>Solución aplicada</span><p><?= nl2br(htmlspecialchars($resolution['solution_applied'])) ?></p></div><?php endif; ?>
</div></section>
<?php endif; ?>

<div class="ticket-content-grid"><section class="card"><div class="card-body"><h2>Información del caso</h2><div class="ticket-info-list"><div><span>Solicitante</span><strong><?= htmlspecialchars($ticket['requester_name']) ?></strong><small><?= htmlspecialchars($ticket['requester_email']) ?><?= !empty($ticket['requester_phone'])?' · '.htmlspecialchars($ticket['requester_phone']):'' ?></small></div><div><span>Ubicación</span><strong><?= htmlspecialchars($ticket['park_name']??'No especificada') ?></strong><small><?= htmlspecialchars($ticket['area_name']??'Área no especificada') ?></small></div><div><span>Tipo de solicitud</span><strong><?= htmlspecialchars($ticket['category_name']??'No especificado') ?></strong></div></div><div class="ticket-description"><span>Descripción</span><p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p></div></div></section>
<section class="card"><div class="card-body"><h2>Actividad</h2><div class="ticket-timeline"><?php foreach($events as $e): ?><div class="timeline-item"><span class="timeline-dot"></span><div><strong><?= htmlspecialchars($eventLabels[$e['event_type']]??ucfirst(strtolower(str_replace('_',' ',$e['event_type'])))) ?></strong><p><?= htmlspecialchars(date('d/m/Y H:i',strtotime($e['created_at']))) ?><?= !empty($e['actor_name'])?' · '.htmlspecialchars($e['actor_name']):'' ?></p></div></div><?php endforeach; ?><?php if(!$events): ?><div class="empty-state">Aún no hay movimientos registrados.</div><?php endif; ?></div></div></section></div>

<?php if($isSupport && $similar): ?><section class="card similar-solutions"><div class="card-body"><div class="ticket-actions-head"><div><span class="ticket-kicker">Experiencia previa</span><h2>Casos similares que ya se resolvieron</h2><p>Antes de empezar desde cero, revisa qué funcionó anteriormente.</p></div></div><div class="similar-list"><?php foreach($similar as $s): ?><a href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$s['id'] ?>" class="similar-item"><div><strong><?= htmlspecialchars($s['ticket_number'].' · '.$s['subject']) ?></strong><small><?= htmlspecialchars($s['park_name']??'Sin ubicación') ?><?= !empty($s['resolved_at'])?' · '.htmlspecialchars(date('d/m/Y',strtotime($s['resolved_at']))):'' ?></small></div><p><?= htmlspecialchars(mb_strimwidth((string)$s['solution_applied'],0,180,'…')) ?></p></a><?php endforeach; ?></div></div></section><?php endif; ?>
</div>
<style>
.resolution-capture{margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border,#dbe2ea)}.resolution-form{display:grid;gap:1rem}.resolution-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.resolution-grid label{display:grid;gap:.45rem;font-weight:700}.resolution-full{grid-column:1/-1}.resolution-check{display:flex!important;grid-column:1/-1;align-items:center;gap:.6rem;font-weight:600!important}.resolution-check input{width:18px;height:18px}.resolution-read-grid{display:grid;grid-template-columns:1fr 2fr;gap:1rem}.resolution-read-grid>div{padding:.9rem;border:1px solid var(--border,#dbe2ea);border-radius:12px}.resolution-read-grid span{display:block;font-size:.78rem;text-transform:uppercase;font-weight:800;opacity:.7;margin-bottom:.35rem}.resolution-read-grid p{margin:0}.similar-list{display:grid;gap:.75rem}.similar-item{display:grid;grid-template-columns:minmax(260px,.8fr) 1.2fr;gap:1rem;padding:1rem;border:1px solid var(--border,#dbe2ea);border-radius:12px;text-decoration:none;color:inherit}.similar-item:hover{border-color:#1d4ed8}.similar-item small{display:block;margin-top:.25rem;opacity:.7}.similar-item p{margin:0}@media(max-width:800px){.resolution-grid,.resolution-read-grid,.similar-item{grid-template-columns:1fr}.resolution-full{grid-column:auto}}
</style>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>