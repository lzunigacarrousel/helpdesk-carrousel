<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$view=body($root.'/app/Views/tickets/show_external.php');

ok(!str_contains($view,'<aside class="external-work-side"'),'Vista externa elimina columna lateral');
ok(!str_contains($view,'Qué sigue'),'Vista externa elimina tarjeta Qué sigue');
ok(!str_contains($view,'external-next-steps'),'Vista externa elimina pasos redundantes');
ok(!str_contains($view,'<span class="ticket-kicker">Flujo</span>'),'Vista externa elimina encabezado Flujo');
ok(
    str_contains($view,'.external-work-grid{grid-template-columns:minmax(0,1fr)') ||
    str_contains($view,'.external-work-grid{display:block') ||
    str_contains($view,'.external-work-grid{grid-template-columns:1fr'),
    'Contenido externo aprovecha una sola columna'
);
ok(str_contains($view,'external-problem-card'),'Se conserva bloque del problema');
ok(str_contains($view,'external-conversation-card'),'Se conserva conversación');
ok(str_contains($view,'external-work-report'),'Se conserva informe técnico');
ok(str_contains($view,'external-solution-card'),'Se conserva solución final');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión layout externo sin panel lateral completada.".PHP_EOL;
