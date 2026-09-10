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

requesterCheck(str_contains($controller,'REQUESTER_CATEGORY_PRESENTATION'),'Existe capa de presentación de categorías para solicitantes');
foreach(['Facturación / POS','Semnox','Internet','Acceso a un sistema','Equipo','Reporte','Otro'] as $label){
    requesterCheck(str_contains($controller,$label),'Categoría humana disponible: '.$label);
}
requesterCheck(str_contains($controller,'singleActiveAssignment'),'El formulario puede detectar una única asignación activa');
requesterCheck(str_contains($controller,"ua.status='ACTIVE'"),'La ubicación automática usa asignaciones activas');
requesterCheck(str_contains($controller,'requester_user_id'),'Se conserva vínculo del ticket con usuario autenticado');
requesterCheck(str_contains($controller,'$authUser=Auth::user()')||str_contains($controller,'$authUser = Auth::user()'),'publicStore obtiene identidad autenticada del servidor');
requesterCheck(str_contains($controller,'$name=(string)$authUser[\'full_name\']')||str_contains($controller,'$name = (string)$authUser[\'full_name\']'),'Nombre autenticado no depende de un hidden manipulable');
requesterCheck(str_contains($controller,'$email=strtolower((string)$authUser[\'email\'])')||str_contains($controller,'$email = strtolower((string)$authUser[\'email\'])'),'Correo autenticado no depende de un hidden manipulable');

requesterCheck(str_contains($view,'¿En qué necesitas ayuda?'),'Formulario habla en lenguaje de ayuda');
requesterCheck(str_contains($view,'Cuéntanos qué está pasando'),'Descripción usa lenguaje sencillo');
requesterCheck(str_contains($view,'data-category-help'),'Opciones de categoría incluyen ayuda contextual');
requesterCheck(str_contains($view,'data-category-help-text'),'Existe salida accesible para ayuda contextual');
requesterCheck(str_contains($view,'data-location-summary'),'Existe resumen de ubicación automática');
requesterCheck(str_contains($view,'data-location-fields'),'La ubicación automática se puede cambiar');
requesterCheck(str_contains($view,'Reportar en otro lugar'),'Cambio de ubicación se presenta como acción secundaria');
requesterCheck(str_contains($view,'$c[\'display_name\']')||str_contains($view,'$c[\'requester_label\']'),'La vista usa copy humano sin cambiar category_id');
requesterCheck(!str_contains($view,'>Tipo de solicitud<'),'No se expone el rótulo técnico Tipo de solicitud');
requesterCheck(str_contains($view,'name="subject" id="subject"'),'Se conserva subject generado');
requesterCheck(str_contains($view,'buildSubject'),'Se conserva generación del resumen interno');

exit($ok?0:1);
