<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$feedback=(string)@file_get_contents($root.'/app/Controllers/TicketFeedbackController.php');
$ok=true;

function returnCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

returnCheck(str_contains($feedback,"'source'=>'REQUESTER_RETURN'"),'Devolución conserva origen REQUESTER_RETURN');
returnCheck(str_contains($feedback,"'comment_id'")&&str_contains($feedback,'$commentId'),'Devolución conserva referencia al comentario con el motivo');
returnCheck(str_contains($feedback,'Motivo de devolución'),'Comentario guardado identifica explícitamente el motivo de devolución');
returnCheck(str_contains($feedback,'Devuelto por el solicitante'),'Comentario guardado identifica quién devolvió el caso');
returnCheck(str_contains($feedback,'necesita más ayuda'),'Notificación al soporte conserva contexto humano de la devolución');
returnCheck(str_contains($feedback,"['reason'=>\$reason]"),'Auditoría conserva el motivo original sin perder estructura');

exit($ok?0:1);
