<?php
use App\Core\{Auth,Csrf,Database};
$isAdmin=Auth::role()==='ADMIN';
$isSemi=Auth::role()==='SEMIADMIN';
$isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
$isSupport=!$isExternal&&(Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.view_all'));
$canManagement=!$isExternal&&($isAdmin||$isSemi||Auth::can('management.view'));
$canExternalManage=!$isExternal&&($isAdmin||$isSemi||Auth::can('external.manage'));
$activeNav=$activeNav??'home';
$pageSection=$pageSection??'Inicio';
$helpContext=$helpContext??'general';

/* Atención rápida en topbar. Resume trabajo pendiente real. */
$attentionItems=[];
$attentionCount=0;
try{
    $pdo=Database::pdo();
    $uid=(int)Auth::id();
    $email=strtolower((string)($user['email']??''));

    if($isExternal){
        $q=$pdo->prepare("SELECT COUNT(*) FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL AND t.status NOT IN('CLOSED','CANCELLED')");
        $q->execute([$uid]);
        $count=(int)$q->fetchColumn();
        if($count>0)$attentionItems[]=['label'=>'Casos compartidos contigo','detail'=>'Revisa los casos que Carrousel necesita que atiendas.','count'=>$count,'href'=>APP_BASE_URL.'/mis-tickets'];
    }elseif($isSupport){
        $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to=? AND deleted_at IS NULL AND status IN('IN_PROGRESS','PENDING','REOPENED')");
        $q->execute([$uid]);
        $mine=(int)$q->fetchColumn();
        if($mine>0)$attentionItems[]=['label'=>'Mis casos activos','detail'=>'Casos que ya están bajo tu responsabilidad.','count'=>$mine,'href'=>APP_BASE_URL.'/tickets/queue'];

        $available=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE assigned_to IS NULL AND deleted_at IS NULL AND status IN('NEW','AVAILABLE','REOPENED')")->fetchColumn();
        if($available>0)$attentionItems[]=['label'=>'Casos por atender','detail'=>'Solicitudes disponibles en la cola de soporte.','count'=>$available,'href'=>APP_BASE_URL.'/tickets/queue'];

        if($canManagement){
            $overdue=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')")->fetchColumn();
            if($overdue>0)$attentionItems[]=['label'=>'Casos fuera de tiempo','detail'=>'Solicitudes que requieren revisión prioritaria.','count'=>$overdue,'href'=>APP_BASE_URL.'/gestion'];
        }
    }else{
        $q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND status NOT IN('CLOSED','CANCELLED') AND (requester_user_id=? OR LOWER(requester_email)=?)");
        $q->execute([$uid,$email]);
        $open=(int)$q->fetchColumn();
        if($open>0)$attentionItems[]=['label'=>'Solicitudes abiertas','detail'=>'Tienes solicitudes que todavía están en seguimiento.','count'=>$open,'href'=>APP_BASE_URL.'/mis-tickets'];
    }
    foreach($attentionItems as $item)$attentionCount+=(int)$item['count'];
}catch(Throwable){
    $attentionItems=[];$attentionCount=0;
}
$assetVersion='20260908-1745';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<title><?= htmlspecialchars(($pageTitle??'Helpdesk Carrousel').' | Helpdesk Carrousel') ?></title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ux-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/ui-refresh.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/shell-v2.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/management.css?v=<?= $assetVersion ?>">
<script>try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body>
<div class="brand-strip"></div>
<div class="app-shell">
<aside class="sidebar" id="app-sidebar" aria-label="Navegación principal">
  <div class="sidebar-brand"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><strong>Helpdesk Carrousel</strong><div class="small">Corporación Carrousel</div></div></div>
  <nav>
    <div class="nav-section">Inicio</div>
    <a class="side-link <?= $activeNav==='home'?'active':'' ?>" href="<?= APP_BASE_URL ?>/dashboard"><span class="side-icon">⌂</span><span class="side-label">Inicio</span></a>

    <?php if($isSupport): ?>
      <div class="nav-section">Trabajo</div>
      <a class="side-link <?= $activeNav==='support'?'active':'' ?>" href="<?= APP_BASE_URL ?>/tickets/queue"><span class="side-icon">◎</span><span class="side-label">Centro de soporte</span></a>
      <div class="nav-section">Personal</div>
      <a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis solicitudes</span></a>
    <?php elseif($isExternal): ?>
      <div class="nav-section">Casos</div>
      <a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis casos</span></a>
    <?php else: ?>
      <div class="nav-section">Solicitudes</div>
      <a class="side-link <?= $activeNav==='mine'?'active':'' ?>" href="<?= APP_BASE_URL ?>/mis-tickets"><span class="side-icon">▤</span><span class="side-label">Mis solicitudes</span></a>
    <?php endif; ?>

    <?php if($canManagement): ?>
      <div class="nav-section">Gestión</div>
      <a class="side-link <?= $activeNav==='management'?'active':'' ?>" href="<?= APP_BASE_URL ?>/gestion"><span class="side-icon">◫</span><span class="side-label">Dashboard interno</span></a>
      <a class="side-link <?= $activeNav==='reports'?'active':'' ?>" href="<?= APP_BASE_URL ?>/gestion/informes"><span class="side-icon">▥</span><span class="side-label">Informes</span></a>
    <?php endif; ?>

    <?php if($isAdmin||Auth::can('users.manage')||Auth::can('audit.view')||$canExternalManage): ?><div class="nav-section">Administración</div><?php endif; ?>
    <?php if($isAdmin||Auth::can('users.manage')): ?><a class="side-link <?= $activeNav==='users'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/users"><span class="side-icon">♟</span><span class="side-label">Usuarios</span></a><?php endif; ?>
    <?php if($canExternalManage): ?><a class="side-link <?= $activeNav==='externals'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/externos"><span class="side-icon">◇</span><span class="side-label">Proveedores externos</span></a><?php endif; ?>
    <?php if(Auth::can('audit.view')): ?><a class="side-link <?= $activeNav==='audit'?'active':'' ?>" href="<?= APP_BASE_URL ?>/admin/audit"><span class="side-icon">◉</span><span class="side-label">Auditoría</span></a><?php endif; ?>

    <div class="nav-section sidebar-help">Ayuda</div>
    <button class="side-link side-link-button" type="button" data-help-open><span class="side-icon">?</span><span class="side-label">Ayuda de esta pantalla</span></button>
  </nav>
</aside>
<div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>

<div class="main-wrap">
<header class="topbar">
  <div class="topbar-left">
    <button class="btn btn-outline-secondary sidebar-toggle" data-sidebar-toggle type="button" aria-controls="app-sidebar" aria-expanded="true" aria-label="Ocultar menú" title="Ocultar menú lateral">☰</button>
    <a href="<?= APP_BASE_URL ?>/dashboard" class="topbar-title">
      <span class="topbar-section">Helpdesk Carrousel</span>
      <strong class="topbar-page"><?= htmlspecialchars($pageSection) ?></strong>
    </a>
  </div>

  <div class="topbar-user">
    <div class="shell-notifications" data-notifications>
      <button class="btn btn-outline-secondary btn-sm shell-icon-btn" type="button" data-notifications-toggle aria-expanded="false" aria-label="Ver atención pendiente" title="Atención pendiente">🔔<?php if($attentionCount>0): ?><span class="shell-notification-badge"><?= $attentionCount>99?'99+':(int)$attentionCount ?></span><?php endif; ?></button>
      <div class="shell-notification-menu" data-notifications-menu hidden>
        <div class="shell-notification-head"><strong>Atención pendiente</strong><span><?= $attentionCount>0?(int)$attentionCount.' elemento(s)':'Sin pendientes' ?></span></div>
        <?php if($attentionItems): ?><div class="shell-notification-list">
          <?php foreach($attentionItems as $item): ?><a class="shell-notification-item" href="<?= htmlspecialchars($item['href']) ?>"><span class="shell-notification-dot"></span><span><strong><?= htmlspecialchars($item['label']) ?></strong><small><?= htmlspecialchars($item['detail']) ?></small></span><span class="shell-notification-count"><?= (int)$item['count'] ?></span></a><?php endforeach; ?>
        </div><?php else: ?><div class="shell-notification-empty"><strong>Todo al día</strong>No tienes elementos pendientes en este momento.</div><?php endif; ?>
      </div>
    </div>

    <button class="btn btn-outline-secondary btn-sm shell-icon-btn shell-help-btn" type="button" data-help-open title="Ayuda de esta pantalla" aria-label="Ayuda de esta pantalla">?</button>
    <button class="btn btn-outline-secondary btn-sm theme-btn shell-icon-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia"><span data-theme-icon>◐</span></button>
    <a class="btn btn-outline-secondary btn-sm portal-btn" href="<?= htmlspecialchars(PORTAL_URL) ?>" title="Volver al Portal">← Portal</a>
    <div class="text-end user-summary"><strong><?= htmlspecialchars($user['full_name']) ?></strong><div class="small subtle"><?= htmlspecialchars($user['role_name']) ?> · <?= htmlspecialchars($user['email']) ?></div></div>
    <form method="post" action="<?= APP_BASE_URL ?>/logout" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-outline-secondary btn-sm logout-btn" type="submit">Salir</button></form>
  </div>
</header>
<main class="content">
