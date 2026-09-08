<?php
use App\Core\{Auth,Csrf};
$isAdmin=Auth::role()==='ADMIN';
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$isSupport=!$isExternal&&(Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.view_all'));
$helpContext=$isSupport?'support_center':($isExternal?'my_tickets':'requester_home');
$statusLabels=['NEW'=>'Nuevo','AVAILABLE'=>'Por atender','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><title>Helpdesk Carrousel | Inicio</title><link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css"><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css"><script>try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script></head>
<body><div class="brand-strip"></div><div class="app-shell">
<aside class="sidebar" id="app-sidebar"><div class="sidebar-brand"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><strong>Helpdesk Carrousel</strong><div class="small">Corporación Carrousel</div></div></div><nav>
<div class="nav-section">Inicio</div><a class="side-link active" href="<?= APP_BASE_URL ?>/dashboard"><span class="side-icon">⌂</span>Inicio</a>
<?php if($isSupport): ?>
<div class="nav-section">Trabajo</div><a class="side-link" href="<?= APP_BASE_URL ?>/tickets/queue"><span class="side-icon">◎</span>Centro de soporte</a>
<div class="nav-section">Personal</div><a class="side-link" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span>Mis solicitudes</a>
<?php elseif($isExternal): ?>
<div class="nav-section">Casos</div><a class="side-link" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span>Mis casos asignados</a>
<?php else: ?>
<div class="nav-section">Solicitudes</div><a class="side-link" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span>Mis solicitudes</a>
<?php endif; ?>
<?php if($isAdmin||Auth::can('users.manage')): ?><div class="nav-section">Administración</div><a class="side-link" href="<?= APP_BASE_URL ?>/admin/users"><span class="side-icon">♟</span>Usuarios</a><?php endif; ?>
</nav></aside>
<div class="main-wrap"><header class="topbar"><div class="topbar-left"><button class="btn btn-outline-secondary sidebar-toggle" data-sidebar-toggle type="button">☰</button><a href="<?= APP_BASE_URL ?>/dashboard" class="topbar-title"><strong>Inicio</strong></a></div><div class="topbar-user"><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Claro / Oscuro / Sistema"><span data-theme-icon>◐</span></button><a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(PORTAL_URL) ?>">← Portal</a><div class="text-end user-summary"><strong><?= htmlspecialchars($user['full_name']) ?></strong><div class="small subtle"><?= htmlspecialchars($user['role_name']) ?> · <?= htmlspecialchars($user['email']) ?></div></div><form method="post" action="<?= APP_BASE_URL ?>/logout" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-outline-secondary btn-sm">Salir</button></form></div></header>
<main class="content">
<div class="dashboard-primary-row"><div class="page-heading"><div><h1 class="page-title"><?= $isSupport?'Centro de trabajo':($isExternal?'Mis casos asignados':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isSupport?'Tus casos activos y los pendientes por atender.':($isExternal?'Solo verás los casos especiales asignados a tu cuenta.':'Consulta el estado de tus solicitudes o registra una nueva.') ?></p></div></div><?php if($isSupport): ?><a class="btn btn-primary dashboard-primary-button" href="<?= APP_BASE_URL ?>/tickets/queue">Abrir centro de soporte</a><?php elseif(!$isExternal): ?><a class="btn btn-primary dashboard-primary-button" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a><?php endif; ?></div>
<?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<div class="dashboard-overview">
<section class="card dashboard-welcome"><h2>Hola, <?= htmlspecialchars($user['full_name']) ?></h2><p><?= $isSupport?'Tu perfil está listo para gestionar soporte.':($isExternal?'Tu acceso está limitado a los casos autorizados.':'Tu cuenta está lista para dar seguimiento a tus solicitudes.') ?></p><div class="hero-meta"><span class="badge badge-primary"><?= htmlspecialchars($user['role_name']) ?></span><?php if($user['status']==='PENDING'): ?><span class="badge badge-warning">Pendiente de asignación</span><?php else: ?><span class="badge badge-success">Acceso activo</span><?php endif; ?></div><?php if(!$isExternal&&!empty($assignment['park_name'])): ?><div class="stat-note" style="margin-top:12px"><?= htmlspecialchars($assignment['park_name']) ?><?= !empty($assignment['area_name'])?' · '.htmlspecialchars($assignment['area_name']):'' ?></div><?php endif; ?></section>
<?php if($isSupport): ?>
<section class="card stat"><span class="stat-label">Mis casos activos</span><b><?= (int)($supportStats['mine']??0) ?></b><div class="stat-note">Casos que ya estás atendiendo.</div></section><section class="card stat"><span class="stat-label">Por atender</span><b><?= (int)($supportStats['available']??0) ?></b><div class="stat-note">Casos disponibles en la cola.</div></section>
<?php else: ?>
<section class="card stat"><span class="stat-label">Abiertas</span><b><?= (int)($ticketStats['open_count']??0) ?></b><div class="stat-note">Solicitudes que aún requieren seguimiento.</div></section><section class="card stat"><span class="stat-label">Total</span><b><?= (int)($ticketStats['total']??0) ?></b><div class="stat-note">Historial asociado a tu acceso.</div></section>
<?php endif; ?>
</div>
<?php if(!$isSupport): ?><section class="card dashboard-recent"><div class="dashboard-recent-head"><h2><?= $isExternal?'Casos recientes':'Solicitudes recientes' ?></h2><a href="<?= APP_BASE_URL ?>/mis-tickets">Ver todas</a></div><div class="dashboard-recent-list"><?php foreach(($recentTickets??[]) as $t): ?><a class="dashboard-recent-item" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>"><div><strong><?= htmlspecialchars($t['subject']) ?></strong><span><?= htmlspecialchars($t['ticket_number']) ?> · <?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span></div><div class="dashboard-recent-status"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?> →</div></a><?php endforeach; ?><?php if(empty($recentTickets)): ?><div class="empty-state"><?= $isExternal?'No tienes casos asignados por el momento.':'Todavía no tienes solicitudes registradas.' ?></div><?php endif; ?></div></section><?php endif; ?>
<footer class="app-corporate-footer"><div>© <?= date('Y') ?> <strong>Carrousel Guatemala ✨🎠</strong> · Desarrollado por <strong>Luis Fernando Zuniga</strong></div><div class="no-print">Helpdesk Carrousel</div></footer>
</main></div></div>
<?php require APP_ROOT.'/app/Views/shared/help_widget.php'; ?>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script></body></html>