<?php use App\Core\{Auth,Csrf}; $isAdmin=Auth::role()==='ADMIN'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light dark">
<title>Corporación Carrousel | Helpdesk - Inicio</title>
<link rel="icon" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/favicon.ico">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css">
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/layout-fixes.css">
<script>try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body>
<div class="brand-strip"></div>
<div class="app-shell">
<aside class="sidebar" id="app-sidebar">
  <div class="sidebar-brand"><img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel"><div><strong>Helpdesk Carrousel</strong><div class="small">Corporación Carrousel</div></div></div>
  <nav>
    <div class="nav-section">Inicio</div>
    <a class="side-link active" href="<?= APP_BASE_URL ?>/dashboard"><span class="side-icon">⌂</span>Dashboard</a>
    <div class="nav-section">Soporte</div>
    <a class="side-link" href="<?= APP_BASE_URL ?>/tickets"><span class="side-icon">▤</span>Mis tickets</a>
    <?php if(Auth::can('tickets.view_queue')): ?><a class="side-link" href="<?= APP_BASE_URL ?>/tickets?view=queue"><span class="side-icon">◎</span>Cola de soporte</a><?php endif; ?>
    <?php if($isAdmin||Auth::can('users.manage')): ?><div class="nav-section">Administración</div><a class="side-link" href="<?= APP_BASE_URL ?>/admin/users"><span class="side-icon">♟</span>Usuarios</a><?php endif; ?>
  </nav>
</aside>
<div class="main-wrap">
<header class="topbar">
  <div class="topbar-left"><button class="btn btn-outline-secondary sidebar-toggle" data-sidebar-toggle type="button">☰</button><a href="<?= APP_BASE_URL ?>/dashboard" class="topbar-title"><strong>Inicio</strong></a></div>
  <div class="topbar-user"><button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Claro / Oscuro / Sistema"><span data-theme-icon>◐</span></button><a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(PORTAL_URL) ?>">← Portal</a><div class="text-end user-summary"><strong><?= htmlspecialchars($user['full_name']) ?></strong><div class="small subtle"><?= htmlspecialchars($user['role_name']) ?> · <?= htmlspecialchars($user['email']) ?></div></div><form method="post" action="<?= APP_BASE_URL ?>/logout"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-outline-secondary btn-sm">Salir</button></form></div>
</header>
<main class="content">
  <div class="page-heading"><div><h1 class="page-title">Dashboard</h1><p class="page-subtitle">Centro de trabajo de Helpdesk Carrousel.</p></div></div>
  <?php if(!empty($flash)): ?><div class="alert alert-success"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <section class="card hero-card"><div class="card-body"><h2>Hola, <?= htmlspecialchars($user['full_name']) ?></h2><p class="subtle" style="margin:0"><?= $isAdmin?'Tienes control administrativo del Helpdesk Carrousel.':'Tu acceso está limitado por rol y alcance.' ?></p><div class="hero-meta"><span class="badge badge-primary"><?= htmlspecialchars($user['role_name']) ?></span><?php if($user['status']==='PENDING'): ?><span class="badge badge-warning">Pendiente de asignación</span><?php else: ?><span class="badge badge-success">Acceso activo</span><?php endif; ?></div></div></section>

  <div class="grid grid-3">
    <section class="card stat"><span class="stat-label">Organización</span><b style="font-size:21px"><?= htmlspecialchars($assignment['park_name']??($isAdmin?'Administración global':'Sin parque asignado')) ?></b><div class="stat-note"><?= htmlspecialchars($assignment['area_name']??'') ?><?= !empty($assignment['manager_name'])?' · Responsable: '.htmlspecialchars($assignment['manager_name']):'' ?></div></section>
    <section class="card stat"><span class="stat-label">Tickets abiertos</span><b style="font-size:21px"><?= (int)($ticketStats['open_count']??0) ?></b><div class="stat-note">Solicitudes asociadas a tu correo.</div></section>
    <section class="card stat"><span class="stat-label">Histórico</span><b style="font-size:21px"><?= (int)($ticketStats['total']??0) ?></b><div class="stat-note">Resueltos: <?= (int)($ticketStats['resolved_count']??0) ?> · Cerrados: <?= (int)($ticketStats['closed_count']??0) ?></div></section>
  </div>

  <footer class="app-corporate-footer"><div>© <?= date('Y') ?> <strong>Carrousel Guatemala ✨🎠</strong> · Desarrollado por <strong>Luis Fernando Zuniga</strong></div><div class="no-print">Helpdesk Carrousel</div></footer>
</main>
</div>
</div>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script>
</body>
</html>
