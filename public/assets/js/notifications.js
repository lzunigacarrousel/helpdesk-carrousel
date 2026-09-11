(function(){
  'use strict';
  const root=document.querySelector('[data-notifications]');
  if(!(root instanceof HTMLElement))return;

  const readUrl=root.dataset.notificationReadUrl||'';
  const readAllUrl=root.dataset.notificationReadAllUrl||'';
  const csrf=root.dataset.notificationCsrf||'';
  const appBaseUrl=root.dataset.notificationBaseUrl||'';
  const fallbackUrl=root.dataset.notificationFallbackUrl||'';
  const badge=root.querySelector('[data-notification-badge]');
  const readAll=root.querySelector('[data-notifications-read-all]');

  const INTERNAL_ROUTE_MARKERS=[
    '/tickets/','/mis-tickets','/dashboard','/buscar','/manual','/crear-ticket',
    '/problems','/knowledge','/gestion','/admin/'
  ];

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
    const raw=appBaseUrl||readUrl;
    if(!raw)return null;
    try{
      const url=new URL(raw,window.location.href);
      if(!appBaseUrl)url.pathname=url.pathname.replace(/\/notifications\/read\/?$/,'');
      url.search='';url.hash='';
      return url;
    }catch(_){return null;}
  }

  function safeFallback(){
    try{return new URL(fallbackUrl||'./dashboard',window.location.href).href;}catch(_){return window.location.href;}
  }

  function notificationTicketFallback(link){
    const ticketId=Number(link?.dataset?.notificationTicketId||0);
    const base=currentAppBase();
    if(!base||ticketId<=0)return null;
    return base.href.replace(/\/$/,'')+'/tickets/view?id='+encodeURIComponent(String(ticketId));
  }

  function internalRelativePath(destination,base){
    const basePath=base.pathname.replace(/\/$/,'');
    if(destination.origin===base.origin&&(destination.pathname===basePath||destination.pathname.startsWith(basePath+'/'))){
      const suffix=destination.pathname.slice(basePath.length);
      return suffix||'/dashboard';
    }
    for(const marker of INTERNAL_ROUTE_MARKERS){
      const at=destination.pathname.indexOf(marker);
      if(at>=0)return destination.pathname.slice(at);
    }
    return null;
  }

  function normalizeNotificationHref(rawHref,link){
    const base=currentAppBase();
    if(!base)return notificationTicketFallback(link)||safeFallback();
    try{
      const destination=new URL(rawHref||'',window.location.href);
      if(destination.protocol!=='http:'&&destination.protocol!=='https:')return notificationTicketFallback(link)||safeFallback();
      const relative=internalRelativePath(destination,base);
      if(!relative)return notificationTicketFallback(link)||safeFallback();
      return base.href.replace(/\/$/,'')+(relative.startsWith('/')?relative:'/'+relative)+destination.search+destination.hash;
    }catch(_){
      return notificationTicketFallback(link)||safeFallback();
    }
  }

  function clearUnreadUi(){
    root.querySelectorAll('.shell-notification-item.unread').forEach(el=>el.classList.remove('unread'));
    badge?.remove();
    readAll?.remove();
    const status=root.querySelector('.shell-notification-head span');
    if(status instanceof HTMLElement)status.textContent='Todo revisado';
  }

  function decrementUnreadUi(){
    const currentBadge=root.querySelector('[data-notification-badge]');
    if(!(currentBadge instanceof HTMLElement))return;
    const current=Number(String(currentBadge.textContent||'').replace(/\D/g,''));
    if(!Number.isFinite(current)||current<=1){
      currentBadge.remove();readAll?.remove();
      const status=root.querySelector('.shell-notification-head span');
      if(status instanceof HTMLElement)status.textContent='Todo revisado';
      return;
    }
    currentBadge.textContent=String(current-1);
    const status=root.querySelector('.shell-notification-head span');
    if(status instanceof HTMLElement)status.textContent=(current-1)+' sin leer';
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

    // La URL guardada puede venir de otro host (servidor, localhost o IP).
    // Siempre reconstruimos destinos internos contra la instancia que el usuario tiene abierta.
    link.href=normalizeNotificationHref(link.getAttribute('href')||'',link);

    const id=link.dataset.notificationId||'';
    if(!id||!link.classList.contains('unread'))return;

    // La lectura nunca debe frenar la navegación. El destino ya quedó resuelto
    // y la escritura termina en segundo plano con keepalive.
    link.classList.remove('unread');
    decrementUnreadUi();
    postInBackground(readUrl,{delivery_id:id});
  },true);
})();
