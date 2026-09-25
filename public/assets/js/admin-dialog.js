(function(){
  'use strict';

  const getDialog=function(id){
    if(!id)return null;
    const node=document.getElementById(id);
    return node instanceof HTMLDialogElement?node:null;
  };

  const triggerFor=function(dialog){
    if(!dialog?.id)return null;
    return document.querySelector('[data-admin-dialog-open="'+CSS.escape(dialog.id)+'"]');
  };

  const closeDialog=function(dialog){
    if(!(dialog instanceof HTMLDialogElement)||!dialog.open)return;
    dialog.close();
  };

  document.addEventListener('click',function(event){
    const opener=event.target.closest('[data-admin-dialog-open]');
    if(opener){
      const dialog=getDialog(opener.getAttribute('data-admin-dialog-open')||'');
      if(!dialog)return;
      event.preventDefault();
      document.querySelectorAll('dialog[data-admin-dialog][open]').forEach(function(other){
        if(other!==dialog)other.close();
      });
      opener.setAttribute('aria-expanded','true');
      dialog.showModal();
      const focusTarget=dialog.querySelector('[data-admin-dialog-focus],input:not([type="hidden"]):not([disabled]),select:not([disabled]),textarea:not([disabled]),button:not([disabled])');
      window.setTimeout(function(){focusTarget?.focus();},0);
      return;
    }

    const closer=event.target.closest('[data-admin-dialog-close]');
    if(closer){
      const dialog=closer.closest('dialog[data-admin-dialog]');
      if(dialog){
        event.preventDefault();
        closeDialog(dialog);
      }
    }
  });

  document.querySelectorAll('dialog[data-admin-dialog]').forEach(function(dialog){
    dialog.addEventListener('click',function(event){
      if(event.target===dialog)dialog.close();
    });
    dialog.addEventListener('close',function(){
      triggerFor(dialog)?.setAttribute('aria-expanded','false');
    });
  });
})();
