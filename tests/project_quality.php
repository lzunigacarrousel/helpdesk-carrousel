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
$managementCss=(string)@file_get_contents($root.'/public/assets/css/management.css');
$componentsCss=(string)@file_get_contents($root.'/public/assets/css/components.css');
check(!str_contains($appEnd,'sticky-footer.css'),'Footer no depende de una segunda capa CSS contradictoria');
check(preg_match('/\.report-table\s*\{[^}]*min-width\s*:\s*1450px/s',$managementCss)!==1,'Informes no fuerzan ancho de 1450px');
check(str_contains($componentsCss,'.data-table'),'Existe componente global .data-table');
check(str_contains($componentsCss,'content:attr(data-label)'),'Tablas móviles usan data-label en lugar de scroll horizontal obligatorio');

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

exit($ok?0:1);