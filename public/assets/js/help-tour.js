(function(){
  'use strict';

  const panel=document.querySelector('[data-help-panel]');
  if(!(panel instanceof HTMLElement))return;

  const rawContext=panel.dataset.helpContext||'general';
  let context=document.querySelector('.dashboard-support')?'support_dashboard':rawContext;
  if(rawContext==='reports'&&document.querySelector('.external-report-page'))context='external_report';
  else if(rawContext==='reports'&&document.querySelector('.support-team-page'))context='support_team';
  else if(rawContext==='ticket'&&document.querySelector('.external-problem-card'))context='ticket_external';
  else if(rawContext==='ticket'&&document.querySelector('.case-action-card, .suggested-solutions'))context='ticket_support';
  else if(rawContext==='ticket')context='ticket_requester';
  const TOUR_SEEN_KEY=`helpdesk:tour-seen:${context}`;

  const GUIDE_FIELDS=[
    ['see','Qué estás viendo'],
    ['purpose','Para qué sirve'],
    ['first','Qué hacer primero'],
    ['wait','Qué puede esperar'],
    ['next','Qué ocurre después'],
    ['mistakes','Errores comunes'],
    ['help','Dónde obtener más ayuda']
  ];

  const defaultGuide={
    see:'Esta pantalla reúne la información y acciones disponibles para tu perfil.',
    purpose:'Úsala para completar la tarea principal de esta sección sin salir del flujo.',
    first:'Empieza por leer el contexto y revisar los elementos que requieren atención.',
    wait:'Deja para después las acciones secundarias que no bloquean el trabajo actual.',
    next:'Cuando completes la acción principal, revisa el estado resultante y continúa desde la misma pantalla.',
    mistakes:'Evita duplicar acciones, cambiar datos sin contexto o usar controles que no correspondan al estado actual.',
    help:'Abre el botón ? para volver a esta guía o entra a Abrir manual aquí para ver el procedimiento completo.'
  };

  const tourGuides={
    public_home:{
      see:'Estás en la entrada pública del Helpdesk.',
      purpose:'Sirve para crear una solicitud nueva o continuar una que ya existe.',
      first:'Decide si vas a reportar algo nuevo o consultar un caso existente.',
      wait:'No necesitas iniciar sesión ni conocer términos técnicos para empezar.',
      next:'Si reportas, recibirás un número de caso; si consultas, validaremos tu correo.',
      mistakes:'No crees un ticket nuevo solo para agregar información a uno que ya existe.',
      help:'Usa el botón ? de esta pantalla; si ya tienes un caso, continúa desde Mis solicitudes.'
    },
    public_create:{
      see:'Estás en el formulario para reportar un problema.',
      purpose:'Sirve para contar qué ocurre con lenguaje cotidiano y generar un caso trazable.',
      first:'Confirma tus datos y la ubicación antes de describir el problema.',
      wait:'La causa técnica, categoría exacta o diagnóstico pueden esperar; soporte los ajustará si hace falta.',
      next:'Al enviar recibirás el número del caso y podrás seguir respuestas desde Mis solicitudes.',
      mistakes:'No escribas contraseñas, no inventes datos y no pulses Enviar varias veces.',
      help:'Vuelve al botón ? para repasar el recorrido o consulta al equipo de Sistemas si no puedes completar el formulario.'
    },
    requester_home:{
      see:'Estás en tu espacio personal de soporte.',
      purpose:'Sirve para crear solicitudes y continuar las que ya están abiertas.',
      first:'Revisa primero solicitudes recientes o pendientes de confirmación.',
      wait:'Una solicitud nueva puede esperar si el problema ya está registrado en otro caso.',
      next:'Al abrir un caso verás conversación, archivos, próximas atenciones y solución.',
      mistakes:'Evita duplicar tickets por el mismo problema.',
      help:'Usa el botón ? o abre el Manual para Solicitante.'
    },
    external_home:{
      see:'Estás en el espacio de colaboración asignado a tu cuenta.',
      purpose:'Sirve para trabajar únicamente los casos que el equipo interno compartió contigo.',
      first:'Abre el caso activo y revisa exactamente qué apoyo se necesita.',
      wait:'No necesitas revisar información interna que no fue compartida contigo.',
      next:'Responde, adjunta evidencia y deja el caso listo para revisión cuando corresponda.',
      mistakes:'No marques trabajo como listo si todavía faltan evidencias o una respuesta solicitada.',
      help:'Usa el botón ? o abre el Manual para Colaborador.'
    },
    my_tickets:{
      see:'Estás viendo los casos asociados a tu cuenta.',
      purpose:'Sirve para encontrar una solicitud y continuar su seguimiento.',
      first:'Busca confirmaciones pendientes o el caso que tenga una novedad reciente.',
      wait:'Los casos cerrados pueden revisarse después si no requieren ninguna acción.',
      next:'Al abrir un caso verás su conversación, archivos, estado y solución.',
      mistakes:'No abras otro ticket para responder algo que pertenece a un caso existente.',
      help:'Usa el botón ? o Abrir manual aquí para ver el flujo de seguimiento.'
    },
    support_dashboard:{
      see:'Estás en el centro de trabajo del equipo de soporte.',
      purpose:'Sirve para priorizar la atención antes de entrar al detalle de cada caso.',
      first:'Empieza por Requiere acción ahora y después continúa tus casos ya activos.',
      wait:'Actividad histórica, aprendizaje y métricas pueden esperar si existe un SLA crítico o vencido.',
      next:'Después de priorizar, abre el ticket y trabaja desde su flujo normal.',
      mistakes:'No tomes casos nuevos si ya tienes trabajo activo sin atender.',
      help:'Usa el botón ? o el Manual de Soporte para profundizar en SLA, esperas y resolución.'
    },
    support_center:{
      see:'Estás en la cola operativa de tickets.',
      purpose:'Sirve para encontrar qué caso atender según responsabilidad, SLA, impacto y estado.',
      first:'Usa filtros rápidos y revisa tus casos antes de tomar uno disponible.',
      wait:'Los filtros avanzados pueden esperar hasta que necesites una combinación específica.',
      next:'Al abrir o tomar un caso, continúa la atención desde la vista del ticket.',
      mistakes:'No priorices solo por antigüedad ni pongas En espera sin una dependencia real.',
      help:'Usa el botón ? o la sección Soporte del Manual.'
    },
    ticket_support:{
      see:'Estás en el espacio operativo completo de un ticket.',
      purpose:'Sirve para entender el problema, conversar, coordinar trabajo y documentar una solución trazable.',
      first:'Lee el problema reportado y revisa referencias o recurrencias antes de ejecutar acciones.',
      wait:'Acciones secundarias, archivo o conocimiento pueden esperar hasta entender el caso.',
      next:'Después de conversar o intervenir, documenta causa, solución y prevención antes de resolver.',
      mistakes:'No mezcles conversación pública con interna, no resuelvas sin documentación y no uses una referencia sin revisarla.',
      help:'Usa el botón ?; Abrir manual aquí lleva a Soporte y Preguntas de esta pantalla abre la FAQ relacionada.'
    },
    ticket_external:{
      see:'Estás viendo un caso compartido con tu cuenta de colaborador.',
      purpose:'Sirve para responder, adjuntar evidencia y completar la intervención solicitada.',
      first:'Lee el contexto visible y qué necesita el equipo interno.',
      wait:'La administración del ticket, notas internas y decisiones de cierre corresponden al equipo interno.',
      next:'Cuando tu intervención esté completa, deja evidencia y marca Listo para revisión cuando aplique.',
      mistakes:'No asumas que Listo para revisión resuelve o cierra el ticket.',
      help:'Usa el botón ? o el Manual para Colaborador.'
    },
    ticket_requester:{
      see:'Estás viendo el detalle de tu solicitud.',
      purpose:'Sirve para consultar respuestas, aportar información y revisar la solución.',
      first:'Lee el último seguimiento y verifica si soporte necesita algo de ti.',
      wait:'No necesitas interpretar notas técnicas o procesos internos que no aparecen en tu vista.',
      next:'Cuando exista una solución podrás confirmarla o devolver el caso a soporte si el problema continúa.',
      mistakes:'No abras otro ticket para responder sobre esta misma solicitud.',
      help:'Usa el botón ? o el Manual para Solicitante.'
    },
    management:{
      see:'Estás en el Dashboard interno de consulta.',
      purpose:'Sirve para entender volumen, tiempos, carga y tendencias dentro de tu alcance.',
      first:'Aplica período y filtros antes de interpretar indicadores.',
      wait:'El detalle caso por caso puede esperar hasta que un indicador necesite explicación.',
      next:'Abre Informes para profundizar en el dato que quieras explicar.',
      mistakes:'No compares métricas de períodos o alcances distintos como si fueran equivalentes.',
      help:'Usa el botón ? o la sección Gestión del Manual.'
    },
    reports:{
      see:'Estás en el Centro de informes.',
      purpose:'Sirve para explicar indicadores con detalle de Tickets/SLA, Agenda, Proveedores, Equipo y Conocimiento.',
      first:'Define el período y los filtros antes de leer las métricas.',
      wait:'La exportación puede esperar hasta que confirmes que el filtro representa lo que quieres analizar.',
      next:'Abre el módulo especializado o exporta Excel conservando el mismo conjunto filtrado.',
      mistakes:'No interpretes un KPI fuera de su período, alcance o filtro.',
      help:'Usa el botón ? o Abrir manual aquí para la guía de Gestión.'
    },
    external_report:{
      see:'Estás en el informe especializado de proveedores.',
      purpose:'Sirve para revisar participación, respuesta, entregas, devoluciones y calidad por ciclo.',
      first:'Selecciona período y proveedor antes de comparar resultados.',
      wait:'La administración de accesos del proveedor puede esperar; este informe es de rendimiento operativo.',
      next:'Profundiza en el ciclo o descarga Excel con los mismos filtros.',
      mistakes:'No atribuyas una reapertura al proveedor si ocurrió antes de su entrega lista para revisión.',
      help:'Usa el botón ? o la sección Informe de proveedores del Manual.'
    },
    support_team:{
      see:'Estás en la vista especializada del equipo de soporte.',
      purpose:'Sirve para revisar carga actual y desempeño del período sin mezclar ambos conceptos.',
      first:'Define el período y revisa volumen, resueltos, primera respuesta y NPS.',
      wait:'La administración de integrantes puede esperar si solo estás analizando desempeño.',
      next:'Usa el detalle por integrante o exporta Excel conservando el período visible.',
      mistakes:'No confundas carga actual con métricas históricas del período.',
      help:'Usa el botón ? o la sección Gestión del Manual.'
    },
    agenda:{
      see:'Estás en la Agenda de actividades.',
      purpose:'Sirve para consultar planificación, atrasos, conflictos y trabajo programado dentro de tu alcance.',
      first:'Elige Calendario o Lista y filtra por fecha, responsable, parque o estado según lo que necesites.',
      wait:'La edición de una actividad puede esperar hasta abrir el ticket; Agenda es principalmente la vista de planificación.',
      next:'Abre el caso relacionado para programar, reprogramar, iniciar, finalizar o cancelar si tu perfil puede hacerlo.',
      mistakes:'No confundas el estado de una actividad con el estado del ticket.',
      help:'Usa el botón ? o la sección Agenda del Manual.'
    },
    problems:{
      see:'Estás en Problemas conocidos.',
      purpose:'Sirve para agrupar recurrencias y documentar causa, alternativa y solución.',
      first:'Busca si el problema ya existe antes de crear uno nuevo.',
      wait:'La solución definitiva puede esperar mientras exista una alternativa segura y la investigación continúe.',
      next:'Relaciona tickets y conocimiento para mantener la recurrencia y reutilización actualizadas.',
      mistakes:'No crees problemas separados para la misma falla solo porque ocurre en tickets distintos.',
      help:'Usa el botón ? o la sección Conocimiento del Manual.'
    },
    knowledge:{
      see:'Estás en la Base de conocimiento versionada.',
      purpose:'Sirve para documentar y reutilizar soluciones sin sobrescribir versiones publicadas.',
      first:'Busca un artículo existente antes de crear un borrador nuevo.',
      wait:'La publicación para solicitantes puede esperar hasta que la versión ya esté validada para soporte.',
      next:'Envía el borrador a revisión; publicar internamente y hacerlo público son decisiones separadas.',
      mistakes:'No edites como si una versión publicada pudiera sobrescribirse directamente y no restaures una versión antigua como vigente sin revisión.',
      help:'Usa el botón ? o Abrir manual aquí para ver el flujo editorial completo.'
    },
    users:{
      see:'Estás en la administración de usuarios internos.',
      purpose:'Sirve para dar acceso, editar perfil, asignación y responsable organizacional.',
      first:'Busca si la persona ya existe antes de crear un acceso nuevo.',
      wait:'Cambios no relacionados con el acceso actual pueden esperar hasta validar su necesidad.',
      next:'Después de modificar acceso o asignación, revisa que el perfil y alcance resultantes sean correctos.',
      mistakes:'No dupliques personas ni confundas responsable directo con asignación de tickets o permisos.',
      help:'Usa el botón ? o la sección Administración del Manual.'
    },
    externals:{
      see:'Estás en la administración de colaboradores externos.',
      purpose:'Sirve para registrar proveedores y compartir únicamente los casos en los que deben participar.',
      first:'Verifica que la persona no exista ya como usuario interno o externo.',
      wait:'Plantillas y datos secundarios pueden esperar si primero necesitas controlar un acceso activo.',
      next:'Comparte el caso concreto y revoca el acceso cuando termine la participación.',
      mistakes:'No dupliques identidades ni dejes accesos vigentes después de finalizar la colaboración.',
      help:'Usa el botón ? o la sección Administración del Manual.'
    },
    mail:{
      see:'Estás en Correo y notificaciones.',
      purpose:'Sirve para comprobar SMTP, revisar entregas y reintentar fallos de forma controlada.',
      first:'Revisa si el canal está activo o en modo de prueba y valida con un correo de prueba.',
      wait:'Un reintento puede esperar hasta corregir la causa del fallo.',
      next:'Después de una prueba exitosa, revisa el registro para confirmar el estado real de entrega.',
      mistakes:'No reintentes OTP antiguos ni interpretes Modo prueba como correo enviado a Internet.',
      help:'Usa el botón ? o la FAQ de Correo del Manual.'
    },
    audit:{
      see:'Estás en Auditoría.',
      purpose:'Sirve para reconstruir quién hizo qué, cuándo y sobre qué registro.',
      first:'Filtra por persona, acción, fecha u origen antes de abrir detalles.',
      wait:'La revisión masiva de eventos puede esperar si ya conoces el caso o acción que investigas.',
      next:'Abre el evento relevante y compara valores anteriores/nuevos cuando existan.',
      mistakes:'No uses Auditoría como bandeja operativa diaria ni expongas información técnica al usuario final.',
      help:'Usa el botón ? o la sección Administración del Manual.'
    },
    manual:{
      see:'Estás dentro del Manual interactivo.',
      purpose:'Sirve para encontrar procedimientos por perfil, tema o pregunta sin cargar las pantallas principales de texto.',
      first:'Usa los accesos rápidos, el buscador o el filtro por tema.',
      wait:'No necesitas leer todo el manual de principio a fin.',
      next:'Abre la función real desde los enlaces del Manual cuando ya tengas claro el procedimiento.',
      mistakes:'No sigas instrucciones de una función que tu perfil no tiene disponible.',
      help:'El propio botón ? inicia este recorrido; las FAQ y enlaces profundos te llevan al contenido específico.'
    },
    search:{
      see:'Estás en la búsqueda global.',
      purpose:'Sirve para encontrar tickets, problemas y conocimiento desde un solo lugar.',
      first:'Busca por número, persona, parque, categoría o términos del problema.',
      wait:'Los filtros más específicos pueden esperar hasta revisar el tipo de resultado.',
      next:'Abre el resultado que mejor responda a tu objetivo.',
      mistakes:'No asumas que todos los resultados son visibles para todos los perfiles; la búsqueda respeta permisos.',
      help:'Usa el botón ? o el Manual para revisar cómo encontrar información.'
    },
    general:{...defaultGuide}
  };

  const tours={
    public_home:[
      ['.public-brand','Inicio del Helpdesk','Desde aquí puedes crear una solicitud nueva o consultar algo que ya reportaste.','No necesitas iniciar sesión para registrar una solicitud.'],
      ['.choice-grid','Elige qué quieres hacer','Usa Crear solicitud para un caso nuevo o Ver mis solicitudes para continuar uno existente.','Evita crear otro ticket cuando solo necesitas agregar información a uno que ya existe.'],
      ['.help-note','Seguimiento seguro','Para consultar solicitudes confirmaremos tu correo con un código temporal.','El código protege la información de tus casos.']
    ],
    public_create:[
      ['.public-request-heading','Antes de empezar','Esta pantalla sirve para pedir ayuda sin aprender términos técnicos. Completa únicamente lo que conozcas.','Describe hechos concretos; no necesitas conocer la causa.'],
      ['[data-public-step="requester"]','Tus datos','Usa un nombre y correo que puedas consultar. Ahí recibirás el número de caso y las novedades.','El teléfono es opcional y ayuda si necesitamos contactarte rápidamente.'],
      ['[data-public-step="location"]','Dónde ocurre','Confirma la ubicación o cámbiala si reportas algo de otro parque o área.','Si no estás seguro, es mejor dejar el dato sin definir que inventarlo.'],
      ['#requester_topic','¿En qué necesitas ayuda?','Elige la opción que más se parezca a lo que necesitas. El catálogo se mantiene actualizado y usa lenguaje cotidiano.','No hace falta encontrar una opción perfecta; soporte puede ajustar la clasificación después.'],
      ['#description','Cuéntanos qué está pasando','Explica qué sucede, desde cuándo, a quién o qué equipo afecta y qué intentaste si ya hiciste alguna prueba.','Incluye mensajes de error si los conoces, pero nunca escribas contraseñas.'],
      ['[data-public-step="submit"]','Enviar y dar seguimiento','Al enviar recibirás un número de caso. Después podrás consultar avances desde Mis solicitudes.','Pulsa Enviar una sola vez; el sistema evita envíos duplicados mientras procesa.']
    ],
    requester_home:[
      ['.dashboard-primary-row','Tu espacio','Desde aquí puedes crear una solicitud nueva y entrar a tus casos.','Usa Nueva solicitud solo para algo diferente a lo que ya está reportado.'],
      ['.dashboard-requester-grid','Resumen personal','Aquí ves cuántas solicitudes siguen abiertas y tu estructura organizacional cuando aplica.','Tu responsable directo es una referencia organizacional; no asigna tus tickets.'],
      ['.dashboard-activity-card','Solicitudes recientes','Abre el caso que necesites continuar para ver respuestas, archivos y solución.','La campanita también te lleva directamente a las novedades relevantes.']
    ],
    external_home:[
      ['.dashboard-primary-row','Tu espacio de colaboración','Aquí aparecen únicamente los casos que Carrousel comparte con tu cuenta.','No tienes acceso al resto de solicitudes internas.'],
      ['.dashboard-requester-grid','Resumen de casos','Consulta activos y finalizados antes de abrir un caso.','Participa solo mientras tu apoyo sea necesario.'],
      ['.dashboard-activity-card','Casos recientes','Abre un caso para responder o adjuntar evidencia según los permisos habilitados.','Cuando tu participación termine, el acceso se revoca pero queda trazabilidad.']
    ],
    my_tickets:[
      ['.tickets-heading','Mis solicitudes o casos','Esta lista reúne únicamente lo que corresponde a tu cuenta.','Usa el mismo caso para continuar una conversación existente.'],
      ['.ticket-review-notice','Confirmaciones pendientes','Si una solución necesita tu confirmación, aparecerá destacada aquí.','Confirma solo después de revisar la solución registrada.'],
      ['.external-case-overview, .ticket-list','Estado y seguimiento','Abre una tarjeta para revisar el problema, estado, responsable y conversación.','Las notificaciones importantes también aparecen en la campanita.']
    ],
    support_dashboard:[
      ['.dashboard-primary-row','Centro de trabajo','Aquí tienes los accesos frecuentes y el objetivo operativo de la pantalla.','Empieza por lo que requiere acción; evita tomar casos nuevos si ya tienes trabajo activo.'],
      ['.dashboard-action-now','Requiere acción ahora','Empieza por SLA vencidos, próximos a vencer y casos críticos.','Estos indicadores sirven para priorizar, no solo para medir volumen.'],
      ['.dashboard-work-grid','Mi trabajo y cola','Continúa primero tus casos activos. Toma nuevos casos únicamente cuando puedas atenderlos.','La cola muestra disponibilidad; tus casos muestran responsabilidad actual.'],
      ['.dashboard-external-collab','Colaboración externa','Si un proveedor participa, aquí ves casos activos, esperas y cuántos colaboradores están apoyando.','Úsalo para detectar dependencias externas sin abrir cada caso.'],
      ['.dashboard-itsm-card','Aprendizaje','Problemas conocidos y conocimiento convierten recurrencias y resoluciones en información reutilizable.','Antes de empezar desde cero, revisa si ya existe una solución aplicable.'],
      ['.dashboard-activity-card','Actividad reciente','Muestra movimientos operativos recientes, no la auditoría técnica completa.','Úsala para entender qué cambió sin revisar registros administrativos.']
    ],
    support_center:[
      ['.queue-quick-filters','Filtros rápidos','Separa de inmediato tus casos, disponibles, críticos, vencidos o en espera.','Usa estos filtros para el trabajo diario; los avanzados quedan para búsquedas específicas.'],
      ['.queue-filters-panel','Filtros avanzados','Despliega esta sección solo cuando necesites combinar parque, categoría, prioridad, estado o responsable.','Puedes combinar criterios sin perder los filtros rápidos.'],
      ['.queue-table-card','Cola','Lee primero asunto y problema reportado. El tiempo objetivo y responsable ayudan a decidir qué atender.','No tomes un caso únicamente por antigüedad: considera impacto, tiempo y tu carga actual.']
    ],
    ticket:[
      ['.case-problem-card, .external-problem-card, .ticket-problem-focus','Problema reportado','Este bloque es la fuente principal: entiende qué pasó antes de ejecutar acciones.','La descripción tiene más valor operativo que el número del caso o la categoría.'],
      ['.ticket-problem-link','Problema conocido','Relaciona recurrencias y aprovecha una solución temporal o definitiva ya documentada.','Si el mismo fallo ocurre varias veces, relaciónalo en vez de documentarlo desde cero.'],
      ['.suggested-solutions','Posibles soluciones','Revisa conocimiento, problemas conocidos y casos resueltos similares antes de empezar desde cero. Usa “Usar como referencia” cuando una opción realmente aplique.','La referencia solo precarga información y deja trazabilidad: no resuelve automáticamente el ticket y puedes editar causa, solución y prevención.'],
      ['.case-action-card','Acciones','La acción principal cambia según el estado. Las opciones menos frecuentes quedan en segundo plano.','Usa En espera solo cuando exista una dependencia real y registra la razón.'],
      ['.case-conversation-card, .external-conversation-card, #conversacion','Conversaciones','El seguimiento distingue lo visible para el solicitante, la participación de proveedores y la conversación interna del equipo.','La conversación interna nunca se muestra ni se envía al solicitante o proveedor.'],
      ['.external-reply-card','Responder como proveedor','Si colaboras como proveedor, usa este espacio para enviar avance, consulta o evidencia.','Comparte únicamente la información necesaria para continuar el caso.'],
      ['.resolution-card, .ticket-resolution-card, .case-resolution-capture, .external-solution-card','Solución','Cuando el caso se resuelve, la solución queda visible en un bloque fácil de encontrar.','Una buena resolución alimenta informes y conocimiento para futuros casos.']
    ],
    reports:[
      ['.report-module-hero','Centro de informes','Esta pantalla reúne el análisis transversal del Helpdesk sin sustituir los informes especializados.','Empieza confirmando el período y el alcance con el que estás trabajando.'],
      ['.report-catalog-grid','Módulos disponibles','Entra a Equipo, Proveedores, Agenda o Conocimiento cuando necesites profundizar.','El catálogo solo muestra módulos disponibles para tu perfil.'],
      ['.report-summary-grid','Resumen de Tickets y SLA','Lee volumen, documentación y tiempos del conjunto filtrado.','Los KPIs usan todo el conjunto; la tabla web puede limitar solo el detalle visual.'],
      ['.report-filterbar','Filtros','Acota por período, parque, categoría, responsable, estado o prioridad.','Aplica filtros antes de comparar responsables o ubicaciones.'],
      ['.report-table-card','Detalle operativo','Reconstruye cada caso con contexto, tiempos, cambios de estado y solución.','Excel conserva el filtro completo incluso cuando la tabla visual muestra solo los casos más recientes.']
    ],
    agenda:[
      ['.agenda-toolbar','Agenda y período','El encabezado reúne vista, período y accesos de planificación.','Elige primero la vista que mejor responda a tu tarea.'],
      ['.agenda-view-switch','Calendario o Lista','Cambia la forma de leer las mismas actividades sin alterar sus datos.','Calendario ayuda a distribuir carga; Lista facilita revisar detalle.'],
      ['.agenda-filter-card','Filtros','Acota por fecha, responsable, parque, tipo o estado según tu alcance.','Filtra antes de interpretar atrasos o conflictos.'],
      ['.agenda-overdue, .agenda-month, .agenda-calendar, .agenda-list','Planificación visible','Aquí aparecen actividades, atrasos y conflictos dentro del filtro actual.','Un conflicto es una señal de coordinación, no un nuevo estado del ticket.'],
      ['.agenda-program-card','Programar desde un ticket','Cuando esta opción está disponible puedes localizar un caso y preparar una actividad.','La operación completa y su trazabilidad siguen ligadas al ticket.']
    ],
    external_report:[
      ['.external-report-head','Informe de proveedores','Esta vista analiza participación externa sin mezclarla con administración de accesos.','Empieza confirmando el período que estás analizando.'],
      ['.external-report-filters','Filtros del informe','Filtra proveedor, estado del ciclo, actividad, valoración o fecha.','Compara solo ciclos bajo condiciones equivalentes.'],
      ['.external-report-summary','Resumen ejecutivo','Revisa proveedores, participaciones, respuesta, entregas, devoluciones y calidad.','Usa estos datos como entrada al detalle, no como conclusión aislada.'],
      ['.data-table-shell','Detalle por ciclo','Cada fila conserva el ciclo de colaboración y sus métricas operativas.','Una nueva asignación inicia un ciclo nuevo y no debe mezclarse con el anterior.'],
      ['.external-report-head .btn-primary','Exportar','Descarga Excel cuando el filtro ya represente exactamente el análisis que necesitas.','El archivo conserva el mismo período, filtros y alcance.']
    ],
    support_team:[
      ['.support-team-head','Equipo de soporte','Esta pantalla separa análisis del equipo y administración de integrantes.','Empieza por el período antes de leer desempeño.'],
      ['.support-period-card','Desempeño del período','Tickets, resueltos, primera respuesta, resolución y NPS se calculan sobre las fechas seleccionadas.','No mezcles estas métricas con la carga actual.'],
      ['.support-team-summary','Carga actual','Aquí ves casos activos, en proceso y en espera del equipo en este momento.','Carga actual y desempeño histórico responden preguntas distintas.'],
      ['.data-table-shell','Detalle por integrante','Revisa carga, tiempos y satisfacción por persona cuando necesites profundizar.','Evita comparar integrantes sin considerar alcance, volumen y tipo de casos.'],
      ['.support-routing-card','Composición del equipo','La administración de integrantes está separada del análisis de desempeño.','Retira o agrega integrantes solo cuando corresponda organizacionalmente.']
    ],
    management:[
      ['.mgmt-head','Dashboard interno','El encabezado concentra el alcance y las acciones principales.','Gerencia y Supervisión consultan información; no necesitan convertirse en técnicos.'],
      ['.mgmt-filterbar','Filtros de gestión','Usa filtros antes de interpretar métricas o comparar responsables y parques.','El alcance de tu perfil se aplica además de los filtros visibles.'],
      ['.mgmt-kpis','Indicadores','Lee estas métricas como resumen del filtro actual.','Abre Informes cuando necesites explicar el detalle detrás de un indicador.'],
      ['.dashboard-external-collab','Proveedores','Este resumen muestra dependencias y participación externa dentro de tu alcance.','Sirve para saber dónde una respuesta externa puede estar deteniendo el avance.']
    ],
    problems:[
      ['.page-heading','Problemas conocidos','Agrupa recurrencias para investigar causa y compartir soluciones temporales o permanentes.','Un problema puede existir mientras la causa todavía está en investigación.'],
      ['.itsm-filter-grid','Filtros','Busca por número, título, estado, categoría, parque o propietario.','Filtra antes de revisar recurrencias cuando la lista crezca.'],
      ['.itsm-table','Recurrencias','Abre un problema para ver casos relacionados, solución temporal, solución definitiva y conocimiento asociado.','Relacionar tickets mantiene la recurrencia actualizada.']
    ],
    knowledge:[
      ['.page-heading','Qué estás viendo','La Base de conocimiento reúne soluciones reutilizables y conserva sus versiones.','Empieza buscando si ya existe una solución antes de crear otra.'],
      ['.itsm-filter-grid','Encuentra primero','Filtra por estado, categoría o texto para localizar el artículo correcto.','Un borrador o una versión En revisión no reemplaza la versión que ya usa soporte.'],
      ['.itsm-table, .knowledge-grid','Artículos','Abre el artículo que responda al problema que estás investigando.','Publicado para soporte y Disponible para solicitantes son pasos distintos.'],
      ['.itsm-editor-form','Crear borrador','Documenta problema, causa, solución, pasos y prevención. Después usa Enviar a revisión.','Guardar o editar un borrador nunca publica automáticamente.'],
      ['.knowledge-state-bar','Qué ocurre después','La revisión puede volver a borrador o quedar Publicado para soporte. La publicación para solicitantes se decide después.','No publiques una versión solo para quitarla de pendientes; primero valida su contenido.'],
      ['.knowledge-actions-menu','Acciones menos frecuentes','Aquí aparecen devolución, publicación para solicitantes y archivo cuando tu perfil puede hacerlo.','Si necesitas una versión anterior, usa Comparar versiones y Restaurar versión desde el historial; restaurar crea un borrador nuevo.'],
      ['.suggested-solutions','Usar como referencia','Desde un ticket puedes usar conocimiento, problemas o casos anteriores como referencia sin resolver automáticamente el caso.','Revisa la precarga antes de guardar la solución; causa, solución y prevención siguen editables.']
    ],
    search:[
      ['.search-page-form','Búsqueda global','Busca ticket, problema o artículo desde un solo lugar.','Puedes usar número, asunto, términos del problema, parque o categoría.'],
      ['.search-result-section','Resultados agrupados','Los resultados se separan por tipo y respetan permisos.','Abre primero el tipo de resultado que responda mejor a lo que buscas.']
    ],
    manual:[
      ['.manual-search','Buscar en el manual','Escribe una tarea, duda o palabra clave y la guía ocultará lo que no coincide.','Prueba con “notificación”, “resolver”, “proveedor”, “espera”, “usuario” o “informe”.'],
      ['.manual-quick-grid','Empieza por tu objetivo','Estas tarjetas te llevan directamente a las tareas más frecuentes disponibles para tu perfil.','No necesitas leer el manual completo de principio a fin.'],
      ['.manual-index','Índice','Salta directamente al tema que necesitas.','El índice permanece visible mientras recorres la guía en pantallas grandes.'],
      ['.manual-faq','Preguntas frecuentes','Abre únicamente la duda que necesites resolver.','Las respuestas explican decisiones de uso, no detalles técnicos internos.'],
      ['.manual-section','Guía por función','El manual se adapta al perfil conectado y evita mostrar funciones que no corresponden.','Cada sección explica propósito, flujo y acciones relacionadas.']
    ],
    general:[
      ['.page-heading, .mgmt-head, .content','Esta pantalla','Revisa primero el título, contexto y datos principales disponibles para tu perfil.','El tutorial se adapta a los elementos realmente visibles.'],
      ['.content','Continúa desde aquí','Usa la acción principal de la pantalla y deja las opciones secundarias para cuando realmente hagan falta.','Si necesitas un procedimiento completo, usa Abrir manual aquí desde el botón ?.']
    ],
    users:[
      ['.admin-users-heading','Usuarios','Desde aquí administras accesos internos sin crear identidades duplicadas.','Una misma persona puede cambiar entre acceso interno y externo conservando historial.'],
      ['.admin-access-toggle','Dar acceso','Crea un acceso interno nuevo únicamente cuando el correo todavía no existe en Helpdesk.','Si ya es proveedor externo, conviértelo nuevamente en usuario interno.'],
      ['.admin-user-toolbar','Buscar y filtrar','Encuentra rápidamente usuarios por nombre, perfil o estado.','Filtra antes de abrir fichas cuando existan muchos usuarios.'],
      ['.admin-user-table','Asignación y responsable','La asignación indica dónde trabaja la persona; Responsable directo representa la jerarquía organizacional.','Responsable directo no asigna tickets, no cambia permisos ni modifica el alcance.']
    ],
    externals:[
      ['.external-admin-kpis','Resumen','Controla proveedores registrados, casos compartidos y accesos activos.','Un proveedor solo debe conservar acceso mientras su participación sea necesaria.'],
      ['.external-share-primary','Compartir un caso','Selecciona un proveedor activo y un caso específico.','Compartir un caso no convierte al proveedor en usuario interno.'],
      ['.external-provider-create','Registrar proveedor','Crea una cuenta externa para una empresa o colaborador que necesite participar en casos concretos.','Si la persona ya existe como usuario interno, conviértela en vez de duplicarla.'],
      ['[data-external-directory]','Directorio','Edita, desactiva o convierte nuevamente un proveedor a usuario interno cuando corresponda.','La conversión conserva su historial.'],
      ['.external-access-card','Casos compartidos','Retira el acceso cuando termine la participación externa.','La revocación conserva trazabilidad.']
    ],
    mail:[
      ['.mail-admin-page .audit-stats','Estado del canal','Aquí distingues un envío SMTP real del modo de prueba y ves fallos o pendientes.','En modo de prueba los mensajes se registran, pero no salen a Internet.'],
      ['.mail-admin-page .card form','Correo de prueba','Envía una prueba a una cuenta que puedas revisar antes de depender del correo en operación.','Una prueba exitosa confirma conexión y autenticación del servidor SMTP.'],
      ['.mail-admin-page .audit-log-card','Entregas recientes','Cada movimiento queda registrado como Enviado, Falló, Pendiente o Modo prueba.','Si un correo falla, revisa el motivo y reintenta solo después de corregir la causa.']
    ],
    audit:[
      ['.page-heading, .mgmt-head','Auditoría','Consulta trazabilidad cuando necesites investigar un cambio.','No es una bandeja de trabajo diario; úsala para reconstruir acciones.'],
      ['.audit-filters, .mgmt-filterbar','Filtros','Acota por persona, acción, fecha u origen antes de revisar detalle.','Filtrar reduce ruido y facilita encontrar el evento relevante.']
    ]
  };

  let active=false;
  let steps=[];
  let index=0;
  let currentElement=null;
  let overlay=null;
  let popover=null;

  function isVisible(el){
    if(!(el instanceof HTMLElement))return false;
    const style=window.getComputedStyle(el);
    if(style.display==='none'||style.visibility==='hidden')return false;
    const rect=el.getBoundingClientRect();
    return rect.width>0&&rect.height>0;
  }

  function guideFor(key){
    return {...defaultGuide,...(tourGuides[key]||tourGuides[rawContext]||tourGuides.general)};
  }

  function distributeGuide(found,key){
    if(!found.length)return found;
    const guide=guideFor(key);
    const items=GUIDE_FIELDS.map(([field,label])=>({field,label,text:guide[field]||defaultGuide[field]}));
    found.forEach((step,stepIndex)=>{
      const start=Math.floor((stepIndex*items.length)/found.length);
      const end=Math.floor(((stepIndex+1)*items.length)/found.length);
      step.guideItems=items.slice(start,Math.max(start+1,end));
    });
    return found;
  }

  function resolveTour(){
    const source=tours[context]||tours[rawContext]||tours.general||[];
    const found=[];const missing=[];
    source.forEach(([selector,title,text,tip])=>{
      const el=document.querySelector(selector);
      if(isVisible(el))found.push({el,title,text,tip:tip||'',selector});
      else missing.push({selector,title});
    });
    distributeGuide(found,context);
    return{steps:found,missing,total:source.length};
  }

  function resolveSteps(){return resolveTour().steps;}

  function ensureUi(){
    if(overlay&&popover)return;
    overlay=document.createElement('div');
    overlay.className='tour-overlay';
    overlay.addEventListener('click',stop);
    popover=document.createElement('aside');
    popover.className='tour-popover';
    popover.setAttribute('role','dialog');
    popover.setAttribute('aria-modal','true');
    popover.setAttribute('aria-live','polite');
    popover.tabIndex=-1;
    document.body.append(overlay,popover);
  }

  function positionPopover(el){
    if(!popover||!isVisible(el))return;
    const r=el.getBoundingClientRect();
    const viewport=window.visualViewport;
    const viewportWidth=viewport?.width||window.innerWidth;
    const viewportHeight=viewport?.height||window.innerHeight;
    const viewportTop=viewport?.offsetTop||0;
    const viewportLeft=viewport?.offsetLeft||0;
    const width=Math.min(440,viewportWidth-24);
    popover.style.width=`${width}px`;
    const h=popover.offsetHeight||250;
    let top=r.bottom+14;
    if(top+h>viewportTop+viewportHeight-12)top=Math.max(viewportTop+12,r.top-h-14);
    let left=r.left;
    if(left+width>viewportLeft+viewportWidth-12)left=viewportLeft+viewportWidth-width-12;
    left=Math.max(viewportLeft+12,left);
    popover.style.top=`${Math.round(top)}px`;
    popover.style.left=`${Math.round(left)}px`;
  }

  function render(){
    if(!active||!steps.length)return;
    const step=steps[index];
    currentElement?.classList.remove('tour-highlight');
    currentElement=step.el;
    currentElement.classList.add('tour-highlight');
    currentElement.scrollIntoView({behavior:'auto',block:'center',inline:'nearest'});
    if(!popover)return;
    popover.innerHTML=`
      <div class="tour-progress">Paso ${index+1} de ${steps.length}</div>
      <h3>${escapeHtml(step.title)}</h3>
      <p>${escapeHtml(step.text)}</p>
      ${step.guideItems?.length?`<div class="tour-guide-notes">${step.guideItems.map(item=>`<div class="tour-guide-note"><strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(item.text)}</span></div>`).join('')}</div>`:''}
      ${step.tip?`<div class="tour-tip"><strong>Consejo</strong><span>${escapeHtml(step.tip)}</span></div>`:''}
      <div class="tour-actions">
        <button type="button" class="btn btn-outline-secondary" data-tour-stop>Salir</button>
        <span></span>
        ${index>0?'<button type="button" class="btn btn-outline-secondary" data-tour-prev>Anterior</button>':''}
        <button type="button" class="btn btn-primary" data-tour-next>${index===steps.length-1?'Finalizar':'Siguiente'}</button>
      </div>`;
    requestAnimationFrame(()=>{positionPopover(currentElement);popover?.focus({preventScroll:true});});
    window.setTimeout(()=>positionPopover(currentElement),80);
  }

  function escapeHtml(value){
    return String(value).replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
  }

  function start(){
    const resolved=resolveTour();
    steps=resolved.steps;
    if(!steps.length){window.HelpdeskUI?.notify?.('Esta pantalla no tiene pasos visibles para el recorrido guiado.','info');return;}
    if(resolved.missing.length){
      console.warn('[Helpdesk tour] Pasos no disponibles',resolved.missing);
      window.HelpdeskUI?.notify?.('Recorrido adaptado: algunas partes no están disponibles en esta vista.','info',4200);
    }
    ensureUi();
    document.querySelector('[data-help-panel]')?.classList.remove('open');
    const backdrop=document.querySelector('[data-help-backdrop]');if(backdrop instanceof HTMLElement)backdrop.hidden=true;
    document.body.classList.remove('help-open');
    active=true;index=0;
    overlay?.classList.add('show');popover?.classList.add('show');
    document.body.classList.add('tour-open');
    localStorage.setItem(TOUR_SEEN_KEY,'1');
    render();
  }

  function stop(){
    active=false;
    currentElement?.classList.remove('tour-highlight');currentElement=null;
    overlay?.classList.remove('show');popover?.classList.remove('show');
    document.body.classList.remove('tour-open');
  }

  document.addEventListener('click',e=>{
    const target=e.target instanceof Element?e.target:null;if(!target)return;
    if(target.closest('[data-tour-start]')){e.preventDefault();start();return;}
    if(target.closest('[data-tour-stop]')){stop();return;}
    if(target.closest('[data-tour-prev]')){index=Math.max(0,index-1);render();return;}
    if(target.closest('[data-tour-next]')){if(index>=steps.length-1){stop();return;}index+=1;render();return;}
  });
  document.addEventListener('keydown',e=>{if(active&&e.key==='Escape')stop();});
  const reposition=()=>{if(active&&currentElement)positionPopover(currentElement);};
  window.addEventListener('resize',reposition);
  window.addEventListener('scroll',reposition,{passive:true});
  window.visualViewport?.addEventListener('resize',reposition);
  window.visualViewport?.addEventListener('scroll',reposition);
})();
