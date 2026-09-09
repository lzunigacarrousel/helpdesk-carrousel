(function(){
  'use strict';

  const panel=document.querySelector('[data-help-panel]');
  if(!(panel instanceof HTMLElement))return;

  const rawContext=panel.dataset.helpContext||'general';
  const context=document.querySelector('.dashboard-support')?'support_dashboard':rawContext;
  const TOUR_SEEN_KEY=`helpdesk:tour-seen:${context}`;

  const tours={
    support_dashboard:[
      ['.dashboard-primary-row','Centro de trabajo','Aquí tienes los accesos frecuentes y el objetivo operativo de la pantalla.'],
      ['.dashboard-action-now','Requiere acción ahora','Empieza por SLA vencidos, próximos a vencer y casos críticos.'],
      ['.dashboard-work-grid','Mi trabajo y cola','Continúa primero tus casos activos. Toma nuevos casos únicamente cuando puedas atenderlos.'],
      ['.dashboard-itsm-card','Aprendizaje operativo','Problemas conocidos y conocimiento convierten recurrencias y resoluciones en información reutilizable.'],
      ['.dashboard-activity-card','Actividad reciente','Muestra movimientos operativos recientes, no la auditoría técnica completa.']
    ],
    support_center:[
      ['.queue-quick-filters','Filtros rápidos','Separa de inmediato tus casos, disponibles, críticos, vencidos o en espera.'],
      ['.queue-filters-panel','Filtros avanzados','Despliega esta sección solo cuando necesites combinar parque, categoría, prioridad, estado o responsable.'],
      ['.queue-table-card','Cola operacional','Lee primero asunto y problema reportado. El SLA y responsable te ayudan a decidir qué atender.']
    ],
    ticket:[
      ['.case-problem-card, .ticket-problem-focus','Problema reportado','Este bloque es la fuente principal: entiende qué pasó antes de ejecutar acciones.'],
      ['.ticket-problem-link','Problema conocido','Relaciona recurrencias y aprovecha workaround o solución ya documentada.'],
      ['.suggested-solutions','Posibles soluciones','Revisa coincidencias con conocimiento, problemas conocidos y tickets resueltos antes de empezar desde cero.'],
      ['.case-action-card','Acciones del caso','La acción principal cambia según el estado. Las opciones menos frecuentes quedan en segundo plano.'],
      ['.conversation-card, #conversacion','Seguimiento','Diferencia respuesta pública de nota interna antes de enviar.'],
      ['.resolution-card, .ticket-resolution-card','Documentar solución','Registra causa, solución y prevención para cerrar el ciclo de aprendizaje.']
    ],
    reports:[
      ['.report-summary-grid','Resumen del período','Empieza por estas métricas para entender volumen, documentación y tiempos.'],
      ['.report-filterbar','Filtros','Acota el análisis por período, parque, categoría, responsable, estado o prioridad.'],
      ['.report-results-card','Detalle operativo','Cada caso conserva contexto, tiempos, cambios de estado y resolución para reconstruir qué ocurrió.']
    ],
    management:[
      ['.mgmt-head','Dashboard interno','El encabezado concentra el alcance y las acciones principales.'],
      ['.mgmt-filterbar','Filtros de gestión','Usa filtros antes de interpretar métricas o comparar responsables/parques.'],
      ['.mgmt-kpis','Indicadores','Lee estas métricas como resumen del filtro actual, no como valores históricos permanentes.']
    ],
    problems:[
      ['.page-heading','Problemas conocidos','Agrupa recurrencias para investigar causa raíz y compartir soluciones temporales o permanentes.'],
      ['.itsm-filter-grid','Filtros','Busca por número, título, estado, categoría, parque o propietario.'],
      ['.itsm-table','Recurrencias','Abre un problema para ver tickets relacionados, workaround, solución y conocimiento asociado.']
    ],
    knowledge:[
      ['.page-heading','Base de conocimiento','Centraliza procedimientos y soluciones reutilizables.'],
      ['.itsm-filter-grid','Filtros','Ubica artículos por estado, visibilidad, categoría o texto.'],
      ['.itsm-table, .knowledge-grid','Artículos','Los nuevos artículos deben revisarse antes de publicarse.']
    ],
    search:[
      ['.search-page-form','Búsqueda global','Busca ticket, problema o artículo desde un solo lugar.'],
      ['.search-result-section','Resultados agrupados','Los resultados se separan por tipo y respetan permisos y visibilidad.']
    ],
    manual:[
      ['.manual-index','Índice','Salta directamente al tema que necesitas.'],
      ['.manual-section','Guía por función','El manual se adapta al perfil conectado y evita mostrar funciones que no corresponden.']
    ],
    users:[
      ['.organization-guide','Rol y estructura','El rol define permisos; la asignación define dónde trabaja la persona y quién es su responsable.'],
      ['.admin-user-toolbar','Buscar y filtrar','Encuentra rápidamente usuarios sin recorrer toda la lista.'],
      ['.admin-user-list','Usuarios','Abre únicamente el usuario que necesites editar.']
    ],
    externals:[
      ['.external-admin-kpis','Resumen','Controla proveedores registrados, casos compartidos y accesos activos.'],
      ['.external-ops-layout','Operación','Crea proveedores y comparte casos desde acciones separadas.'],
      ['.external-access-list','Accesos compartidos','Revoca el acceso cuando la participación del proveedor termine.']
    ],
    audit:[
      ['.page-heading, .mgmt-head','Auditoría','Consulta trazabilidad cuando necesites investigar un cambio.'],
      ['.audit-filters, .mgmt-filterbar','Filtros','Acota por persona, acción, fecha u origen antes de revisar detalle.']
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
    return source.map(([selector,title,text])=>{
      const el=document.querySelector(selector);
      return el instanceof HTMLElement?{el,title,text}:null;
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
    const width=Math.min(390,window.innerWidth-24);
    popover.style.width=`${width}px`;
    const h=popover.offsetHeight||220;
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
