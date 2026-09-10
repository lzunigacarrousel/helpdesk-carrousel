<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ok=true;

function check(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

function filesRecursive(string $dir,string $extension): array{
    $out=[];
    if(!is_dir($dir))return $out;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if($file->isFile()&&strtolower($file->getExtension())===strtolower($extension))$out[]=$file->getPathname();
    }
    sort($out);
    return $out;
}

$routerFile=$root.'/public/index.php';
$router=(string)@file_get_contents($routerFile);
check($router!=='','public/index.php se puede leer');

preg_match_all("/\['(GET|POST)','([^']+)',\[([A-Za-z0-9_]+)::class,'([^']+)'\]\]/",$router,$routeMatches,PREG_SET_ORDER);
$routes=[];
foreach($routeMatches as $m){
    [$all,$method,$path,$controller,$action]=$m;
    $key=$method.' '.$path;
    check(!isset($routes[$key]),'Ruta única '.$key);
    $routes[$key]=[$controller,$action];

    $controllerFile=$root.'/app/Controllers/'.$controller.'.php';
    $controllerContent=(string)@file_get_contents($controllerFile);
    check($controllerContent!=='','Controlador existe para '.$key.' → '.$controller);
    check($controllerContent!==''&&preg_match('/public\s+function\s+'.preg_quote($action,'/').'\s*\(/',$controllerContent)===1,'Acción existe '.$controller.'::'.$action.' para '.$key);
}
check(count($routes)>=20,'Router contiene rutas de la aplicación ('.count($routes).')');

// Verifica que los formularios visibles que usan APP_BASE_URL apunten a una ruta POST real.
$formActions=[];
foreach(filesRecursive($root.'/app/Views','php') as $file){
    $content=(string)file_get_contents($file);
    if(preg_match_all('/<form\b[^>]*method="post"[^>]*action="<\?=\s*APP_BASE_URL\s*\?>(\/[A-Za-z0-9_\-\/]+)"/i',$content,$matches)){
        foreach($matches[1] as $path)$formActions[$path]=true;
    }
    if(preg_match_all('/<form\b[^>]*action="<\?=\s*APP_BASE_URL\s*\?>(\/[A-Za-z0-9_\-\/]+)"[^>]*method="post"/i',$content,$matches2)){
        foreach($matches2[1] as $path)$formActions[$path]=true;
    }
}
foreach(array_keys($formActions) as $path){
    check(isset($routes['POST '.$path]),'Formulario POST tiene ruta: '.$path);
}

// CSS: chequeo estructural básico para detectar llaves rotas en cualquier hoja.
foreach(filesRecursive($root.'/public/assets/css','css') as $file){
    $css=(string)file_get_contents($file);
    $withoutComments=preg_replace('~/\*.*?\*/~s','',$css)??$css;
    $balance=substr_count($withoutComments,'{')-substr_count($withoutComments,'}');
    check($balance===0,'CSS balanceado: '.str_replace($root.'/','',$file));
}

// HTML/PHP: evita acciones POST sin CSRF en vistas internas, salvo formularios explícitamente públicos de autenticación.
$csrfExempt=['auth/login.php','auth/otp.php','auth/register.php'];
foreach(filesRecursive($root.'/app/Views','php') as $file){
    $relative=str_replace('\\','/',substr($file,strlen($root.'/app/Views/')));
    $content=(string)file_get_contents($file);
    if(!str_contains(strtolower($content),'method="post"'))continue;
    if(in_array($relative,$csrfExempt,true))continue;
    check(str_contains($content,'_csrf'),'Vista POST contiene CSRF: '.$relative);
}

// No exponer warnings técnicos al usuario final.
$bootstrap=(string)@file_get_contents($root.'/bootstrap.php');
check(str_contains($bootstrap,"ini_set('display_errors','0')"),'Errores PHP no se muestran al usuario');

// Endpoints críticos que históricamente han fallado.
check(isset($routes['POST /tickets/resolve']),'Existe POST /tickets/resolve');
check(isset($routes['GET /tickets/resolve']),'GET /tickets/resolve redirige de forma segura');
check(isset($routes['GET /gestion/informes/exportar']),'Existe exportación XLSX de informes');

// Normalización UI: una sola estrategia de geometría/footer y tablas semánticas.
$appEnd=(string)@file_get_contents($root.'/app/Views/shared/app_end.php');
$dataTableCss=(string)@file_get_contents($root.'/public/assets/css/data-tables.css');
$tableJs=(string)@file_get_contents($root.'/public/assets/js/table-normalization.js');
check(!is_file($root.'/public/assets/css/sticky-footer.css'),'No existe la hoja legacy sticky-footer.css');
check(!str_contains($appEnd,'sticky-footer.css'),'Footer no depende de una segunda capa CSS contradictoria');
check(str_contains($appEnd,'layout-density-v25.css'),'Shell carga geometría canónica');
check(str_contains($appEnd,'data-tables.css'),'Shell carga tablas canónicas');
check(str_contains($appEnd,'table-normalization.js'),'Shell activa normalización para tablas existentes');
check(str_contains($dataTableCss,'.data-table'),'Existe componente global .data-table');
check(str_contains($dataTableCss,'content:attr(data-label)'),'Tablas móviles usan data-label en lugar de scroll horizontal obligatorio');
check(str_contains($dataTableCss,'min-width:0!important'),'Capa final neutraliza anchos mínimos heredados');
check(str_contains($dataTableCss,'.report-table.data-table'),'Informes heredan tabla canónica sin ancho forzado');
check(str_contains($tableJs,".content table:not([data-table-skip])"),'Normalizador alcanza todas las tablas internas');

$requiredTableViews=[
    'admin/users.php',
    'admin/audit.php',
    'management/support_team.php',
    'admin/externals.php',
    'management/external_report.php',
];
foreach($requiredTableViews as $relative){
    $view=(string)@file_get_contents($root.'/app/Views/'.$relative);
    check($view!==''&&str_contains($view,'<table'),'Vista usa tabla semántica: '.$relative);
    check($view!==''&&str_contains($view,'data-label='),'Vista prepara lectura responsive con data-label: '.$relative);
}

// Ningún formulario de edición de usuario puede contener un segundo form anidado.
$usersView=(string)@file_get_contents($root.'/app/Views/admin/users.php');
$assignStart=strpos($usersView,'action="<?= APP_BASE_URL ?>/admin/users/assign"');
$formClose=$assignStart===false?false:strpos($usersView,'</form>',$assignStart);
$deleteStart=strpos($usersView,'action="<?= APP_BASE_URL ?>/admin/users/delete"');
check($assignStart!==false&&$formClose!==false&&($deleteStart===false||$deleteStart>$formClose),'Usuarios no anida formulario de retiro dentro de edición');

exit($ok?0:1);