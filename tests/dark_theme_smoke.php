<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$css=(string)@file_get_contents($root.'/public/assets/css/dark-refinement.css');
$ok=true;

function check(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

check($css!=='','Se puede leer dark-refinement.css');
check(str_contains($css,'--dark-canvas:#0b0f17;'),'Modo oscuro define canvas neutro');
check(str_contains($css,'--dark-surface:#121821;'),'Modo oscuro define superficie principal');
check(str_contains($css,'--dark-surface-raised:#18212e;'),'Modo oscuro define superficie elevada');
check(str_contains($css,'--dark-sidebar:#0f172a;'),'Sidebar usa tono oscuro neutro');
check(str_contains($css,'--dark-topbar:#0d141f;'),'Topbar usa tono oscuro neutro');
check(str_contains($css,'color-scheme:dark;'),'Navegador recibe esquema de color oscuro');
check(str_contains($css,'background:var(--dark-sidebar);'),'Sidebar consume token semántico oscuro');
check(str_contains($css,'background:var(--dark-topbar);'),'Topbar consume token semántico oscuro');
check(str_contains($css,'background:var(--dark-surface);'),'Componentes consumen superficie principal');
check(str_contains($css,'html[data-theme="dark"] .data-table-shell'),'Tablas canónicas tienen tratamiento oscuro explícito');
check(str_contains($css,'html[data-theme="dark"] .data-table thead th'),'Encabezado de tablas tiene jerarquía oscura explícita');
check(str_contains($css,'html[data-theme="dark"] .public-request-topbar'),'Formulario público tiene tratamiento oscuro explícito');
check(str_contains($css,'html[data-theme="dark"] .public-panel'),'Inicio público tiene tratamiento oscuro explícito');
check(str_contains($css,'html[data-theme="dark"] .friendly-error-card'),'Pantalla de error tiene tratamiento oscuro explícito');
check(str_contains($css,'html[data-theme="dark"] .public-result-card'),'Confirmación pública tiene tratamiento oscuro explícito');
check(!str_contains($css,'background:#14245b'),'Se elimina el sidebar azul saturado anterior');
check(!str_contains($css,'background:#101827'),'Se elimina el canvas azul saturado anterior');

exit($ok?0:1);
