<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$widget=(string)file_get_contents($root.'/app/Views/shared/help_widget.php');
$app=(string)file_get_contents($root.'/public/assets/js/app.js');
$manual=(string)file_get_contents($root.'/app/Views/help/manual.php');
$components=(string)file_get_contents($root.'/public/assets/css/components.css');
$tour=(string)file_get_contents($root.'/public/assets/js/help-tour.js');
$tourCss=(string)file_get_contents($root.'/public/assets/css/help-tour-contrast.css');

ok(str_contains($widget,'aria-controls="helpdesk-context-help"'),'Botón ayuda declara aria-controls');
ok(str_contains($widget,'aria-expanded="false"'),'Botón ayuda expone estado expandido');
ok(str_contains($widget,'role="dialog"'),'Panel de ayuda usa role dialog');
ok(str_contains($widget,'aria-modal="true"'),'Panel de ayuda es modal');
ok(str_contains($widget,'aria-labelledby="helpdesk-context-help-title"'),'Panel enlaza título accesible');
ok(str_contains($widget,'id="helpdesk-context-help-title"'),'Título accesible existe');

ok(str_contains($app,'let helpReturnFocus=null'),'Ayuda recuerda foco de origen');
ok(str_contains($app,'function helpFocusable(panel)'),'Ayuda calcula controles enfocables');
ok(str_contains($app,"trigger.setAttribute('aria-expanded',open?'true':'false')"),'Ayuda sincroniza aria-expanded');
ok(str_contains($app,"close.focus({preventScroll:true})"),'Ayuda enfoca botón cerrar al abrir');
ok(str_contains($app,"target.focus({preventScroll:true})"),'Ayuda devuelve foco al cerrar');
ok(str_contains($app,"if(helpIsOpen&&e.key==='Tab')"),'Ayuda atrapa Tab');
ok(str_contains($app,'e.shiftKey&&document.activeElement===first'),'Ayuda soporta Shift+Tab');

ok(str_contains($manual,'role="status" aria-live="polite" aria-atomic="true"'),'Estado del buscador se anuncia');
ok(str_contains($manual,"target.setAttribute('tabindex','-1')"),'Destino profundo puede recibir foco');
ok(str_contains($manual,"target.focus({preventScroll:true})"),'Deep-link enfoca sección destino');

ok(str_contains($components,'.manual-topic{min-height:44px'),'Temas tienen tamaño táctil');
ok(str_contains($components,'.manual-quick-card:focus-visible'),'Accesos rápidos tienen foco visible');
ok(str_contains($components,'.manual-faq summary:focus-visible'),'FAQ tiene foco visible');
ok(str_contains($components,'.manual-section{scroll-margin-top:92px}'),'Secciones dejan espacio al topbar');
ok(str_contains($components,'@media(max-width:430px)'),'Manual tiene cierre para móvil pequeño');
ok(str_contains($components,'.manual-topic-filter{grid-template-columns:1fr}'),'Temas pasan a una columna en móvil pequeño');

ok(str_contains($tour,'let tourReturnFocus=null'),'Tutorial recuerda foco de origen');
ok(str_contains($tour,"popover.setAttribute('aria-labelledby','helpdesk-tour-title')"),'Tutorial enlaza título accesible');
ok(str_contains($tour,'id="helpdesk-tour-title"'),'Título del tutorial tiene id estable');
ok(str_contains($tour,'function start(trigger=null)'),'Tutorial recibe control de origen');
ok(str_contains($tour,"trigger.closest('[data-help-panel]')"),'Tutorial iniciado desde ayuda vuelve al botón flotante');
ok(str_contains($tour,"helpPanel.setAttribute('aria-hidden','true')"),'Tutorial cierra semánticamente la ayuda');
ok(str_contains($tour,"helpTrigger.setAttribute('aria-expanded','false')"),'Tutorial sincroniza botón ayuda');
ok(str_contains($tour,'tourReturnFocus instanceof HTMLElement'),'Tutorial devuelve foco al salir');
ok(str_contains($tour,"if(e.key==='Escape')"),'Tutorial soporta Escape');
ok(str_contains($tour,"if(e.key!=='Tab'"),'Tutorial atrapa Tab');
ok(str_contains($tour,'e.shiftKey&&document.activeElement===first'),'Tutorial soporta Shift+Tab');

ok(str_contains($tourCss,'.tour-actions .btn{min-height:44px'),'Acciones del tutorial son táctiles');
ok(str_contains($tourCss,'.tour-actions .btn:focus-visible'),'Acciones del tutorial muestran foco');
ok(str_contains($tourCss,'@media(max-width:520px)'),'Tutorial conserva breakpoint móvil');
ok(str_contains($tourCss,'.tour-actions{grid-template-columns:1fr}'),'Acciones del tutorial se apilan en móvil');
ok(str_contains($tourCss,'@media(prefers-reduced-motion:reduce)'),'Tutorial respeta reduced motion');
ok(str_contains($tourCss,'transition:none!important;transform:none!important'),'Reduced motion elimina transición y desplazamiento');

ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Task 5 no introduce migración de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] UI, responsive y accesibilidad del Manual Fase 11 consolidados.'.PHP_EOL;
