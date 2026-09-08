<?php
$pageTitle='Auditoría';$pageSection='Auditoría';$activeNav='audit';$helpContext='audit';
require APP_ROOT.'/app/Views/shared/app_start.php';
$sourceLabels=['PUBLIC_WEB'=>'Público','AUTHENTICATED_WEB'=>'Autenticado','SYSTEM'=>'Sistema','IMPORT'=>'Importación'];
$actionLabels=[
'USER_REGISTERED'=>'Usuario registrado','OTP_REQUESTED'=>'OTP solicitado','OTP_FAILED'=>'OTP fallido','LOGIN_SUCCESS'=>'Inicio de sesión','LOGOUT'=>'Cierre de sesión',
'TICKET_CREATED_PUBLIC'=>'Ticket creado','TICKET_CLAIMED'=>'Caso tomado','TICKET_ASSIGNED'=>'Caso asignado','TICKET_RELEASED'=>'Caso devuelto','TICKET_STATUS_CHANGED'=>'Estado cambiado','USER_ASSIGNMENT_UPDATED'=>'Asignación actualizada'
];
$pages=max(1,(int)ceil($total/$perPage));
$baseQuery=$_GET;$baseQuery['page']=1;
?>
<div class="audit-page">
<div class="page-heading audit-heading"><div><div class="ticket-kicker">Trazabilidad</div><h1 class="page-title">Auditoría</h1><p class="page-subtitle">Quién hizo qué, cuándo, desde dónde y sobre qué registro.</p></div></div>

<div class="audit-stats">
<section class="card stat"><span class="stat-label">Hoy</span><b><?= (int)$stats['today'] ?></b><div class="stat-note">Eventos registrados.</div></section>
<section class="card stat"><span class="stat-label">Últimos 7 días</span><b><?= (int)$stats['week'] ?></b><div class="stat-note">Actividad total.</div></section>
<section class="card stat"><span class="stat-label">Ingresos</span><b><?= (int)$stats['logins'] ?></b><div class="stat-note">Sesiones exitosas.</div></section>
<section class="card stat"><span class="stat-label">Intentos fallidos</span><b><?= (int)$stats['failures'] ?></b><div class="stat-note">OTP / acceso fallido.</div></section>
</div>

<section class="card audit-filter-card"><div class="card-body"><form method="get" class="audit-filter-grid">
<div><label class="form-label">Buscar</label><input class="form-control" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Correo, acción, entidad, IP..."></div>
<div><label class="form-label">Acción</label><select class="form-control" name="action"><option value="">Todas</option><?php foreach($actions as $a): $v=(string)$a['action']; ?><option value="<?= htmlspecialchars($v) ?>" <?= $filters['action']===$v?'selected':'' ?>><?= htmlspecialchars($actionLabels[$v]??$v) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label">Origen</label><select class="form-control" name="source"><option value="">Todos</option><?php foreach($sourceLabels as $v=>$label): ?><option value="<?= $v ?>" <?= $filters['source']===$v?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label">Desde</label><input class="form-control" type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></div>
<div><label class="form-label">Hasta</label><input class="form-control" type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></div>
<div class="audit-filter-actions"><button class="btn btn-primary" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/admin/audit">Limpiar</a></div>
</form></div></section>

<section class="card audit-log-card"><div class="card-header audit-log-head"><div><strong>Registro de actividad</strong><span><?= number_format($total) ?> evento<?= $total===1?'':'s' ?></span></div></div><div class="audit-log-list">
<?php foreach($logs as $log):
$old=$log['old_values']?json_decode((string)$log['old_values'],true):null;
$new=$log['new_values']?json_decode((string)$log['new_values'],true):null;
$meta=$log['metadata_json']?json_decode((string)$log['metadata_json'],true):null;
?>
<article class="audit-row">
<div class="audit-time"><strong><?= htmlspecialchars(date('d/m/Y',strtotime($log['created_at']))) ?></strong><span><?= htmlspecialchars(date('H:i:s',strtotime($log['created_at']))) ?></span></div>
<div class="audit-event"><strong><?= htmlspecialchars($actionLabels[$log['action']]??$log['action']) ?></strong><span><?= htmlspecialchars($log['entity_type']) ?><?= $log['entity_id']!==null?' #'.htmlspecialchars((string)$log['entity_id']):'' ?></span></div>
<div class="audit-actor"><strong><?= htmlspecialchars($log['actor_email']?:'Sistema / público') ?></strong><span><?= htmlspecialchars($sourceLabels[$log['source']]??$log['source']) ?> · <?= htmlspecialchars($log['ip_address']?:'IP no disponible') ?></span></div>
<div class="audit-detail"><?php if($old||$new||$meta): ?><details><summary>Ver detalle</summary><div class="audit-json"><?php if($old): ?><div><b>Antes</b><pre><?= htmlspecialchars(json_encode($old,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?></pre></div><?php endif; ?><?php if($new): ?><div><b>Después</b><pre><?= htmlspecialchars(json_encode($new,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?></pre></div><?php endif; ?><?php if($meta): ?><div><b>Contexto</b><pre><?= htmlspecialchars(json_encode($meta,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?></pre></div><?php endif; ?></div></details><?php else: ?><span class="muted">Sin detalle adicional</span><?php endif; ?></div>
</article>
<?php endforeach; ?>
<?php if(!$logs): ?><div class="empty-state">No hay eventos que coincidan con los filtros.</div><?php endif; ?>
</div></section>

<?php if($pages>1): ?><nav class="audit-pagination"><span>Página <?= $page ?> de <?= $pages ?></span><div><?php if($page>1): $q=$baseQuery;$q['page']=$page-1; ?><a class="btn btn-outline-secondary btn-sm" href="?<?= htmlspecialchars(http_build_query($q)) ?>">← Anterior</a><?php endif; ?><?php if($page<$pages): $q=$baseQuery;$q['page']=$page+1; ?><a class="btn btn-outline-secondary btn-sm" href="?<?= htmlspecialchars(http_build_query($q)) ?>">Siguiente →</a><?php endif; ?></div></nav><?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
