<?php
$pageTitle='Manual';$pageSection='Ayuda';$activeNav='manual';$helpContext='manual';
$manualProfile=$manualProfile??['key'=>'requester','label'=>'Solicitante','summary'=>'Guía de uso según tus permisos.'];
$manualGuide=$manualGuide??['start'=>'Empieza por la función principal de tu perfil.','workspace_label'=>'Inicio','workspace_href'=>APP_BASE_URL.'/dashboard','workspace'=>'Usa únicamente las funciones visibles para tu cuenta.','after'=>'Consulta la ayuda contextual cuando necesites más detalle.'];
$isRequesterProfile=(bool)($isRequesterProfile??false);$isTechnicianProfile=(bool)($isTechnicianProfile??false);$isSupervisorProfile=(bool)($isSupervisorProfile??false);$isManagementProfile=(bool)($isManagementProfile??false);$isAdminProfile=(bool)($isAdminProfile??false);$isCollaboratorProfile=(bool)($isCollaboratorProfile??false);
$canReports=(bool)($canReports??false);$canProviderReport=(bool)($canProviderReport??false);$canUsersManage=(bool)($canUsersManage??false);$canExternalManage=(bool)($canExternalManage??false);$canAudit=(bool)($canAudit??false);$canMailAdmin=(bool)($canMailAdmin??false);
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="manual-page">
  <div class="page-heading manual-heading">
    <div><span class="ticket-kicker">Guía de uso</span><h1 class="page-title">Manual</h1><p class="page-subtitle">Busca una tarea o duda. Solo verás instrucciones que correspondan a tu perfil.</p></div>
    <button class="btn btn-primary" type="button" data-tour-start>Ver recorrido guiado</button>
  </div>

  <section class="manual-profile-card" aria-label="Perfil del manual">
    <div><span class="manual-profile-kicker">Guía para tu perfil</span><strong>Manual para <?= htmlspecialchars($manualProfile['label']) ?></strong><p><?= htmlspecialchars($manualProfile['summary']) ?></p></div>
    <span class="manual-profile-badge"><?= htmlspecialchars($manualProfile['label']) ?></span>
  </section>
  <section class="manual-profile-guide" aria-label="Cómo empezar">
    <article><span>1</span><div><strong>Empieza aquí</strong><p><?= htmlspecialchars($manualGuide['start']) ?></p></div></article>
    <article><span>2</span><div><strong>Tu espacio principal</strong><p><?= htmlspecialchars($manualGuide['workspace']) ?></p><a href="<?= htmlspecialchars($manualGuide['workspace_href']) ?>"><?= htmlspecialchars($manualGuide['workspace_label']) ?> →</a></div></article>
    <article><span>3</span><div><strong>Después</strong><p><?= htmlspecialchars($manualGuide['after']) ?></p></div></article>
  </section>

  <div class="manual-search" role="search">
    <input class="form-control" type="search" placeholder="Buscar: resolver, proveedor, espera, informe…" data-manual-search aria-label="Buscar en el manual">
    <button class="btn btn-outline-secondary" type="button" data-manual-clear>Limpiar</button>
    <span class="manual-search-status" data-manual-search-status>Busca una tarea o elige un tema</span>
  </div>

  <nav class="manual-topic-filter" aria-label="Filtrar manual por tema" data-manual-topics>
    <button type="button" class="manual-topic is-active" data-manual-topic="all" aria-pressed="true">Todo</button>
    <?php if(!$isSupervisorProfile&&!$isManagementProfile): ?><button type="button" class="manual-topic" data-manual-topic="solicitudes" aria-pressed="false">Solicitudes</button><?php endif; ?>
    <?php if($isSupport): ?><button type="button" class="manual-topic" data-manual-topic="soporte" aria-pressed="false">Soporte</button><?php endif; ?>
    <?php if(!$isExternal): ?><button type="button" class="manual-topic" data-manual-topic="actividades" aria-pressed="false">Actividades</button><?php endif; ?>
    <?php if($canProblems||$canKnowledge): ?><button type="button" class="manual-topic" data-manual-topic="conocimiento" aria-pressed="false">Conocimiento</button><?php endif; ?>
    <?php if($canManagement): ?><button type="button" class="manual-topic" data-manual-topic="gestion" aria-pressed="false">Gestión</button><?php endif; ?>
    <?php if($canAdmin): ?><button type="button" class="manual-topic" data-manual-topic="administracion" aria-pressed="false">Administración</button><?php endif; ?>
    <button type="button" class="manual-topic" data-manual-topic="ayuda" aria-pressed="false">Ayuda y FAQ</button>
  </nav>

  <section aria-label="Accesos rápidos del manual">
    <div class="manual-quick-grid" data-manual-quick>
      <?php if($isSupport): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/tickets/queue"><span>Trabajo diario</span><strong>Atender casos</strong><small>Prioriza por SLA, responsabilidad y estado dentro de tu alcance.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/buscar"><span>Encontrar información</span><strong>Buscar una solución</strong><small>Busca tickets, problemas conocidos y artículos desde un solo lugar.</small></a>
        <?php if($canProblems): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/problems"><span>Recurrencias</span><strong>Problemas conocidos</strong><small>Agrupa fallas repetidas y documenta causa, alternativa y solución.</small></a><?php endif; ?>
        <?php if($canKnowledge): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/knowledge"><span>Aprendizaje</span><strong>Base de conocimiento</strong><small>Consulta y documenta soluciones reutilizables según tu capacidad editorial.</small></a><?php endif; ?>
      <?php elseif($isCollaboratorProfile): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Mis casos</strong><small>Revisa únicamente los casos compartidos con tu cuenta.</small></a>
        <a class="manual-quick-card" href="#solicitudes"><span>Participación</span><strong>Responder y adjuntar</strong><small>Envía avances, preguntas o evidencia dentro del caso.</small></a>
      <?php elseif($isSupervisorProfile||$isManagementProfile): ?>
        <?php if($canManagement): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion"><span>Consulta</span><strong>Dashboard interno</strong><small>Revisa volumen, tiempos y tendencias dentro de tu alcance.</small></a><?php endif; ?>
        <?php if($canReports): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion/informes"><span>Análisis</span><strong>Centro de informes</strong><small>Profundiza en tickets, SLA, agenda, equipo, proveedores y conocimiento según permisos.</small></a><?php endif; ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/agenda"><span>Planificación</span><strong>Agenda</strong><small>Consulta actividades, atrasos y conflictos sin operar la atención.</small></a>
        <?php if($canKnowledge): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/knowledge"><span>Consulta</span><strong>Conocimiento</strong><small>Revisa soluciones y procedimientos visibles para tu perfil.</small></a><?php endif; ?>
      <?php else: ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/crear-ticket"><span>Nueva solicitud</span><strong>Solicitar ayuda</strong><small>Elige en qué necesitas ayuda y cuéntanos qué está pasando.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Mis solicitudes</strong><small>Consulta respuestas, archivos, estado y solución.</small></a>
      <?php endif; ?>
      <a class="manual-quick-card" href="#notificaciones"><span>Novedades</span><strong>Notificaciones</strong><small>Abre una novedad para ir al caso o acción relacionada.</small></a>
      <?php if($canManagement&&!$isSupervisorProfile&&!$isManagementProfile): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion"><span>Gestión</span><strong>Dashboard e informes</strong><small>Analiza operación y tendencias dentro de tu alcance.</small></a><?php endif; ?>
      <?php if($canAdmin): ?><a class="manual-quick-card" href="#administracion"><span>Administración</span><strong>Funciones habilitadas</strong><small>Usuarios, proveedores, auditoría y correo aparecen únicamente si tu cuenta tiene la capacidad correspondiente.</small></a><?php endif; ?>
    </div>
  </section>

  <nav class="manual-index" aria-label="Índice del manual">
    <a href="#inicio">Inicio</a><a href="#notificaciones">Notificaciones</a><?php if(!$isSupervisorProfile&&!$isManagementProfile): ?><a href="#solicitudes">Solicitudes</a><?php endif; ?><?php if($isSupport||$isRequesterProfile): ?><a href="#actividades">Actividades</a><?php endif; ?><?php if($isSupport||$canManagement): ?><a href="#agenda">Agenda</a><?php endif; ?><?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?><?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?><?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?><?php if($canProviderReport): ?><a href="#proveedores">Proveedores</a><?php endif; ?><?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?><a href="#preguntas">Preguntas frecuentes</a>
  </nav>

  <section class="manual-section" id="inicio" data-manual-section data-manual-topics="ayuda">
    <div class="manual-section-head"><span>01</span><div><h2>Inicio y navegación</h2><p>Ubica rápidamente lo que requiere atención y usa el buscador global cuando necesites encontrar un caso o una solución.</p></div></div>
    <div class="manual-cards"><article><strong>Buscador global</strong><p>Usa la barra superior o <kbd>Ctrl K</kbd>. Los resultados se agrupan según el tipo de información y tus permisos.</p></article><article><strong>Menú lateral</strong><p>Las opciones cambian según tu perfil. Si una función no corresponde a tu cuenta, no aparece.</p></article><article><strong>Ayuda flotante</strong><p>El botón <b>?</b> abre ayuda de la pantalla y permite iniciar un tutorial guiado.</p></article></div>
  </section>

  <section class="manual-section" id="notificaciones" data-manual-section data-manual-topics="ayuda">
    <div class="manual-section-head"><span>N</span><div><h2>Notificaciones y campanita</h2><p>La campanita concentra novedades que requieren contexto: respuestas, cambios de estado, asignaciones, resoluciones y otras acciones relacionadas con tu cuenta.</p></div></div>
    <div class="manual-cards"><article><strong>Abrir una notificación</strong><p>Haz clic sobre la novedad. El Helpdesk reconstruye el destino contra la instancia que tienes abierta y, cuando pertenece a un ticket, usa el número interno del caso como respaldo si el enlace guardado ya no es válido.</p></article><article><strong>Leída no significa resuelta</strong><p>Al abrirla se marca como revisada, pero el ticket conserva su estado real. Usa <b>Marcar leídas</b> únicamente para limpiar la bandeja de novedades.</p></article><article><strong>Si un destino cambió</strong><p>El sistema evita enviar una notificación interna a otro host o entorno. Si no puede recuperar una ruta válida, vuelve a un destino seguro del Helpdesk.</p></article></div>
  </section>
  <?php if(!$isSupervisorProfile&&!$isManagementProfile): ?>
  <section class="manual-section" id="solicitudes" data-manual-section data-manual-topics="solicitudes">
    <div class="manual-section-head"><span>02</span><div><h2><?= $isExternal?'Casos compartidos':'Solicitudes y seguimiento' ?></h2><p><?= $isExternal?'Trabaja únicamente los casos asignados a tu cuenta.':'Elige en qué necesitas ayuda, describe lo que ocurre y sigue las respuestas desde el mismo caso.' ?></p></div></div>
    <div class="manual-flow">
      <?php if($isExternal): ?><div><b>1</b><strong>Abre el caso</strong><span>Lee primero qué apoyo se necesita.</span></div><div><b>2</b><strong>Actualiza</strong><span>Responde, pide información o adjunta evidencia.</span></div><div><b>3</b><strong>Espera validación</strong><span>El equipo interno continúa la gestión y cierre.</span></div>
      <?php else: ?><div><b>1</b><strong>Elige la ayuda</strong><span>Selecciona la opción del catálogo que más se parezca a lo que necesitas. El catálogo se mantiene actualizado y la ayuda cambia según tu selección, por lo que no necesitas memorizar categorías.</span></div><div><b>2</b><strong>Cuéntanos qué pasa</strong><span>Describe los hechos con tus propias palabras. Si iniciaste sesión, tus datos se usan automáticamente y tu ubicación asignada se propone cuando es inequívoca; puedes cambiarla si reportas otro lugar.</span></div><div><b>3</b><strong>Da seguimiento</strong><span>Consulta respuestas, archivos, cambios relevantes y la solución desde Mis solicitudes.</span></div><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if($isSupport): ?>
  <section class="manual-section" id="actividades" data-manual-section data-manual-topics="actividades">
    <div class="manual-section-head"><span>A</span><div><h2>Actividades y visitas</h2><p>Programa y documenta trabajo operativo ligado al caso sin confundir la actividad con el estado del ticket.</p></div></div>
    <div class="manual-cards">
      <article><strong>Programar</strong><p>Dentro del caso usa <b>+ Programar actividad</b>. Elige visita en sitio, soporte remoto, seguimiento, intervención de proveedor u otra atención; define responsable, fecha de inicio, fin estimado y objetivo.</p></article>
      <article><strong>Responsable y participantes</strong><p>La actividad tiene un responsable principal y puede incluir participantes internos. Una intervención de proveedor usa únicamente colaboradores con acceso vigente al caso.</p></article>
      <article><strong>Reprogramar</strong><p>Si cambia la fecha, usa <b>Reprogramar</b> y registra el motivo. La actividad se conserva y el cambio queda trazado; no crees otra actividad para ocultar una reprogramación.</p></article>
      <article><strong>Iniciar y Finalizar</strong><p>Usa <b>Iniciar</b> cuando comience el trabajo y <b>Finalizar</b> para registrar resultado, trabajo realizado y pendientes. Finalizar una actividad no resuelve ni cierra el ticket: el caso continúa con su propio flujo.</p></article>
      <article><strong>Cancelar</strong><p>Cancela únicamente cuando la atención ya no corresponda y deja un motivo claro. La cancelación queda registrada para trazabilidad.</p></article>
      <article><strong>Información para el solicitante</strong><p>Activa la visibilidad solo cuando quieras publicar una actualización. Escribe un resumen claro para el usuario; preparación, responsable interno, proveedor, trabajo técnico y otros detalles privados permanecen dentro de soporte.</p></article>
    </div>
  </section>
  <?php elseif($isRequesterProfile): ?>
  <section class="manual-section" id="actividades" data-manual-section data-manual-topics="actividades">
    <div class="manual-section-head"><span>A</span><div><h2>Actividades y visitas</h2><p>Cuando soporte publique una atención programada para tu solicitud, aparecerá dentro del caso como <b>Próxima atención</b>.</p></div></div>
    <div class="manual-cards">
      <article><strong>Próxima atención</strong><p>Puede indicar el tipo de atención, estado, fecha programada, fin estimado, ubicación y el resumen que soporte preparó para ti.</p></article>
      <article><strong>Información segura</strong><p>Solo verás la información que el equipo de soporte decidió publicar. La preparación interna, responsables técnicos y detalles privados de trabajo no se muestran en tu solicitud.</p></article>
      <article><strong>Actividad y solicitud son diferentes</strong><p>Una visita o seguimiento puede finalizar y tu solicitud continuar abierta mientras el equipo completa la solución, validaciones o pasos pendientes.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($isSupport||$canManagement): ?>
  <section class="manual-section" id="agenda" data-manual-section data-manual-topics="actividades gestion">
    <div class="manual-section-head"><span>AG</span><div><h2>Agenda</h2><p>Consulta actividades programadas desde Calendario o Lista con el alcance autorizado para tu perfil.</p></div></div>
    <div class="manual-cards">
      <?php if($isTechnicianProfile): ?>
      <article><strong>Mi trabajo y mi alcance</strong><p>Usa <b>Mis actividades</b> para tu carga directa y <b>Todo mi alcance</b> para revisar actividades visibles por parque, región, área o equipo.</p></article>
      <?php endif; ?>
      <?php if($isAdminProfile): ?>
      <article><strong>Vista operativa del equipo</strong><p>Consulta la agenda global permitida por alcance y usa filtros de responsable, parque, tipo, estado y fecha para coordinar trabajo.</p></article>
      <?php endif; ?>
      <?php if($isSupport): ?>
      <article><strong>Atrasadas y conflictos</strong><p>Son señales para priorizar. No crean estados nuevos ni sustituyen el estado del ticket.</p></article>
      <article><strong>La operación ocurre en el ticket</strong><p>Abre el caso para programar, reprogramar, iniciar, finalizar o cancelar una actividad. La Agenda es la vista de planificación.</p></article>
      <?php endif; ?>
      <?php if($isSupervisorProfile): ?>
      <article><strong>Seguimiento por alcance</strong><p>Consulta carga, atrasos y conflictos dentro del parque, región o área asignada. Usa Agenda para coordinación y seguimiento.</p></article>
      <article><strong>Perfil de consulta</strong><p>Supervisor no programa, inicia, finaliza, cancela ni reprograma actividades salvo que tenga otro perfil técnico explícito.</p></article>
      <?php endif; ?>
      <?php if($isManagementProfile): ?>
      <article><strong>Lectura ejecutiva</strong><p>Consulta carga programada, atrasos y conflictos como contexto de operación. Profundiza desde Informes cuando necesites explicar tendencias.</p></article>
      <article><strong>Sin operación de soporte</strong><p>Gerencia consulta Agenda y tickets; la ejecución continúa a cargo del equipo de soporte.</p></article>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>
  <?php if($isSupport): ?>
  <section class="manual-section" id="calidad-proveedor" data-manual-section data-manual-topics="soporte">
    <div class="manual-section-head"><span>QP</span><div><h2>Calidad del proveedor</h2><p>Registra una valoración interna de IT cuando la participación del proveedor haya finalizado mediante revocación explícita.</p></div></div>
    <div class="manual-cards">
      <article><strong>Escala 1–5</strong><p>1★ Muy deficiente, 2★ Deficiente, 3★ Adecuado, 4★ Bueno y 5★ Excelente. La valoración describe la calidad observada por IT en ese ciclo concreto.</p></article>
      <article><strong>Comentario obligatorio</strong><p>En 1★ o 2★ debes explicar el motivo. Para 3★, 4★ y 5★ el comentario es opcional en la primera valoración.</p></article>
      <article><strong>Correcciones</strong><p>Si necesitas ajustar una valoración ya guardada, usa <b>Registrar corrección</b>. Toda corrección exige comentario y crea una nueva versión; el registro anterior se conserva.</p></article>
      <article><strong>Sin evaluar</strong><p>Un ciclo puede quedar Sin evaluar. Ese estado no vale cero y no entra al promedio del proveedor.</p></article>
      <article><strong>Cuándo se habilita</strong><p>Solo puedes evaluar una participación cerrada mediante revocación explícita. Un ciclo activo o cerrado implícitamente por una nueva asignación no es evaluable.</p></article>
      <article><strong>Privacidad</strong><p>La valoración es interna de IT: no es visible para el proveedor ni para el solicitante. El proveedor no la ve en su acceso externo.</p></article>
      <article><strong>Informe de proveedores</strong><p>El informe muestra valoración vigente, promedio, ciclos evaluados y Sin evaluar, además de filtros por estrellas. La exportación XLSX usa el mismo conjunto filtrado.</p></article>
    </div>
  </section>
  <?php endif; ?>
<?php if($isSupport): ?><section class="manual-section" id="soporte" data-manual-section data-manual-topics="soporte"><div class="manual-section-head"><span>03</span><div><h2>Centro de soporte</h2><p>Prioriza por alcance y tiempo, continúa la conversación y documenta correctamente esperas y solución.</p></div></div><div class="manual-cards"><article><strong>Cola de trabajo</strong><p><b>Todos</b> reúne los casos activos que puedes consultar según tu alcance. Usa <b>Míos</b>, <b>Sin asignar</b>, <b>Por vencer</b>, <b>Vencidos</b>, <b>En espera</b>, <b>Reabiertos</b> o <b>Críticos</b> para concentrarte.</p></article><article><strong>Clasificación ITSM</strong><p>El Helpdesk propone automáticamente Incidente o Solicitud de servicio, Impacto y Urgencia a partir de la categoría y lo descrito por el usuario. Impacto + Urgencia producen la prioridad inicial. Soporte debe validar la clasificación y, si ajusta manualmente la prioridad, dejar motivo y auditoría.</p></article><article><strong>Resolución objetivo</strong><p>El SLA muestra el tiempo restante, cuánto del objetivo ya se utilizó y una lectura operativa: Dentro de objetivo, Atención requerida, Próximo a vencer o Vencido.</p></article><article><strong>Tomar un caso</strong><p>Usa <b>Tomar</b> cuando realmente puedas iniciar trabajo. Solo puedes tomar casos disponibles dentro de tu alcance.</p></article><article><strong>En espera</strong><p>Selecciona la razón real: usuario, proveedor, compra, visita, aprobación u otra dependencia. Los informes conservan cuánto tiempo se acumuló en cada motivo aunque cambie durante el mismo caso.</p></article><article><strong>Respuesta y conversación interna</strong><p><b>Respuesta al usuario</b> es visible para el solicitante. <b>Conversación interna</b> es solo para el equipo de soporte.</p></article><article><strong>Resolver</strong><p>Registra qué encontraste, qué hiciste y cómo evitar que vuelva a ocurrir. Al resolver, el cierre final queda pendiente de confirmación del solicitante; IT puede reabrir si detecta que aún falta trabajo.</p></article></div></section><?php endif; ?>

  <?php if($canProblems||$canKnowledge): ?>
  <section class="manual-section" id="conocimiento" data-manual-section data-manual-topics="conocimiento">
    <div class="manual-section-head">
      <span><?= $isSupport?'04':'03' ?></span>
      <div>
        <h2>Problemas y conocimiento</h2>
        <p>Convierte recurrencias y buenas resoluciones en información útil sin exponer borradores o detalles internos a quien no corresponda.</p>
      </div>
    </div>

    <div class="manual-cards">
      <?php if($canProblems): ?>
        <article>
          <strong>Problemas conocidos</strong>
          <p>Relaciona casos recurrentes y documenta causa, solución temporal y solución permanente. Úsalo cuando el mismo problema aparece varias veces.</p>
          <a href="<?= APP_BASE_URL ?>/problems">Abrir problemas →</a>
        </article>
      <?php endif; ?>

      <?php if($canKnowledge&&!$canKnowledgeDraft): ?>
        <article>
          <strong>Información útil durante tu solicitud</strong>
          <p>Mientras describes un problema, el Helpdesk puede mostrar hasta tres artículos disponibles para solicitantes. Puedes abrirlos y, si todavía necesitas ayuda, continuar con la solicitud normalmente.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgeDraft): ?>
        <article>
          <strong>Crear borrador</strong>
          <p>Desde Conocimiento crea una solución nueva o inicia el borrador desde un caso resuelto. Guardar un borrador nunca lo publica automáticamente.</p>
          <a href="<?= APP_BASE_URL ?>/knowledge/new">Crear borrador →</a>
        </article>
        <article>
          <strong>Enviar a revisión</strong>
          <p>Cuando el contenido esté claro y completo, usa <b>Enviar a revisión</b>. Después de enviarlo ya no debes tratarlo como una edición libre: espera la revisión o las observaciones.</p>
        </article>
      <?php endif; ?>

      <?php if($isSupport): ?>
        <article>
          <strong>Usar como referencia</strong>
          <p>En un ticket revisa las soluciones sugeridas y usa <b>Usar como referencia</b> cuando una realmente aplique. El sistema precarga causa, solución y prevención cuando existan; todo sigue editable y el ticket no se resuelve por esa acción.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgeReview): ?>
        <article>
          <strong>Revisar antes de publicar</strong>
          <p>Una revisión puede aprobarse o devolverse a borrador con una observación clara. La revisión editorial evita reemplazar silenciosamente conocimiento que ya está en uso.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgePublishInternal): ?>
        <article>
          <strong>Publicado para soporte</strong>
          <p>La primera publicación convierte esa versión en la vigente para el equipo interno. Una versión publicada no se edita directamente: cualquier mejora crea un borrador nuevo.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgePublishPublic): ?>
        <article>
          <strong>Disponible para solicitantes</strong>
          <p>Esta es una segunda acción separada. Solo una versión que ya fue publicada para soporte puede habilitarse para autoservicio; hacerlo no cambia automáticamente otros borradores o revisiones.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgeHistory): ?>
        <article>
          <strong>Comparar versiones</strong>
          <p>Abre el historial para revisar versiones anteriores y comparar Título, Resumen, Contenido y Categoría antes de decidir qué recuperar.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledgeRestore): ?>
        <article>
          <strong>Restaurar versión</strong>
          <p>Restaurar una versión antigua crea un borrador nuevo basado en ella. La versión vigente continúa activa hasta que el nuevo borrador complete revisión y publicación.</p>
        </article>
      <?php endif; ?>

      <?php if($canKnowledge): ?>
        <article>
          <strong>Base de conocimiento</strong>
          <p>Busca primero si ya existe una solución antes de documentar otra. Los artículos visibles siempre respetan el nivel de acceso de tu perfil.</p>
          <a href="<?= APP_BASE_URL ?>/knowledge">Abrir conocimiento →</a>
        </article>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canManagement): ?>
  <section class="manual-section" id="gestion" data-manual-section data-manual-topics="gestion">
    <div class="manual-section-head"><span>05</span><div><h2>Dashboard e informes</h2><p><?= $isSupervisorProfile?'Consulta lo que ocurre dentro de tu alcance organizacional.':($isManagementProfile?'Lee la operación a nivel ejecutivo sin intervenir en la atención.':'Usa métricas para entender qué ocurre y el detalle para tomar decisiones operativas.') ?></p></div></div>
    <div class="manual-cards">
      <article><strong>Dashboard</strong><p><?= $isSupervisorProfile?'Revisa volumen, tiempos, carga y tendencias del parque, región o área que tienes asignada.':($isManagementProfile?'Usa indicadores para detectar tendencias, carga, tiempos y excepciones que requieran seguimiento.':'Analiza volumen, tiempos, carga y tendencias dentro del alcance autorizado.') ?></p><a href="<?= APP_BASE_URL ?>/gestion">Abrir Dashboard →</a></article>
      <?php if($canReports): ?><article><strong>Centro de informes</strong><p>Aplica período y filtros para explicar el detalle de Tickets/SLA, Agenda, Proveedores, Equipo y Conocimiento según tus capacidades. Excel conserva el mismo conjunto filtrado.</p><a href="<?= APP_BASE_URL ?>/gestion/informes">Abrir Informes →</a></article><?php endif; ?>
      <?php if($isSupervisorProfile): ?><article><strong>Seguimiento, no atención</strong><p>Usa la información para coordinar y escalar. Tomar, reasignar, responder, poner en espera o resolver corresponde al perfil técnico.</p></article><?php endif; ?>
      <?php if($isManagementProfile): ?><article><strong>Lectura ejecutiva</strong><p>Profundiza solo cuando un indicador requiera contexto. Gerencia no necesita entrar a operar tickets para entender el estado del servicio.</p></article><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if($canProviderReport): ?>
  <section class="manual-section" id="proveedores" data-manual-section data-manual-topics="gestion">
    <div class="manual-section-head"><span>PR</span><div><h2>Informe de proveedores</h2><p>Consulta cómo participó cada colaborador externo en los casos compartidos sin mezclar administración de accesos con rendimiento operativo.</p></div></div>
    <div class="manual-cards">
      <article><strong>Participación por ciclo</strong><p>Cada vez que un proveedor recibe acceso a un ticket inicia un ciclo nuevo. El informe muestra asignación, primera respuesta, duración, actividad actual, tiempo declarado, respuestas, archivos e informes de ese ciclo.</p></article>
      <article><strong>Listo para revisión</strong><p>Cuando el proveedor marca <b>Listo para revisión</b>, informa que su trabajo está listo para que Carrousel lo valide. Esa acción no resuelve ni cierra automáticamente el ticket; el equipo interno decide el siguiente paso.</p></article>
      <article><strong>Devoluciones</strong><p>Una devolución se registra cuando, después de una entrega lista para revisión, el ticket vuelve a atención o se reabre. Las reaperturas anteriores a la entrega no se atribuyen al proveedor.</p></article>
      <article><strong>Filtros y Excel</strong><p>Filtra por proveedor, estado del ciclo, actividad y fechas. <b>Descargar Excel</b> utiliza los mismos filtros y métricas que ves en pantalla.</p><a href="<?= APP_BASE_URL ?>/admin/externos/informe">Abrir Informe de proveedores →</a></article>
    </div>
  </section>
  <?php endif; ?>
  <?php if($canAdmin): ?>
  <section class="manual-section" id="administracion" data-manual-section data-manual-topics="administracion">
    <div class="manual-section-head"><span>06</span><div><h2>Administración</h2><p>Solo aparecen las funciones administrativas habilitadas para tu cuenta.</p></div></div>
    <div class="manual-cards">
      <?php if($canUsersManage): ?><article><strong>Usuarios y asignaciones</strong><p>El perfil define capacidades y la asignación define dónde trabaja la persona. <b>Responsable directo</b> es una relación organizacional: no asigna tickets ni cambia permisos.</p><a href="<?= APP_BASE_URL ?>/admin/users">Abrir Usuarios →</a></article><?php endif; ?>
      <?php if($canExternalManage): ?><article><strong>Proveedores y accesos</strong><p>Administra colaboradores externos, comparte únicamente casos concretos y retira el acceso cuando termine su participación.</p><a href="<?= APP_BASE_URL ?>/admin/externos">Abrir Proveedores →</a></article><?php endif; ?>
      <?php if($canAudit): ?><article><strong>Auditoría</strong><p>Reconstruye quién realizó una acción y cuándo. La auditoría es para trazabilidad, no para el usuario final.</p><a href="<?= APP_BASE_URL ?>/admin/audit">Abrir Auditoría →</a></article><?php endif; ?>
      <?php if($canMailAdmin): ?><article><strong>Correo y notificaciones</strong><p>Comprueba el canal, envía una prueba y revisa enviados, fallidos, pendientes e intentos sin exponer credenciales.</p><a href="<?= APP_BASE_URL ?>/admin/correo">Abrir Correo →</a></article><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="manual-section" id="preguntas" data-manual-section data-manual-topics="ayuda">
    <div class="manual-section-head"><span>?</span><div><h2>Preguntas frecuentes</h2><p>Respuestas breves según las funciones de tu perfil.</p></div></div>
    <div class="manual-faq">
      <?php if($isRequesterProfile): ?>
        <details><summary>¿Qué información debo incluir al reportar?</summary><div>Indica qué sucede, desde cuándo, dónde ocurre y a quién o qué equipo afecta. No necesitas conocer la causa técnica y nunca debes escribir tu contraseña.</div></details>
        <details><summary>¿Dónde veo lo que respondió soporte?</summary><div>Abre <b>Mis solicitudes</b>. Allí encontrarás conversación, archivos, estado, próximas atenciones publicadas y solución.</div></details>
        <details><summary>¿Quién cierra una solicitud resuelta?</summary><div>Soporte documenta la solución y la marca como resuelta. Después puedes confirmar que quedó solucionada o devolverla a soporte si todavía existe el problema.</div></details>
      <?php endif; ?>
      <?php if($isCollaboratorProfile): ?>
        <details><summary>¿Qué casos puedo ver?</summary><div>Solo los casos que el equipo interno compartió de forma explícita con tu cuenta y mientras ese acceso siga vigente.</div></details>
        <details><summary>¿Puedo ver notas internas?</summary><div>No. Tu espacio contiene únicamente el contexto, conversación, archivos y solución que corresponden a la colaboración externa.</div></details>
        <details><summary>¿Qué significa Listo para revisión?</summary><div>Indica que tu intervención está lista para que el equipo interno la valide. No resuelve ni cierra automáticamente el ticket.</div></details>
      <?php endif; ?>
      <?php if($isSupport): ?>
        <details><summary>¿Qué significan los estados del SLA?</summary><div><b>Dentro de objetivo</b> conserva margen normal; <b>Atención requerida</b> indica consumo relevante; <b>Próximo a vencer</b> requiere prioridad; <b>Vencido</b> superó el objetivo.</div></details>
        <details><summary>¿Cuándo debo poner un caso en espera?</summary><div>Solo cuando una dependencia real impida continuar: usuario, proveedor, compra, visita, aprobación u otra causa documentable.</div></details>
        <details><summary>¿Respuesta al usuario o conversación interna?</summary><div>La respuesta al usuario forma parte del seguimiento visible. La conversación interna es exclusivamente del equipo de soporte.</div></details>
        <details><summary>¿Qué debo documentar al resolver?</summary><div>Qué encontraste, qué hiciste y cómo evitar que vuelva a ocurrir. Usa referencias cuando una solución previa realmente aplique.</div></details>
      <?php endif; ?>
      <?php if($isSupervisorProfile): ?>
        <details><summary>¿Por qué no veo botones para atender?</summary><div>Supervisor es un perfil de consulta y seguimiento por alcance. La atención requiere un perfil técnico explícito.</div></details>
        <details><summary>¿Qué determina lo que puedo consultar?</summary><div>Tu asignación organizacional define el alcance visible: parque, región, área u otra relación configurada.</div></details>
      <?php endif; ?>
      <?php if($isManagementProfile): ?>
        <details><summary>¿Por qué Gerencia no opera tickets?</summary><div>El perfil está diseñado para consulta ejecutiva. Dashboard, Informes, Problemas y Conocimiento permiten entender la operación sin intervenir en el flujo técnico.</div></details>
        <details><summary>¿Cuándo conviene abrir el detalle de un informe?</summary><div>Cuando un indicador requiera explicar volumen, tiempo, espera, recurrencia, proveedor, equipo o conocimiento detrás del dato agregado.</div></details>
      <?php endif; ?>
      <?php if($canProblems): ?><details><summary>¿Cuándo conviene crear un problema conocido?</summary><div>Cuando varios tickets representan la misma falla o la causa requiere investigación más allá de un solo caso.</div></details><?php endif; ?>
      <?php if($canKnowledgeDraft): ?><details><summary>¿Cuándo debo crear un artículo?</summary><div>Cuando una solución o procedimiento sea claro y reutilizable. Empieza como borrador; la publicación requiere el flujo editorial correspondiente.</div></details><?php endif; ?>
      <?php if($canUsersManage): ?><details><summary>¿Perfil, asignación y responsable directo son lo mismo?</summary><div>No. Perfil define capacidades; asignación define dónde trabaja; responsable directo representa a quién reporta organizacionalmente.</div></details><?php endif; ?>
      <?php if($canMailAdmin): ?><details><summary>¿Cómo sé si un correo realmente salió?</summary><div>En <b>Correo y notificaciones</b>, Enviado significa que SMTP aceptó la entrega; Falló requiere revisión; Pendiente no terminó; Modo prueba no salió a Internet.</div></details><?php endif; ?>
    </div>
  </section>
</div>
<script nonce="<?= htmlspecialchars(CSP_NONCE) ?>">
(function(){
  const input=document.querySelector('[data-manual-search]');
  const status=document.querySelector('[data-manual-search-status]');
  const clear=document.querySelector('[data-manual-clear]');
  const topicButtons=[...document.querySelectorAll('[data-manual-topic]')];
  if(!input)return;

  let activeTopic='all';
  const normalize=v=>(v||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
  const labelForTopic=topic=>{
    const button=topicButtons.find(el=>el.dataset.manualTopic===topic);
    return button?button.textContent.trim():'Todo';
  };

  const run=()=>{
    const q=normalize(input.value);
    let visible=0;

    document.querySelectorAll('.manual-quick-card').forEach(el=>{
      const textMatch=!q||normalize(el.textContent).includes(q);
      el.classList.toggle('is-hidden',!textMatch);
    });

    document.querySelectorAll('[data-manual-section]').forEach(section=>{
      const topics=(section.dataset.manualTopics||'').split(/\s+/).filter(Boolean);
      const topicMatch=activeTopic==='all'||topics.includes(activeTopic);
      let queryMatch=!q;

      const faq=[...section.querySelectorAll('details')];
      if(faq.length){
        let faqVisible=0;
        faq.forEach(el=>{
          const match=!q||normalize(el.textContent).includes(q);
          el.classList.toggle('is-hidden',!(topicMatch&&match));
          if(match)faqVisible++;
        });
        const own=normalize(section.querySelector('.manual-section-head')?.textContent||'').includes(q);
        queryMatch=!q||own||faqVisible>0;
      }else if(q){
        queryMatch=normalize(section.textContent).includes(q);
      }

      const show=topicMatch&&queryMatch;
      section.classList.toggle('is-hidden',!show);
      if(show)visible++;
    });

    document.querySelectorAll('.manual-index a[href^="#"]').forEach(link=>{
      const target=document.querySelector(link.getAttribute('href'));
      link.classList.toggle('is-hidden',!!target&&target.classList.contains('is-hidden'));
    });

    if(status){
      if(q||activeTopic!=='all'){
        status.textContent=visible
          ? visible+' sección(es) · '+labelForTopic(activeTopic)
          : 'No encontramos coincidencias';
      }else{
        status.textContent='Busca una tarea o elige un tema';
      }
    }
  };

  topicButtons.forEach(button=>{
    button.addEventListener('click',()=>{
      activeTopic=button.dataset.manualTopic||'all';
      topicButtons.forEach(item=>{
        const active=item===button;
        item.classList.toggle('is-active',active);
        item.setAttribute('aria-pressed',active?'true':'false');
      });
      run();
      document.querySelector('.manual-search')?.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
  });

  input.addEventListener('input',run);
  clear?.addEventListener('click',()=>{
    input.value='';
    activeTopic='all';
    topicButtons.forEach(item=>{
      const active=item.dataset.manualTopic==='all';
      item.classList.toggle('is-active',active);
      item.setAttribute('aria-pressed',active?'true':'false');
    });
    run();
    input.focus();
  });
  run();
})();
</script>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>