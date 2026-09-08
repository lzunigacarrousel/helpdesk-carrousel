<?php use App\Core\Csrf; $u=$user??null; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<title>Reportar un problema | Helpdesk Carrousel</title>
<link rel="stylesheet" href="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/css/app.css">
<script>try{const p=localStorage.getItem('carrousel-theme')||'system';const r=p==='system'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;document.documentElement.dataset.theme=r}catch(e){}</script>
</head>
<body>
<div class="brand-strip"></div>
<main class="auth-page public-form-page">
  <div class="auth-shell public-form-shell">
    <section class="auth-card public-form-card">
      <div class="public-form-head">
        <img class="auth-logo public-form-logo" src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/images/logo.png" alt="Carrousel">
        <div class="auth-eyebrow">Soporte Carrousel</div>
        <h1 class="auth-title">Cuéntanos qué pasó</h1>
        <p class="auth-subtitle">Completa estos datos y enviaremos tu solicitud al equipo de soporte. No necesitas iniciar sesión.</p>
      </div>

      <form method="post" action="<?= APP_BASE_URL ?>/crear-ticket" class="public-ticket-form">
        <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">

        <section class="form-step">
          <div class="form-step-title"><span>1</span><div><strong>Tus datos</strong><small>Para identificarte y mantenerte informado.</small></div></div>
          <div class="grid grid-2 public-form-grid">
            <div>
              <label class="form-label" for="name">Nombre completo</label>
              <input class="form-control" id="name" name="name" required maxlength="180" autocomplete="name" placeholder="Ej. Kenny López" value="<?= htmlspecialchars((string)($u['full_name']??'')) ?>">
            </div>
            <div>
              <label class="form-label" for="email">Correo electrónico</label>
              <input class="form-control" id="email" type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="nombre@carrousel.com.gt" value="<?= htmlspecialchars((string)($u['email']??'')) ?>">
              <div class="field-help">Usaremos este correo para enviarte el número y seguimiento de tu solicitud.</div>
            </div>
            <div>
              <label class="form-label" for="phone">Teléfono <span class="optional">Opcional</span></label>
              <input class="form-control" id="phone" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Ej. 5555 5555" value="<?= htmlspecialchars((string)($u['phone']??'')) ?>">
            </div>
          </div>
        </section>

        <section class="form-step">
          <div class="form-step-title"><span>2</span><div><strong>¿Dónde y qué necesitas?</strong><small>Esto nos ayuda a dirigir tu solicitud más rápido.</small></div></div>
          <div class="grid grid-2 public-form-grid">
            <div>
              <label class="form-label" for="category_id">¿Qué tipo de problema tienes?</label>
              <select class="form-control" id="category_id" name="category_id" required>
                <option value="">Selecciona una opción</option>
                <?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label" for="park_id">¿En qué parque ocurrió?</label>
              <select class="form-control" id="park_id" name="park_id">
                <option value="">No aplica / No lo sé</option>
                <?php foreach($parks as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label" for="area_id">Área <span class="optional">Opcional</span></label>
              <select class="form-control" id="area_id" name="area_id">
                <option value="">No aplica / No lo sé</option>
                <?php foreach($areas as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
        </section>

        <section class="form-step">
          <div class="form-step-title"><span>3</span><div><strong>Explícanos el problema</strong><small>No necesitas usar términos técnicos.</small></div></div>
          <label class="form-label" for="subject">Resumen breve</label>
          <input class="form-control" id="subject" name="subject" required maxlength="220" placeholder="Ej. No funciona el internet de cajas">

          <label class="form-label" for="description">¿Qué está pasando?</label>
          <textarea class="form-control public-description" id="description" name="description" rows="6" required placeholder="Cuéntanos qué sucede, desde cuándo y qué intentaste hacer. Mientras más claro, más rápido podremos ayudarte."></textarea>
        </section>

        <div class="public-submit-box">
          <button class="btn btn-primary public-submit" type="submit">Enviar mi solicitud</button>
          <div class="field-help centered">Al enviarla recibirás un número de caso en tu correo.</div>
        </div>
      </form>

      <div class="auth-actions public-form-actions">
        <a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/">← Volver al inicio</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/mis-tickets">Ver mis solicitudes</a>
        <button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-theme-toggle title="Cambiar tema"><span data-theme-icon>◐</span></button>
      </div>
    </section>
  </div>
</main>
<script src="<?= htmlspecialchars(APP_PUBLIC_PATH) ?>/assets/js/app.js"></script>
</body>
</html>