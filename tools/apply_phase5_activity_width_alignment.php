<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$test=$root.'/tests/phase5_activities_ui_regression.php';
$view=$root.'/app/Views/tickets/show.php';
$css=$root.'/public/assets/css/ticket-activities.css';

function fail(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}
function readStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function writeStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false) fail('No se pudo escribir '.$path);
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count!==1) fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return str_replace($search,$replace,$content);
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}

chdir($root) || fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0) fail('No se pudo consultar git status.');
if($status) fail('El working tree debe estar limpio antes de aplicar este ajuste.');

// RED: gates visuales antes de tocar la vista/CSS.
$testBody=readStrict($test);
$testEol=str_contains($testBody,"\r\n")?"\r\n":"\n";
$checks=<<<'PHP'
// Pulido final Fase 5: todas las transiciones aprovechan el ancho disponible.
ok(substr_count($ticketView,'ticket-activity-transition-details')>=3,'Reprogramar, Finalizar y Cancelar comparten contenedor de ancho completo');
ok(str_contains($ticketView,'ticket-activity-transition--reschedule'),'Reprogramar usa variante amplia');
ok(str_contains($ticketView,'ticket-activity-transition--cancel'),'Cancelar usa variante amplia');
ok(str_contains($activityCss,'.ticket-activity-create{')&&str_contains($activityCss,'overflow:hidden;width:100%'),'Programar actividad ocupa el ancho del contenedor');
ok(str_contains($activityCss,'.ticket-activity-actions details.ticket-activity-transition-details[open]{flex:1 0 100%;width:100%}'),'Toda transición abierta ocupa ancho completo');
ok(str_contains($activityCss,'.ticket-activity-transition--reschedule,.ticket-activity-transition--cancel{width:100%;min-width:0}'),'Reprogramar y Cancelar eliminan ancho compacto');
ok(str_contains($activityCss,'.ticket-activity-transition-actions{display:flex;justify-content:flex-end;align-items:center}'),'Acciones de transición quedan alineadas');
ok(str_contains($activityCss,'.ticket-activity-transition-actions .btn,.ticket-activity-transition--cancel>.btn,.ticket-activity-complete-actions .btn{width:100%}'),'Botones de transición se adaptan en móvil');

PHP;
$checks=str_replace(["\r\n","\r"],"\n",$checks);
$checks=str_replace("\n",$testEol,$checks);
$anchor='// Hardening final Fase 5: privacidad integral del solicitante.';
$testBody=replaceOnce($testBody,$anchor,$checks.$anchor,'Inserción de gates de anchos');
writeStrict($test,$testBody);

if(run('"'.$php.'" -l "'.$test.'"')!==0) fail('El gate RED generado tiene error de sintaxis.');
echo "=== RED esperado: Reprogramar y Cancelar aún son compactos ===".PHP_EOL;
$red=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($red===0) fail('El RED no falló; los nuevos gates no detectan el problema visual.');
echo '[OK] RED confirmado: los nuevos gates detectan los anchos inconsistentes.'.PHP_EOL.PHP_EOL;

// GREEN: vista.
$viewBody=readStrict($view);
$viewBody=replaceOnce(
    $viewBody,
    '<details><summary class="btn btn-outline-secondary btn-sm">Reprogramar</summary><form class="ticket-activity-transition two-columns"',
    '<details class="ticket-activity-transition-details ticket-activity-reschedule-details"><summary class="btn btn-outline-secondary btn-sm">Reprogramar</summary><form class="ticket-activity-transition ticket-activity-transition--reschedule two-columns"',
    'Reprogramar amplio'
);
$viewBody=replaceOnce(
    $viewBody,
    '<div class="activity-span-2"><button class="btn btn-primary btn-sm" type="submit">Guardar nueva fecha</button></div></form></details>',
    '<div class="activity-span-2 ticket-activity-transition-actions"><button class="btn btn-primary btn-sm" type="submit">Guardar nueva fecha</button></div></form></details>',
    'Acción Reprogramar alineada'
);
$viewBody=replaceOnce(
    $viewBody,
    '<details class="ticket-activity-complete-details"><summary class="btn btn-primary btn-sm">Finalizar</summary>',
    '<details class="ticket-activity-transition-details ticket-activity-complete-details"><summary class="btn btn-primary btn-sm">Finalizar</summary>',
    'Finalizar comparte contenedor amplio'
);
$viewBody=replaceOnce(
    $viewBody,
    '<details><summary class="btn btn-outline-secondary btn-sm">Cancelar</summary><form class="ticket-activity-transition"',
    '<details class="ticket-activity-transition-details ticket-activity-cancel-details"><summary class="btn btn-outline-secondary btn-sm">Cancelar</summary><form class="ticket-activity-transition ticket-activity-transition--cancel"',
    'Cancelar amplio'
);
writeStrict($view,$viewBody);

// GREEN: CSS.
$cssBody=readStrict($css);
$cssEol=str_contains($cssBody,"\r\n")?"\r\n":"\n";
$cssBody=replaceOnce(
    $cssBody,
    '.ticket-activity-create{border:1px solid color-mix(in srgb,var(--brand) 16%,var(--border) 84%);border-radius:14px;background:var(--vs-brand-soft);overflow:hidden}',
    '.ticket-activity-create{border:1px solid color-mix(in srgb,var(--brand) 16%,var(--border) 84%);border-radius:14px;background:var(--vs-brand-soft);overflow:hidden;width:100%}',
    'Programar ancho completo'
);
$cssSearch='.ticket-activity-actions details.ticket-activity-complete-details[open]{flex:1 0 100%;width:100%}'.$cssEol.'.ticket-activity-transition--complete{width:100%;min-width:0}';
$cssReplace='.ticket-activity-actions details.ticket-activity-transition-details[open]{flex:1 0 100%;width:100%}'.$cssEol
    .'.ticket-activity-transition--reschedule,.ticket-activity-transition--cancel{width:100%;min-width:0}'.$cssEol
    .'.ticket-activity-transition-actions{display:flex;justify-content:flex-end;align-items:center}'.$cssEol
    .'.ticket-activity-transition-actions .btn,.ticket-activity-transition--cancel>.btn{min-width:180px}'.$cssEol
    .'.ticket-activity-transition--complete{width:100%;min-width:0}';
$cssBody=replaceOnce($cssBody,$cssSearch,$cssReplace,'Transiciones amplias');
$mobileSearch='  .ticket-activity-actions details>summary{display:flex;align-items:center;justify-content:center;width:100%}'.$cssEol.'}';
$mobileReplace='  .ticket-activity-actions details>summary{display:flex;align-items:center;justify-content:center;width:100%}'.$cssEol
    .'  .ticket-activity-transition-actions .btn,.ticket-activity-transition--cancel>.btn,.ticket-activity-complete-actions .btn{width:100%}'.$cssEol.'}';
$cssBody=replaceOnce($cssBody,$mobileSearch,$mobileReplace,'Botones responsive');
writeStrict($css,$cssBody);

echo "=== GREEN esperado: anchos de Actividades alineados ===".PHP_EOL;
$green=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($green!==0) fail('La regresión de Fase 5 no quedó en verde.');

foreach([$view,$css,$test] as $file){
    if(str_ends_with($file,'.php')&&run('"'.$php.'" -l "'.$file.'"')!==0) fail('Falló PHP lint en '.$file);
}

echo '[OK] Anchos de Programar, Reprogramar, Finalizar y Cancelar alineados.'.PHP_EOL;
echo '[OK] Desktop conserva columnas útiles; tablet/móvil vuelve a una columna/ancho completo.'.PHP_EOL;
echo '[OK] No se modificó la base de datos ni la lógica de actividades.'.PHP_EOL;
