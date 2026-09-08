(function(){
  const root=document.documentElement;
  const KEY='carrousel-theme';
  function systemTheme(){return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
  function read(){return localStorage.getItem(KEY)||'system';}
  function apply(pref){const resolved=pref==='system'?systemTheme():pref;root.dataset.theme=resolved;root.dataset.themePreference=pref;document.querySelectorAll('[data-theme-label]').forEach(el=>el.textContent=pref==='dark'?'Oscuro':pref==='light'?'Claro':'Sistema');document.querySelectorAll('[data-theme-icon]').forEach(el=>el.textContent=pref==='dark'?'☾':pref==='light'?'☀':'◐');}
  apply(read());
  document.addEventListener('click',function(e){
    const t=e.target.closest('[data-theme-toggle]');if(t){const current=read();const next=current==='system'?'light':current==='light'?'dark':'system';localStorage.setItem(KEY,next);apply(next);return;}
    const s=e.target.closest('[data-sidebar-toggle]');if(s){document.body.classList.toggle('sidebar-collapsed');const sidebar=document.getElementById('app-sidebar');if(window.innerWidth<=760&&sidebar)sidebar.classList.toggle('open');}
  });
  if(window.matchMedia){window.matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change',()=>{if(read()==='system')apply('system');});}
})();
