<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="color-scheme" content="light dark"><title>Solicitud enviada | Helpdesk Carrousel</title><link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css"></head>
<body><div class="brand-strip"></div><main class="auth-page public-result-page"><div class="auth-shell public-result-shell"><section class="auth-card public-result-card">
<img class="auth-logo public-result-logo" src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel">
<div class="result-check" aria-hidden="true">✓</div><div class="auth-eyebrow">Solicitud registrada</div><h1 class="auth-title result-title">Listo, recibimos tu solicitud</h1><p class="result-lead">Guarda este número para identificar tu caso.</p>
<?php if($ticketNumber!==''): ?><div class="ticket-number-box"><span>Número de caso</span><strong><?= htmlspecialchars($ticketNumber) ?></strong></div><?php endif; ?>
<div class="result-next"><strong>¿Qué sigue?</strong><p>Te enviaremos las novedades al correo que registraste. Para revisar el estado cuando quieras, entra en <b>Ver mis solicitudes</b>.</p></div>
<a class="btn btn-primary result-primary" href="<?= APP_BASE_URL ?>/mis-tickets">Ver mis solicitudes</a>
<div class="result-secondary"><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/crear-ticket">Reportar otro problema</a><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/">Ir al inicio</a></div>
</section></div></main></body></html>