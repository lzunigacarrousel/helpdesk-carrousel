(function(){
  const root=document.documentElement;
  const KEY='carrousel-theme';
  function systemTheme(){return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
  function read(){return localStorage.getItem(KEY)||'system';}
  function apply(pref){const resolved=pref==='system'?systemTheme():pref;root.dataset.theme=resolved;root.dataset.themePreference=pref;document.querySelectorAll('[data-theme-label]').forEach(el=>el.textContent=pref==='dark'?'Oscuro':pref==='light'?'Claro':'Sistema');document.querySelectorAll('[data-theme-icon]').forEach(el=>el.textContent=pref==='dark'?'☾':pref==='light'?'☀':'◐');}
  function setHelp(open){const panel=document.querySelector('[data-help-panel]'),backdrop=document.querySelector('[data-help-backdrop]');if(!panel)return;panel.classList.toggle('open',open);panel.setAttribute('aria-hidden',open?'false':'true');if(backdrop)backdrop.hidden=!open;document.body.classList.toggle('help-open',open);}
  apply(read());
  document.addEventListener('click',function(e){
    const t=e.target.closest('[data-theme-toggle]');if(t){const current=read();const next=current==='system'?'light':current==='light'?'dark':'system';localStorage.setItem(KEY,next);apply(next);return;}
    const s=e.target.closest('[data-sidebar-toggle]');if(s){document.body.classList.toggle('sidebar-collapsed');const sidebar=document.getElementById('app-sidebar');if(window.innerWidth<=760&&sidebar)sidebar.classList.toggle('open');return;}
    if(e.target.closest('[data-help-open]')){setHelp(true);return;}
    if(e.target.closest('[data-help-close]')||e.target.matches('[data-help-backdrop]')){setHelp(false);return;}
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape')setHelp(false);});
  document.addEventListener('submit',function(e){
    const form=e.target.closest('form[data-single-submit]');
    if(!form)return;
    if(form.dataset.submitting==='1'){e.preventDefault();return;}
    if(!form.checkValidity())return;
    form.dataset.submitting='1';
    form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(btn=>{
      btn.disabled=true;btn.setAttribute('aria-disabled','true');
      if(btn.tagName==='BUTTON'&&!btn.dataset.keepLabel){btn.dataset.originalText=btn.textContent;btn.textContent='Procesando…';}
    });
  });
  if(window.matchMedia){window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change',()=>{if(read()==='system')apply('system');});}
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
