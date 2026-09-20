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
  const topbarSearch=document.querySelector('.topbar-search');
  const topbarSearchInput=topbarSearch?.querySelector('input[name="q"]');
  const topbarSearchClear=topbarSearch?.querySelector('[data-topbar-search-clear]');

  function syncTopbarSearchClear(){
    if(!(topbarSearchInput instanceof HTMLInputElement)||!(topbarSearchClear instanceof HTMLElement))return;
    topbarSearchClear.hidden=topbarSearchInput.value.trim()==='';
  }

  if(topbarSearchInput instanceof HTMLInputElement){
    topbarSearchInput.addEventListener('input',syncTopbarSearchClear);
    window.addEventListener('pageshow',syncTopbarSearchClear);
  }
  topbarSearchClear?.addEventListener('click',()=>{
    if(!(topbarSearchInput instanceof HTMLInputElement))return;
    const hadValue=topbarSearchInput.value.trim()!=='';
    topbarSearchInput.value='';
    syncTopbarSearchClear();
    topbarSearchInput.focus();
    if(hadValue&&topbarSearch instanceof HTMLFormElement&&/\/buscar\/?$/.test(window.location.pathname)){
      topbarSearch.requestSubmit();
    }
  });
  syncTopbarSearchClear();

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

  let helpReturnFocus=null;

  function helpFocusable(panel){
    return [...panel.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')]
      .filter(el=>el instanceof HTMLElement&&!el.hidden&&el.offsetParent!==null);
  }

  function setHelp(open){
    const panel=document.querySelector('[data-help-panel]');
    const backdrop=document.querySelector('[data-help-backdrop]');
    const trigger=document.querySelector('[data-help-open]');
    if(!(panel instanceof HTMLElement))return;

    if(open){
      const active=document.activeElement;
      helpReturnFocus=active instanceof HTMLElement?active:(trigger instanceof HTMLElement?trigger:null);
    }

    panel.classList.toggle('open',open);
    panel.setAttribute('aria-hidden',open?'false':'true');
    if(trigger instanceof HTMLElement)trigger.setAttribute('aria-expanded',open?'true':'false');
    if(backdrop instanceof HTMLElement)backdrop.hidden=!open;
    body.classList.toggle('help-open',open);

    if(open){
      window.requestAnimationFrame(()=>{
        const close=panel.querySelector('[data-help-close]');
        if(close instanceof HTMLElement)close.focus({preventScroll:true});
      });
    }else if(helpReturnFocus instanceof HTMLElement&&helpReturnFocus.isConnected){
      const target=helpReturnFocus;
      helpReturnFocus=null;
      window.requestAnimationFrame(()=>target.focus({preventScroll:true}));
    }
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

  function openSearch(){
    if(topbarSearchInput instanceof HTMLInputElement&&topbarSearch instanceof HTMLElement&&topbarSearch.offsetParent!==null){
      topbarSearchInput.focus();topbarSearchInput.select();return;
    }
    if(topbarSearch instanceof HTMLFormElement&&topbarSearch.action)window.location.href=topbarSearch.action;
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
    const helpPanel=document.querySelector('[data-help-panel]');
    const helpIsOpen=helpPanel instanceof HTMLElement&&helpPanel.classList.contains('open');

    if(helpIsOpen&&e.key==='Tab'){
      const focusable=helpFocusable(helpPanel);
      if(focusable.length){
        const first=focusable[0],last=focusable[focusable.length-1];
        if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}
        else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
      }
      return;
    }

    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){
      e.preventDefault();openSearch();return;
    }
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
    const qTokens=q?q.split(' ').filter(Boolean):[];
    const roleFilter=norm(role instanceof HTMLSelectElement?role.value:'');
    const statusFilter=norm(status instanceof HTMLSelectElement?status.value:'');
    let visible=0;

    rows.forEach(row=>{
      if(!(row instanceof HTMLElement))return;
      const text=norm(row.dataset.userSearch||row.textContent||'');
      const rowRole=norm(row.dataset.userRole||'');
      const rowStatus=norm(row.dataset.userStatus||'');
      const searchMatch=qTokens.length===0||qTokens.every(token=>text.includes(token));
      const show=searchMatch&&(!roleFilter||rowRole===roleFilter)&&(!statusFilter||rowStatus===statusFilter);
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

/* Proveedor externo · el caso debe ser un espacio de colaboración, no una ficha de solo lectura */
(function(){
  const providerCard=document.querySelector('.provider-task-card');
  const workspace=document.querySelector('.ticket-workspace');
  if(!(providerCard instanceof HTMLElement)||!(workspace instanceof HTMLElement))return;

  document.body.classList.add('external-session');

  const conversation=document.querySelector('.conversation-card');
  if(conversation instanceof HTMLElement){
    providerCard.insertAdjacentElement('afterend',conversation);
  }

  const actions=document.createElement('div');
  actions.className='provider-quick-actions';

  if(conversation instanceof HTMLElement){
    const reply=document.createElement('a');
    reply.className='btn btn-primary';
    reply.href='#conversacion';
    reply.dataset.noLoading='1';
    reply.textContent='Responder a Carrousel';
    reply.addEventListener('click',()=>{
      const field=conversation.querySelector('textarea');
      window.setTimeout(()=>{if(field instanceof HTMLTextAreaElement)field.focus();},250);
    });
    actions.appendChild(reply);
  }

  const back=document.createElement('a');
  back.className='btn btn-outline-secondary';
  back.href='javascript:history.back()';
  back.dataset.noLoading='1';
  back.textContent='Volver a mis casos';
  actions.appendChild(back);

  providerCard.appendChild(actions);
})();

/* Select buscable global · inspirado en la experiencia de PayOutParques */
(function(){
  'use strict';

  const states=new WeakMap();
  const openStates=new Set();
  let sequence=0;

  function ensureSearchableSelectStyles(){
    if(document.querySelector('link[data-searchable-select-css]'))return;
    const current=document.currentScript;
    if(!(current instanceof HTMLScriptElement)||!current.src)return;
    const source=new URL(current.src,window.location.href);
    const href=new URL('../css/searchable-select.css',source);
    href.search=source.search;
    const link=document.createElement('link');
    link.rel='stylesheet';
    link.href=href.href;
    link.dataset.searchableSelectCss='1';
    document.head.appendChild(link);
  }

  const normalize=value=>String(value??'')
    .toLocaleLowerCase('es-GT')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g,'')
    .replace(/\s+/g,' ')
    .trim();

  function visibleButtons(state){
    return state.optionButtons.filter(button=>!button.hidden&&!button.disabled);
  }

  function syncFromNative(state){
    const selected=state.select.selectedOptions[0]||state.select.options[0]||null;
    const text=selected?.textContent?.trim()||'Selecciona una opción';
    state.value.textContent=text;
    state.value.classList.toggle('is-placeholder',!selected||selected.value==='');
    state.trigger.disabled=state.select.disabled;
    state.wrapper.classList.toggle('is-disabled',state.select.disabled);
    state.wrapper.classList.toggle('is-invalid',!state.select.validity.valid);
    state.optionButtons.forEach(button=>{
      const active=selected&&button.dataset.value===selected.value;
      button.setAttribute('aria-selected',active?'true':'false');
    });
  }

  function positionMenu(state){
    if(!state.menu.classList.contains('is-open'))return;
    const rect=state.trigger.getBoundingClientRect();
    const margin=8;
    const width=Math.min(Math.max(rect.width,220),window.innerWidth-margin*2);
    let left=Math.min(Math.max(rect.left,margin),window.innerWidth-width-margin);
    state.menu.style.width=`${width}px`;
    state.menu.style.left=`${left}px`;
    state.menu.style.top=`${Math.min(rect.bottom+6,window.innerHeight-margin)}px`;
    const height=state.menu.offsetHeight;
    const roomBelow=window.innerHeight-rect.bottom-margin;
    const roomAbove=rect.top-margin;
    if(height>roomBelow&&roomAbove>roomBelow){
      state.menu.style.top=`${Math.max(margin,rect.top-height-6)}px`;
    }
  }

  function setActive(state,index){
    const buttons=visibleButtons(state);
    state.optionButtons.forEach(button=>button.classList.remove('is-active'));
    if(buttons.length===0){state.activeIndex=-1;return;}
    const normalizedIndex=((index%buttons.length)+buttons.length)%buttons.length;
    state.activeIndex=normalizedIndex;
    const active=buttons[normalizedIndex];
    active.classList.add('is-active');
    active.scrollIntoView({block:'nearest'});
  }

  function filterOptions(state){
    const query=normalize(state.search.value);
    const tokens=query?query.split(' ').filter(Boolean):[];
    let shown=0;
    state.optionButtons.forEach(button=>{
      const haystack=normalize(button.dataset.searchTerms||button.dataset.label||'');
      const show=tokens.length===0||tokens.every(token=>haystack.includes(token));
      button.hidden=!show;
      if(show)shown+=1;
    });
    state.empty.hidden=shown!==0;
    state.activeIndex=-1;
    if(shown>0)setActive(state,0);
  }

  function closeSelect(state,returnFocus=false){
    if(!state.menu.classList.contains('is-open'))return;
    state.menu.classList.remove('is-open');
    state.wrapper.classList.remove('is-open');
    state.trigger.setAttribute('aria-expanded','false');
    openStates.delete(state);
    state.search.value='';
    filterOptions(state);
    if(returnFocus)state.trigger.focus();
  }

  function closeOthers(except=null){
    [...openStates].forEach(state=>{if(state!==except)closeSelect(state);});
  }

  function openSelect(state,focusLast=false){
    if(state.select.disabled)return;
    closeOthers(state);
    syncFromNative(state);
    state.menu.classList.add('is-open');
    state.wrapper.classList.add('is-open');
    state.trigger.setAttribute('aria-expanded','true');
    openStates.add(state);
    state.search.value='';
    filterOptions(state);
    positionMenu(state);
    window.requestAnimationFrame(()=>{
      state.search.focus();
      if(focusLast){const buttons=visibleButtons(state);if(buttons.length)setActive(state,buttons.length-1);}
    });
  }

  function selectOption(state,option){
    if(option.disabled)return;
    const changed=state.select.value!==option.value;
    state.select.value=option.value;
    state.wrapper.classList.remove('is-invalid');
    syncFromNative(state);
    if(changed){
      state.select.dispatchEvent(new Event('input',{bubbles:true}));
      state.select.dispatchEvent(new Event('change',{bubbles:true}));
    }
    closeSelect(state,true);
  }

  function rebuildOptions(state){
    state.options.innerHTML='';
    state.optionButtons=[];
    [...state.select.options].forEach((option,index)=>{
      const button=document.createElement('button');
      button.type='button';
      button.className='smart-select-option';
      button.setAttribute('role','option');
      button.dataset.value=option.value;
      button.dataset.label=option.textContent?.trim()||'';
      const group=option.parentElement instanceof HTMLOptGroupElement?option.parentElement.label:'';
      button.dataset.searchTerms=[
        option.textContent?.trim()||'',
        option.value||'',
        group,
        option.dataset.searchTerms||'',
        option.dataset.categoryHelp||'',
        option.dataset.categoryPlaceholder||''
      ].join(' ');
      button.textContent=option.textContent?.trim()||'—';
      button.disabled=option.disabled;
      button.dataset.optionIndex=String(index);
      button.addEventListener('click',()=>selectOption(state,option));
      state.options.appendChild(button);
      state.optionButtons.push(button);
    });
    state.options.appendChild(state.empty);
    filterOptions(state);
    syncFromNative(state);
  }

  function initSearchableSelect(select){
    if(!(select instanceof HTMLSelectElement))return;
    if(select.multiple||select.size>1||select.dataset.searchableSelect==='off'||states.has(select))return;

    sequence+=1;
    const wrapper=document.createElement('div');
    wrapper.className='smart-select';

    const trigger=document.createElement('button');
    trigger.type='button';
    trigger.className='smart-select-trigger';
    trigger.setAttribute('role','combobox');
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');

    const value=document.createElement('span');
    value.className='smart-select-value';
    const chevron=document.createElement('span');
    chevron.className='smart-select-chevron';
    chevron.setAttribute('aria-hidden','true');
    chevron.textContent='⌄';
    trigger.append(value,chevron);

    const menu=document.createElement('div');
    menu.className='smart-select-menu';
    menu.id=`smart-select-menu-${sequence}`;
    menu.setAttribute('role','presentation');
    trigger.setAttribute('aria-controls',menu.id);

    const searchWrap=document.createElement('div');
    searchWrap.className='smart-select-search-wrap';
    const search=document.createElement('input');
    search.type='search';
    search.className='smart-select-search';
    search.placeholder=select.dataset.searchPlaceholder||'Buscar...';
    search.autocomplete='off';
    search.spellcheck=false;
    search.setAttribute('aria-label','Buscar opción');
    searchWrap.appendChild(search);

    const options=document.createElement('div');
    options.className='smart-select-options';
    options.setAttribute('role','listbox');
    options.setAttribute('aria-label',select.getAttribute('aria-label')||select.name||'Opciones');

    const empty=document.createElement('div');
    empty.className='smart-select-empty';
    empty.textContent='Sin coincidencias';
    empty.hidden=true;

    menu.append(searchWrap,options);
    select.insertAdjacentElement('afterend',wrapper);
    wrapper.appendChild(trigger);
    document.body.appendChild(menu);
    select.classList.add('smart-select-native');

    const state={select,wrapper,trigger,value,menu,search,options,empty,optionButtons:[],activeIndex:-1};
    states.set(select,state);

    trigger.addEventListener('click',()=>{
      if(menu.classList.contains('is-open'))closeSelect(state);else openSelect(state);
    });
    trigger.addEventListener('keydown',event=>{
      if(event.key==='ArrowDown'){event.preventDefault();openSelect(state,false);}
      else if(event.key==='ArrowUp'){event.preventDefault();openSelect(state,true);}
      else if(event.key==='Enter'||event.key===' '){event.preventDefault();openSelect(state,false);}
      else if(event.key==='Escape'){event.preventDefault();closeSelect(state);}
    });
    search.addEventListener('input',()=>filterOptions(state));
    search.addEventListener('keydown',event=>{
      const buttons=visibleButtons(state);
      if(event.key==='ArrowDown'){
        event.preventDefault();setActive(state,state.activeIndex+1);
      }else if(event.key==='ArrowUp'){
        event.preventDefault();setActive(state,state.activeIndex-1);
      }else if(event.key==='Enter'){
        event.preventDefault();
        const active=buttons[state.activeIndex]||buttons[0];
        if(active){const index=Number(active.dataset.optionIndex);const option=state.select.options[index];if(option)selectOption(state,option);}
      }else if(event.key==='Escape'){
        event.preventDefault();closeSelect(state,true);
      }else if(event.key==='Tab'){
        closeSelect(state);
      }
    });
    select.addEventListener('change',()=>syncFromNative(state));
    select.addEventListener('focus',()=>trigger.focus());
    select.addEventListener('invalid',()=>{
      wrapper.classList.add('is-invalid');
      window.setTimeout(()=>trigger.focus(),0);
    });

    rebuildOptions(state);
  }

  ensureSearchableSelectStyles();
  document.querySelectorAll('select').forEach(initSearchableSelect);

  document.addEventListener('click',event=>{
    const target=event.target instanceof Node?event.target:null;
    if(!target)return;
    [...openStates].forEach(state=>{
      if(state.wrapper.contains(target)||state.menu.contains(target))return;
      closeSelect(state);
    });
  });

  document.addEventListener('reset',event=>{
    const form=event.target instanceof HTMLFormElement?event.target:null;
    if(!form)return;
    window.setTimeout(()=>form.querySelectorAll('select').forEach(select=>{
      const state=states.get(select);if(state)syncFromNative(state);
    }),0);
  });

  const reposition=()=>openStates.forEach(positionMenu);
  window.addEventListener('resize',reposition,{passive:true});
  window.addEventListener('scroll',reposition,{passive:true,capture:true});

  const observer=new MutationObserver(mutations=>{
    mutations.forEach(mutation=>{
      if(mutation.type==='childList'){
        mutation.addedNodes.forEach(node=>{
          if(!(node instanceof Element))return;
          if(node.matches('select'))initSearchableSelect(node);
          node.querySelectorAll?.('select').forEach(initSearchableSelect);
        });
        const select=mutation.target instanceof Element?mutation.target.closest('select'):null;
        const state=select?states.get(select):null;
        if(state)rebuildOptions(state);
      }else if(mutation.type==='attributes'){
        const target=mutation.target instanceof Element?mutation.target:null;
        const select=target instanceof HTMLSelectElement?target:target?.closest('select');
        const state=select?states.get(select):null;
        if(state)rebuildOptions(state);
      }
    });
  });
  observer.observe(document.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['disabled','selected']});

  window.HelpdeskSearchableSelect=Object.freeze({
    init:initSearchableSelect,
    refresh(select){const state=states.get(select);if(state)rebuildOptions(state);else initSearchableSelect(select);}
  });
})();
