(function(){
  'use strict';

  const panel=document.querySelector('[data-help-panel]');
  if(!(panel instanceof HTMLElement))return;

  const rawContext=panel.dataset.helpContext||'general';
  const context=document.querySelector('.dashboard-support')?'support_dashboard':rawContext;
  const TOUR_SEEN_KEY=`helpdesk:tour-seen:${context}`;

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
      ['.suggested-solutions','Posibles soluciones','Revisa conocimiento, problemas conocidos y casos resueltos similares antes de empezar desde cero.','Las sugerencias ayudan, pero siempre valida que realmente apliquen.'],
      ['.case-action-card','Acciones','La acción principal cambia según el estado. Las opciones menos frecuentes quedan en segundo plano.','Usa En espera solo cuando exista una dependencia real y registra la razón.'],
      ['.case-conversation-card, .external-conversation-card, #conversacion','Conversaciones','El seguimiento distingue lo visible para el solicitante, la participación de proveedores y la conversación interna del equipo.','La conversación interna nunca se muestra ni se envía al solicitante o proveedor.'],
      ['.external-reply-card','Responder como proveedor','Si colaboras como proveedor, usa este espacio para enviar avance, consulta o evidencia.','Comparte únicamente la información necesaria para continuar el caso.'],
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

  function resolveTour(){
    const source=tours[context]||tours[rawContext]||[];
    const found=[];const missing=[];
    source.forEach(([selector,title,text,tip])=>{
      const el=document.querySelector(selector);
      if(isVisible(el))found.push({el,title,text,tip:tip||'',selector});
      else missing.push({selector,title});
    });
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
    const width=Math.min(410,viewportWidth-24);
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
