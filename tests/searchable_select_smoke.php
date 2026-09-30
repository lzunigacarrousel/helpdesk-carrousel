<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$js=(string)@file_get_contents($root.'/public/assets/js/app.js');
$css=(string)@file_get_contents($root.'/public/assets/css/searchable-select.css');
$shell=(string)@file_get_contents($root.'/app/Views/shared/app_end.php');
$catalogs=(string)@file_get_contents($root.'/app/Views/admin/catalogs.php');
$ok=true;

function check(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

check($js!=='','Se puede leer app.js');
check($css!=='','Existe y se puede leer searchable-select.css');
check(str_contains($js,'initSearchableSelect'),'Existe inicializador global de selects buscables');
check(str_contains($js,"querySelectorAll('select')"),'La mejora alcanza los selects del documento completo');
check(str_contains($js,'MutationObserver'),'Los selects agregados dinámicamente también se mejoran');
check(str_contains($js,"new Event('change',{bubbles:true})"),'La selección conserva eventos change para la lógica existente');
check(str_contains($js,"event.key==='ArrowDown'"),'Navegación por teclado contempla ArrowDown');
check(str_contains($js,"event.key==='ArrowUp'"),'Navegación por teclado contempla ArrowUp');
check(str_contains($js,"event.key==='Enter'"),'Navegación por teclado contempla Enter');
check(str_contains($js,"event.key==='Escape'"),'Navegación por teclado contempla Escape');
check(str_contains($js,'searchable-select.css'),'app.js conserva fallback para cargar la hoja visual');
check(str_contains($shell,'searchable-select.css')&&str_contains($shell,'data-searchable-select-css'),'Shell carga searchable-select.css globalmente');
check(str_contains($js,"querySelectorAll('select').forEach(initSearchableSelect)"),'Todos los selects simples se inicializan con el componente global');
check(str_contains($catalogs,'data-search-placeholder="Buscar región…"'),'Catálogos define búsqueda contextual para Región');
check(substr_count($catalogs,'data-search-placeholder="Buscar estado…"')>=3,'Catálogos define búsqueda contextual para Estado');
check(str_contains($css,'.smart-select'),'Existe la capa visual del select buscable');
check(str_contains($css,'.smart-select-search'),'Existe el campo de búsqueda del desplegable');
check(str_contains($css,'html[data-theme="dark"] .smart-select'),'El componente tiene tratamiento explícito en modo oscuro');
check(str_contains($css,'@media(max-width:760px)')&&str_contains($css,'.smart-select-menu'),'El componente contempla vista móvil');
check(str_contains($js,"menu.setAttribute('popover','manual')"),'El menú buscable usa Popover API cuando está disponible');
check(str_contains($js,'state.menu.showPopover()')&&str_contains($js,'state.menu.hidePopover()'),'Popover abre y cierra junto al estado del select');
check(str_contains($css,'.smart-select-menu[popover]:popover-open'),'CSS contempla el menú en la top layer');
check(str_contains($js,"document.addEventListener('close'"),'Cerrar un dialog también cierra su select buscable abierto');

exit($ok?0:1);
