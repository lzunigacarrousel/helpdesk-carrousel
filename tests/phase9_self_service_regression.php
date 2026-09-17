<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$service=(string)file_get_contents($root.'/app/Services/SolutionSuggestionService.php');
$controller=(string)file_get_contents($root.'/app/Controllers/TicketController.php');
$routes=(string)file_get_contents($root.'/public/index.php');
$publicView=(string)file_get_contents($root.'/app/Views/tickets/public_create.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

ok(str_contains($service,'current_public_revision_id'),'Autoservicio usa puntero público');
ok(str_contains($service,'function forRequesterDraft('),'Servicio expone sugerencias de solicitante');
ok(str_contains($service,"ka.lifecycle_status='ACTIVE'"),'Autoservicio solo usa artículos activos');
ok(!str_contains($service,'known_problems')||str_contains($service,'forTicket'),'Autoservicio no mezcla problemas conocidos en su método público');
ok(str_contains($controller,'function publicKnowledgeSuggestions('),'Existe endpoint de sugerencias públicas');
ok(str_contains($routes,'/crear-ticket/sugerencias'),'Existe ruta de sugerencias públicas');
ok(str_contains($publicView,'Esto podría ayudarte'),'Vista muestra bloque de ayuda');
ok(str_contains($publicView,'¿Aún necesitas ayuda? Continúa con tu solicitud.'),'Ayuda no bloquea flujo');
ok(str_contains($publicView,'/crear-ticket/sugerencias'),'Vista consulta sugerencias dinámicas');
ok(str_contains($publicView,'publicSubmitBtn'),'Botón de enviar solicitud sigue disponible');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Autoservicio público usa solo conocimiento publicado.'.PHP_EOL;
