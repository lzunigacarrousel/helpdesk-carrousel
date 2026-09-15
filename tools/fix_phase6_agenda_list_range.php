<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$view=$root.'/app/Views/agenda/index.php';
$listTest=$root.'/tests/phase6_agenda_list_range_regression.php';
$uiTest=$root.'/tests/phase6_agenda_ui_regression.php';
$calendarTest=$root.'/tests/phase6_agenda_multiday_calendar_regression.php';

function fail(string $message): never {
    fwrite(STDERR,'[ERROR] '.$message.PHP_EOL);
    exit(1);
}
function readStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function replaceFirst(string $content,string $search,string $replace,string $label): string {
    $pos=strpos($content,$search);
    if($pos===false) fail($label.' no encontró el patrón esperado.');
    return substr_replace($content,$replace,$pos,strlen($search));
}
function replaceExactlyOnce(string $content,string $search,string $replace,string $label): string {
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

$body=readStrict($view);
if(str_contains($body,'$listRange=static function')) fail('El formateador contextual de Lista ya existe.');
$eol=str_contains($body,"\r\n")?"\r\n":"\n";

$anchor='};'.$eol.'$withFilter=static function(array $changes)use($filters):string{';
$insert='};'.$eol
    .'$listRange=static function(array $row)use($timeRange,$multiDayRange):string{'.$eol
    .'    $startDate=substr((string)($row[\'scheduled_start_at\']??\'\'),0,10);'.$eol
    .'    $endDate=substr((string)($row[\'scheduled_end_at\']??\'\'),0,10);'.$eol
    .'    return $startDate!==\'\'&&$endDate!==\'\'&&$startDate!==$endDate?$multiDayRange($row):$timeRange($row);'.$eol
    .'};'.$eol
    .'$withFilter=static function(array $changes)use($filters):string{';
$body=replaceExactlyOnce($body,$anchor,$insert,'Inserción de listRange');

$body=replaceExactlyOnce(
    $body,
    '$renderItem=static function(array $item)use($h,$timeRange,$typeLabels,$statusLabels):void{',
    '$renderItem=static function(array $item)use($h,$listRange,$typeLabels,$statusLabels):void{',
    'Dependencias de renderItem'
);

$body=replaceFirst(
    $body,
    '<time><?= $h($timeRange($item)) ?></time>',
    '<time><?= $h($listRange($item)) ?></time>',
    'Rango visible de Lista'
);

if(file_put_contents($view,$body)===false) fail('No se pudo escribir Agenda.');

echo '[OK] Lista usa hora simple para un día y fecha+hora cuando la actividad cruza días.'.PHP_EOL;

if(run('"'.$php.'" -l "'.$view.'"')!==0) fail('Falló PHP lint en Agenda.');
if(run('"'.$php.'" "'.$listTest.'"')!==0) fail('La regresión de rangos de Lista no quedó en verde.');
if(run('"'.$php.'" "'.$uiTest.'"')!==0) fail('La regresión UI de Agenda no quedó en verde.');
if(run('"'.$php.'" "'.$calendarTest.'"')!==0) fail('La regresión multiday del Calendario no quedó en verde.');
if(run('git --no-pager diff --check')!==0) fail('git diff --check detectó problemas de formato.');

echo '[OK] GREEN confirmado: Lista distingue actividad horaria y multiday sin afectar Calendario.'.PHP_EOL;
