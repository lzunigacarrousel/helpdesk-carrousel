<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function okUtf8(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function bodyUtf8(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$source=bodyUtf8($root.'/tools/apply_phase5_task7_activity_ui.ps1');
$runner=bodyUtf8($root.'/tools/run_phase5_task7_activity_ui_utf8.ps1');

okUtf8($source!=='','Existe aplicador Task 7');
okUtf8(str_contains($source,'Próximas / activas'),'Aplicador conserva texto UTF-8 real');
okUtf8($runner!=='','Existe runner UTF-8 seguro para Windows PowerShell 5.1');
okUtf8(str_contains($runner,'UTF8Encoding'),'Runner fuerza lectura UTF-8 del aplicador');
okUtf8(str_contains($runner,'ScriptBlock')||str_contains($runner,'scriptblock'),'Runner ejecuta el contenido ya decodificado');
okUtf8(str_contains($runner,'0x00C3'),'Runner detecta mojibake U+00C3');
okUtf8(str_contains($runner,'show.php'),'Runner valida la vista generada');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) UTF-8 fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión UTF-8 Task 7 completada.".PHP_EOL;
