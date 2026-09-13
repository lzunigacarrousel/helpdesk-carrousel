(function(){
  'use strict';

  function initFeedback(root){
    var choices=Array.prototype.slice.call(root.querySelectorAll('[data-feedback-choice]'));
    var panels=Array.prototype.slice.call(root.querySelectorAll('[data-feedback-panel]'));
    if(!choices.length||!panels.length)return;

    function select(choice){
      choices.forEach(function(button){
        var active=button.getAttribute('data-feedback-choice')===choice;
        button.setAttribute('aria-expanded',active?'true':'false');
        button.setAttribute('aria-pressed',active?'true':'false');
      });
      panels.forEach(function(panel){
        panel.hidden = panel.getAttribute('data-feedback-panel')!==choice;
      });

      var target=root.querySelector('[data-feedback-panel="'+choice+'"]');
      if(target){
        var field=target.querySelector('input:not([type="hidden"]), textarea, button');
        if(field&&typeof field.focus==='function')field.focus({preventScroll:true});
      }
    }

    choices.forEach(function(button){
      button.addEventListener('click',function(){
        select(button.getAttribute('data-feedback-choice')||'');
      });
    });
  }

  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-feedback-flow]').forEach(initFeedback);
  });
})();
