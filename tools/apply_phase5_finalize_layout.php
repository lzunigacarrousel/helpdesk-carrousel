<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$testPath=$root.'/tests/phase5_activities_ui_regression.php';
$viewPath=$root.'/app/Views/tickets/show.php';
$cssPath=$root.'/public/assets/css/ticket-activities.css';

function failNow(string $message):never{
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}

function readFileSafe(string $path):string{
    $content=@file_get_contents($path);
    if(!is_string($content))failNow('No se pudo leer '.$path);
    return $content;
}

function detectEol(string $content):string{
    return str_contains($content,"\r\n")?"\r\n":"\n";
}

function writePreservingEol(string $path,string $content,string $eol):void{
    $normalized=str_replace(["\r\n","\r"],"\n",$content);
    if($eol!=="\n")$normalized=str_replace("\n",$eol,$normalized);
    if(file_put_contents($path,$normalized)===false)failNow('No se pudo escribir '.$path);
}

function replaceOnce(string $content,string $search,string $replace,string $label):string{
    $count=substr_count($content,$search);
    if($count!==1)failNow("Ancla inesperada para {$label}: se esperó 1 coincidencia y se encontraron {$count}.");
    return str_replace($search,$replace,$content);
}

function runPhpTest(string $path):int{
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg($path).' 2>&1';
    passthru($command,$code);
    return (int)$code;
}

$test=readFileSafe($testPath);
$testEol=detectEol($test);
$view=readFileSafe($viewPath);
$viewEol=detectEol($view);
$css=readFileSafe($cssPath);
$cssEol=detectEol($css);

// RED: primero agregamos el contrato visual esperado.
if(!str_contains($test,'Finalizar abierto ocupa el ancho disponible')){
    $anchor="ok(str_contains(\$activityCss,'.ticket-activity-more-options'),'CSS contempla bloque de opciones secundarias');";
    $checks=$anchor."\n".
        "ok(str_contains(\$ticketView,'ticket-activity-complete-details'),'Vista identifica transición amplia de Finalizar');\n".
        "ok(str_contains(\$ticketView,'ticket-activity-transition--complete'),'Formulario Finalizar usa variante de ancho completo');\n".
        "ok(str_contains(\$ticketView,'ticket-activity-complete-grid'),'Formulario Finalizar define grid propio');\n".
        "ok(str_contains(\$activityCss,'.ticket-activity-complete-details[open]'),'Finalizar abierto ocupa el ancho disponible');\n".
        "ok(str_contains(\$activityCss,'.ticket-activity-complete-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))'),'Finalizar aprovecha dos columnas en escritorio');\n".
        "ok(str_contains(\$activityCss,'.ticket-activity-complete-grid{grid-template-columns:1fr}'),'Finalizar vuelve a una columna en tablet/móvil');";
    $test=replaceOnce($test,$anchor,$checks,'gate visual Finalizar');
    writePreservingEol($testPath,$test,$testEol);
}

echo "=== RED esperado: gate visual antes del cambio ===".PHP_EOL;
$redCode=runPhpTest($testPath);
if($redCode===0){
    echo "[AVISO] El gate ya estaba en verde antes de aplicar el layout; se continuará de forma idempotente.".PHP_EOL;
}else{
    echo "[OK] RED confirmado: el gate detectó que el layout amplio aún no estaba aplicado.".PHP_EOL;
}

// Implementación: solo markup/clases del formulario de finalización.
$oldComplete=<<<'HTML'
                  <details><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace('_',' ',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></form></details>
HTML;

$newComplete=<<<'HTML'
                  <details class="ticket-activity-complete-details"><summary class="btn btn-primary btn-sm">Finalizar</summary><form class="ticket-activity-transition ticket-activity-transition--complete" method="post" action="<?= APP_BASE_URL ?>/tickets/activities/complete" enctype="multipart/form-data" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="activity_id" value="<?= $activityId ?>"><div class="ticket-activity-complete-grid"><label><span class="form-label">Resultado</span><select class="form-control" name="result_code" required><option value="">Selecciona</option><?php foreach(($activityResults??[]) as $result): ?><option value="<?= htmlspecialchars((string)$result) ?>"><?= htmlspecialchars($activityResultLabels[$result]??str_replace('_',' ',(string)$result)) ?></option><?php endforeach; ?></select></label><label><span class="form-label">Evidencia <span class="optional">Opcional · máximo 10 MB</span></span><input class="form-control" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx"></label><label><span class="form-label">Trabajo realizado</span><textarea class="form-control" name="work_performed" required placeholder="Qué se hizo durante la actividad"></textarea></label><label><span class="form-label">Resultado obtenido</span><textarea class="form-control" name="result_summary" required placeholder="Qué se comprobó o resolvió"></textarea></label><label class="activity-span-2"><span class="form-label">Pendientes <span class="optional">Opcional</span></span><textarea class="form-control" name="pending_items" placeholder="Qué queda pendiente o requiere seguimiento"></textarea></label><div class="activity-span-2 ticket-activity-complete-actions"><button class="btn btn-primary btn-sm" type="submit">Finalizar actividad</button></div></div></form></details>
HTML;

if(!str_contains($view,'ticket-activity-transition--complete')){
    $view=replaceOnce($view,$oldComplete,$newComplete,'markup Finalizar actividad');
    writePreservingEol($viewPath,$view,$viewEol);
}

$cssAnchor='.ticket-activity-transition textarea{min-height:84px;resize:vertical}';
$cssBlock=$cssAnchor."\n".
'.ticket-activity-actions details.ticket-activity-complete-details[open]{flex:1 0 100%;width:100%}'."\n".
'.ticket-activity-transition--complete{width:100%;min-width:0}'."\n".
'.ticket-activity-complete-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;width:100%}'."\n".
'.ticket-activity-complete-grid>.activity-span-2{grid-column:1/-1}'."\n".
'.ticket-activity-complete-actions{display:flex;justify-content:flex-end;align-items:center}'."\n".
'.ticket-activity-complete-actions .btn{min-width:180px}';
if(!str_contains($css,'.ticket-activity-complete-details[open]')){
    $css=replaceOnce($css,$cssAnchor,$cssBlock,'CSS Finalizar ancho completo');
}

$mediaAnchor='  .ticket-activity-transition .activity-span-2{grid-column:auto}';
$mediaReplace=$mediaAnchor."\n".
'  .ticket-activity-complete-grid{grid-template-columns:1fr}'."\n".
'  .ticket-activity-complete-grid>.activity-span-2{grid-column:auto}';
if(!str_contains($css,'.ticket-activity-complete-grid{grid-template-columns:1fr}')){
    $css=replaceOnce($css,$mediaAnchor,$mediaReplace,'responsive Finalizar');
}
writePreservingEol($cssPath,$css,$cssEol);

echo PHP_EOL."=== GREEN esperado: gate visual después del cambio ===".PHP_EOL;
$greenCode=runPhpTest($testPath);
if($greenCode!==0)failNow('El gate de Fase 5 sigue fallando después del ajuste visual.');

$lintCommand=escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($viewPath).' 2>&1';
passthru($lintCommand,$lintCode);
if((int)$lintCode!==0)failNow('show.php no pasó php -l.');

echo "[OK] Pulido de ancho de Finalizar actividad aplicado.".PHP_EOL;
echo "[OK] Solo se modificaron vista, CSS y regresión UI; no se tocó BD ni dominio.".PHP_EOL;
