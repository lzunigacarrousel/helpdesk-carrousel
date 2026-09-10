<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$js=(string)@file_get_contents($root.'/public/assets/js/app.js');
$css=(string)@file_get_contents($root.'/public/assets/css/searchable-select.css');
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
check(str_contains($js,'searchable-select.css'),'app.js carga la hoja visual del componente sin depender del módulo');
check(str_contains($css,'.smart-select'),'Existe la capa visual del select buscable');
check(str_contains($css,'.smart-select-search'),'Existe el campo de búsqueda del desplegable');
check(str_contains($css,'html[data-theme="dark"] .smart-select'),'El componente tiene tratamiento explícito en modo oscuro');
check(str_contains($css,'@media(max-width:760px)')&&str_contains($css,'.smart-select-menu'),'El componente contempla vista móvil');

exit($ok?0:1);
