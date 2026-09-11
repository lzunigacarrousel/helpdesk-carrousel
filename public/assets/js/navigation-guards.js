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

  function markAttachmentLink(link){
    if(!(link instanceof HTMLAnchorElement)||!isTicketAttachment(link))return;
    // La respuesta usa Content-Disposition: attachment. Se marca antes del click para que
    // app.js no la trate como una navegación que deba mantener el overlay visible.
    link.setAttribute('data-no-loading','1');
    if(!link.hasAttribute('download'))link.setAttribute('download','');
  }

  function markTicketAttachments(root=document){
    if(!(root instanceof Document||root instanceof Element))return;
    root.querySelectorAll('a[href]').forEach(link=>markAttachmentLink(link));
  }

  // El shell carga este script al final del documento: los adjuntos existentes quedan
  // protegidos inmediatamente, sin depender del orden de listeners durante el click.
  markTicketAttachments();

  // Conserva el comportamiento si un módulo agrega mensajes/adjuntos dinámicamente.
  const observer=new MutationObserver(records=>{
    records.forEach(record=>record.addedNodes.forEach(node=>{
      if(node instanceof HTMLAnchorElement)markAttachmentLink(node);
      else if(node instanceof Element)markTicketAttachments(node);
    }));
  });
  observer.observe(document.body,{childList:true,subtree:true});

  document.addEventListener('click',event=>{
    const target=event.target instanceof Element?event.target:null;
    const link=target?.closest('a[href]');
    if(link instanceof HTMLAnchorElement)markAttachmentLink(link);
  },true);
})();
