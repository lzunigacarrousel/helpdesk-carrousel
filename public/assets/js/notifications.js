(function(){
  'use strict';
  const root=document.querySelector('[data-notifications]');
  if(!(root instanceof HTMLElement))return;

  const readUrl=root.dataset.notificationReadUrl||'';
  const readAllUrl=root.dataset.notificationReadAllUrl||'';
  const csrf=root.dataset.notificationCsrf||'';
  const badge=root.querySelector('[data-notification-badge]');
  const readAll=root.querySelector('[data-notifications-read-all]');

  async function post(url,data){
    if(!url||!csrf)return false;
    try{
      const body=new URLSearchParams({_csrf:csrf,...data});
      const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body});
      return response.ok;
    }catch(_){return false;}
  }

  function clearUnreadUi(){
    root.querySelectorAll('.shell-notification-item.unread').forEach(el=>el.classList.remove('unread'));
    badge?.remove();
    readAll?.remove();
    const status=root.querySelector('.shell-notification-head span');
    if(status instanceof HTMLElement)status.textContent='Todo revisado';
  }

  root.addEventListener('click',async event=>{
    const target=event.target instanceof Element?event.target:null;
    if(!target)return;

    const allButton=target.closest('[data-notifications-read-all]');
    if(allButton){
      event.preventDefault();event.stopPropagation();
      if(allButton instanceof HTMLButtonElement)allButton.disabled=true;
      const ok=await post(readAllUrl,{});
      if(ok)clearUnreadUi();
      else if(allButton instanceof HTMLButtonElement)allButton.disabled=false;
      return;
    }

    const link=target.closest('[data-notification-link]');
    if(!(link instanceof HTMLAnchorElement))return;
    const id=link.dataset.notificationId||'';
    const href=link.href;
    if(!id||!link.classList.contains('unread'))return;
    event.preventDefault();event.stopPropagation();
    await post(readUrl,{delivery_id:id});
    window.location.href=href;
  },true);
})();
