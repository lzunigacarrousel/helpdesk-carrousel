(function(){
  'use strict';

  const root=document.documentElement;
  const body=document.body;
  const THEME_KEY='carrousel-theme';
  const SIDEBAR_KEY='helpdesk:sidebar-collapsed';
  const desktopSidebar=window.matchMedia('(min-width:761px)');
  const sidebar=document.getElementById('app-sidebar');
  const sidebarBackdrop=document.querySelector('[data-sidebar-backdrop]');
  const actionStatus=document.getElementById('global-action-status');
  const actionMessage=actionStatus?.querySelector('[data-action-message]');
  const toastContainer=document.getElementById('app-toast-container');

  function systemTheme(){return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
  function readTheme(){return localStorage.getItem(THEME_KEY)||'system';}
  function applyTheme(pref){
    const resolved=pref==='system'?systemTheme():pref;
    root.dataset.theme=resolved;
    root.dataset.themePreference=pref;
    document.querySelectorAll('[data-theme-label]').forEach(el=>el.textContent=pref==='dark'?'Oscuro':pref==='light'?'Claro':'Sistema');
    document.querySelectorAll('[data-theme-icon]').forEach(el=>el.textContent=pref==='dark'?'☾':pref==='light'?'☀':'◐');
  }

  function notify(message,type='info',duration=3600){
    if(!message||!toastContainer)return;
    const toast=document.createElement('div');
    toast.className=`app-toast ${type}`;
    const text=document.createElement('span');
    text.className='app-toast-message';
    text.textContent=String(message);
    const close=document.createElement('button');
    close.className='app-toast-close';close.type='button';close.setAttribute('aria-label','Cerrar');close.textContent='×';
    toast.append(text,close);toastContainer.appendChild(toast);
    const remove=()=>toast.remove();close.addEventListener('click',remove);if(duration>0)window.setTimeout(remove,duration);
  }
  window.HelpdeskUI=Object.freeze({notify});

  function startAction(message='Procesando solicitud…'){
    if(!actionStatus)return;
    if(actionMessage)actionMessage.textContent=message;
    actionStatus.classList.add('show');
    actionStatus.setAttribute('aria-hidden','false');
  }
  function stopAction(){
    if(!actionStatus)return;
    actionStatus.classList.remove('show');
    actionStatus.setAttribute('aria-hidden','true');
  }

  function setHelp(open){
    const panel=document.querySelector('[data-help-panel]');
    const backdrop=document.querySelector('[data-help-backdrop]');
    if(!panel)return;
    panel.classList.toggle('open',open);
    panel.setAttribute('aria-hidden',open?'false':'true');
    if(backdrop)backdrop.hidden=!open;
    body.classList.toggle('help-open',open);
  }

  function updateSidebarToggle(){
    const button=document.querySelector('[data-sidebar-toggle]');
    if(!(button instanceof HTMLElement))return;
    const collapsed=desktopSidebar.matches&&body.classList.contains('sidebar-collapsed');
    const mobileOpen=!desktopSidebar.matches&&sidebar?.classList.contains('open');
    const expanded=desktopSidebar.matches?!collapsed:Boolean(mobileOpen);
    button.setAttribute('aria-expanded',expanded?'true':'false');
    const label=expanded?'Ocultar menú':'Mostrar menú';
    button.setAttribute('aria-label',label);
    button.setAttribute('title',`${label} lateral`);
  }

  function restoreSidebar(){
    if(desktopSidebar.matches){
      body.classList.toggle('sidebar-collapsed',localStorage.getItem(SIDEBAR_KEY)==='1');
      sidebar?.classList.remove('open');
      if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=true;
    }else{
      body.classList.remove('sidebar-collapsed');
    }
    updateSidebarToggle();
  }

  function toggleSidebar(){
    if(desktopSidebar.matches){
      const collapsed=body.classList.toggle('sidebar-collapsed');
      localStorage.setItem(SIDEBAR_KEY,collapsed?'1':'0');
    }else if(sidebar){
      const open=sidebar.classList.toggle('open');
      if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=!open;
    }
    updateSidebarToggle();
  }

  function closeMobileSidebar(){
    if(desktopSidebar.matches)return;
    sidebar?.classList.remove('open');
    if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=true;
    updateSidebarToggle();
  }

  function closeNotifications(){
    const menu=document.querySelector('[data-notifications-menu]');
    const toggle=document.querySelector('[data-notifications-toggle]');
    if(menu instanceof HTMLElement)menu.hidden=true;
    if(toggle instanceof HTMLElement)toggle.setAttribute('aria-expanded','false');
  }

  function toggleNotifications(){
    const menu=document.querySelector('[data-notifications-menu]');
    const toggle=document.querySelector('[data-notifications-toggle]');
    if(!(menu instanceof HTMLElement))return;
    const open=menu.hidden;
    menu.hidden=!open;
    if(toggle instanceof HTMLElement)toggle.setAttribute('aria-expanded',open?'true':'false');
  }

  function setConnectionState(){
    const el=document.querySelector('[data-connection-indicator]');
    if(!(el instanceof HTMLElement))return;
    const online=navigator.onLine;
    el.hidden=online;
    el.classList.toggle('offline',!online);
    el.textContent='Sin conexión';
    el.title='Revisa tu conexión antes de guardar cambios';
  }

  applyTheme(readTheme());
  restoreSidebar();
  setConnectionState();
  desktopSidebar.addEventListener?.('change',restoreSidebar);
  window.addEventListener('online',()=>{setConnectionState();notify('Conexión restablecida.','success',2200);});
  window.addEventListener('offline',()=>{setConnectionState();notify('Sin conexión. Evita enviar cambios hasta recuperar internet.','warning',4800);});

  document.addEventListener('click',function(e){
    const target=e.target instanceof Element?e.target:null;
    if(!target)return;

    if(target.closest('[data-theme-toggle]')){
      const current=readTheme();
      const next=current==='system'?'light':current==='light'?'dark':'system';
      localStorage.setItem(THEME_KEY,next);applyTheme(next);return;
    }
    if(target.closest('[data-sidebar-toggle]')){toggleSidebar();return;}
    if(target.matches('[data-sidebar-backdrop]')){closeMobileSidebar();return;}
    if(target.closest('[data-help-open]')){setHelp(true);closeNotifications();return;}
    if(target.closest('[data-help-close]')||target.matches('[data-help-backdrop]')){setHelp(false);return;}
    if(target.closest('[data-notifications-toggle]')){toggleNotifications();return;}
    if(!target.closest('[data-notifications]'))closeNotifications();

    const link=target.closest('a[href]');
    if(link instanceof HTMLAnchorElement){
      if(!desktopSidebar.matches)closeMobileSidebar();
      if(e.defaultPrevented||e.button!==0||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
      if(link.hasAttribute('download')||link.target==='_blank'||link.dataset.noLoading==='1')return;
      const href=link.getAttribute('href')||'';
      if(!href||href.startsWith('#')||href.startsWith('javascript:')||href.startsWith('mailto:')||href.startsWith('tel:'))return;
      startAction(link.dataset.actionMessage||'Abriendo…');
    }
  });

  document.addEventListener('keydown',e=>{
    if(e.key!=='Escape')return;
    setHelp(false);closeNotifications();closeMobileSidebar();
  });

  document.addEventListener('submit',function(e){
    const form=e.target instanceof Element?e.target.closest('form[data-single-submit]'):null;
    if(!(form instanceof HTMLFormElement))return;
    if(form.dataset.submitting==='1'){e.preventDefault();return;}
    if(!form.checkValidity())return;
    form.dataset.submitting='1';
    startAction(form.dataset.actionMessage||'Procesando solicitud…');
    form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(btn=>{
      if(!(btn instanceof HTMLButtonElement||btn instanceof HTMLInputElement)||btn.disabled)return;
      btn.disabled=true;btn.setAttribute('aria-disabled','true');
      if(btn instanceof HTMLButtonElement&&!btn.dataset.keepLabel){btn.dataset.originalText=btn.textContent||'';btn.textContent='Procesando…';}
    });
  });

  window.addEventListener('pageshow',stopAction);
  window.addEventListener('load',stopAction);
  if(window.matchMedia)window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change',()=>{if(readTheme()==='system')applyTheme('system');});
})();

/* Administración de usuarios · patrón funcional reutilizado de PayOutParques */
(function(){
  const root=document.querySelector('[data-users-admin]');
  if(!(root instanceof HTMLElement))return;

  const search=root.querySelector('[data-users-search]');
  const role=root.querySelector('[data-users-role-filter]');
  const status=root.querySelector('[data-users-status-filter]');
  const clear=root.querySelector('[data-users-clear]');
  const summary=root.querySelector('[data-users-summary]');
  const badge=root.querySelector('[data-users-count]');
  const empty=root.querySelector('[data-users-empty]');
  const rows=[...root.querySelectorAll('[data-user-row]')];

  const norm=value=>String(value||'')
    .toLocaleLowerCase('es-GT')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ')
    .trim();

  function applyFilters(){
    const q=norm(search instanceof HTMLInputElement?search.value:'');
    const roleFilter=norm(role instanceof HTMLSelectElement?role.value:'');
    const statusFilter=norm(status instanceof HTMLSelectElement?status.value:'');
    let visible=0;

    rows.forEach(row=>{
      if(!(row instanceof HTMLElement))return;
      const text=norm(row.dataset.userSearch||row.textContent||'');
      const rowRole=norm(row.dataset.userRole||'');
      const rowStatus=norm(row.dataset.userStatus||'');
      const show=(!q||text.includes(q))&&(!roleFilter||rowRole===roleFilter)&&(!statusFilter||rowStatus===statusFilter);
      row.hidden=!show;
      if(show)visible+=1;
    });

    if(summary instanceof HTMLElement)summary.textContent=`Mostrando ${visible} de ${rows.length} usuarios`;
    if(badge instanceof HTMLElement)badge.textContent=`${visible} visible${visible===1?'':'s'}`;
    if(empty instanceof HTMLElement)empty.hidden=visible!==0;
  }

  search?.addEventListener('input',applyFilters);
  role?.addEventListener('change',applyFilters);
  status?.addEventListener('change',applyFilters);
  clear?.addEventListener('click',()=>{
    if(search instanceof HTMLInputElement)search.value='';
    if(role instanceof HTMLSelectElement)role.value='';
    if(status instanceof HTMLSelectElement)status.value='';
    applyFilters();
    if(search instanceof HTMLInputElement)search.focus();
  });

  applyFilters();
})();
