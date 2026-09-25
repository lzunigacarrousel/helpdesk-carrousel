<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/public/assets/js/app.js');
$view=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$ok=true;

function sidebarCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

sidebarCheck(str_contains($js,"SIDEBAR_SCROLL_KEY='helpdesk:sidebar-scroll-top'"),'Existe clave de posición del sidebar');
sidebarCheck(str_contains($js,'sessionStorage.setItem(SIDEBAR_SCROLL_KEY'),'Sidebar guarda posición vertical');
sidebarCheck(str_contains($js,'sessionStorage.getItem(SIDEBAR_SCROLL_KEY'),'Sidebar recupera posición vertical');
sidebarCheck(str_contains($js,'restoreSidebarScroll();'),'Posición se restaura al cargar');
sidebarCheck(str_contains($js,"window.addEventListener('pageshow',restoreSidebarScroll)"),'Back/forward también restaura posición');
sidebarCheck(str_contains($js,"sidebar?.addEventListener('scroll'"),'Scroll manual queda persistido');
sidebarCheck(str_contains($js,"link.closest('#app-sidebar')"),'Click de navegación guarda posición antes de salir');
sidebarCheck(str_contains($js,"sidebar.querySelector('.side-link.active')"),'Se localiza la opción activa');
sidebarCheck(str_contains($js,"active.scrollIntoView({block:'nearest'})"),'La opción activa se mantiene visible');
sidebarCheck(str_contains($view,'id="app-sidebar"'),'Shell conserva id estable del sidebar');

exit($ok?0:1);
