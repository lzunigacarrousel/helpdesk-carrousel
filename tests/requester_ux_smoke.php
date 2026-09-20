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
$searchable=(string)@file_get_contents($root.'/public/assets/js/searchable-select.js');

requesterCheck($controller!=='','Se puede leer TicketController');
requesterCheck($view!=='','Se puede leer public_create.php');
requesterCheck($topics!=='','Se puede leer RequesterTopicService');
requesterCheck($searchable!=='','Se puede leer searchable-select.js');
requesterCheck(str_contains($searchable,'Object.values(option.dataset||{})'),'Buscador considera metadatos y ayuda contextual de cada opción');
requesterCheck(str_contains($searchable,'tokens.every'),'Buscador filtra todas las palabras escritas');
requesterCheck(str_contains($searchable,"style.display=show?'flex':'none'"),'Buscador fuerza ocultamiento visual de opciones no coincidentes');
requesterCheck(str_contains($view,'smart-select-native'),'Selector nativo queda oculto desde el HTML y no se duplica');
requesterCheck(str_contains($view,'data-search-terms'),'Opciones exponen términos semánticos para búsquedas como NIT');
requesterCheck(str_contains($view,'data-searchable-select'),'Selector público usa buscador controlado por la app');
requesterCheck(str_contains($view,'searchable-select.css'),'Vista pública carga estilos del buscador');
requesterCheck(str_contains($view,'searchable-select.js'),'Vista pública carga lógica del buscador');
requesterCheck(str_contains($topics,'public static function options(array $categories):array'),'Existe catálogo dinámico de temas separado de la presentación');
requesterCheck(str_contains($topics,'HELP_BY_CODE'),'El catálogo dinámico conserva ayuda contextual por código');
requesterCheck(str_contains($topics,'HELP_BY_PARENT'),'El catálogo dinámico conserva ayuda contextual por familia');
requesterCheck(str_contains($topics,"'category_id'=>\$id"),'Cada tema conserva category_id real de BD');
requesterCheck(str_contains($topics,"'category_code'=>\$code"),'Cada tema conserva category_code real de BD');
requesterCheck(str_contains($topics,"'label'=>\$name"),'La etiqueta visible del tema proviene de BD');
requesterCheck(str_contains($topics,"'group'=>\$parentName"),'Los temas conservan agrupación por categoría padre');
requesterCheck(str_contains($topics,'isset($hasChildren[$id])'),'El selector evita ofrecer padres que tienen subcategorías');
requesterCheck(str_contains($topics,'No escribas tu contraseña'),'Ayuda de accesos evita pedir contraseñas');
requesterCheck(str_contains($topics,'Ejemplo:'),'Los temas incluyen ejemplos escritos como solicitudes reales');

if(is_file($root.'/app/Services/RequesterTopicService.php')){
    require_once $root.'/app/Services/RequesterTopicService.php';
    $sampleCategories=[
        ['id'=>1,'code'=>'NETWORK','name'=>'Internet / Conexión','parent_id'=>null,'parent_name'=>null,'parent_code'=>null,'sort_order'=>10,'parent_sort_order'=>null],
        ['id'=>2,'code'=>'NETWORK_OUTAGE','name'=>'Sin Internet','parent_id'=>1,'parent_name'=>'Internet / Conexión','parent_code'=>'NETWORK','sort_order'=>10,'parent_sort_order'=>10],
        ['id'=>3,'code'=>'ACCESS','name'=>'Acceso','parent_id'=>null,'parent_name'=>null,'parent_code'=>null,'sort_order'=>20,'parent_sort_order'=>null],
        ['id'=>4,'code'=>'ACCESS_PASSWORD','name'=>'No puedo ingresar','parent_id'=>3,'parent_name'=>'Acceso','parent_code'=>'ACCESS','sort_order'=>10,'parent_sort_order'=>20],
        ['id'=>5,'code'=>'OTHER','name'=>'Otro','parent_id'=>null,'parent_name'=>null,'parent_code'=>null,'sort_order'=>999,'parent_sort_order'=>null],
    ];
    $sampleOptions=\App\Services\RequesterTopicService::options($sampleCategories);
    $byKey=[];
    foreach($sampleOptions as $option)$byKey[(string)$option['key']]=$option;

    requesterCheck(count($sampleOptions)===3,'El catálogo público ofrece hojas y categorías sin hijos');
    requesterCheck(!isset($byKey['NETWORK'])&&!isset($byKey['ACCESS']),'Los padres con subcategorías no se ofrecen como temas finales');
    requesterCheck(isset($byKey['NETWORK_OUTAGE']),'Tema dinámico disponible desde categoría hoja de red');
    requesterCheck(isset($byKey['ACCESS_PASSWORD']),'Tema dinámico disponible desde categoría hoja de acceso');
    requesterCheck(isset($byKey['OTHER']),'Categoría sin hijos sigue disponible como tema');
    requesterCheck(($byKey['NETWORK_OUTAGE']['label']??'')==='Sin Internet','Etiqueta visible se toma de la categoría real');
    requesterCheck((int)($byKey['NETWORK_OUTAGE']['category_id']??0)===2,'Tema conserva category_id canónico');
    requesterCheck(($byKey['NETWORK_OUTAGE']['category_code']??'')==='NETWORK_OUTAGE','Tema conserva category_code canónico');
    requesterCheck(($byKey['NETWORK_OUTAGE']['group']??'')==='Internet / Conexión','Tema conserva grupo padre');
    requesterCheck(str_contains((string)($byKey['ACCESS_PASSWORD']['help']??''),'No escribas tu contraseña'),'Tema de acceso conserva ayuda segura contextual');
    requesterCheck(str_contains((string)($byKey['ACCESS_PASSWORD']['placeholder']??''),'Ejemplo:'),'Tema dinámico conserva ejemplo contextual');
}

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
