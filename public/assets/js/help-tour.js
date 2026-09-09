(function(){
  'use strict';

  const panel=document.querySelector('[data-help-panel]');
  if(!(panel instanceof HTMLElement))return;

  const rawContext=panel.dataset.helpContext||'general';
  const context=document.querySelector('.dashboard-support')?'support_dashboard':rawContext;
  const TOUR_SEEN_KEY=`helpdesk:tour-seen:${context}`;

  const tours={
    public_create:[
      ['.public-form-head','Antes de empezar','Esta pantalla sirve para reportar un problema sin necesidad de aprender términos técnicos. Completa únicamente lo que conozcas.','No necesitas saber la causa; describe lo que estás viendo.'],
      ['[data-public-step="requester"]','Tus datos','Escribe un nombre y un correo que puedas consultar. Ahí recibirás el número de caso y las novedades.','El teléfono es opcional y solo ayuda si necesitamos contactarte rápidamente.'],
      ['[data-public-step="location"]','Dónde ocurre','Selecciona el parque o ubicación y el área relacionada cuando los conozcas. Si no estás seguro, puedes dejar “No aplica / No lo sé”.','No inventes una ubicación: es mejor dejarla sin definir que enviar un dato incorrecto.'],
      ['[data-public-step="classification"]','Resume el caso','Elige el tipo más parecido y escribe un resumen corto que permita reconocer el problema de inmediato.','Un buen resumen sería “Internet lento en caja” o “POS no permite facturar”.'],
      ['[data-public-step="problem"]','Describe qué está pasando','Cuenta qué sucede, desde cuándo, a quién o qué equipo afecta y qué intentaste si ya hiciste alguna prueba.','No necesitas escribir mucho: prioriza hechos concretos que ayuden a reproducir o entender el problema.'],
      ['[data-public-step="submit"]','Enviar y dar seguimiento','Al enviar recibirás un número de caso. Después podrás consultar avances desde Mis solicitudes y recibirás novedades por correo.','Pulsa Enviar una sola vez; el sistema evita envíos duplicados mientras procesa la solicitud.']
    ],
    support_dashboard:[
      ['.dashboard-primary-row','Centro de trabajo','Aquí tienes los accesos frecuentes y el objetivo operativo de la pantalla.','Empieza por lo que requiere acción; evita tomar casos nuevos si ya tienes trabajo activo.'],
      ['.dashboard-action-now','Requiere acción ahora','Empieza por SLA vencidos, próximos a vencer y casos críticos.','Estos indicadores sirven para priorizar, no solo para medir volumen.'],
      ['.dashboard-work-grid','Mi trabajo y cola','Continúa primero tus casos activos. Toma nuevos casos únicamente cuando puedas atenderlos.','La cola muestra disponibilidad; tus casos muestran responsabilidad actual.'],
      ['.dashboard-itsm-card','Aprendizaje operativo','Problemas conocidos y conocimiento convierten recurrencias y resoluciones en información reutilizable.','Antes de empezar desde cero, revisa si ya existe una solución aplicable.'],
      ['.dashboard-activity-card','Actividad reciente','Muestra movimientos operativos recientes, no la auditoría técnica completa.','Úsala para entender qué cambió sin revisar registros administrativos.']
    ],
    support_center:[
      ['.queue-quick-filters','Filtros rápidos','Separa de inmediato tus casos, disponibles, críticos, vencidos o en espera.','Usa estos filtros para el trabajo diario; los avanzados quedan para búsquedas específicas.'],
      ['.queue-filters-panel','Filtros avanzados','Despliega esta sección solo cuando necesites combinar parque, categoría, prioridad, estado o responsable.','Puedes combinar criterios sin perder los filtros rápidos.'],
      ['.queue-table-card','Cola operacional','Lee primero asunto y problema reportado. El SLA y responsable te ayudan a decidir qué atender.','No tomes un caso únicamente por antigüedad: considera impacto, SLA y tu carga actual.']
    ],
    ticket:[
      ['.case-problem-card, .ticket-problem-focus','Problema reportado','Este bloque es la fuente principal: entiende qué pasó antes de ejecutar acciones.','La descripción tiene más valor operativo que el número del ticket o la categoría.'],
      ['.ticket-problem-link','Problema conocido','Relaciona recurrencias y aprovecha workaround o solución ya documentada.','Si el mismo fallo ocurre varias veces, relaciónalo en vez de crear conocimiento duplicado.'],
      ['.suggested-solutions','Posibles soluciones','Revisa coincidencias con conocimiento, problemas conocidos y tickets resueltos antes de empezar desde cero.','Las sugerencias ayudan, pero el técnico debe validar que realmente apliquen al caso.'],
      ['.case-action-card','Acciones del caso','La acción principal cambia según el estado. Las opciones menos frecuentes quedan en segundo plano.','Usa En espera solo cuando exista una dependencia real y registra el motivo.'],
      ['.conversation-card, #conversacion','Seguimiento','Diferencia respuesta pública de nota interna antes de enviar.','La nota interna nunca debe utilizarse para responder al solicitante.'],
      ['.resolution-card, .ticket-resolution-card','Documentar solución','Registra causa, solución y prevención para cerrar el ciclo de aprendizaje.','Una buena resolución alimenta informes, casos similares y futuros artículos de conocimiento.']
    ],
    reports:[
      ['.report-summary-grid','Resumen del período','Empieza por estas métricas para entender volumen, documentación y tiempos.','Interpreta siempre los valores junto con el período y filtros seleccionados.'],
      ['.report-filterbar','Filtros','Acota el análisis por período, parque, categoría, responsable, estado o prioridad.','Aplica filtros antes de comparar responsables o ubicaciones.'],
      ['.report-results-card','Detalle operativo','Cada caso conserva contexto, tiempos, cambios de estado y resolución para reconstruir qué ocurrió.','El Excel conserva el filtro actual y sirve para análisis adicional fuera del sistema.']
    ],
    management:[
      ['.mgmt-head','Dashboard interno','El encabezado concentra el alcance y las acciones principales.','Gerencia y supervisión consultan información; no necesitan convertirse en técnicos.'],
      ['.mgmt-filterbar','Filtros de gestión','Usa filtros antes de interpretar métricas o comparar responsables/parques.','El alcance de tu perfil se aplica además de los filtros visibles.'],
      ['.mgmt-kpis','Indicadores','Lee estas métricas como resumen del filtro actual, no como valores históricos permanentes.','Abre Informes cuando necesites explicar el detalle detrás de un indicador.']
    ],
    problems:[
      ['.page-heading','Problemas conocidos','Agrupa recurrencias para investigar causa raíz y compartir soluciones temporales o permanentes.','Un problema puede existir mientras la causa todavía está en investigación.'],
      ['.itsm-filter-grid','Filtros','Busca por número, título, estado, categoría, parque o propietario.','Filtra antes de revisar recurrencias cuando la lista crezca.'],
      ['.itsm-table','Recurrencias','Abre un problema para ver tickets relacionados, workaround, solución y conocimiento asociado.','Relacionar tickets mantiene first/last seen y recurrencia actualizados.']
    ],
    knowledge:[
      ['.page-heading','Base de conocimiento','Centraliza procedimientos y soluciones reutilizables.','El objetivo es que una buena resolución no tenga que descubrirse nuevamente.'],
      ['.itsm-filter-grid','Filtros','Ubica artículos por estado, visibilidad, categoría o texto.','Interno y público son visibilidades distintas; revisa antes de publicar.'],
      ['.itsm-table, .knowledge-grid','Artículos','Los nuevos artículos deben revisarse antes de publicarse.','Publicar es una acción explícita; crear un borrador no lo expone automáticamente.']
    ],
    search:[
      ['.search-page-form','Búsqueda global','Busca ticket, problema o artículo desde un solo lugar.','Puedes usar número, asunto, términos del problema, parque o categoría.'],
      ['.search-result-section','Resultados agrupados','Los resultados se separan por tipo y respetan permisos y visibilidad.','Abre primero el tipo de resultado que responda mejor a lo que buscas.']
    ],
    manual:[
      ['.manual-quick-grid','Empieza por tu objetivo','Estas tarjetas te llevan directamente a las tareas más frecuentes disponibles para tu perfil.','No necesitas leer el manual completo de principio a fin.'],
      ['.manual-index','Índice','Salta directamente al tema que necesitas.','El índice permanece visible mientras recorres la guía en pantallas grandes.'],
      ['.manual-faq','Preguntas frecuentes','Abre únicamente la duda que necesites resolver.','Las respuestas están pensadas para explicar decisiones de uso, no detalles técnicos internos.'],
      ['.manual-section','Guía por función','El manual se adapta al perfil conectado y evita mostrar funciones que no corresponden.','Cada sección explica propósito, flujo y acciones relacionadas.']
    ],
    users:[
      ['.organization-guide','Rol y estructura','El rol define permisos; la asignación define dónde trabaja la persona y quién es su responsable.','Perfil, alcance y atención de soporte son conceptos distintos.'],
      ['.admin-user-toolbar','Buscar y filtrar','Encuentra rápidamente usuarios sin recorrer toda la lista.','Usa filtros antes de abrir fichas cuando existan muchos usuarios.'],
      ['.admin-user-list','Usuarios','Abre únicamente el usuario que necesites editar.','Gerencia y Supervisión pueden consultar información sin formar parte del equipo técnico.']
    ],
    externals:[
      ['.external-admin-kpis','Resumen','Controla proveedores registrados, casos compartidos y accesos activos.','Un proveedor solo debe conservar acceso mientras su participación sea necesaria.'],
      ['.external-ops-layout','Operación','Crea proveedores y comparte casos desde acciones separadas.','Compartir un caso no convierte al proveedor en usuario interno.'],
      ['.external-access-list','Accesos compartidos','Revoca el acceso cuando la participación del proveedor termine.','La revocación conserva trazabilidad sin dejar el caso expuesto.']
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
