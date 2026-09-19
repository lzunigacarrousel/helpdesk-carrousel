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

$app=(string)file_get_contents($root.'/public/assets/css/app.css');
$shell=(string)file_get_contents($root.'/public/assets/css/shell-v2.css');
$components=(string)file_get_contents($root.'/public/assets/css/components.css');
$visual=(string)file_get_contents($root.'/public/assets/css/visual-system.css');
$dark=(string)file_get_contents($root.'/public/assets/css/dark-refinement.css');
$appStart=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$publicHome=(string)file_get_contents($root.'/app/Views/tickets/public_home.php');
$publicCreate=(string)file_get_contents($root.'/app/Views/tickets/public_create.php');
$ticket=(string)file_get_contents($root.'/app/Views/tickets/show.php');
$external=(string)file_get_contents($root.'/app/Views/tickets/show_external.php');
$manual=(string)file_get_contents($root.'/app/Views/help/manual.php');
$reports=(string)file_get_contents($root.'/app/Views/management/reports.php');

ok(str_contains($appStart,'name="viewport"'),'Shell autenticado declara viewport');
ok(str_contains($publicHome,'name="viewport"'),'Portada pública declara viewport');
ok(str_contains($publicCreate,'name="viewport"'),'Crear ticket declara viewport');

ok(str_contains($visual,'max-width:none!important'),'Workspace puede aprovechar pantallas amplias');
ok(str_contains($visual,'width:min(var(--vs-max),100%)'),'Contenido conserva ancho útil');
ok(str_contains($app,'max-width:1700px')||str_contains($visual,'--vs-max'),'Escritorio amplio conserva límite razonable');
ok(str_contains($app,'font-size:15px'),'Texto base mantiene tamaño legible');
ok(str_contains($app,'.field-help{font-size:13px'),'Ayuda secundaria mantiene legibilidad');
ok(str_contains($app,'.small{font-size:12px'),'Microtexto queda reservado a metadata');

ok(str_contains($shell,'@media(max-width:1260px)'),'Shell adapta escritorio compacto');
ok(str_contains($shell,'@media(max-width:1100px)'),'Shell adapta tablet horizontal');
ok(str_contains($shell,'@media(max-width:900px)'),'Shell adapta tablet vertical');
ok(str_contains($shell,'@media(max-width:760px)'),'Shell reserva móvil para <=760px');
ok(str_contains($shell,'@media(min-width:761px) and (max-width:900px)'),'Existe tratamiento específico tablet');
ok(!str_contains($shell,'@media(max-width:768px)'),'iPad 768px no se fuerza automáticamente a móvil');
ok(!str_contains($visual,'@media(max-width:768px)'),'Sistema visual no fuerza iPad 768px a móvil');

ok(str_contains($components,'@media(min-width:1500px)'),'Componentes aprovechan 1920px');
ok(str_contains($components,'@media(max-width:1180px)')||str_contains($visual,'@media(max-width:1180px)'),'Componentes adaptan 1366/tablet');
ok(str_contains($components,'@media(max-width:430px)'),'Componentes cierran móvil pequeño');
ok(str_contains($visual,'.public-form-shell{width:min(1320px,100%)'),'Formulario público aprovecha escritorio sin estirar lectura');
ok(str_contains($visual,'.public-form-upper{gap:12px!important;grid-template-columns:repeat(2,minmax(0,1fr))'),'Formulario público usa grid eficiente');
ok(str_contains($visual,'@media(max-width:900px)')&&str_contains($visual,'.public-form-upper{grid-template-columns:1fr!important}'),'Formulario pasa a una columna en tablet estrecha/móvil');
ok(str_contains($visual,'.queue-table.responsive thead{display:none}'),'Cola tiene patrón de tabla móvil');
ok(str_contains($visual,'.queue-table.responsive td:before{content:attr(data-label)'),'Cola conserva etiquetas al apilarse');

ok(str_contains($ticket,'ticket-')||str_contains($ticket,'case-focus'),'Ticket usa layout dedicado');
ok(str_contains($external,'external-'),'Proveedor usa superficie dedicada');
ok(str_contains($manual,'manual-'),'Manual usa superficie dedicada');
ok(str_contains($reports,'report-'),'Reportes usan superficie dedicada');

ok(str_contains($dark,'html[data-theme="dark"] .sidebar'),'Dark cubre navegación');
ok(str_contains($dark,'html[data-theme="dark"] .topbar'),'Dark cubre topbar');
ok(str_contains($dark,'html[data-theme="dark"] .card'),'Dark cubre tarjetas');
ok(str_contains($dark,'html[data-theme="dark"] .data-table'),'Dark cubre tablas');
ok(str_contains($dark,'html[data-theme="dark"] .form-control'),'Dark cubre formularios');
ok(str_contains($dark,'html[data-theme="dark"] .public-request-topbar'),'Dark cubre formulario público');
ok(str_contains($dark,'html[data-theme="dark"] .shell-notification-menu'),'Dark cubre campana');
ok(str_contains($dark,'@media(max-width:760px)'),'Dark conserva tratamiento móvil');
ok(str_contains($dark,'@media(min-width:1001px)'),'Dark conserva refinamiento escritorio');

ok(str_contains($app,'min-height:42px'),'Botones base mantienen tamaño táctil');
ok(str_contains($components,'min-height:44px')||str_contains($visual,'min-height:44px'),'Controles críticos incluyen objetivo táctil 44px');
ok(str_contains($dark,'focus-visible'),'Modo oscuro conserva foco visible');
ok(str_contains($shell,'focus-visible')||str_contains($components,'focus-visible'),'UI conserva foco visible');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contrato visual/responsive transversal Fase 12 preparado.'.PHP_EOL;
