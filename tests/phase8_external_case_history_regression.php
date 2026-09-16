<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$controllerPath=$root.'/app/Controllers/TicketController.php';
$viewPath=$root.'/app/Views/tickets/index.php';

ok(is_file($controllerPath),'Existe TicketController');
ok(is_file($viewPath),'Existe vista Mis casos');

$controller=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$view=is_file($viewPath)?(string)file_get_contents($viewPath):'';

ok(str_contains($controller,'eta.granted_at external_granted_at'),'Listado externo conserva fecha de asignación');
ok(str_contains($controller,'eta.revoked_at external_revoked_at'),'Listado externo conserva fecha de finalización');
ok(str_contains($controller,"eta.revoked_at IS NOT NULL OR t.visibility_mode='EXTERNAL_ALLOWED'"),'Listado externo conserva histórico revocado sin reabrir acceso');
ok(!str_contains($controller,"eta.user_id=? AND eta.revoked_at IS NULL AND t.case_type='SPECIAL'"),'Listado externo ya no limita todo a accesos vigentes');

ok(str_contains($view,'external_revoked_at'),'Vista distingue participación activa e histórica');
ok(str_contains($view,'>Total</span>'),'Proveedor ve total histórico de casos');
ok(str_contains($view,'Participación finalizada'),'Historial identifica participación finalizada');
ok(str_contains($view,'external_granted_at'),'Historial muestra fecha de asignación');
ok(str_contains($view,'external_revoked_at'),'Historial muestra fecha de finalización');
ok(str_contains($view,'if(!$isExternalHistory): ?><a class="ticket-card-link"'),'Caso histórico no enlaza al detalle');
ok(!str_contains($view,'provider_rating'),'Vista externa no expone valoración interna');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Historial seguro de casos para proveedor externo.'.PHP_EOL;
