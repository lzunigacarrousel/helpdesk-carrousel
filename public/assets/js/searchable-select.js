(() => {
  'use strict';

  const SELECTOR='select[data-searchable-select]';
  const OPEN=[];

  const norm=(value)=>String(value??'')
    .toLocaleLowerCase('es-GT')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,' ')
    .replace(/\s+/g,' ')
    .trim();

  const optionTerms=(option)=>{
    const group=option.parentElement instanceof HTMLOptGroupElement?option.parentElement.label:'';
    const explicit=option.dataset.searchTerms||'';
    const data=Object.values(option.dataset||{}).join(' ');
    return norm([option.textContent,option.value,group,explicit,data].join(' '));
  };

  const positionMenu=(state)=>{
    if(!state.open)return;
    const rect=state.trigger.getBoundingClientRect();
    const margin=8;
    const width=Math.min(Math.max(rect.width,280),window.innerWidth-margin*2);
    state.menu.style.width=width+'px';
    state.menu.style.left=Math.min(Math.max(margin,rect.left),window.innerWidth-width-margin)+'px';

    const menuHeight=Math.min(state.menu.scrollHeight,420);
    const below=window.innerHeight-rect.bottom-margin;
    const above=rect.top-margin;
    if(below>=Math.min(menuHeight,260)||below>=above){
      state.menu.style.top=Math.min(window.innerHeight-menuHeight-margin,rect.bottom+5)+'px';
    }else{
      state.menu.style.top=Math.max(margin,rect.top-menuHeight-5)+'px';
    }
  };

  const close=(state,restoreFocus=false)=>{
    if(!state.open)return;
    state.open=false;
    state.root.classList.remove('is-open');
    state.menu.classList.remove('is-open');
    state.trigger.setAttribute('aria-expanded','false');
    if(restoreFocus)state.trigger.focus({preventScroll:true});
  };

  const open=(state)=>{
    if(state.select.disabled)return;
    OPEN.forEach(other=>{if(other!==state)close(other);});
    state.open=true;
    state.root.classList.add('is-open');
    state.menu.classList.add('is-open');
    state.trigger.setAttribute('aria-expanded','true');
    state.search.value='';
    filter(state);
    positionMenu(state);
    requestAnimationFrame(()=>state.search.focus({preventScroll:true}));
  };

  const updateTrigger=(state)=>{
    const option=state.select.options[state.select.selectedIndex];
    const text=option?.textContent?.trim()||state.placeholder;
    state.value.textContent=text;
    state.value.classList.toggle('is-placeholder',!state.select.value);
    state.trigger.disabled=state.select.disabled;
    state.root.classList.toggle('is-invalid',state.select.matches(':invalid'));
    state.buttons.forEach(({button,option:opt})=>{
      button.setAttribute('aria-selected',opt.selected?'true':'false');
    });
  };

  function filter(state){
    const query=norm(state.search.value);
    const tokens=query?query.split(' '):[];
    let visible=0;
    state.buttons.forEach(item=>{
      const show=!tokens.length||tokens.every(token=>item.terms.includes(token));
      item.button.hidden=!show;
      item.button.style.display=show?'flex':'none';
      if(show)visible++;
    });
    state.empty.hidden=visible!==0;
    state.options.scrollTop=0;
  }

  const choose=(state,option)=>{
    if(option.disabled)return;
    state.select.value=option.value;
    state.select.dispatchEvent(new Event('input',{bubbles:true}));
    state.select.dispatchEvent(new Event('change',{bubbles:true}));
    updateTrigger(state);
    close(state,true);
  };

  const enhance=(select)=>{
    if(!(select instanceof HTMLSelectElement)||select.dataset.searchableReady==='1')return;
    let sibling=select.nextElementSibling;
    while(sibling instanceof HTMLElement&&sibling.matches('[data-smart-select]')){
      const next=sibling.nextElementSibling;
      sibling.remove();
      sibling=next;
    }
    select.dataset.searchableReady='1';

    const placeholder=select.options[0]?.value===''?(select.options[0].textContent?.trim()||'Selecciona una opción'):'Selecciona una opción';
    const root=document.createElement('div');
    root.className='smart-select';
    root.dataset.smartSelect='1';
    root.dataset.smartSelect='';

    const trigger=document.createElement('button');
    trigger.type='button';
    trigger.className='smart-select-trigger';
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
    menu.setAttribute('role','dialog');

    const searchWrap=document.createElement('div');
    searchWrap.className='smart-select-search-wrap';
    const search=document.createElement('input');
    search.type='search';
    search.className='smart-select-search';
    search.autocomplete='off';
    search.spellcheck=false;
    search.placeholder=select.dataset.searchPlaceholder||'Buscar…';
    search.setAttribute('aria-label','Buscar opciones');
    searchWrap.appendChild(search);

    const options=document.createElement('div');
    options.className='smart-select-options';
    options.setAttribute('role','listbox');

    const empty=document.createElement('div');
    empty.className='smart-select-empty';
    empty.hidden=true;
    empty.textContent='No encontramos opciones con esa búsqueda.';

    const buttons=[];
    [...select.options].forEach(option=>{
      if(option.value==='')return;
      const button=document.createElement('button');
      button.type='button';
      button.className='smart-select-option';
      button.setAttribute('role','option');
      button.disabled=option.disabled;

      const text=document.createElement('span');
      text.className='smart-select-option-main';
      text.textContent=option.textContent?.trim()||option.value;
      button.appendChild(text);

      const group=option.parentElement instanceof HTMLOptGroupElement?option.parentElement.label:'';
      if(group){
        const meta=document.createElement('small');
        meta.className='smart-select-option-group';
        meta.textContent=group;
        button.appendChild(meta);
      }

      const item={button,option,terms:optionTerms(option)};
      button.addEventListener('click',()=>choose(state,option));
      options.appendChild(button);
      buttons.push(item);
    });

    options.appendChild(empty);
    menu.append(searchWrap,options);

    select.classList.add('smart-select-native');
    select.insertAdjacentElement('afterend',root);
    root.append(trigger,menu);

    const state={select,root,trigger,value,menu,search,options,empty,buttons,placeholder,open:false};
    OPEN.push(state);

    trigger.addEventListener('click',()=>state.open?close(state,true):open(state));
    search.addEventListener('input',()=>filter(state));
    search.addEventListener('keydown',(event)=>{
      if(event.key==='Escape'){event.preventDefault();close(state,true);return;}
      if(event.key==='Enter'){
        const first=state.buttons.find(item=>!item.button.hidden&&!item.button.disabled);
        if(first){event.preventDefault();choose(state,first.option);}
      }
    });
    select.addEventListener('change',()=>updateTrigger(state));
    select.addEventListener('invalid',(event)=>{
      event.preventDefault();
      state.root.classList.add('is-invalid');
      open(state);
    });
    select.form?.addEventListener('reset',()=>requestAnimationFrame(()=>updateTrigger(state)));
    updateTrigger(state);
  };

  document.querySelectorAll(SELECTOR).forEach(enhance);

  document.addEventListener('pointerdown',(event)=>{
    const target=event.target;
    OPEN.forEach(state=>{
      if(state.open&&target instanceof Node&&!state.root.contains(target)&&!state.menu.contains(target))close(state);
    });
  });
  window.addEventListener('resize',()=>OPEN.forEach(positionMenu));
  window.addEventListener('scroll',()=>OPEN.forEach(positionMenu),true);
})();