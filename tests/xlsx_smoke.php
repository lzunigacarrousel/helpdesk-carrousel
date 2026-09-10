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

xcheck(strpos($xml,'<dimension ref="A1:B5"/>')!==false,'Worksheet declara dimensión usada');
xcheck(strpos($xml,'<sheetFormatPr defaultRowHeight="15"/>')!==false,'Worksheet declara formato base');
xcheck(strpos($xml,'<mergeCells ')!==false,'Worksheet conserva título combinado');
xcheck(strpos($xml,'<autoFilter ')===false,'Modo compatible: no serializa autoFilter manual');
xcheck(strpos($xml,'<sheetViews>')===false,'Modo compatible: no serializa vistas congeladas manuales');

$sheetDataPos=strpos($xml,'<sheetData>');
$mergeCellsPos=strpos($xml,'<mergeCells ');
$pageMarginsPos=strpos($xml,'<pageMargins ');
xcheck($sheetDataPos!==false&&$mergeCellsPos!==false&&$sheetDataPos<$mergeCellsPos,'OOXML: mergeCells aparece después de sheetData');
xcheck($mergeCellsPos!==false&&$pageMarginsPos!==false&&$mergeCellsPos<$pageMarginsPos,'OOXML: pageMargins aparece después de mergeCells');

if(class_exists(DOMDocument::class)){
    $dom=new DOMDocument();
    xcheck(@$dom->loadXML($xml),'Worksheet XML bien formado');
}

$stylesMethod=new ReflectionMethod(XlsxExportService::class,'styles');
$stylesMethod->setAccessible(true);
$styles=$stylesMethod->invoke(null);
xcheck(strpos($styles,'<protection ')===false,'Styles no incluye protección de celda innecesaria');
if(class_exists(DOMDocument::class)){
    $dom=new DOMDocument();
    xcheck(@$dom->loadXML($styles),'Styles XML bien formado');
}

exit($ok?0:1);
