(function(){
  'use strict';

  function isTicketAttachment(link){
    try{
      const url=new URL(link.href,window.location.href);
      return url.pathname.endsWith('/tickets/attachment');
    }catch(_){
      return false;
    }
  }

  document.addEventListener('click',event=>{
    const target=event.target instanceof Element?event.target:null;
    const link=target?.closest('a[href]');
    if(!(link instanceof HTMLAnchorElement)||!isTicketAttachment(link))return;

    // La respuesta es una descarga (Content-Disposition: attachment), no una navegacion.
    // app.js consulta este atributo antes de mostrar el overlay global.
    link.setAttribute('data-no-loading','1');
  },true);
})();
