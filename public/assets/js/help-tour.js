(function(){
  'use strict';

  const panel=document.querySelector('[data-help-panel]');
  if(!(panel instanceof HTMLElement))return;

  const rawContext=panel.dataset.helpContext||'general';
  const context=document.querySelector('.dashboard-support')?'support_dashboard':rawContext;
  const TOUR_SEEN_KEY=`helpdesk:tour-seen:${context}`;

  const tours={
    public_create:[
      ['.public-form-head','Antes de empezar','Esta pantalla sirve para reportar un problema sin aprender términos técnicos. Completa únicamente lo que conozcas.','No necesitas saber la causa; describe lo que estás viendo.'],
      ['[data-public-step="requester"]','Tus datos','Usa un nombre y correo que puedas consultar. Ahí recibirás el número de caso y las novedades.','El teléfono es opcional y solo ayuda si necesitamos contactarte rápidamente.'],
      ['[data-public-step="location"]','Dónde ocurre','Selecciona parque y área cuando los conozcas. Si no estás seguro, deja “No aplica / No lo sé”.','Es mejor dejar una ubicación sin definir que inventar un dato.'],
      ['[data-public-step="classification"]','Tipo de solicitud','Elige la opción que más se parezca a lo que necesitas.','No hace falta encontrar una categoría perfecta; el equipo puede ajustar la clasificación después.'],
      ['[data-public-step="problem"]','Cuéntanos qué sucede','Usa un solo espacio para explicar qué pasa, desde cuándo, a quién afecta y qué intentaste si ya hiciste alguna prueba.','Prioriza hechos concretos. No necesitas escribir un resumen adicional ni conocer la causa técnica.'],
      ['[data-public-step="submit"]','Enviar y dar seguimiento','Al enviar recibirás un número de caso. Después podrás consultar avances desde Mis solicitudes y recibir novedades por correo.','Pulsa Enviar una sola vez; el sistema evita envíos duplicados mientras procesa la solicitud.']
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
      ['.suggested-solutions','Posibles soluciones','Revisa conocimiento, problemas conocidos y casos resueltos similares antes de empezar desde cero.','Las sugerencias ayudan, pero siempre valida que realmente apliquen.'],
      ['.case-action-card','Acciones','La acción principal cambia según el estado. Las opciones menos frecuentes quedan en segundo plano.','Usa En espera solo cuando exista una dependencia real y registra la razón.'],
      ['.case-conversation-card, .external-conversation-card, #conversacion','Conversaciones','El seguimiento distingue lo visible para el solicitante, la participación de proveedores y la conversación interna del equipo.','La conversación interna nunca se muestra ni se envía al solicitante o proveedor.'],
      ['.external-reply-card','Responder','Si colaboras como proveedor, usa este espacio para enviar avance, consulta o evidencia.','Comparte únicamente la información necesaria para continuar el caso.'],
      ['.resolution-card, .ticket-resolution-card, .case-resolution-capture, .external-solution-card','Solución','Cuando el caso se resuelve, la solución queda visible en un bloque fácil de encontrar.','Una buena resolución alimenta informes y conocimiento para futuros casos.']
    ],
    reports:[
      ['.report-summary-grid','Resumen del período','Empieza por estas métricas para entender volumen, documentación y tiempos.','Interpreta siempre los valores junto con el período y filtros seleccionados.'],
      ['.report-filterbar','Filtros','Acota el análisis por período, parque, categoría, responsable, estado o prioridad.','Aplica filtros antes de comparar responsables o ubicaciones.'],
      ['.report-results-card','Detalle operativo','Cada caso conserva contexto, tiempos, cambios de estado y solución para reconstruir qué ocurrió.','El Excel conserva el filtro actual y sirve para análisis adicional fuera del sistema.']
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
      ['.page-heading','Base de conocimiento','Centraliza procedimientos y soluciones reutilizables.','El objetivo es que una buena solución no tenga que descubrirse nuevamente.'],
      ['.itsm-filter-grid','Filtros','Ubica artículos por estado, visibilidad, categoría o texto.','Revisa antes de publicar.'],
      ['.itsm-table, .knowledge-grid','Artículos','Los nuevos artículos deben revisarse antes de publicarse.','Publicar es una acción explícita; crear un borrador no lo expone automáticamente.']
    ],
    search:[
      ['.search-page-form','Búsqueda global','Busca ticket, problema o artículo desde un solo lugar.','Puedes usar número, asunto, términos del problema, parque o categoría.'],
      ['.search-result-section','Resultados agrupados','Los resultados se separan por tipo y respetan permisos.','Abre primero el tipo de resultado que responda mejor a lo que buscas.']
    ],
    manual:[
      ['.manual-search','Buscar en el manual','Escribe una tarea, duda o palabra clave y la guía ocultará lo que no coincide.','Prueba con “resolver”, “proveedor”, “espera”, “correo” o “informe”.'],
      ['.manual-quick-grid','Empieza por tu objetivo','Estas tarjetas te llevan directamente a las tareas más frecuentes disponibles para tu perfil.','No necesitas leer el manual completo de principio a fin.'],
      ['.manual-index','Índice','Salta directamente al tema que necesitas.','El índice permanece visible mientras recorres la guía en pantallas grandes.'],
      ['.manual-faq','Preguntas frecuentes','Abre únicamente la duda que necesites resolver.','Las respuestas explican decisiones de uso, no detalles técnicos internos.'],
      ['.manual-section','Guía por función','El manual se adapta al perfil conectado y evita mostrar funciones que no corresponden.','Cada sección explica propósito, flujo y acciones relacionadas.']
    ],
    users:[
      ['.organization-guide','Perfil y estructura','El perfil define permisos; la asignación define dónde trabaja la persona y quién es su responsable.','Perfil, alcance y atención de soporte son conceptos distintos.'],
      ['.admin-user-toolbar','Buscar y filtrar','Encuentra rápidamente usuarios sin recorrer toda la lista.','Usa filtros antes de abrir fichas cuando existan muchos usuarios.'],
      ['.admin-user-list','Usuarios','Abre únicamente el usuario que necesites editar.','Gerencia y Supervisión pueden consultar información sin formar parte del equipo técnico.']
    ],
    externals:[
      ['.external-admin-kpis','Resumen','Controla proveedores registrados, casos compartidos y accesos activos.','Un proveedor solo debe conservar acceso mientras su participación sea necesaria.'],
      ['.external-ops-layout','Operación','Crea proveedores y comparte casos desde acciones separadas.','Compartir un caso no convierte al proveedor en usuario interno.'],
      ['.external-access-list','Casos compartidos','Retira el acceso cuando la participación del proveedor termine.','La revocación conserva trazabilidad sin dejar el caso disponible.']
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

  function resolveSteps(){
    const source=tours[context]||tours[rawContext]||[];
    return source.map(([selector,title,text,tip])=>{
      const el=document.querySelector(selector);
      return el instanceof HTMLElement?{el,title,text,tip:tip||''}:null;
    }).filter(Boolean);
  }

  function ensureUi(){
    if(overlay&&popover)return;
    overlay=document.createElement('div');
    overlay.className='tour-overlay';
    overlay.addEventListener('click',stop);
    popover=document.createElement('aside');
    popover.className='tour-popover';
    popover.setAttribute('role','dialog');
    popover.setAttribute('aria-modal','true');
    document.body.append(overlay,popover);
  }

  function positionPopover(el){
    if(!popover)return;
    const r=el.getBoundingClientRect();
    const width=Math.min(410,window.innerWidth-24);
    popover.style.width=`${width}px`;
    const h=popover.offsetHeight||250;
    let top=r.bottom+14;
    if(top+h>window.innerHeight-12)top=Math.max(12,r.top-h-14);
    let left=r.left;
    if(left+width>window.innerWidth-12)left=window.innerWidth-width-12;
    left=Math.max(12,left);
    popover.style.top=`${Math.round(top)}px`;
    popover.style.left=`${Math.round(left)}px`;
  }

  function render(){
    if(!active||!steps.length)return;
    const step=steps[index];
    currentElement?.classList.remove('tour-highlight');
    currentElement=step.el;
    currentElement.classList.add('tour-highlight');
    currentElement.scrollIntoView({behavior:'smooth',block:'center',inline:'nearest'});
    if(!popover)return;
    popover.innerHTML=`
      <div class="tour-progress">Paso ${index+1} de ${steps.length}</div>
      <h3>${escapeHtml(step.title)}</h3>
      <p>${escapeHtml(step.text)}</p>
      ${step.tip?`<div class="tour-tip"><strong>Consejo</strong><span>${escapeHtml(step.tip)}</span></div>`:''}
      <div class="tour-actions">
        <button type="button" class="btn btn-outline-secondary" data-tour-stop>Salir</button>
        <span></span>
        ${index>0?'<button type="button" class="btn btn-outline-secondary" data-tour-prev>Anterior</button>':''}
        <button type="button" class="btn btn-primary" data-tour-next>${index===steps.length-1?'Finalizar':'Siguiente'}</button>
      </div>`;
    window.setTimeout(()=>positionPopover(currentElement),260);
  }

  function escapeHtml(value){
    return String(value).replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
  }

  function start(){
    steps=resolveSteps();
    if(!steps.length){window.HelpdeskUI?.notify?.('Esta pantalla todavía no necesita un recorrido guiado.','info');return;}
    ensureUi();
    document.querySelector('[data-help-panel]')?.classList.remove('open');
    const backdrop=document.querySelector('[data-help-backdrop]');if(backdrop instanceof HTMLElement)backdrop.hidden=true;
    document.body.classList.remove('help-open');
    active=true;index=0;
    overlay?.classList.add('show');popover?.classList.add('show');
    document.body.classList.add('tour-open');
    localStorage.setItem(TOUR_SEEN_KEY,'1');
    document.querySelector('[data-tour-hint]')?.setAttribute('hidden','');
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
  window.addEventListener('resize',()=>{if(active&&currentElement)positionPopover(currentElement);});
  window.addEventListener('scroll',()=>{if(active&&currentElement)positionPopover(currentElement);},{passive:true});

  const hint=document.querySelector('[data-tour-hint]');
  if(hint instanceof HTMLElement&&localStorage.getItem(TOUR_SEEN_KEY)!=='1'&&resolveSteps().length>0){
    hint.hidden=false;
    window.setTimeout(()=>{if(!active)hint.classList.add('is-visible');},700);
  }
})();