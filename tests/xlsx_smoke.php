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

$worksheetMethod=new ReflectionMethod(XlsxExportService::class,'worksheet');
$worksheetMethod->setAccessible(true);
$xml=$worksheetMethod->invoke(null,[
    'name'=>'Prueba',
    'title'=>'Libro de prueba',
    'subtitle'=>'Compatibilidad Excel',
    'headers'=>['Columna A','Columna B'],
    'rows'=>[['Uno',1],['Dos',2]],
]);

$autoFilterPos=strpos($xml,'<autoFilter ');
$mergeCellsPos=strpos($xml,'<mergeCells ');
xcheck($autoFilterPos!==false,'Worksheet incluye autoFilter');
xcheck($mergeCellsPos!==false,'Worksheet incluye mergeCells');
xcheck($autoFilterPos!==false&&$mergeCellsPos!==false&&$autoFilterPos<$mergeCellsPos,'OOXML válido: autoFilter aparece antes de mergeCells');

if(class_exists(DOMDocument::class)){
    $dom=new DOMDocument();
    xcheck(@$dom->loadXML($xml),'Worksheet XML bien formado');
}

exit($ok?0:1);
