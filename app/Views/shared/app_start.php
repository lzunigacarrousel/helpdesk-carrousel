<?php
use App\Core\{Auth,Csrf,Database};
use App\Services\NotificationService;
$isAdmin=Auth::role()==='ADMIN';
$isSemi=Auth::role()==='SEMIADMIN';
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$isSupport=Auth::isSupportOperator();
$canManagement=!$isExternal&&($isAdmin||$isSemi||Auth::can('management.view'));
$canReports=!$isExternal&&($isAdmin||$isSemi||Auth::can('reports.view')||Auth::can('management.view'));
$canSupportTeam=!$isExternal&&($isAdmin||$isSemi||Auth::can('users.manage')||Auth::can('management.view'));
$canExternalManage=!$isExternal&&($isAdmin||$isSemi||Auth::can('external.manage'));
$canProblems=!$isExternal&&Auth::can('problems.view');
$canKnowledge=!$isExternal&&Auth::can('knowledge.view');
$canMailAdmin=!$isExternal&&Auth::can('audit.view');
$activeNav=$activeNav??'home';
$pageSection=$pageSection??'Inicio';
$helpContext=$helpContext??'general';
$searchValue=$activeNav==='search'?trim((string)($_GET['q']??'')):'';
$supportView=$activeNav==='support'?strtolower(trim((string)($_GET['view']??''))):'';
$userContextLabel=Auth::profileLabel();

$attentionItems=[];$attentionCount=0;$recentNotifications=[];$notificationUnread=0;
try{
    $pdo=Database::pdo();$uid=(int)Auth::id();$email=strtolower((string)($user['email']??''));
    if($isExternal){
        $q=$pdo->prepare("SELECT COUNT(*) FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL AND t.status NOT IN('CLOSED','CANCELLED')");$q->execute([$uid]);$count=(int)$q->fetchColumn();if($count>0)$attentionItems[]=['label'=>'Casos activos','detail'=>'Casos asignados a tu cuenta que siguen en seguimiento.','count'=>$count,'href'=>APP_BASE_URL.'/mis-tickets'];
    }elseif($isSupport){
        $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND deleted_at IS NULL AND status IN('IN_PROGRESS','PENDING','REOPENED')");$q->execute([$uid]);$mine=(int)$q->fetchColumn();if($mine>0)$attentionItems[]=['label'=>'Mis casos activos','detail'=>'Casos que están bajo tu responsabilidad.','count'=>$mine,'href'=>APP_BASE_URL.'/tickets/queue?view=mine'];
        $available=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE assigned_to IS NULL AND deleted_at IS NULL AND status IN('NEW','AVAILABLE','REOPENED')")->fetchColumn();if($available>0)$attentionItems[]=['label'=>'Casos disponibles','detail'=>'Solicitudes pendientes de responsable.','count'=>$available,'href'=>APP_BASE_URL.'/tickets/queue?view=available'];
        if($canManagement){$overdue=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')")->fetchColumn();if($overdue>0)$attentionItems[]=['label'=>'SLA vencidos','detail'=>'Casos fuera del tiempo objetivo.','count'=>$overdue,'href'=>APP_BASE_URL.'/tickets/queue?view=overdue'];}
    }elseif(Auth::isManagementViewer()){
        $attentionItems=[];
    }else{
        $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND status NOT IN('CLOSED','CANCELLED') AND (requester_user_id=? OR LOWER(requester_email)=?)");$q->execute([$uid,$email]);$open=(int)$q->fetchColumn();if($open>0)$attentionItems[]=['label'=>'Solicitudes abiertas','detail'=>'Solicitudes que todavía están en seguimiento.','count'=>$open,'href'=>APP_BASE_URL.'/mis-tickets'];
    }
    foreach($attentionItems as $item)$attentionCount+=(int)$item['count'];
}catch(Throwable){$attentionItems=[];$attentionCount=0;}

try{
    $notificationService=new NotificationService();
    $recentNotifications=$notificationService->recentForUser((int)Auth::id(),8);
    $notificationUnread=$notificationService->unreadCount((int)Auth::id());
}catch(Throwable){$recentNotifications=[];$notificationUnread=0;}

$assetVersion='20260911-UXHELP1';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark">
<title><?= htmlspecialchars(($pageTitle??'Helpdesk').' | Helpdesk Carrousel') ?></title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/case-focus.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/content-priority.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/shell-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/management.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/components.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/visual-system.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/dark-refinement.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/help-tour-contrast.css?v=20260912-UXHELP2">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/itsm-classification.css?v=20260912-ITSM21">
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body><div class="brand-strip"></div><div class="app-shell">
<aside class="sidebar" id="app-sidebar" aria-label="Navegación principal"><div class="sidebar-brand"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><strong>Helpdesk</strong><div class="small">Corporación Carrousel</div></div></div><nav>
<div class="nav-section">Inicio</div><a class="side-link <?= $activeNav==='home'?'active':'' ?>" href="<?= APP_BASE_URL ?>/dashboard"><span class="side-icon">⌂</span><span class="side-label">Inicio</span></a><a class="side-link <?= $activeNav==='search'?'active':'' ?>" href="<?= APP_BASE_URL ?>/buscar"><span class="side-icon">⌕</span><span class="side-label">Buscar</span></a>
<?php if($isSupport): ?><div class="nav-section">Soporte</div><a class="side-link <?= $activeNav==='support'&&$supportView===''?'active':'' ?>" href="<?= APP_BASE_URL ?>/tickets/queue"><span class="side-icon">◎</span><span class="side-label">Centro de soporte</span></a><a class="side-link <?= $activeNav==='support'&&$supportView==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/tickets/queue?view=mine"><span class="side-icon">◉</span><span class="side-label">Mis casos</span></a><a class="side-link <?= $activeNav==='support'&&$supportView==='available'?'active':'' ?>" href="<?= APP_BASE_URL ?>/tickets/queue?view=available"><span class="side-icon">○</span><span class="side-label">Disponibles</span></a><div class="nav-section">Personal</div><a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis solicitudes</span></a><?php elseif($isExternal): ?><div class="nav-section">Casos</div><a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis casos</span></a><?php elseif(Auth::isManagementViewer()): ?><?php else: ?><div class="nav-section">Solicitudes</div><a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis solicitudes</span></a><?php endif; ?>
<?php if($canProblems||$canKnowledge): ?><div class="nav-section">Conocimiento</div><?php endif; ?>
<?php if($canProblems): ?><a class="side-link <?= $activeNav==='problems'?'active':'' ?>" href="<?= APP_BASE_URL ?>/problems"><span class="side-icon">◇</span><span class="side-label">Problemas conocidos</span></a><?php endif; ?>
<?php if($canKnowledge): ?><a class="side-link <?= $activeNav==='knowledge'?'active':'' ?>" href="<?= APP_BASE_URL ?>/knowledge"><span class="side-icon">▧</span><span class="side-label">Base de conocimiento</span></a><?php endif; ?>
<?php if($canManagement||$canReports||$canSupportTeam): ?><div class="nav-section">Gestión</div><?php endif; ?><?php if($canManagement): ?><a class="side-link <?= $activeNav==='management'?'active':'' ?>" href="<?= APP_BASE_URL ?>/gestion"><span class="side-icon">◫</span><span class="side-label">Dashboard interno</span></a><?php endif; ?><?php if($canSupportTeam): ?><a class="side-link <?= $activeNav==='support-team'?'active':'' ?>" href="<?= APP_BASE_URL ?>/gestion/equipo"><span class="side-icon">◉</span><span class="side-label">Equipo de soporte</span></a><?php endif; ?><?php if($canReports): ?><a class="side-link <?= $activeNav==='reports'?'active':'' ?>" href="<?= APP_BASE_URL ?>/gestion/informes"><span class="side-icon">▥</span><span class="side-label">Informes</span></a><?php endif; ?>
<?php if($isAdmin||Auth::can('users.manage')||Auth::can('audit.view')||$canExternalManage): ?><div class="nav-section">Administración</div><?php endif; ?><?php if($isAdmin||Auth::can('users.manage')): ?><a class="side-link <?= $activeNav==='users'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/users"><span class="side-icon">♟</span><span class="side-label">Usuarios</span></a><?php endif; ?><?php if($canExternalManage): ?><a class="side-link <?= $activeNav==='externals'||$activeNav==='external-report'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/externos"><span class="side-icon">◇</span><span class="side-label">Proveedores externos</span></a><?php endif; ?><?php if(Auth::can('audit.view')): ?><a class="side-link <?= $activeNav==='audit'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/audit"><span class="side-icon">◉</span><span class="side-label">Auditoría</span></a><?php endif; ?><?php if($canMailAdmin): ?><a class="side-link <?= $activeNav==='mail'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/correo"><span class="side-icon">✉</span><span class="side-label">Correo y notificaciones</span></a><?php endif; ?>
</nav></aside><div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>
<div class="main-wrap"><header class="topbar"><div class="topbar-left"><button class="btn btn-outline-secondary sidebar-toggle" data-sidebar-toggle type="button" aria-controls="app-sidebar" aria-expanded="true" aria-label="Ocultar menú" title="Ocultar menú lateral">☰</button><a href="<?= APP_BASE_URL ?>/dashboard" class="topbar-title"><span class="topbar-section">Helpdesk</span><strong class="topbar-page"><?= htmlspecialchars($pageSection) ?></strong></a></div>
<form class="topbar-search" method="get" action="<?= APP_BASE_URL ?>/buscar" role="search"><span aria-hidden="true">⌕</span><input type="search" name="q" value="<?= htmlspecialchars($searchValue) ?>" placeholder="Buscar ticket, problema, artículo…" autocomplete="off" aria-label="Buscar en Helpdesk"><kbd>Ctrl K</kbd></form>
<div class="topbar-user"><div class="shell-notifications" data-notifications data-notification-read-url="<?= APP_BASE_URL ?>/notifications/read" data-notification-read-all-url="<?= APP_BASE_URL ?>/notifications/read-all" data-notification-csrf="<?= htmlspecialchars(Csrf::token()) ?>" data-notification-base-url="<?= htmlspecialchars(APP_BASE_URL) ?>" data-notification-fallback-url="<?= htmlspecialchars(APP_BASE_URL.'/dashboard') ?>"><button class="btn btn-outline-secondary btn-sm shell-icon-btn" type="button" data-notifications-toggle aria-expanded="false" aria-label="Ver notificaciones" title="Notificaciones">🔔<?php if($notificationUnread>0): ?><span class="shell-notification-badge" data-notification-badge><?= $notificationUnread>99?'99+':(int)$notificationUnread ?></span><?php endif; ?></button><div class="shell-notification-menu" data-notifications-menu hidden><div class="shell-notification-head"><div><strong>Notificaciones</strong><span><?= $notificationUnread>0?(int)$notificationUnread.' sin leer':'Todo revisado' ?></span></div><?php if($notificationUnread>0): ?><button type="button" class="shell-notification-read-all" data-notifications-read-all>Marcar leídas</button><?php endif; ?></div>
<?php if($recentNotifications): ?><div class="shell-notification-list shell-notification-events"><?php foreach($recentNotifications as $notification): ?><a class="shell-notification-item <?= empty($notification['read_at'])?'unread':'' ?>" href="<?= htmlspecialchars((string)($notification['action_url']?:APP_BASE_URL.'/dashboard')) ?>" data-notification-link data-notification-id="<?= (int)$notification['id'] ?>" data-notification-ticket-id="<?= (int)($notification['ticket_id']??0) ?>" data-notification-event-key="<?= htmlspecialchars((string)($notification['event_key']??'')) ?>"><span class="shell-notification-dot"></span><span><strong><?= htmlspecialchars((string)$notification['title']) ?></strong><small><?= htmlspecialchars((string)$notification['message']) ?></small><time><?= htmlspecialchars(date('d/m H:i',strtotime((string)$notification['created_at']))) ?></time></span></a><?php endforeach; ?></div><?php else: ?><div class="shell-notification-empty"><strong>Sin novedades</strong>Los cambios de estado, respuestas, asignaciones y resoluciones aparecerán aquí.</div><?php endif; ?>
<?php if($attentionItems): ?><div class="shell-attention-section"><div class="shell-attention-title">Pendientes <span><?= (int)$attentionCount ?></span></div><div class="shell-notification-list"><?php foreach($attentionItems as $item): ?><a class="shell-notification-item attention" href="<?= htmlspecialchars($item['href']) ?>"><span class="shell-notification-dot"></span><span><strong><?= htmlspecialchars($item['label']) ?></strong><small><?= htmlspecialchars($item['detail']) ?></small></span><span class="shell-notification-count"><?= (int)$item['count'] ?></span></a><?php endforeach; ?></div></div><?php endif; ?></div></div><button class="btn btn-outline-secondary btn-sm theme-btn shell-icon-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button><?php if(!$isExternal): ?><a class="btn btn-outline-secondary btn-sm portal-btn" href="<?= htmlspecialchars(PORTAL_URL) ?>" title="Volver al Portal">← Portal</a><?php endif; ?><div class="text-end user-summary"><strong><?= htmlspecialchars($user['full_name']) ?></strong><div class="small subtle"><?= htmlspecialchars($userContextLabel) ?> · <?= htmlspecialchars($user['email']) ?></div></div><form method="post" action="<?= APP_BASE_URL ?>/logout" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-outline-secondary btn-sm logout-btn" type="submit">Salir</button></form></div></header><main class="content">