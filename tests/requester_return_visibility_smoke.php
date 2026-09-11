<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$feedback=(string)@file_get_contents($root.'/app/Controllers/TicketFeedbackController.php');
$ticketController=(string)@file_get_contents($root.'/app/Controllers/TicketController.php');
$show=(string)@file_get_contents($root.'/app/Views/tickets/show.php');
$ok=true;

function returnCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

returnCheck(str_contains($feedback,"'source'=>'REQUESTER_RETURN'"),'Devolución conserva origen REQUESTER_RETURN');
returnCheck(str_contains($feedback,"'comment_id'=>$commentId"),'Devolución conserva referencia al comentario con el motivo');
returnCheck(str_contains($ticketController,'requesterReturn'),'Workspace recibe contexto explícito de devolución');
returnCheck(str_contains($ticketController,'REQUESTER_RETURN'),'Controlador distingue devolución hecha por el solicitante');
returnCheck(str_contains($ticketController,'comment_id'),'Controlador reutiliza el comentario ya existente como fuente del motivo');
returnCheck(str_contains($show,'Devuelto por el solicitante'),'Workspace identifica claramente quién devolvió el caso');
returnCheck(str_contains($show,'Motivo de devolución'),'Workspace muestra el motivo con una etiqueta inequívoca');
returnCheck(str_contains($show,'requesterReturn'),'Vista consume el motivo derivado sin crear nueva estructura de BD');

exit($ok?0:1);
