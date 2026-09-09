<?php
$pageTitle='Manual del Helpdesk';$pageSection='Ayuda';$activeNav='manual';$helpContext='manual';
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="manual-page">
  <div class="page-heading manual-heading">
    <div><span class="ticket-kicker">Guía de uso</span><h1 class="page-title">Manual de Helpdesk Carrousel</h1><p class="page-subtitle">Consulta el flujo correcto según tu perfil. El manual muestra únicamente funciones que tu cuenta puede utilizar.</p></div>
    <button class="btn btn-primary" type="button" data-tour-start>Iniciar tutorial de esta pantalla</button>
  </div>

  <nav class="manual-index" aria-label="Índice del manual">
    <a href="#inicio">Inicio</a>
    <a href="#solicitudes">Solicitudes</a>
    <?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?>
    <?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?>
    <?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?>
    <?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?>
  </nav>

  <section class="manual-section" id="inicio">
    <div class="manual-section-head"><span>01</span><div><h2>Inicio y navegación</h2><p>Ubica rápidamente lo que requiere atención y usa el buscador global para encontrar tickets, problemas o artículos.</p></div></div>
    <div class="manual-cards">
      <article><strong>Buscador global</strong><p>Usa la barra superior o <kbd>Ctrl K</kbd>. Los resultados se agrupan por Tickets, Problemas conocidos y Conocimiento según tus permisos.</p></article>
      <article><strong>Menú lateral</strong><p>Las opciones cambian por rol. Si una función no corresponde a tu perfil, no se muestra y tampoco se autoriza por URL directa.</p></article>
      <article><strong>Ayuda flotante</strong><p>El botón <b>?</b> abre una guía de la pantalla actual. Desde allí puedes iniciar el tutorial guiado o abrir este manual.</p></article>
    </div>
  </section>

  <section class="manual-section" id="solicitudes">
    <div class="manual-section-head"><span>02</span><div><h2><?= $isExternal?'Casos compartidos':'Solicitudes y seguimiento' ?></h2><p><?= $isExternal?'Trabaja únicamente los casos que Carrousel comparte contigo.':'Registra el problema con suficiente contexto y sigue las respuestas desde el mismo caso.' ?></p></div></div>
    <div class="manual-flow">
      <?php if($isExternal): ?>
        <div><b>1</b><strong>Abre el caso</strong><span>Revisa qué necesita Carrousel.</span></div><div><b>2</b><strong>Actualiza</strong><span>Responde, pide información o adjunta evidencia.</span></div><div><b>3</b><strong>Espera validación</strong><span>Sistemas conserva el control del estado y cierre.</span></div>
      <?php else: ?>
        <div><b>1</b><strong>Describe qué pasa</strong><span>Indica ubicación, tipo y detalle del problema.</span></div><div><b>2</b><strong>Da seguimiento</strong><span>Lee respuestas, archivos y cambios de estado.</span></div><div><b>3</b><strong>Consulta la solución</strong><span>Al resolver, la causa y la solución quedan documentadas.</span></div>
      <?php endif; ?>
    </div>
  </section>

  <?php if($isSupport): ?>
  <section class="manual-section" id="soporte">
    <div class="manual-section-head"><span>03</span><div><h2>Centro de soporte</h2><p>Atiende primero lo urgente, continúa tus casos activos y documenta por qué un caso queda en espera.</p></div></div>
    <div class="manual-cards">
      <article><strong>Tomar un caso</strong><p>Usa <b>Tomar y atender</b> cuando realmente puedas iniciar trabajo. El ticket pasa a tu responsabilidad.</p></article>
      <article><strong>En espera</strong><p>Selecciona el motivo real: usuario, proveedor, compra, visita u otra dependencia. Agrega detalle cuando ayude a explicar el tiempo.</p></article>
      <article><strong>Respuesta vs nota interna</strong><p><b>Respuesta al usuario</b> es pública. <b>Nota interna</b> solo la ve Soporte. Verifica el selector antes de enviar.</p></article>
      <article><strong>Resolver</strong><p>Registra causa, solución aplicada y prevención. Esa resolución alimenta informes, casos similares y conocimiento.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canProblems||$canKnowledge): ?>
  <section class="manual-section" id="conocimiento">
    <div class="manual-section-head"><span><?= $isSupport?'04':'03' ?></span><div><h2>Problemas y conocimiento</h2><p>Convierte recurrencias y buenas resoluciones en aprendizaje reutilizable para el equipo.</p></div></div>
    <div class="manual-cards">
      <?php if($canProblems): ?><article><strong>Problemas conocidos</strong><p>Relaciona tickets recurrentes, documenta causa raíz, workaround y solución permanente. La recurrencia se actualiza automáticamente.</p><a href="<?= APP_BASE_URL ?>/problems">Abrir Problemas conocidos →</a></article><?php endif; ?>
      <?php if($canKnowledge): ?><article><strong>Base de conocimiento</strong><p>Crea artículos en borrador, revísalos y publícalos únicamente cuando estén listos. Los artículos internos no se exponen a usuarios externos.</p><a href="<?= APP_BASE_URL ?>/knowledge">Abrir Base de conocimiento →</a></article><?php endif; ?>
      <article><strong>Posibles soluciones</strong><p>En el ticket se sugieren artículos, problemas y tickets resueltos similares usando categoría, parque y términos del caso, sin IA externa.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canManagement): ?>
  <section class="manual-section" id="gestion">
    <div class="manual-section-head"><span>05</span><div><h2>Dashboard e informes</h2><p>Usa métricas para priorizar operación y el informe detallado para reconstruir tiempos, estados y resolución.</p></div></div>
    <div class="manual-cards">
      <article><strong>Dashboard interno</strong><p>Analiza volumen, SLA, carga, categorías y evolución. Usa filtros antes de interpretar los indicadores.</p><a href="<?= APP_BASE_URL ?>/gestion">Abrir Dashboard →</a></article>
      <article><strong>Informes</strong><p>Consulta cada ticket con tiempos, cambios de estado, motivos de espera y aprendizaje documentado. La exportación oficial es Excel (.xlsx).</p><a href="<?= APP_BASE_URL ?>/gestion/informes">Abrir Informes →</a></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canAdmin): ?>
  <section class="manual-section" id="administracion">
    <div class="manual-section-head"><span>06</span><div><h2>Administración</h2><p>Roles, asignaciones, externos y auditoría deben gestionarse sin convertir al usuario operativo en técnico por error.</p></div></div>
    <div class="manual-cards">
      <article><strong>Usuarios y estructura</strong><p>El rol define permisos; la asignación define parque/área, puesto y responsable. Son conceptos separados.</p></article>
      <article><strong>Proveedores externos</strong><p>Comparte únicamente tickets específicos y revoca el acceso al terminar la colaboración.</p></article>
      <article><strong>Auditoría</strong><p>Consulta quién cambió qué y cuándo. Úsala para trazabilidad, no como actividad diaria del técnico.</p></article>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>
