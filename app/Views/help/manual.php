<?php
$pageTitle='Manual del Helpdesk';$pageSection='Ayuda';$activeNav='manual';$helpContext='manual';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="manual-page">
  <div class="page-heading manual-heading">
    <div><span class="ticket-kicker">Guía de uso</span><h1 class="page-title">Manual del Helpdesk</h1><p class="page-subtitle">Encuentra una tarea, abre una duda concreta o recorre el flujo completo. El contenido cambia según las funciones disponibles para tu perfil.</p></div>
    <button class="btn btn-primary" type="button" data-tour-start>Recorrer esta pantalla</button>
  </div>

  <section aria-label="Accesos rápidos del manual">
    <div class="manual-legend"><span>1. Elige una tarea</span><span>2. Consulta la guía</span><span>3. Vuelve directamente a la función</span></div>
    <div class="manual-quick-grid">
      <?php if($isSupport): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/tickets/queue"><span>Trabajo diario</span><strong>Atender casos</strong><small>Continúa tus casos, revisa disponibles y prioriza por SLA.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/buscar"><span>Encontrar información</span><strong>Buscar una solución</strong><small>Busca tickets, problemas conocidos y artículos desde un solo lugar.</small></a>
        <?php if($canProblems): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/problems"><span>Recurrencias</span><strong>Problemas conocidos</strong><small>Agrupa fallas repetidas, workaround y solución permanente.</small></a><?php endif; ?>
        <?php if($canKnowledge): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/knowledge"><span>Aprendizaje</span><strong>Base de conocimiento</strong><small>Consulta y documenta soluciones que puedan reutilizarse.</small></a><?php endif; ?>
      <?php elseif($isExternal): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Colaboración</span><strong>Ver mis casos</strong><small>Abre los casos asignados a tu cuenta y continúa el seguimiento.</small></a>
        <a class="manual-quick-card" href="#solicitudes"><span>Cómo participar</span><strong>Responder y adjuntar</strong><small>Consulta el flujo correcto para enviar avances o evidencias.</small></a>
      <?php else: ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/crear-ticket"><span>Nueva solicitud</span><strong>Reportar un problema</strong><small>Registra un caso con contexto suficiente sin usar lenguaje técnico.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Ver mis solicitudes</strong><small>Consulta respuestas, archivos, estado y solución.</small></a>
      <?php endif; ?>
      <?php if($canManagement): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion"><span>Consulta</span><strong>Dashboard e informes</strong><small>Analiza volumen, SLA, tiempos, carga y tendencias según tu alcance.</small></a><?php endif; ?>
      <?php if($canAdmin): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/admin/correo"><span>Operación</span><strong>Correo y notificaciones</strong><small>Prueba el canal, revisa entregas y detecta fallos antes de que afecten el seguimiento.</small></a><?php endif; ?>
    </div>
  </section>

  <nav class="manual-index" aria-label="Índice del manual">
    <a href="#inicio">Inicio</a>
    <a href="#solicitudes">Solicitudes</a>
    <?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?>
    <?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?>
    <?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?>
    <?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?>
    <a href="#preguntas">Preguntas frecuentes</a>
  </nav>

  <section class="manual-section" id="inicio">
    <div class="manual-section-head"><span>01</span><div><h2>Inicio y navegación</h2><p>Ubica rápidamente lo que requiere atención y usa el buscador global para encontrar tickets, problemas o artículos.</p></div></div>
    <div class="manual-cards">
      <article><strong>Buscador global</strong><p>Usa la barra superior o <kbd>Ctrl K</kbd>. Los resultados se agrupan por Tickets, Problemas conocidos y Conocimiento según tus permisos.</p></article>
      <article><strong>Menú lateral</strong><p>Las opciones cambian por perfil y alcance. Si una función no corresponde a tu cuenta, no se muestra y tampoco se autoriza por URL directa.</p></article>
      <article><strong>Ayuda flotante</strong><p>El botón <b>?</b> abre una guía de la pantalla actual. Desde allí puedes iniciar un recorrido que señala cada parte directamente sobre la interfaz.</p></article>
    </div>
  </section>

  <section class="manual-section" id="solicitudes">
    <div class="manual-section-head"><span>02</span><div><h2><?= $isExternal?'Casos compartidos':'Solicitudes y seguimiento' ?></h2><p><?= $isExternal?'Trabaja únicamente los casos asignados a tu cuenta.':'Registra el problema con suficiente contexto y sigue las respuestas desde el mismo caso.' ?></p></div></div>
    <div class="manual-flow">
      <?php if($isExternal): ?>
        <div><b>1</b><strong>Abre el caso</strong><span>Lee primero qué apoyo se necesita y el contexto disponible.</span></div><div><b>2</b><strong>Actualiza</strong><span>Responde, pide información o adjunta evidencia desde el seguimiento.</span></div><div><b>3</b><strong>Espera validación</strong><span>El equipo interno conserva el control del estado y cierre.</span></div>
      <?php else: ?>
        <div><b>1</b><strong>Describe qué pasa</strong><span>Indica ubicación, tipo, resumen y hechos concretos.</span></div><div><b>2</b><strong>Da seguimiento</strong><span>Lee respuestas, archivos y cambios relevantes.</span></div><div><b>3</b><strong>Consulta la solución</strong><span>Al resolver, la causa y la solución quedan documentadas cuando aplica.</span></div>
      <?php endif; ?>
    </div>
  </section>

  <?php if($isSupport): ?>
  <section class="manual-section" id="soporte">
    <div class="manual-section-head"><span>03</span><div><h2>Centro de soporte</h2><p>Atiende primero lo urgente, continúa tus casos activos y documenta correctamente pausas, respuestas y solución.</p></div></div>
    <div class="manual-cards">
      <article><strong>Tomar un caso</strong><p>Usa <b>Tomar y atender</b> cuando realmente puedas iniciar trabajo. El ticket pasa a tu responsabilidad.</p></article>
      <article><strong>En espera</strong><p>Selecciona el motivo real: usuario, proveedor, compra, visita u otra dependencia. Agrega detalle cuando ayude a explicar el tiempo.</p></article>
      <article><strong>Respuesta vs nota interna</strong><p><b>Respuesta al usuario</b> es pública. <b>Nota interna</b> solo la ve Soporte. Verifica el modo antes de enviar.</p></article>
      <article><strong>Resolver</strong><p>Registra causa, solución aplicada y prevención. Esa resolución alimenta informes, casos similares y conocimiento.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canProblems||$canKnowledge): ?>
  <section class="manual-section" id="conocimiento">
    <div class="manual-section-head"><span><?= $isSupport?'04':'03' ?></span><div><h2>Problemas y conocimiento</h2><p>Convierte recurrencias y buenas resoluciones en aprendizaje reutilizable para el equipo.</p></div></div>
    <div class="manual-cards">
      <?php if($canProblems): ?><article><strong>Problemas conocidos</strong><p>Relaciona tickets recurrentes, documenta causa raíz, workaround y solución permanente. La recurrencia se actualiza automáticamente.</p><a href="<?= APP_BASE_URL ?>/problems">Abrir Problemas conocidos →</a></article><?php endif; ?>
      <?php if($canKnowledge): ?><article><strong>Base de conocimiento</strong><p>Crea artículos en borrador, revísalos y publícalos únicamente cuando estén listos. Los artículos internos no se exponen a perfiles sin permiso.</p><a href="<?= APP_BASE_URL ?>/knowledge">Abrir Base de conocimiento →</a></article><?php endif; ?>
      <article><strong>Posibles soluciones</strong><p>En el ticket se sugieren artículos, problemas y tickets resueltos similares usando categoría, parque y términos del caso, sin IA externa.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canManagement): ?>
  <section class="manual-section" id="gestion">
    <div class="manual-section-head"><span>05</span><div><h2>Dashboard e informes</h2><p>Usa métricas para priorizar operación y el informe detallado para reconstruir tiempos, estados y resolución.</p></div></div>
    <div class="manual-cards">
      <article><strong>Dashboard interno</strong><p>Analiza volumen, SLA, carga, categorías y evolución dentro del alcance de tu perfil. Usa filtros antes de interpretar los indicadores.</p><a href="<?= APP_BASE_URL ?>/gestion">Abrir Dashboard →</a></article>
      <article><strong>Informes</strong><p>Consulta cada ticket con tiempos, cambios de estado, motivos de espera y aprendizaje documentado. La exportación oficial es Excel (.xlsx).</p><a href="<?= APP_BASE_URL ?>/gestion/informes">Abrir Informes →</a></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canAdmin): ?>
  <section class="manual-section" id="administracion">
    <div class="manual-section-head"><span>06</span><div><h2>Administración</h2><p>Perfil, alcance, equipo de soporte, externos y auditoría se gestionan como conceptos separados para evitar permisos incorrectos.</p></div></div>
    <div class="manual-cards">
      <article><strong>Usuarios y estructura</strong><p>El perfil define qué puede hacer; el alcance define qué puede consultar; atender soporte indica si participa en la operación de tickets.</p></article>
      <article><strong>Proveedores externos</strong><p>Comparte únicamente tickets específicos y revoca el acceso al terminar la colaboración.</p></article>
      <article><strong>Auditoría</strong><p>Consulta quién cambió qué y cuándo. Úsala para trazabilidad, no como actividad diaria del técnico.</p></article>
      <article><strong>Correo y notificaciones</strong><p>Comprueba si el canal está en SMTP o en modo de prueba, envía una prueba real y revisa entregas fallidas o pendientes sin exponer credenciales.</p><a href="<?= APP_BASE_URL ?>/admin/correo">Abrir Correo y notificaciones →</a></article>
    </div>
  </section>
  <?php endif; ?>

  <section class="manual-section" id="preguntas">
    <div class="manual-section-head"><span>?</span><div><h2>Preguntas frecuentes</h2><p>Abre únicamente la respuesta que necesites. Esta sección busca resolver dudas sin obligarte a leer todo el manual.</p></div></div>
    <div class="manual-faq">
      <details><summary>No sé dónde registrar o buscar un caso</summary><div>Para un caso nuevo usa <b>Reportar un problema</b>. Para algo ya registrado usa <b>Mis solicitudes</b> o el buscador global. Si trabajas en Soporte, usa el Centro de soporte para operar tickets.</div></details>
      <details><summary>¿Qué información es realmente importante al reportar?</summary><div>Prioriza hechos: qué sucede, desde cuándo, dónde ocurre y a quién o qué equipo afecta. No necesitas conocer la causa técnica para enviar una buena solicitud.</div></details>
      <?php if($isSupport): ?><details><summary>¿Cuándo debo poner un caso en espera?</summary><div>Solo cuando exista una dependencia real que impida continuar: respuesta del usuario, proveedor, compra, visita u otra causa documentable. Registra el motivo porque afecta la lectura de tiempos e informes.</div></details>
      <details><summary>¿Respuesta al usuario o nota interna?</summary><div>La respuesta al usuario forma parte de la conversación pública. La nota interna es exclusivamente para el equipo de Soporte y debe utilizarse para coordinación o diagnóstico que no corresponde enviar al solicitante.</div></details><?php endif; ?>
      <?php if($canProblems): ?><details><summary>¿Cuándo conviene crear un problema conocido?</summary><div>Cuando varios tickets representan la misma falla o cuando necesitas investigar una causa que trasciende un solo caso. Relaciona las ocurrencias en vez de documentar el mismo problema varias veces.</div></details><?php endif; ?>
      <?php if($canKnowledge): ?><details><summary>¿Cuándo debo crear un artículo?</summary><div>Cuando una solución o procedimiento sea suficientemente claro y reutilizable. Créalo como borrador, elimina información específica del caso y publícalo únicamente después de revisarlo.</div></details><?php endif; ?>
      <?php if($canManagement): ?><details><summary>¿Por qué Gerencia o Supervisión no ven botones para atender?</summary><div>Porque consultar información y atender soporte son responsabilidades distintas. Estos perfiles pueden revisar dashboard, informes y conocimiento dentro de su alcance sin convertirse en técnicos.</div></details><?php endif; ?>
      <?php if($canAdmin): ?><details><summary>¿Cómo sé si un correo realmente salió?</summary><div>Abre <b>Correo y notificaciones</b>. <b>Enviado</b> significa que el servidor SMTP aceptó la entrega; <b>Falló</b> requiere revisión; <b>Pendiente</b> no ha terminado; <b>Modo prueba</b> significa que solo se registró localmente y no salió a Internet.</div></details><?php endif; ?>
    </div>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
