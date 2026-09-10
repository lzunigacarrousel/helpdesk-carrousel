<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;
use ZipArchive;

final class XlsxExportService
{
    public static function download(string $filename,array $sheets): void
    {
        if(!class_exists(ZipArchive::class)){
            throw new RuntimeException('La exportación Excel requiere la extensión ZIP de PHP habilitada.');
        }
        if(!$sheets)throw new RuntimeException('No hay información para exportar.');

        $tmp=tempnam(sys_get_temp_dir(),'helpdesk_xlsx_');
        if($tmp===false)throw new RuntimeException('No pudimos preparar el archivo Excel.');
        $zip=new ZipArchive();
        if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){@unlink($tmp);throw new RuntimeException('No pudimos crear el archivo Excel.');}

        $sheetEntries=[];$sheetRels=[];$contentSheets=[];
        foreach(array_values($sheets) as $i=>$sheet){
            $n=$i+1;$name=self::safeSheetName((string)($sheet['name']??('Hoja '.$n)));
            $sheetEntries[]='<sheet name="'.self::xml($name).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
            $sheetRels[]='<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
            $contentSheets[]='<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $zip->addFromString('xl/worksheets/sheet'.$n.'.xml',self::worksheet($sheet));
        }

        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .implode('',$contentSheets).'</Types>');

        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>');

        $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.implode('',$sheetEntries).'</sheets></workbook>');

        $sheetRels[]='<Relationship Id="rId'.(count($sheets)+1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('',$sheetRels).'</Relationships>');
        $zip->addFromString('xl/styles.xml',self::styles());

        $now=gmdate('Y-m-d\TH:i:s\Z');
        $zip->addFromString('docProps/core.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Helpdesk Carrousel</dc:creator><cp:lastModifiedBy>Helpdesk Carrousel</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Helpdesk Carrousel</Application></Properties>');
        $zip->close();

        $filename=preg_replace('/[^A-Za-z0-9._-]+/','_',pathinfo($filename,PATHINFO_FILENAME)).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($tmp));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($tmp);@unlink($tmp);exit;
    }

    private static function worksheet(array $sheet): string
    {
        $headers=array_values($sheet['headers']??[]);$rows=array_values($sheet['rows']??[]);
        $title=trim((string)($sheet['title']??''));$subtitle=trim((string)($sheet['subtitle']??''));
        $headerRow=1;$dataStart=2;$xmlRows=[];$maxCols=max(1,count($headers));

        if($title!==''){
            $xmlRows[]='<row r="1" ht="26" customHeight="1"><c r="A1" t="inlineStr" s="2"><is><t>'.self::xml($title).'</t></is></c></row>';
            $headerRow=3;$dataStart=4;
            if($subtitle!=='')$xmlRows[]='<row r="2"><c r="A2" t="inlineStr" s="3"><is><t>'.self::xml($subtitle).'</t></is></c></row>';
        }

        $headerCells=[];foreach($headers as $i=>$h){$col=self::col($i+1);$headerCells[]='<c r="'.$col.$headerRow.'" t="inlineStr" s="1"><is><t>'.self::xml((string)$h).'</t></is></c>';}
        $xmlRows[]='<row r="'.$headerRow.'" ht="22" customHeight="1">'.implode('',$headerCells).'</row>';

        $widths=[];foreach($headers as $i=>$h)$widths[$i]=min(42,max(10,mb_strlen((string)$h)+2));
        foreach($rows as $rIndex=>$row){
            $excelRow=$dataStart+$rIndex;$cells=[];$values=array_values($row);
            foreach($headers as $i=>$h){
                $v=$values[$i]??'';$col=self::col($i+1);$widths[$i]=min(48,max($widths[$i]??10,min(48,mb_strlen(trim((string)$v))+2)));
                if(is_int($v)||is_float($v))$cells[]='<c r="'.$col.$excelRow.'" s="0"><v>'.$v.'</v></c>';
                else $cells[]='<c r="'.$col.$excelRow.'" t="inlineStr" s="0"><is><t xml:space="preserve">'.self::xml((string)$v).'</t></is></c>';
            }
            $xmlRows[]='<row r="'.$excelRow.'">'.implode('',$cells).'</row>';
        }

        $cols=[];foreach($headers as $i=>$h){$n=$i+1;$cols[]='<col min="'.$n.'" max="'.$n.'" width="'.number_format((float)($widths[$i]??12),1,'.','').'" customWidth="1"/>';}
        $lastCol=self::col($maxCols);$lastRow=max($headerRow,$dataStart+count($rows)-1);
        $dimension='A1:'.$lastCol.$lastRow;
        $merge=$title!==''?'<mergeCells count="1"><mergeCell ref="A1:'.$lastCol.'1"/></mergeCells>':'';

        /*
         * Compatibilidad Excel:
         * El archivo probado en Microsoft Excel abre sin reparación cuando la hoja usa
         * el subconjunto estable de SpreadsheetML: dimension + sheetFormatPr + cols +
         * sheetData + mergeCells + pageMargins. Los filtros siguen existiendo en la
         * pantalla web y pueden aplicarse antes de exportar; evitamos serializar
         * autoFilter/sheetViews manualmente porque Excel estaba reparando esos libros.
         */
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="'.$dimension.'"/><sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.implode('',$cols).'</cols><sheetData>'.implode('',$xmlRows).'</sheetData>'.$merge
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3"><font><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Calibri"/></font><font><b/><color rgb="FF173D75"/><sz val="16"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF173D75"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD9E1EC"/></left><right style="thin"><color rgb="FFD9E1EC"/></right><top style="thin"><color rgb="FFD9E1EC"/></top><bottom style="thin"><color rgb="FFD9E1EC"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private static function safeSheetName(string $name): string
    {
        // Excel no permite estos caracteres en nombres de hojas: \\ / ? * [ ] :
        // Usamos ~ como delimitador para evitar ambigüedad con la barra /.
        $name=preg_replace('~[\\\\/?*\[\]:]+~u',' ',trim($name))??'';
        $name=trim(preg_replace('/\s+/u',' ',$name)??'');
        return mb_substr($name!==''?$name:'Hoja',0,31);
    }
    private static function xml(string $value): string{return htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');}
    private static function col(int $n): string{$s='';while($n>0){$n--; $s=chr(65+($n%26)).$s;$n=intdiv($n,26);}return$s;}
}
