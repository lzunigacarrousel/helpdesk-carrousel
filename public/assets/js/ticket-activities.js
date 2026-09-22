(()=>{
  'use strict';

  const root=document.querySelector('#actividades');
  if(!root)return;

  const createPanel=root.querySelector('[data-activity-create-panel]');
  const openButton=root.querySelector('[data-activity-open]');
  const closeButtons=root.querySelectorAll('[data-activity-close]');
  const createForm=root.querySelector('[data-activity-create-form]');

  const setCreateOpen=(open)=>{
    if(!createPanel)return;
    createPanel.hidden=!open;
    if(open){
      const first=createPanel.querySelector('select,input,textarea');
      if(first)window.requestAnimationFrame(()=>first.focus());
    }
  };

  if(openButton)openButton.addEventListener('click',()=>setCreateOpen(true));
  closeButtons.forEach(btn=>btn.addEventListener('click',()=>setCreateOpen(false)));

  const syncType=(form)=>{
    if(!form)return;
    const type=form.querySelector('[data-activity-type]');
    if(!type)return;
    const value=String(type.value||'').toUpperCase();

    form.querySelectorAll('[data-activity-panel]').forEach(panel=>{
      const expected=String(panel.getAttribute('data-activity-panel')||'').toUpperCase();
      const visible=expected===value;
      panel.hidden=!visible;
      panel.querySelectorAll('select,input,textarea').forEach(field=>{
        if(field.hasAttribute('data-activity-required'))field.required=visible;
      });
    });

    const remote=form.querySelector('input[name="is_remote"]');
    if(remote)remote.value=value==='SOPORTE_REMOTO'?'1':'0';
  };

  const syncRequesterVisible=(form)=>{
    if(!form)return;
    const toggle=form.querySelector('[data-requester-visible]');
    const summary=form.querySelector('.ticket-activity-requester-summary');
    if(!toggle||!summary)return;
    const visible=Boolean(toggle.checked);
    summary.hidden=!visible;
    const field=summary.querySelector('[name="requester_summary"]');
    if(field)field.required=visible;
  };

  if(createForm){
    const type=createForm.querySelector('[data-activity-type]');
    const requester=createForm.querySelector('[data-requester-visible]');
    if(type)type.addEventListener('change',()=>syncType(createForm));
    if(requester)requester.addEventListener('change',()=>syncRequesterVisible(createForm));

    const participantRoot=createForm.querySelector('[data-activity-participants]');
    const participantSearch=participantRoot?.querySelector('[data-participant-search]');
    const participantOptions=[...(participantRoot?.querySelectorAll('[data-participant-option]')||[])];
    const participantChecks=[...(participantRoot?.querySelectorAll('[data-participant-checkbox]')||[])];
    const participantCount=participantRoot?.querySelector('[data-participant-count]');
    const participantEmpty=participantRoot?.querySelector('[data-participant-empty]');
    const responsible=createForm.querySelector('[data-activity-responsible]');

    const normalizeParticipant=(value)=>String(value||'').toLocaleLowerCase('es-GT').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim();
    const syncParticipants=()=>{
      const responsibleId=responsible instanceof HTMLSelectElement?String(responsible.value||''):'';
      let selected=0;
      let visible=0;
      const query=normalizeParticipant(participantSearch instanceof HTMLInputElement?participantSearch.value:'');
      const tokens=query?query.split(' ').filter(Boolean):[];

      participantOptions.forEach(option=>{
        if(!(option instanceof HTMLElement))return;
        const checkbox=option.querySelector('[data-participant-checkbox]');
        if(!(checkbox instanceof HTMLInputElement))return;
        const isResponsible=responsibleId!==''&&checkbox.value===responsibleId;
        if(isResponsible)checkbox.checked=false;
        checkbox.disabled=isResponsible;
        option.classList.toggle('is-responsible',isResponsible);

        const haystack=normalizeParticipant(option.dataset.search||option.textContent||'');
        const matches=tokens.length===0||tokens.every(token=>haystack.includes(token));
        option.hidden=!matches;
        if(matches)visible+=1;
        if(checkbox.checked)selected+=1;
      });

      if(participantCount)participantCount.textContent=selected===1?'1 seleccionado':selected+' seleccionados';
      if(participantEmpty)participantEmpty.hidden=visible!==0;
    };

    participantSearch?.addEventListener('input',syncParticipants);
    participantChecks.forEach(check=>check.addEventListener('change',syncParticipants));
    responsible?.addEventListener('change',syncParticipants);

    syncType(createForm);
    syncRequesterVisible(createForm);
    syncParticipants();
  }

  root.querySelectorAll('[data-activity-confirm]').forEach(button=>{
    button.addEventListener('click',(event)=>{
      const message=button.getAttribute('data-activity-confirm')||'¿Confirmar esta acción?';
      if(!window.confirm(message))event.preventDefault();
    });
  });
})();
