<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$service=(string)file_get_contents($root.'/app/Services/TicketAttachmentService.php');
$controller=(string)file_get_contents($root.'/app/Controllers/ConversationController.php');
$view=(string)file_get_contents($root.'/app/Views/tickets/show.php');
$ok=true;

function attachmentCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

attachmentCheck(str_contains($service,"'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'"),'XLSX conserva MIME oficial');
attachmentCheck(str_contains($service,"'application/zip'"),'XLSX contempla detección como ZIP');
attachmentCheck(str_contains($service,"'application/x-zip-compressed'"),'XLSX contempla ZIP de Windows');
attachmentCheck(str_contains($service,"'application/octet-stream'"),'XLSX contempla MIME binario genérico');
attachmentCheck(str_contains($service,"'xlsx' => 'xl/workbook.xml'"),'XLSX exige marcador interno de workbook');
attachmentCheck(str_contains($service,"'[Content_Types].xml'"),'OOXML exige manifiesto interno');
attachmentCheck(str_contains($service,"str_starts_with(\$signature, 'PK')"),'OOXML exige firma ZIP');
attachmentCheck(!str_contains($service,"'application/zip' => 'xlsx'"),'No se permite cualquier ZIP como Excel');
attachmentCheck(str_contains($controller,"catch (\\RuntimeException \$e)"),'Conversación captura error de adjunto');
attachmentCheck(str_contains($controller,"Flash::set(\$e->getMessage(),'error')"),'Usuario recibe error legible sin pantalla 500');
attachmentCheck(str_contains($view,'.csv,.doc,.docx,.xls,.xlsx'),'Formulario y servicio comparten formatos visibles');

exit($ok?0:1);
