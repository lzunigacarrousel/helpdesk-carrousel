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
$topics=(string)@file_get_contents($root.'/app/Services/RequesterTopicService.php');

requesterCheck($controller!=='','Se puede leer TicketController');
requesterCheck($view!=='','Se puede leer public_create.php');
requesterCheck($topics!=='','Se puede leer RequesterTopicService');
requesterCheck(str_contains($topics,'REQUESTER_TOPIC_PRESENTATION'),'Existe catálogo de temas separado de las categorías internas');

$labels=[
    'Caja Chica / NIT',
    'Payout / Kiddies / Promocionales',
    'Tickets Destruidos',
    'Facturación / POS / Impresora',
    'Acceso / Contraseña',
    'Computadora / Equipo',
    'Internet / Conexión',
    'Reportes / Dashboards / Formularios',
    'Semnox / Parafait',
    'Otro',
];
foreach($labels as $label){
    requesterCheck(str_contains($topics,$label),'Tema humano disponible: '.$label);
}
foreach(['SOFTWARE','POS','ACCESS','HARDWARE','NETWORK','REPORTS','SEMNOX','OTHER'] as $code){
    requesterCheck(str_contains($topics,"'category_code'=>'{$code}'"),'Tema mapea a categoría canónica: '.$code);
}
requesterCheck(str_contains($topics,'No escribas tu contraseña'),'Ayuda de accesos evita pedir contraseñas');
requesterCheck(str_contains($topics,'Ejemplo:'),'Los temas incluyen ejemplos escritos como solicitudes reales');

requesterCheck(str_contains($controller,'singleActiveAssignment'),'El formulario puede detectar una única asignación activa');
requesterCheck(str_contains($controller,"ua.status='ACTIVE'"),'La ubicación automática usa asignaciones activas');
requesterCheck(str_contains($controller,'requester_user_id'),'Se conserva vínculo del ticket con usuario autenticado');
requesterCheck(str_contains($controller,'$authUser=Auth::user()')||str_contains($controller,'$authUser = Auth::user()'),'publicStore obtiene identidad autenticada del servidor');
requesterCheck(str_contains($controller,'$name=(string)$authUser[\'full_name\']')||str_contains($controller,'$name = (string)$authUser[\'full_name\']'),'Nombre autenticado no depende de un hidden manipulable');
requesterCheck(str_contains($controller,'$email=strtolower((string)$authUser[\'email\'])')||str_contains($controller,'$email = strtolower((string)$authUser[\'email\'])'),'Correo autenticado no depende de un hidden manipulable');

requesterCheck(str_contains($view,'¿En qué necesitas ayuda?'),'Formulario habla en lenguaje de ayuda');
requesterCheck(str_contains($view,'Cuéntanos qué está pasando'),'Descripción usa lenguaje sencillo');
requesterCheck(str_contains($view,'RequesterTopicService'),'La vista consume un catálogo reutilizable de temas');
requesterCheck(str_contains($view,'name="requester_topic"'),'Selector usa temas humanos con valor único');
requesterCheck(str_contains($view,'name="category_id"'),'Se conserva category_id interno para el ticket');
requesterCheck(str_contains($view,'data-category-id'),'Cada tema conoce su categoría interna sin cambiar la BD');
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

exit($ok?0:1);
