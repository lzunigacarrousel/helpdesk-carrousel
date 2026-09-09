(function(){
  'use strict';

  const workspace=document.querySelector('.ticket-workspace');
  if(!(workspace instanceof HTMLElement))return;
  if(document.querySelector('.external-workspace'))return;
  if(workspace.querySelector('.ticket-problem-focus'))return;

  const contentGrid=workspace.querySelector(':scope > .ticket-content-grid');
  const infoCard=contentGrid?.querySelector(':scope > .card:first-child');
  const description=infoCard?.querySelector('.ticket-description p');
  if(!(description instanceof HTMLElement))return;

  const descriptionText=(description.innerText||description.textContent||'').trim();
  if(!descriptionText)return;

  const focus=document.createElement('section');
  focus.className='card ticket-problem-focus';

  const body=document.createElement('div');
  body.className='card-body';

  const head=document.createElement('div');
  head.className='ticket-problem-focus-head';
  const headText=document.createElement('div');
  const kicker=document.createElement('span');
  kicker.className='ticket-kicker';
  kicker.textContent='1 · Entiende el problema';
  const title=document.createElement('h2');
  title.textContent='Qué está pasando';
  const subtitle=document.createElement('p');
  subtitle.textContent='Este es el dato principal del caso. Revísalo antes de cambiar estado, asignar o responder.';
  headText.append(kicker,title,subtitle);
  head.appendChild(headText);

  const text=document.createElement('div');
  text.className='ticket-problem-focus-text';
  text.textContent=descriptionText;

  const meta=document.createElement('div');
  meta.className='ticket-problem-focus-meta';
  const factRows=[...infoCard.querySelectorAll('.ticket-info-list > div')];
  factRows.forEach((row,index)=>{
    if(index===0)return; // solicitante queda en datos secundarios
    const label=row.querySelector('span')?.textContent?.trim()||'';
    const strong=row.querySelector('strong')?.textContent?.trim()||'';
    const small=row.querySelector('small')?.textContent?.trim()||'';
    if(!label||!strong)return;
    const item=document.createElement('span');
    const b=document.createElement('b');b.textContent=label+':';
    item.appendChild(b);
    item.append(document.createTextNode(' '+strong+(small?' · '+small:'')));
    meta.appendChild(item);
  });

  body.append(head,text);
  if(meta.childElementCount)body.appendChild(meta);
  focus.appendChild(body);

  const actions=workspace.querySelector(':scope > .ticket-actions-card');
  const summary=workspace.querySelector(':scope > .ticket-summary-grid');
  const target=actions||contentGrid;
  if(target)workspace.insertBefore(focus,target);
  else workspace.appendChild(focus);

  if(summary instanceof HTMLElement){
    focus.insertAdjacentElement('afterend',summary);
  }

  const originalDescription=description.closest('.ticket-description');
  if(originalDescription instanceof HTMLElement)originalDescription.hidden=true;
  if(infoCard instanceof HTMLElement)infoCard.classList.add('ticket-info-secondary');

  const infoTitle=infoCard?.querySelector('.card-body > h2');
  if(infoTitle instanceof HTMLElement)infoTitle.textContent='Datos del caso';
})();
