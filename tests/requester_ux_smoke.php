<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ok=true;
function requesterCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

$controller=(string)@file_get_contents($root.'/app/Controllers/TicketController.php');
$view=(string)@file_get_contents($root.'/app/Views/tickets/public_create.php');

requesterCheck($controller!=='','Se puede leer TicketController');
requesterCheck($view!=='','Se puede leer public_create.php');

requesterCheck(str_contains($controller,'REQUESTER_TOPIC_PRESENTATION'),'Existe capa de temas de ayuda separada de las categorías internas');
$topics=[
    'Caja chica / NIT',
    'Payout / Kiddies / promocionales',
    'Tickets destruidos',
    'Facturación / POS / impresora',
    'Acceso / contraseña',
    'Computadora / equipo',
    'Internet / conexión',
    'Reportes / dashboards / formularios',
    'Semnox / Parafait',
    'Otro',
];
foreach($topics as $label){
    requesterCheck(str_contains($controller,$label),'Tema humano disponible: '.$label);
}
foreach(['SOFTWARE','POS','ACCESS','HARDWARE','NETWORK','REPORTS','SEMNOX','OTHER'] as $code){
    requesterCheck(str_contains($controller,"'category_code'=>'{$code}'"),'Tema mapea a categoría canónica: '.$code);
}
requesterCheck(str_contains($controller,'singleActiveAssignment'),'El formulario puede detectar una única asignación activa');
requesterCheck(str_contains($controller,"ua.status='ACTIVE'"),'La ubicación automática usa asignaciones activas');
requesterCheck(str_contains($controller,'requester_user_id'),'Se conserva vínculo del ticket con usuario autenticado');
requesterCheck(str_contains($controller,'$authUser=Auth::user()')||str_contains($controller,'$authUser = Auth::user()'),'publicStore obtiene identidad autenticada del servidor');
requesterCheck(str_contains($controller,'$name=(string)$authUser[\'full_name\']')||str_contains($controller,'$name = (string)$authUser[\'full_name\']'),'Nombre autenticado no depende de un hidden manipulable');
requesterCheck(str_contains($controller,'$email=strtolower((string)$authUser[\'email\'])')||str_contains($controller,'$email = strtolower((string)$authUser[\'email\'])'),'Correo autenticado no depende de un hidden manipulable');
requesterCheck(str_contains($controller,'requester_topic'),'Backend recibe el tema humano seleccionado');
requesterCheck(str_contains($controller,'category_code'),'Backend resuelve el tema contra la categoría interna existente');

requesterCheck(str_contains($view,'¿En qué necesitas ayuda?'),'Formulario habla en lenguaje de ayuda');
requesterCheck(str_contains($view,'Cuéntanos qué está pasando'),'Descripción usa lenguaje sencillo');
requesterCheck(str_contains($view,'name="requester_topic"'),'Selector usa temas humanos con valor único');
requesterCheck(str_contains($view,'data-category-help'),'Cada tema incluye ayuda contextual');
requesterCheck(str_contains($view,'data-category-placeholder'),'Cada tema incluye ejemplo contextual para el textarea');
requesterCheck(str_contains($view,'data-category-help-text'),'Existe salida accesible para ayuda contextual');
requesterCheck(str_contains($view,'syncCategoryContext'),'Ayuda y ejemplo cambian al seleccionar el tema');
requesterCheck(str_contains($view,'.placeholder'),'El textarea actualiza su ejemplo dinámicamente');
requesterCheck(str_contains($view,'data-location-summary'),'Existe resumen de ubicación automática');
requesterCheck(str_contains($view,'data-location-fields'),'La ubicación automática se puede cambiar');
requesterCheck(str_contains($view,'Reportar en otro lugar'),'Cambio de ubicación se presenta como acción secundaria');
requesterCheck(!str_contains($view,'>Tipo de solicitud<'),'No se expone el rótulo técnico Tipo de solicitud');
requesterCheck(str_contains($view,'name="subject" id="subject"'),'Se conserva subject generado');
requesterCheck(str_contains($view,'buildSubject'),'Se conserva generación del resumen interno');
requesterCheck(str_contains($controller,'No escribas tu contraseña'),'Ayuda de accesos evita pedir contraseñas');

exit($ok?0:1);
