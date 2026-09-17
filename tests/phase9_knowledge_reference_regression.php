<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/KnowledgeReferenceService.php';
$controller=(string)file_get_contents($root.'/app/Controllers/ResolutionController.php');
$routes=(string)file_get_contents($root.'/public/index.php');
$view=(string)file_get_contents($root.'/app/Views/tickets/show.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$service=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($service!=='','Existe KnowledgeReferenceService');
ok(str_contains($service,'ticket_resolution_references'),'Registra relación estructurada');
ok(str_contains($service,'KNOWLEDGE_REFERENCE_USED'),'Registra evento de conocimiento usado');
ok(str_contains($service,'current_internal_revision_id'),'Conocimiento usa revisión interna exacta');
ok(!str_contains($service,"status='RESOLVED'"),'Usar referencia no resuelve ticket');
ok(!str_contains($service,'UPDATE tickets SET status'),'Servicio no cambia estado del ticket');
ok(str_contains($service,'root_cause'),'Devuelve causa');
ok(str_contains($service,"'solution'"),'Devuelve solución');
ok(str_contains($service,"'prevention'"),'Devuelve prevención');

ok(str_contains($routes,'/tickets/reference/use'),'Existe ruta usar referencia');
ok(str_contains($controller,'KnowledgeReferenceService'),'ResolutionController usa servicio de referencia');
ok(str_contains($controller,'function useReference('),'Controller expone useReference');
ok(str_contains($view,'Usar como referencia'),'Ticket muestra acción usar referencia');
ok(str_contains($view,'resolutionPrefill'),'Formulario consume precarga editable');
ok(str_contains($view,'name="root_cause"'),'Formulario conserva causa editable');
ok(str_contains($view,'name="solution_applied"'),'Formulario conserva solución editable');
ok(str_contains($view,'name="preventive_action"'),'Formulario conserva prevención editable');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Uso y trazabilidad de referencias de solución.'.PHP_EOL;
