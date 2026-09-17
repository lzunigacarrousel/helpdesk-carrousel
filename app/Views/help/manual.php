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
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/tickets/queue"><span>Trabajo diario</span><strong>Atender casos</strong><small>Revisa los casos visibles dentro de tu alcance, usa Míos o Sin asignar y prioriza lo que esté por vencer.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/buscar"><span>Encontrar información</span><strong>Buscar una solución</strong><small>Busca tickets, problemas conocidos y artículos desde un solo lugar.</small></a>
        <?php if($canProblems): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/problems"><span>Recurrencias</span><strong>Problemas conocidos</strong><small>Agrupa fallas repetidas y documenta qué hacer mientras se resuelven.</small></a><?php endif; ?>
        <?php if($canKnowledge): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/knowledge"><span>Aprendizaje</span><strong>Base de conocimiento</strong><small>Consulta y documenta soluciones que puedan reutilizarse.</small></a><?php endif; ?>
      <?php elseif($isExternal): ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Ver mis casos</strong><small>Abre los casos asignados a tu cuenta y continúa la conversación.</small></a>
        <a class="manual-quick-card" href="#solicitudes"><span>Participación</span><strong>Responder y adjuntar</strong><small>Envía avances, consultas o evidencia desde el caso.</small></a>
      <?php else: ?>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/crear-ticket"><span>Nueva solicitud</span><strong>Solicitar ayuda</strong><small>Elige en qué necesitas ayuda y cuéntanos qué está pasando, sin lenguaje técnico.</small></a>
        <a class="manual-quick-card" href="<?= APP_BASE_URL ?>/mis-tickets"><span>Seguimiento</span><strong>Ver mis solicitudes</strong><small>Consulta respuestas, archivos, estado y solución.</small></a>
      <?php endif; ?>
      <a class="manual-quick-card" href="#notificaciones"><span>Novedades</span><strong>Notificaciones y campanita</strong><small>Abre una novedad para ir directamente al caso o acción relacionada y usa Marcar leídas cuando ya revisaste todo.</small></a>
      <?php if($canManagement): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/gestion"><span>Consulta</span><strong>Dashboard e informes</strong><small>Analiza volumen, tiempos, carga y tendencias según tu alcance.</small></a><?php endif; ?>
      <?php if($canAdmin): ?><a class="manual-quick-card" href="<?= APP_BASE_URL ?>/admin/correo"><span>Operación</span><strong>Correo y notificaciones</strong><small>Prueba el canal y revisa entregas o fallos.</small></a><?php endif; ?>
    </div>
  </section>

  <nav class="manual-index" aria-label="Índice del manual">
    <a href="#inicio">Inicio</a><a href="#notificaciones">Notificaciones</a><a href="#solicitudes">Solicitudes</a><?php if(!$isExternal): ?><a href="#actividades">Actividades</a><?php endif; ?><?php if($isSupport||$canManagement): ?><a href="#agenda">Agenda</a><?php endif; ?><?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?><?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?><?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?><?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?><a href="#preguntas">Preguntas frecuentes</a>
  </nav>

  <section class="manual-section" id="inicio" data-manual-section>
    <div class="manual-section-head"><span>01</span><div><h2>Inicio y navegación</h2><p>Ubica rápidamente lo que requiere atención y usa el buscador global cuando necesites encontrar un caso o una solución.</p></div></div>
    <div class="manual-cards"><article><strong>Buscador global</strong><p>Usa la barra superior o <kbd>Ctrl K</kbd>. Los resultados se agrupan según el tipo de información y tus permisos.</p></article><article><strong>Menú lateral</strong><p>Las opciones cambian según tu perfil. Si una función no corresponde a tu cuenta, no aparece.</p></article><article><strong>Ayuda flotante</strong><p>El botón <b>?</b> abre ayuda de la pantalla y permite iniciar un tutorial guiado.</p></article></div>
  </section>

  <section class="manual-section" id="notificaciones" data-manual-section>
    <div class="manual-section-head"><span>N</span><div><h2>Notificaciones y campanita</h2><p>La campanita concentra novedades que requieren contexto: respuestas, cambios de estado, asignaciones, resoluciones y otras acciones relacionadas con tu cuenta.</p></div></div>
    <div class="manual-cards"><article><strong>Abrir una notificación</strong><p>Haz clic sobre la novedad. El Helpdesk reconstruye el destino contra la instancia que tienes abierta y, cuando pertenece a un ticket, usa el número interno del caso como respaldo si el enlace guardado ya no es válido.</p></article><article><strong>Leída no significa resuelta</strong><p>Al abrirla se marca como revisada, pero el ticket conserva su estado real. Usa <b>Marcar leídas</b> únicamente para limpiar la bandeja de novedades.</p></article><article><strong>Si un destino cambió</strong><p>El sistema evita enviar una notificación interna a otro host o entorno. Si no puede recuperar una ruta válida, vuelve a un destino seguro del Helpdesk.</p></article></div>
  </section>
  <section class="manual-section" id="solicitudes" data-manual-section>
    <div class="manual-section-head"><span>02</span><div><h2><?= $isExternal?'Casos compartidos':'Solicitudes y seguimiento' ?></h2><p><?= $isExternal?'Trabaja únicamente los casos asignados a tu cuenta.':'Elige en qué necesitas ayuda, describe lo que ocurre y sigue las respuestas desde el mismo caso.' ?></p></div></div>
    <div class="manual-flow">
      <?php if($isExternal): ?><div><b>1</b><strong>Abre el caso</strong><span>Lee primero qué apoyo se necesita.</span></div><div><b>2</b><strong>Actualiza</strong><span>Responde, pide información o adjunta evidencia.</span></div><div><b>3</b><strong>Espera validación</strong><span>El equipo interno continúa la gestión y cierre.</span></div>
      <?php else: ?><div><b>1</b><strong>Elige la ayuda</strong><span>Selecciona la opción del catálogo que más se parezca a lo que necesitas. El catálogo se mantiene actualizado y la ayuda cambia según tu selección, por lo que no necesitas memorizar categorías.</span></div><div><b>2</b><strong>Cuéntanos qué pasa</strong><span>Describe los hechos con tus propias palabras. Si iniciaste sesión, tus datos se usan automáticamente y tu ubicación asignada se propone cuando es inequívoca; puedes cambiarla si reportas otro lugar.</span></div><div><b>3</b><strong>Da seguimiento</strong><span>Consulta respuestas, archivos, cambios relevantes y la solución desde Mis solicitudes.</span></div><?php endif; ?>
    </div>
  </section>

  <?php if($isSupport): ?>
  <section class="manual-section" id="actividades" data-manual-section>
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
  <?php elseif(!$isExternal): ?>
  <section class="manual-section" id="actividades" data-manual-section>
    <div class="manual-section-head"><span>A</span><div><h2>Actividades y visitas</h2><p>Cuando soporte publique una atención programada para tu solicitud, aparecerá dentro del caso como <b>Próxima atención</b>.</p></div></div>
    <div class="manual-cards">
      <article><strong>Próxima atención</strong><p>Puede indicar el tipo de atención, estado, fecha programada, fin estimado, ubicación y el resumen que soporte preparó para ti.</p></article>
      <article><strong>Información segura</strong><p>Solo verás la información que el equipo de soporte decidió publicar. La preparación interna, responsables técnicos y detalles privados de trabajo no se muestran en tu solicitud.</p></article>
      <article><strong>Actividad y solicitud son diferentes</strong><p>Una visita o seguimiento puede finalizar y tu solicitud continuar abierta mientras el equipo completa la solución, validaciones o pasos pendientes.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($isSupport||$canManagement): ?>
  <section class="manual-section" id="agenda" data-manual-section>
    <div class="manual-section-head"><span>AG</span><div><h2>Agenda</h2><p>Consulta actividades programadas desde Calendario o Lista, siempre con el alcance que autoriza el sistema.</p></div></div>
    <div class="manual-cards">
      <?php if($isSupport): ?>
      <article><strong>Técnico</strong><p>Usa <b>Mis actividades</b> para tu carga directa y <b>Todo mi alcance</b> para revisar actividades visibles según parque, región, área o equipo. Filtra por responsable, parque, tipo, estado y rango de fechas.</p></article>
      <article><strong>Atrasadas y conflictos</strong><p>Las actividades atrasadas y los conflictos de horario se calculan a partir de <b>ticket_activities</b>; son señales para priorizar, no estados nuevos ni una agenda separada.</p></article>
      <article><strong>Abrir ticket y programar</strong><p>Cada elemento abre el ticket en <b>Actividades</b>. La programación operativa se hace vía ticket con <b>+ Programar actividad</b>; el ticket sigue siendo el espacio de trabajo.</p></article>
      <article><strong>Admin/Semiadmin</strong><p>Agenda muestra una consulta global visible dentro del alcance autorizado y conserva los mismos filtros para responsable, parque, tipo, estado, Calendario y Lista.</p></article>
      <?php endif; ?>
      <?php if($canManagement): ?>
      <article><strong>Gerencia/Supervisión</strong><p>Agenda es de consulta sin operar: puedes revisar carga, atrasos y conflictos dentro de tu alcance, pero no programar, iniciar, finalizar, cancelar ni reprogramar actividades.</p></article>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>
  <?php if($isSupport): ?>
  <section class="manual-section" id="calidad-proveedor" data-manual-section>
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
<?php if($isSupport): ?><section class="manual-section" id="soporte" data-manual-section><div class="manual-section-head"><span>03</span><div><h2>Centro de soporte</h2><p>Prioriza por alcance y tiempo, continúa la conversación y documenta correctamente esperas y solución.</p></div></div><div class="manual-cards"><article><strong>Cola de trabajo</strong><p><b>Todos</b> reúne los casos activos que puedes consultar según tu alcance. Usa <b>Míos</b>, <b>Sin asignar</b>, <b>Por vencer</b>, <b>Vencidos</b>, <b>En espera</b>, <b>Reabiertos</b> o <b>Críticos</b> para concentrarte.</p></article><article><strong>Clasificación ITSM</strong><p>El Helpdesk propone automáticamente Incidente o Solicitud de servicio, Impacto y Urgencia a partir de la categoría y lo descrito por el usuario. Impacto + Urgencia producen la prioridad inicial. Soporte debe validar la clasificación y, si ajusta manualmente la prioridad, dejar motivo y auditoría.</p></article><article><strong>Resolución objetivo</strong><p>El SLA muestra el tiempo restante, cuánto del objetivo ya se utilizó y una lectura operativa: Dentro de objetivo, Atención requerida, Próximo a vencer o Vencido.</p></article><article><strong>Tomar un caso</strong><p>Usa <b>Tomar</b> cuando realmente puedas iniciar trabajo. Solo puedes tomar casos disponibles dentro de tu alcance.</p></article><article><strong>En espera</strong><p>Selecciona la razón real: usuario, proveedor, compra, visita, aprobación u otra dependencia. Los informes conservan cuánto tiempo se acumuló en cada motivo aunque cambie durante el mismo caso.</p></article><article><strong>Respuesta y conversación interna</strong><p><b>Respuesta al usuario</b> es visible para el solicitante. <b>Conversación interna</b> es solo para el equipo de soporte.</p></article><article><strong>Resolver</strong><p>Registra qué encontraste, qué hiciste y cómo evitar que vuelva a ocurrir. Al resolver, el cierre final queda pendiente de confirmación del solicitante; IT puede reabrir si detecta que aún falta trabajo.</p></article></div></section><?php endif; ?>

  <?php if($canProblems||$canKnowledge): ?>
  <section class="manual-section" id="conocimiento" data-manual-section>
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

  <?php if($canManagement): ?><section class="manual-section" id="gestion" data-manual-section><div class="manual-section-head"><span>05</span><div><h2>Dashboard e informes</h2><p>Usa métricas para entender qué está pasando y los informes para explicar el detalle.</p></div></div><div class="manual-cards"><article><strong>Dashboard</strong><p>Analiza volumen, tiempos, carga y tendencias dentro de tu alcance.</p><a href="<?= APP_BASE_URL ?>/gestion">Abrir Dashboard →</a></article><article><strong>Informes</strong><p>Consulta tiempos, cambios de estado, esperas y solución. La exportación oficial es Excel (.xlsx); la hoja de esperas conserva los minutos históricos por cada motivo.</p><a href="<?= APP_BASE_URL ?>/gestion/informes">Abrir Informes →</a></article></div></section><?php endif; ?>

  <?php if($canManagement||$canAdmin): ?>
  <section class="manual-section" id="proveedores" data-manual-section>
    <div class="manual-section-head"><span>PR</span><div><h2>Informe de proveedores</h2><p>Consulta cómo participó cada colaborador externo en los casos compartidos sin mezclar administración de accesos con rendimiento operativo.</p></div></div>
    <div class="manual-cards">
      <article><strong>Participación por ciclo</strong><p>Cada vez que un proveedor recibe acceso a un ticket inicia un ciclo nuevo. El informe muestra asignación, primera respuesta, duración, actividad actual, tiempo declarado, respuestas, archivos e informes de ese ciclo.</p></article>
      <article><strong>Listo para revisión</strong><p>Cuando el proveedor marca <b>Listo para revisión</b>, informa que su trabajo está listo para que Carrousel lo valide. Esa acción no resuelve ni cierra automáticamente el ticket; el equipo interno decide el siguiente paso.</p></article>
      <article><strong>Devoluciones</strong><p>Una devolución se registra cuando, después de una entrega lista para revisión, el ticket vuelve a atención o se reabre. Las reaperturas anteriores a la entrega no se atribuyen al proveedor.</p></article>
      <article><strong>Filtros y Excel</strong><p>Filtra por proveedor, estado del ciclo, actividad y fechas. <b>Descargar Excel</b> utiliza los mismos filtros y métricas que ves en pantalla.</p><a href="<?= APP_BASE_URL ?>/admin/externos/informe">Abrir Informe de proveedores →</a></article>
    </div>
  </section>
  <?php endif; ?>
  <?php if($canAdmin): ?><section class="manual-section" id="administracion" data-manual-section><div class="manual-section-head"><span>06</span><div><h2>Administración</h2><p>Gestiona perfiles, alcance, proveedores, correo y trazabilidad sin exponer detalles técnicos al usuario final.</p></div></div><div class="manual-cards"><article><strong>Usuarios</strong><p>El perfil define qué puede hacer y la asignación indica dónde trabaja. <b>Responsable directo</b> representa a quién reporta organizacionalmente: no asigna tickets, no cambia permisos ni modifica el alcance.</p></article><article><strong>Proveedores</strong><p><b>Convertir entre usuario interno y proveedor externo</b> reutiliza la misma identidad y conserva historial. Comparte únicamente casos específicos mientras la persona actúe como proveedor y retira el acceso cuando termine su participación.</p></article><article><strong>Auditoría</strong><p>Consulta quién cambió qué y cuándo cuando necesites reconstruir una acción.</p></article><article><strong>Correo y notificaciones</strong><p>Comprueba el canal, envía una prueba y revisa entregas fallidas o pendientes.</p><a href="<?= APP_BASE_URL ?>/admin/correo">Abrir correo →</a></article></div></section><?php endif; ?>

  <section class="manual-section" id="preguntas" data-manual-section><div class="manual-section-head"><span>?</span><div><h2>Preguntas frecuentes</h2><p>Abre únicamente la respuesta que necesites.</p></div></div><div class="manual-faq">
    <details><summary>No sé dónde registrar o buscar un caso</summary><div>Para un caso nuevo usa <b>Solicitar ayuda</b>. Para algo ya registrado usa <b>Mis solicitudes</b> o el buscador global. Si trabajas en soporte, usa el Centro de soporte.</div></details>
    <details><summary>¿Cómo obtengo acceso para ver mis solicitudes?</summary><div>Las cuentas de acceso las crea Administración. Puedes reportar una solicitud sin iniciar sesión; cuando tu cuenta esté habilitada, entrarás con tu correo y un código OTP para consultar tu historial.</div></details>
    <details><summary>¿Qué información es importante al reportar?</summary><div>El formulario te da una sugerencia y un ejemplo según el tipo de ayuda. En general basta con indicar qué sucede, desde cuándo, dónde ocurre y a quién o qué equipo afecta. No necesitas conocer la causa técnica y nunca debes escribir tu contraseña.</div></details>
    <details><summary>¿Por qué ya aparece mi ubicación?</summary><div>Si tu cuenta tiene una única asignación activa a un parque, el Helpdesk la propone automáticamente. Usa <b>Reportar en otro lugar</b> únicamente cuando la solicitud corresponda a otra ubicación.</div></details>
    <?php if($isSupport): ?><details><summary>¿Qué significan los estados del SLA?</summary><div><b>Dentro de objetivo</b> indica que existe margen normal. <b>Atención requerida</b> aparece cuando ya se utilizó una parte importante del tiempo. <b>Próximo a vencer</b> marca los casos que deben priorizarse. <b>Vencido</b> indica que el objetivo de resolución ya fue superado.</div></details><details><summary>¿Cuándo debo poner un caso en espera?</summary><div>Solo cuando exista una dependencia real que impida continuar: respuesta del usuario, proveedor, compra, visita, aprobación u otra causa documentable. Si cambia la dependencia, actualiza el motivo; el historial conserva el tiempo de cada etapa.</div></details><details><summary>¿Quién cierra un caso resuelto?</summary><div>IT documenta la solución y marca el caso como resuelto. El cierre final se produce con la confirmación del solicitante. Si IT detecta que todavía falta trabajo, puede reabrir el caso.</div></details><details><summary>¿Respuesta al usuario o conversación interna?</summary><div>La respuesta al usuario forma parte del seguimiento visible. La conversación interna es exclusivamente para el equipo de soporte.</div></details><?php endif; ?>
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