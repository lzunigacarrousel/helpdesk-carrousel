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
    syncType(createForm);
    syncRequesterVisible(createForm);
  }

  root.querySelectorAll('[data-activity-confirm]').forEach(button=>{
    button.addEventListener('click',(event)=>{
      const message=button.getAttribute('data-activity-confirm')||'¿Confirmar esta acción?';
      if(!window.confirm(message))event.preventDefault();
    });
  });
})();
