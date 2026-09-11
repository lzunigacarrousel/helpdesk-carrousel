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

  function postInBackground(url,data){
    if(!url||!csrf)return;
    try{
      const body=new URLSearchParams({_csrf:csrf,...data});
      void fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body,keepalive:true}).catch(()=>{});
    }catch(_){}
  }

  function currentAppBase(){
    if(!readUrl)return null;
    try{
      const url=new URL(readUrl,window.location.href);
      url.pathname=url.pathname.replace(/\/notifications\/read\/?$/,'');
      url.search='';url.hash='';
      return url;
    }catch(_){return null;}
  }

  function normalizeNotificationHref(rawHref){
    try{
      const destination=new URL(rawHref,window.location.href);
      const marker='/tickets/view';
      if(!destination.pathname.includes(marker))return destination.href;
      const base=currentAppBase();
      if(!base)return destination.href;
      return base.href.replace(/\/$/,'')+marker+destination.search+destination.hash;
    }catch(_){return rawHref;}
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

    // Recalcula links de ticket contra la instancia actual. Evita que una
    // notificacion creada en otro host/entorno redirija fuera del Helpdesk abierto.
    link.href=normalizeNotificationHref(link.href);

    const id=link.dataset.notificationId||'';
    if(!id||!link.classList.contains('unread'))return;

    // Marcar como leida nunca debe frenar la navegacion. El navegador sigue el
    // enlace de inmediato y la escritura termina en segundo plano con keepalive.
    link.classList.remove('unread');
    postInBackground(readUrl,{delivery_id:id});
  },true);
})();
