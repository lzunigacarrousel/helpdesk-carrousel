<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$feedback=(string)@file_get_contents($root.'/app/Controllers/TicketFeedbackController.php');
$ticketView=(string)@file_get_contents($root.'/app/Views/tickets/show.php');
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

$requesterBlock='';
if(preg_match('/<section[^>]*class="[^"]*ticket-requester-activities[^"]*".*?<\/section>/s',$ticketView,$m)){
    $requesterBlock=$m[0];
}
returnCheck($requesterBlock!=='','Existe bloque aislado de Próxima atención para solicitante');
returnCheck(str_contains($requesterBlock,'Próxima atención')||str_contains($requesterBlock,'Proxima atención'),'Bloque del solicitante identifica Próxima atención');
returnCheck(str_contains($requesterBlock,'requester_summary'),'Bloque usa únicamente el resumen publicado');
foreach([
    'responsible_user_id'=>'Responsable interno no se expone al solicitante',
    'provider_user_id'=>'Proveedor interno no se expone al solicitante',
    'internal_preparation_notes'=>'Preparación interna no se expone al solicitante',
    'cancel_reason'=>'Motivo interno de cancelación no se expone al solicitante',
    'work_performed'=>'Trabajo técnico realizado no se expone al solicitante',
    'result_summary'=>'Resultado técnico interno no se expone al solicitante',
] as $needle=>$message){
    returnCheck($requesterBlock!==''&&!str_contains($requesterBlock,$needle),$message);
}

exit($ok?0:1);
