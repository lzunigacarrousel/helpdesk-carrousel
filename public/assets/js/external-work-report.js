(function(){
  'use strict';
  const forms=document.querySelectorAll('[data-external-work-report]');
  if(!forms.length)return;

  const includesToken=(value,token)=>String(value||'').split(/\s+/).filter(Boolean).includes(token);

  forms.forEach(function(form){
    const template=String(form.getAttribute('data-report-template')||'GENERAL_SUPPORT').trim();
    const statusField=form.querySelector('[data-work-status]');
    if(!statusField)return;

    const setBlockState=function(block,visible){
      block.hidden=!visible;
      block.querySelectorAll('input,select,textarea,button').forEach(function(field){
        if(field===statusField)return;
        field.disabled=!visible;
      });
    };

    const refresh=function(){
      const workStatus=String(statusField.value||'ANALYSIS');

      form.querySelectorAll('[data-report-template-block]').forEach(function(block){
        const allowed=block.getAttribute('data-report-template-block')||'';
        setBlockState(block,includesToken(allowed,template));
      });

      form.querySelectorAll('[data-work-status-block]').forEach(function(block){
        const allowed=block.getAttribute('data-work-status-block')||'';
        setBlockState(block,includesToken(allowed,workStatus));
      });
    };

    statusField.addEventListener('change',refresh);
    refresh();
  });
})();
