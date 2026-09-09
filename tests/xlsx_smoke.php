<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/Services/XlsxExportService.php';

use App\Services\XlsxExportService;

$ok=true;
function xcheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

$method=new ReflectionMethod(XlsxExportService::class,'safeSheetName');
$method->setAccessible(true);

$cases=[
    'Informe / General'=>'Informe General',
    'Tickets: Septiembre'=>'Tickets Septiembre',
    'Problemas [abiertos]'=>'Problemas abiertos',
    'A?B*C'=>'A B C',
    '  Nombre   con   espacios  '=>'Nombre con espacios',
];

foreach($cases as $input=>$expected){
    $actual=$method->invoke(null,$input);
    xcheck($actual===$expected,'Nombre de hoja seguro: '.$input.' → '.$actual);
}

$long=str_repeat('A',60);
$short=$method->invoke(null,$long);
xcheck(mb_strlen($short)<=31,'Nombre de hoja limitado a 31 caracteres');

xcheck(class_exists(ZipArchive::class),'Extensión ZIP disponible para XLSX');

exit($ok?0:1);
