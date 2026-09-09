<?php
$pageTitle='Manual';$pageSection='Ayuda';$activeNav='manual';$helpContext='manual';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="manual-page">
  <div class="page-heading manual-heading">
    <div><span class="ticket-kicker">Guía de uso</span><h1 class="page-title">Manual</h1><p class="page-subtitle">Busca una tarea o duda. Solo verás instrucciones que correspondan a tu perfil.</p></div>
    <button class="btn btn-primary" type="button" data-tour-start>Ver recorrido guiado</button>
  </div>

  <div class="manual-search" role="search"><input class="form-control" type="search" placeholder="Buscar: resolver, proveedor, espera, informe…" data-manual-search aria-label="Buscar en el manual"><span class="manual-search-status" data-manual-search-status>Escribe para filtrar la guía</span></div>

  <section aria-label="Accesos rápidos del manual">
    <div class="manual-quick-grid" data-manual-quick>
      <?php if($isSupport): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/tickets/queue"><span>Trabajo diario</span><strong>Atender casos</strong><small>Continúa tus casos, revisa disponibles y prioriza lo urgente.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/buscar"><span>Encontrar información</span><strong>Buscar una solución</strong><small>Busca tickets, problemas conocidos y artículos desde un solo lugar.</small></a>
        <?php if($canProblems): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/problems"><span>Recurrencias</span><strong>Problemas conocidos</strong><small>Agrupa fallas repetidas y documenta qué hacer mientras se resuelven.</small></a><?php endif; ?>
        <?php if($canKnowledge): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/knowledge"><span>Aprendizaje</span><strong>Base de conocimiento</strong><small>Consulta y documenta soluciones que puedan reutilizarse.</small></a><?php endif; ?>
      <?php elseif($isExternal): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Ver mis casos</strong><small>Abre los casos asignados a tu cuenta y continúa la conversación.</small></a>
        <a class="manual-quick-card" href="#solicitudes"><span>Participación</span><strong>Responder y adjuntar</strong><small>Envía avances, consultas o evidencia desde el caso.</small></a>
      <?php else: ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/crear-ticket"><span>Nueva solicitud</span><strong>Reportar un problema</strong><small>Describe qué ocurre sin necesidad de conocer la causa técnica.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Ver mis solicitudes</strong><small>Consulta respuestas, archivos, estado y solución.</small></a>
      <?php endif; ?>
      <?php if($canManagement): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion"><span>Consulta</span><strong>Dashboard e informes</strong><small>Analiza volumen, tiempos, carga y tendencias según tu alcance.</small></a><?php endif; ?>
      <?php if($canAdmin): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/admin/correo"><span>Operación</span><strong>Correo y notificaciones</strong><small>Prueba el canal y revisa entregas o fallos.</small></a><?php endif; ?>
    </div>
  </section>

  <nav class="manual-index" aria-label="Índice del manual">
    <a href="#inicio">Inicio</a><a href="#solicitudes">Solicitudes</a><?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?><?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?><?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?><?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?><a href="#preguntas">Preguntas frecuentes</a>
  </nav>

  <section class="manual-section" id="inicio" data-manual-section>
    <div class="manual-section-head"><span>01</span><div><h2>Inicio y navegación</h2><p>Ubica rápidamente lo que requiere atención y usa el buscador global cuando necesites encontrar un caso o una solución.</p></div></div>
    <div class="manual-cards"><article><strong>Buscador global</strong><p>Usa la barra superior o <kbd>Ctrl K</kbd>. Los resultados se agrupan según el tipo de información y tus permisos.</p></article><article><strong>Menú lateral</strong><p>Las opciones cambian según tu perfil. Si una función no corresponde a tu cuenta, no aparece.</p></article><article><strong>Ayuda flotante</strong><p>El botón <b>?</b> abre ayuda de la pantalla y permite iniciar un tutorial guiado.</p></article></div>
  </section>

  <section class="manual-section" id="solicitudes" data-manual-section>
    <div class="manual-section-head"><span>02</span><div><h2><?= $isExternal?'Casos compartidos':'Solicitudes y seguimiento' ?></h2><p><?= $isExternal?'Trabaja únicamente los casos asignados a tu cuenta.':'Describe el problema una sola vez y sigue las respuestas desde el mismo caso.' ?></p></div></div>
    <div class="manual-flow">
      <?php if($isExternal): ?><div><b>1</b><strong>Abre el caso</strong><span>Lee primero qué apoyo se necesita.</span></div><div><b>2</b><strong>Actualiza</strong><span>Responde, pide información o adjunta evidencia.</span></div><div><b>3</b><strong>Espera validación</strong><span>El equipo interno continúa la gestión y cierre.</span></div>
      <?php else: ?><div><b>1</b><strong>Cuéntanos qué pasa</strong><span>Indica ubicación cuando la conozcas y describe los hechos importantes.</span></div><div><b>2</b><strong>Da seguimiento</strong><span>Lee respuestas, archivos y cambios relevantes.</span></div><div><b>3</b><strong>Consulta la solución</strong><span>Cuando se resuelva, el resultado quedará disponible en el caso.</span></div><?php endif; ?>
    </div>
  </section>

  <?php if($isSupport): ?><section class="manual-section" id="soporte" data-manual-section><div class="manual-section-head"><span>03</span><div><h2>Centro de soporte</h2><p>Continúa tus casos, coordina con el equipo y documenta correctamente pausas, respuestas y solución.</p></div></div><div class="manual-cards"><article><strong>Tomar un caso</strong><p>Usa <b>Tomar y atender</b> cuando realmente puedas iniciar trabajo.</p></article><article><strong>En espera</strong><p>Selecciona la razón real: usuario, proveedor, compra, visita u otra dependencia. El detalle ayuda a explicar el tiempo.</p></article><article><strong>Respuesta y conversación interna</strong><p><b>Respuesta al usuario</b> es visible para el solicitante. <b>Conversación interna</b> es solo para el equipo de soporte.</p></article><article><strong>Resolver</strong><p>Registra qué encontraste, qué hiciste y cómo evitar que vuelva a ocurrir.</p></article></div></section><?php endif; ?>

  <?php if($canProblems||$canKnowledge): ?><section class="manual-section" id="conocimiento" data-manual-section><div class="manual-section-head"><span><?= $isSupport?'04':'03' ?></span><div><h2>Problemas y conocimiento</h2><p>Convierte recurrencias y buenas resoluciones en información útil para futuros casos.</p></div></div><div class="manual-cards"><?php if($canProblems): ?><article><strong>Problemas conocidos</strong><p>Relaciona tickets recurrentes y documenta causa, solución temporal y solución permanente.</p><a href="<?= APP_BASE_URL ?>/problems">Abrir problemas →</a></article><?php endif; ?><?php if($canKnowledge): ?><article><strong>Base de conocimiento</strong><p>Crea artículos en borrador, revísalos y publícalos cuando estén listos.</p><a href="<?= APP_BASE_URL ?>/knowledge">Abrir conocimiento →</a></article><?php endif; ?><article><strong>Posibles soluciones</strong><p>El ticket puede sugerir conocimiento, problemas conocidos y casos anteriores para evitar empezar desde cero.</p></article></div></section><?php endif; ?>

  <?php if($canManagement): ?><section class="manual-section" id="gestion" data-manual-section><div class="manual-section-head"><span>05</span><div><h2>Dashboard e informes</h2><p>Usa métricas para entender qué está pasando y los informes para explicar el detalle.</p></div></div><div class="manual-cards"><article><strong>Dashboard</strong><p>Analiza volumen, tiempos, carga y tendencias dentro de tu alcance.</p><a href="<?= APP_BASE_URL ?>/gestion">Abrir Dashboard →</a></article><article><strong>Informes</strong><p>Consulta tiempos, cambios de estado, esperas y solución. La exportación oficial es Excel (.xlsx).</p><a href="<?= APP_BASE_URL ?>/gestion/informes">Abrir Informes →</a></article></div></section><?php endif; ?>

  <?php if($canAdmin): ?><section class="manual-section" id="administracion" data-manual-section><div class="manual-section-head"><span>06</span><div><h2>Administración</h2><p>Gestiona perfiles, alcance, proveedores, correo y trazabilidad sin exponer detalles técnicos al usuario final.</p></div></div><div class="manual-cards"><article><strong>Usuarios</strong><p>El perfil define qué puede hacer; el alcance define qué información puede consultar.</p></article><article><strong>Proveedores</strong><p>Comparte únicamente casos específicos y retira el acceso cuando termine la participación.</p></article><article><strong>Auditoría</strong><p>Consulta quién cambió qué y cuándo cuando necesites reconstruir una acción.</p></article><article><strong>Correo y notificaciones</strong><p>Comprueba el canal, envía una prueba y revisa entregas fallidas o pendientes.</p><a href="<?= APP_BASE_URL ?>/admin/correo">Abrir correo →</a></article></div></section><?php endif; ?>

  <section class="manual-section" id="preguntas" data-manual-section><div class="manual-section-head"><span>?</span><div><h2>Preguntas frecuentes</h2><p>Abre únicamente la respuesta que necesites.</p></div></div><div class="manual-faq">
    <details><summary>No sé dónde registrar o buscar un caso</summary><div>Para un caso nuevo usa <b>Reportar un problema</b>. Para algo ya registrado usa <b>Mis solicitudes</b> o el buscador global. Si trabajas en soporte, usa el Centro de soporte.</div></details>
    <details><summary>¿Qué información es importante al reportar?</summary><div>Qué sucede, desde cuándo, dónde ocurre y a quién o qué equipo afecta. No necesitas conocer la causa técnica.</div></details>
    <?php if($isSupport): ?><details><summary>¿Cuándo debo poner un caso en espera?</summary><div>Solo cuando exista una dependencia real que impida continuar: respuesta del usuario, proveedor, compra, visita u otra causa documentable.</div></details><details><summary>¿Respuesta al usuario o conversación interna?</summary><div>La respuesta al usuario forma parte del seguimiento visible. La conversación interna es exclusivamente para el equipo de soporte.</div></details><?php endif; ?>
    <?php if($canProblems): ?><details><summary>¿Cuándo conviene crear un problema conocido?</summary><div>Cuando varios tickets representan la misma falla o necesitas investigar una causa que trasciende un solo caso.</div></details><?php endif; ?>
    <?php if($canKnowledge): ?><details><summary>¿Cuándo debo crear un artículo?</summary><div>Cuando una solución o procedimiento sea claro y reutilizable. Créalo como borrador y publícalo después de revisarlo.</div></details><?php endif; ?>
    <?php if($canManagement): ?><details><summary>¿Por qué Gerencia o Supervisión no ven botones para atender?</summary><div>Porque consultar información y atender soporte son responsabilidades distintas.</div></details><?php endif; ?>
    <?php if($canAdmin): ?><details><summary>¿Cómo sé si un correo realmente salió?</summary><div>Abre <b>Correo y notificaciones</b>. Enviado significa que SMTP aceptó la entrega; Falló requiere revisión; Pendiente no ha terminado; Modo prueba no salió a Internet.</div></details><?php endif; ?>
  </div></section>
</div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const input=document.querySelector('[data-manual-search]');
  const status=document.querySelector('[data-manual-search-status]');
  if(!input)return;
  const normalize=v=>(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
  const run=()=>{
    const q=normalize(input.value);let visible=0;
    document.querySelectorAll('.manual-quick-card').forEach(el=>{const ok=!q||normalize(el.textContent).includes(q);el.classList.toggle('is-hidden',!ok);});
    document.querySelectorAll('[data-manual-section]').forEach(section=>{const faq=section.querySelectorAll('details');if(faq.length){let faqVisible=0;faq.forEach(el=>{const ok=!q||normalize(el.textContent).includes(q);el.classList.toggle('is-hidden',!ok);if(ok)faqVisible++;});const own=normalize(section.querySelector('.manual-section-head')?.textContent||'').includes(q);const ok=!q||own||faqVisible>0;section.classList.toggle('is-hidden',!ok);if(ok)visible++;}else{const ok=!q||normalize(section.textContent).includes(q);section.classList.toggle('is-hidden',!ok);if(ok)visible++;}});
    if(status)status.textContent=q?(visible?visible+' sección(es) con resultados':'No encontramos coincidencias'):'Escribe para filtrar la guía';
  };
  input.addEventListener('input',run);
})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
