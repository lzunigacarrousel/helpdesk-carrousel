<?php
declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/app/Core/SearchText.php';

use App\Core\SearchText;

$ok=true;
function searchCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

searchCheck(SearchText::matches('Caja Chica / NIT / facturas','nit'),'Coincidencia parcial encuentra NIT');
searchCheck(SearchText::matches('Pantalla o monitor','nit'),'Coincidencia parcial conserva nit dentro de monitor');
searchCheck(SearchText::matches('Crítica solución rápida','critica solucion'),'Ignora tildes y combina varias palabras');
searchCheck(SearchText::matches('Luis Fernándo Zuñiga','fernando zuniga'),'Ignora tildes en nombres');
searchCheck(SearchText::matches('HD-2026-000001 Error de impresora','2026 impres'),'Combina tokens no contiguos');
searchCheck(!SearchText::matches('Payout Parques','nit'),'Descarta contenido sin coincidencia');
searchCheck(SearchText::tokens('  Caja   NIT  ')===['caja','nit'],'Normaliza espacios y tokens');

$searchController=(string)file_get_contents($root.'/app/Controllers/SearchController.php');
$queue=(string)file_get_contents($root.'/app/Views/tickets/queue.php');
$agenda=(string)file_get_contents($root.'/app/Services/AgendaService.php');
$knowledge=(string)file_get_contents($root.'/app/Controllers/KnowledgeController.php');
$problems=(string)file_get_contents($root.'/app/Controllers/ProblemController.php');
$audit=(string)file_get_contents($root.'/app/Controllers/AuditController.php');
$providers=(string)file_get_contents($root.'/app/Services/ProviderParticipationService.php');
$appJs=(string)file_get_contents($root.'/public/assets/js/app.js');
$appStart=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$shellCss=(string)file_get_contents($root.'/public/assets/css/shell-v2.css');
$searchView=(string)file_get_contents($root.'/app/Views/search/index.php');

searchCheck(str_contains($searchController,'SearchText::tokens'),'Buscador global usa tokens comunes');
searchCheck(str_contains($searchController,'RequesterTopicService::options'),'Buscador global incluye catálogo de ayuda');
searchCheck(str_contains($searchView,'data-search-scope="help"'),'Vista global muestra coincidencias de ayuda');
searchCheck(!str_contains($searchView,'class="search-page-form"'),'La pagina de resultados no duplica el buscador global');
searchCheck(!str_contains($searchView,'class="search-command"'),'La pagina de resultados no repite un bloque grande de contexto');
searchCheck(str_contains($searchView,'Escribe en el buscador superior'),'El estado vacio orienta a usar el buscador unico superior');
searchCheck(str_contains($queue,'SearchText::matches'),'Centro de soporte usa coincidencia común');
searchCheck(str_contains($agenda,'SearchText::tokens'),'Agenda usa coincidencias por palabras');
searchCheck(str_contains($knowledge,'SearchText::tokens'),'Conocimiento usa coincidencias por palabras');
searchCheck(str_contains($problems,'SearchText::tokens'),'Problemas usa coincidencias por palabras');
searchCheck(str_contains($audit,'SearchText::tokens'),'Auditoría usa coincidencias por palabras');
searchCheck(str_contains($providers,'SearchText::matches'),'Informe de proveedores usa coincidencia común');
searchCheck(str_contains($appJs,'qTokens.every'),'Usuarios usa coincidencias por palabras');
searchCheck(str_contains($appJs,'tokens.every'),'Selects buscables usan coincidencias por palabras');
searchCheck(str_contains($appStart,'data-topbar-search-clear'),'Buscador superior tiene limpiador visible');
searchCheck(str_contains($appJs,'syncTopbarSearchClear'),'Limpiador superior sincroniza su visibilidad');
searchCheck(str_contains($shellCss,'.topbar-search-clear'),'Limpiador superior tiene estilo propio y tamaño accesible');
searchCheck(str_contains($shellCss,'::-webkit-search-cancel-button'),'Se oculta la X nativa pequeña del navegador');

exit($ok?0:1);
