<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function body(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}

$view=body($root.'/app/Views/tickets/feedback.php');
$controller=body($root.'/app/Controllers/TicketFeedbackController.php');
$router=body($root.'/public/index.php');
$install=body($root.'/database/INSTALAR.sql');
$js=body($root.'/public/assets/js/ticket-feedback.js');

ok(str_contains($view,'¿Tu problema quedó resuelto?'),'Feedback inicia con una pregunta humana simple');
ok(str_contains($view,'data-feedback-choice="yes"'),'Feedback ofrece decisión Sí');
ok(str_contains($view,'data-feedback-choice="no"'),'Feedback ofrece decisión No');
ok(str_contains($view,'data-feedback-panel="yes"'),'Existe panel progresivo para Sí');
ok(str_contains($view,'data-feedback-panel="no"'),'Existe panel progresivo para No');
ok(str_contains($view,'data-feedback-panel="yes" hidden'),'Panel de calificación inicia oculto');
ok(str_contains($view,'data-feedback-panel="no" hidden'),'Panel de devolución inicia oculto');
ok(str_contains($view,'ticket-feedback.js'),'Vista carga comportamiento progresivo de feedback');

ok(str_contains($js,'data-feedback-choice'),'JS controla únicamente la decisión Sí/No');
ok(str_contains($js,'data-feedback-panel'),'JS muestra el panel correspondiente');
ok(str_contains($js,"setAttribute('aria-expanded'"),'JS conserva estado accesible de la decisión');
ok(str_contains($js,'.hidden ='),'JS alterna visibilidad sin recargar la página');

ok(str_contains($view,'name="nps_score"'),'Sí conserva calificación NPS existente');
ok(str_contains($view,'for($i=0;$i<=10;$i++)'),'NPS conserva escala histórica 0 a 10');
ok(str_contains($view,'Comentario <span class="optional">Opcional</span>'),'Sí conserva comentario opcional');
ok(str_contains($view,'name="reason"'),'No solicita motivo de devolución');
ok(str_contains($view,'name="reason"') && str_contains($view,'required'),'Motivo de devolución sigue siendo obligatorio');

ok(str_contains($controller,'if($score<0||$score>10)'),'Backend conserva validación NPS 0 a 10');
ok(str_contains($controller,"UPDATE tickets SET status='CLOSED'"),'Sí reutiliza cierre actual');
ok(str_contains($controller,"UPDATE tickets SET status='REOPENED'"),'No reutiliza reapertura actual');
ok(str_contains($controller,"'TICKET_FEEDBACK_SUBMITTED'"),'Sí conserva auditoría/notificación de feedback');
ok(str_contains($controller,"'TICKET_REOPENED_BY_REQUESTER'"),'No conserva auditoría de reapertura');

ok(str_contains($router,"['GET','/tickets/feedback'"),'Ruta de revisión de solución se conserva');
ok(str_contains($router,"['POST','/tickets/feedback'"),'Ruta de confirmación se conserva');
ok(str_contains($router,"['POST','/tickets/feedback/reopen'"),'Ruta de reapertura se conserva');

ok(str_contains($install,'CREATE TABLE ticket_feedback'),'Se conserva tabla ticket_feedback existente');
ok(str_contains($install,'nps_score TINYINT UNSIGNED NOT NULL'),'Se conserva nps_score histórico');
ok(str_contains($install,'CHECK (nps_score BETWEEN 0 AND 10)'),'BD conserva significado NPS 0 a 10');
ok(!str_contains($install,'satisfaction_stars'),'Fase 4 no introduce estrellas en BD');
ok(!str_contains($install,'feedback_resolved'),'Fase 4 no agrega columna redundante de Sí/No');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo PHP_EOL."[OK] Regresión Fase 4 Feedback completada.".PHP_EOL;
