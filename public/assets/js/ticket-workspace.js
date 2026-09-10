(function(){
  'use strict';

  const workspace=document.querySelector('.ticket-workspace.case-focus-workspace');
  if(!(workspace instanceof HTMLElement))return;

  const actionCard=workspace.querySelector('.case-action-card');
  const conversation=workspace.querySelector('.case-conversation-card');
  if(!(actionCard instanceof HTMLElement)||!(conversation instanceof HTMLElement))return;

  document.body.classList.add('ticket-flow-simplified');

  const normalize=value=>String(value||'')
    .toLocaleLowerCase('es-GT')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ')
    .trim();

  const problemCard=workspace.querySelector('.case-problem-card');
  if(problemCard instanceof HTMLElement){
    const kicker=problemCard.querySelector('.ticket-kicker');
    const heading=problemCard.querySelector('h2');
    const description=problemCard.querySelector('.case-problem-description');
    const pageTitle=workspace.querySelector('.case-focus-head .page-title');

    if(kicker)kicker.textContent='1 · Problema';
    if(description instanceof HTMLElement&&pageTitle instanceof HTMLElement&&normalize(description.textContent)===normalize(pageTitle.textContent)){
      description.hidden=true;
      if(heading)heading.textContent='Datos del caso';
    }else if(heading){
      heading.textContent='Descripción del problema';
    }
  }

  const conversationHead=conversation.querySelector('.case-section-head');
  if(conversationHead instanceof HTMLElement){
    const kicker=conversationHead.querySelector('.ticket-kicker');
    const heading=conversationHead.querySelector('h2');
    const description=conversationHead.querySelector('p');
    if(kicker)kicker.textContent='2 · Seguimiento';
    if(heading)heading.textContent='Seguimiento';
    if(description)description.textContent='Comunícate con el solicitante, el proveedor o el equipo interno desde un solo lugar.';
  }

  const problemGrid=workspace.querySelector('.case-focus-grid');
  if(problemGrid instanceof HTMLElement)problemGrid.insertAdjacentElement('afterend',conversation);

  const actionHead=actionCard.querySelector('.case-section-head');
  if(actionHead instanceof HTMLElement){
    const kicker=actionHead.querySelector('.ticket-kicker');
    const heading=actionHead.querySelector('h2');
    const description=actionHead.querySelector('p');
    if(kicker)kicker.textContent='3 · Gestión del caso';
    if(heading)heading.textContent='Gestión del caso';
    if(description)description.textContent='Resuelve el caso o utiliza una acción secundaria si necesita pausa, reasignación o devolución.';
  }

  const actionBar=actionCard.querySelector('.case-action-bar');
  const resolution=actionCard.querySelector('.case-resolution-capture');
  let resolutionFlow=null;

  if(resolution instanceof HTMLElement&&resolution.querySelector('form')){
    const resolutionKicker=resolution.querySelector('.ticket-kicker');
    const resolutionHeading=resolution.querySelector('h2');
    const resolutionDescription=resolution.querySelector('.case-section-head p');
    const resolutionSubmit=resolution.querySelector('button[type="submit"]');
    if(resolutionKicker)resolutionKicker.textContent='Solución';
    if(resolutionHeading)resolutionHeading.textContent='Documentar solución';
    if(resolutionDescription)resolutionDescription.textContent='Registra qué ocurrió y qué se hizo antes de enviar la solución al solicitante.';
    if(resolutionSubmit)resolutionSubmit.textContent='Enviar solución al usuario';

    resolutionFlow=document.createElement('details');
    resolutionFlow.className='case-resolution-flow';
    const summary=document.createElement('summary');
    summary.textContent='Resolver caso';
    resolution.parentNode?.insertBefore(resolutionFlow,resolution);
    resolutionFlow.append(summary,resolution);

    if(actionHead)actionHead.insertAdjacentElement('afterend',resolutionFlow);
  }

  const releaseForm=[...actionCard.querySelectorAll('form')].find(form=>{
    const button=form.querySelector('button[type="submit"]');
    return normalize(button?.textContent)==='devolver a la cola';
  });
  const pendingControl=actionCard.querySelector('.case-pending-control');
  const assignDetails=actionCard.querySelector('.case-assign-details');
  const hasSecondary=releaseForm instanceof HTMLFormElement||pendingControl instanceof HTMLElement||assignDetails instanceof HTMLElement;

  if(hasSecondary){
    const secondary=document.createElement('details');
    secondary.className='case-secondary-actions';
    const summary=document.createElement('summary');
    summary.textContent='Más acciones';
    secondary.appendChild(summary);

    if(pendingControl instanceof HTMLElement)secondary.appendChild(pendingControl);
    if(assignDetails instanceof HTMLElement){
      const nestedSummary=assignDetails.querySelector('summary');
      if(nestedSummary)nestedSummary.textContent='Reasignar responsable';
      secondary.appendChild(assignDetails);
    }
    if(releaseForm instanceof HTMLFormElement){
      releaseForm.classList.add('case-release-form');
      secondary.appendChild(releaseForm);
    }

    if(resolutionFlow instanceof HTMLElement)resolutionFlow.insertAdjacentElement('afterend',secondary);
    else if(actionBar instanceof HTMLElement)actionBar.insertAdjacentElement('afterend',secondary);
    else actionCard.querySelector('.card-body')?.appendChild(secondary);
  }

  if(actionBar instanceof HTMLElement&&actionBar.children.length===0)actionBar.hidden=true;

  const knownProblem=workspace.querySelector('.ticket-problem-link');
  if(knownProblem instanceof HTMLElement){
    const body=knownProblem.querySelector('.card-body');
    if(body instanceof HTMLElement){
      const details=document.createElement('details');
      details.className='case-tool-details';
      if(body.querySelector('.ticket-related-problems'))details.open=true;
      const summary=document.createElement('summary');
      summary.textContent='¿Es un problema recurrente?';
      const content=document.createElement('div');
      content.className='case-tool-content';
      while(body.firstChild)content.appendChild(body.firstChild);
      details.append(summary,content);
      body.appendChild(details);
      knownProblem.classList.add('case-secondary-tool');
      actionCard.insertAdjacentElement('afterend',knownProblem);
    }
  }

  const suggestions=workspace.querySelector('.suggested-solutions');
  if(suggestions instanceof HTMLElement){
    const body=suggestions.querySelector('.card-body');
    if(body instanceof HTMLElement){
      const details=document.createElement('details');
      details.className='case-tool-details';
      const summary=document.createElement('summary');
      summary.textContent='Ver posibles soluciones';
      const content=document.createElement('div');
      content.className='case-tool-content';
      while(body.firstChild)content.appendChild(body.firstChild);
      details.append(summary,content);
      body.appendChild(details);
      suggestions.classList.add('case-secondary-tool');
      knownProblem instanceof HTMLElement
        ? knownProblem.insertAdjacentElement('afterend',suggestions)
        : actionCard.insertAdjacentElement('afterend',suggestions);
    }
  }

  const style=document.createElement('style');
  style.textContent=`
    body.ticket-flow-simplified .case-focus-workspace{gap:14px}
    body.ticket-flow-simplified .case-problem-description[hidden]{display:none!important}
    body.ticket-flow-simplified .case-problem-card h2{color:var(--ink);font-size:18px;margin-bottom:8px}
    body.ticket-flow-simplified .case-conversation-card{margin-top:0}
    body.ticket-flow-simplified .case-action-card .case-section-head{margin-bottom:10px}
    body.ticket-flow-simplified .case-action-bar[hidden]{display:none!important}
    body.ticket-flow-simplified .case-resolution-flow,
    body.ticket-flow-simplified .case-secondary-actions,
    body.ticket-flow-simplified .case-tool-details{border:1px solid var(--border);border-radius:12px;background:color-mix(in srgb,var(--card) 97%,var(--bg) 3%)}
    body.ticket-flow-simplified .case-resolution-flow{border-color:color-mix(in srgb,var(--brand) 34%,var(--border) 66%);margin-top:4px}
    body.ticket-flow-simplified .case-resolution-flow>summary,
    body.ticket-flow-simplified .case-secondary-actions>summary,
    body.ticket-flow-simplified .case-tool-details>summary{list-style:none;cursor:pointer;font-weight:850}
    body.ticket-flow-simplified .case-resolution-flow>summary::-webkit-details-marker,
    body.ticket-flow-simplified .case-secondary-actions>summary::-webkit-details-marker,
    body.ticket-flow-simplified .case-tool-details>summary::-webkit-details-marker{display:none}
    body.ticket-flow-simplified .case-resolution-flow>summary{display:flex;align-items:center;justify-content:center;min-height:44px;padding:10px 16px;background:var(--brand);color:#fff;border-radius:11px;font-size:13px}
    body.ticket-flow-simplified .case-resolution-flow[open]>summary{border-radius:11px 11px 0 0}
    body.ticket-flow-simplified .case-resolution-flow .case-resolution-capture{margin:0;border:0;border-top:1px solid var(--border);border-radius:0 0 11px 11px;padding:16px;background:color-mix(in srgb,var(--success-bg) 40%,var(--card) 60%)}
    body.ticket-flow-simplified .case-resolution-flow .case-resolution-capture .case-section-head{margin-bottom:10px}
    body.ticket-flow-simplified .case-secondary-actions{margin-top:10px;padding:0 12px}
    body.ticket-flow-simplified .case-secondary-actions>summary{padding:12px 2px;color:var(--brand);font-size:12px}
    body.ticket-flow-simplified .case-secondary-actions[open]>summary{border-bottom:1px solid var(--border)}
    body.ticket-flow-simplified .case-secondary-actions .case-pending-control{margin:12px 0}
    body.ticket-flow-simplified .case-secondary-actions .case-assign-details{margin:12px 0}
    body.ticket-flow-simplified .case-release-form{margin:12px 0;display:flex;justify-content:flex-start}
    body.ticket-flow-simplified .case-secondary-tool{box-shadow:none;border-color:var(--border)}
    body.ticket-flow-simplified .case-secondary-tool .card-body{padding:0}
    body.ticket-flow-simplified .case-tool-details{border:0;background:transparent}
    body.ticket-flow-simplified .case-tool-details>summary{padding:14px 16px;color:var(--brand)}
    body.ticket-flow-simplified .case-tool-details[open]>summary{border-bottom:1px solid var(--border)}
    body.ticket-flow-simplified .case-tool-content{padding:14px 16px}
    body.ticket-flow-simplified .case-tool-content>.case-section-head{display:none}
    @media(max-width:900px){
      body.ticket-flow-simplified .case-pending-control,
      body.ticket-flow-simplified .case-pending-control form{grid-template-columns:1fr}
    }
  `;
  document.head.appendChild(style);
})();
