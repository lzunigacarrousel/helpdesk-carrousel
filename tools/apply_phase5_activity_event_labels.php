<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$viewPath=$root.'/app/Views/tickets/show.php';
$testPath=$root.'/tests/phase5_activities_ui_regression.php';

function failNow(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}\n");
    exit(1);
}

function readFileStrict(string $path): string {
    $content=@file_get_contents($path);
    if(!is_string($content))failNow('No se pudo leer '.$path);
    return $content;
}

function writeFileStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false)failNow('No se pudo escribir '.$path);
}

$test=readFileStrict($testPath);
$testAnchor="ok(str_contains(\$activityCss,'.ticket-activity-complete-grid{grid-template-columns:1fr}'),'Finalizar vuelve a una columna en tablet/móvil');";
$testBlock=<<<'PHP'

foreach([
    'ACTIVITY_CREATED'=>'Actividad programada',
    'ACTIVITY_RESCHEDULED'=>'Actividad reprogramada',
    'ACTIVITY_STARTED'=>'Actividad iniciada',
    'ACTIVITY_COMPLETED'=>'Actividad finalizada',
    'ACTIVITY_CANCELLED'=>'Actividad cancelada',
] as $eventCode=>$eventLabel){
    ok(str_contains($ticketView,"'{$eventCode}'=>'{$eventLabel}'"),'Historial traduce '.$eventCode.' al español');
}
PHP;

if(!str_contains($test,'Historial traduce ACTIVITY_CREATED al español')){
    if(!str_contains($test,$testAnchor))failNow('No se encontró el ancla de regresión UI para insertar el gate de traducción.');
    $test=str_replace($testAnchor,$testAnchor.$testBlock,$test,$count);
    if($count!==1)failNow('El gate de traducción no pudo insertarse de forma única.');
    writeFileStrict($testPath,$test);
}

echo "=== RED esperado: etiquetas de actividades aún no traducidas ===\n";
$php=PHP_BINARY;
$cmd='"'.$php.'" '.escapeshellarg($testPath);
passthru($cmd,$redExit);
if($redExit===0)failNow('El gate no falló en RED; revisa si las etiquetas ya estaban traducidas.');
echo "[OK] RED confirmado: el historial aún usa el fallback en inglés.\n\n";

$view=readFileStrict($viewPath);
if(!str_contains($view,"'ACTIVITY_CREATED'=>'Actividad programada'")){
    $old="'CLASSIFICATION_CHANGED'=>'Clasificación actualizada'];";
    $new="'CLASSIFICATION_CHANGED'=>'Clasificación actualizada','ACTIVITY_CREATED'=>'Actividad programada','ACTIVITY_RESCHEDULED'=>'Actividad reprogramada','ACTIVITY_STARTED'=>'Actividad iniciada','ACTIVITY_COMPLETED'=>'Actividad finalizada','ACTIVITY_CANCELLED'=>'Actividad cancelada'];";
    if(!str_contains($view,$old))failNow('No se encontró el cierre canónico de eventLabels en show.php.');
    $view=str_replace($old,$new,$view,$count);
    if($count!==1)failNow('Se esperaba modificar exactamente una definición de eventLabels.');
    writeFileStrict($viewPath,$view);
}

echo "=== GREEN esperado: etiquetas de actividades en español ===\n";
passthru($cmd,$greenExit);
if($greenExit!==0)failNow('La regresión UI no quedó en verde después de traducir los eventos.');

passthru('"'.$php.'" -l '.escapeshellarg($viewPath),$lintExit);
if($lintExit!==0)failNow('show.php no supera php -l.');

echo "[OK] Eventos de actividades traducidos en el historial general.\n";
echo "[OK] Solo se modificaron la vista y la regresión UI; no se tocó BD ni dominio.\n";
