<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= htmlspecialchars($errorTitle ?? 'No pudimos completar la acción') ?> | Helpdesk Carrousel</title>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css">
<style>
.friendly-error{min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#15255d 0%,#213a8f 50%,#2d4db1 100%)}
.friendly-error-card{width:min(520px,100%);background:#fff;color:#1f2937;border-radius:20px;padding:34px;text-align:center;box-shadow:0 24px 65px rgba(0,0,0,.28)}
.friendly-error-card img{max-width:145px;max-height:78px;object-fit:contain;margin-bottom:18px}
.friendly-error-icon{width:58px;height:58px;border-radius:50%;display:grid;place-items:center;margin:0 auto 14px;background:#eef4ff;color:#213a8f;font-size:28px;font-weight:800}
.friendly-error-card h1{font-size:28px;line-height:1.2;margin:0 0 10px}.friendly-error-card p{color:#667085;font-size:16px;line-height:1.55;margin:0 auto 22px;max-width:420px}.friendly-error-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.friendly-error-actions .btn{min-width:150px}@media(max-width:600px){.friendly-error{padding:0}.friendly-error-card{min-height:100vh;border-radius:0;display:flex;flex-direction:column;justify-content:center;padding:28px 18px}.friendly-error-actions{display:grid}.friendly-error-actions .btn{width:100%}}
</style>
</head>
<body>
<div class="brand-strip"></div>
<main class="friendly-error">
<section class="friendly-error-card">
<img src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel">
<div class="friendly-error-icon">!</div>
<h1><?= htmlspecialchars($errorTitle ?? 'No pudimos completar la acción') ?></h1>
<p><?= htmlspecialchars($errorMessage ?? 'Intenta nuevamente. Si el inconveniente continúa, comunícate con el equipo de Sistemas.') ?></p>
<div class="friendly-error-actions">
<a class="btn btn-primary" href="<?= APP_BASE_URL ?>/">Ir al inicio</a>
<a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/mis-tickets">Ver mis solicitudes</a>
</div>
</section>
</main>
</body>
</html>
