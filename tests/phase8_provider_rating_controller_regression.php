<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderRatingService.php';
$controllerPath=$root.'/app/Controllers/ProviderRatingController.php';
$routerPath=$root.'/public/index.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$serviceBody=is_file($servicePath)?(string)file_get_contents($servicePath):'';
$controllerBody=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$routerBody=is_file($routerPath)?(string)file_get_contents($routerPath):'';

ok($serviceBody!=='','Existe ProviderRatingService');
ok($routerBody!=='','Existe router principal');
ok($controllerBody!=='','Existe ProviderRatingController');

ok(str_contains($serviceBody,'function ratingEventsForTickets('),'Servicio expone ratingEventsForTickets');
ok(str_contains($serviceBody,'function enrichRows('),'Servicio expone enrichRows');
ok(str_contains($serviceBody,'function rateCycle('),'Servicio expone rateCycle');
ok(str_contains($serviceBody,'function correctCycle('),'Servicio expone correctCycle');
ok(str_contains($serviceBody,'function isCycleEvaluable('),'Servicio centraliza criterio de ciclo evaluable');
ok(substr_count($serviceBody,'isCycleEvaluable(')>=3,'Persistencia reutiliza criterio evaluable en lectura y escritura');

ok(str_contains($serviceBody,"event_type IN('PROVIDER_RATED','PROVIDER_RATING_CORRECTED')")
    ||str_contains($serviceBody,"event_type IN ('PROVIDER_RATED','PROVIDER_RATING_CORRECTED')"),
    'Lectura se limita a eventos de valoración');
ok(str_contains($serviceBody,'FOR UPDATE'),'Persistencia serializa por ticket con FOR UPDATE');
ok(str_contains($serviceBody,"'PROVIDER_RATED'"),'Servicio conoce PROVIDER_RATED');
ok(str_contains($serviceBody,"'PROVIDER_RATING_CORRECTED'"),'Servicio conoce PROVIDER_RATING_CORRECTED');
ok(!preg_match('/UPDATE\s+ticket_events|DELETE\s+FROM\s+ticket_events/i',$serviceBody),'Valoración nunca edita ni borra ticket_events');

ok(str_contains($controllerBody,'class ProviderRatingController'),'Controller define ProviderRatingController');
ok(str_contains($controllerBody,"['ADMIN','SEMIADMIN','TECHNICIAN']")
    ||str_contains($controllerBody,"['ADMIN', 'SEMIADMIN', 'TECHNICIAN']"),
    'Controller limita roles operativos');
ok(str_contains($controllerBody,'Csrf::verify'),'Controller valida CSRF');
ok(str_contains($controllerBody,'userCanAccessTicket'),'Controller exige scope backend');
ok(str_contains($controllerBody,'function rate('),'Controller expone rate');
ok(str_contains($controllerBody,'function correct('),'Controller expone correct');
ok(str_contains($controllerBody,"Audit::log('PROVIDER_RATED'"),'Primera valoración queda auditada');
ok(str_contains($controllerBody,"Audit::log('PROVIDER_RATING_CORRECTED'"),'Corrección queda auditada');
ok(str_contains($controllerBody,"#provider-quality"),'Controller vuelve al bloque de calidad del proveedor');

ok(str_contains($routerBody,'ProviderRatingController'),'Router importa ProviderRatingController');
ok(str_contains($routerBody,"['POST','/tickets/provider-rating',[ProviderRatingController::class,'rate']]")
    ||str_contains($routerBody,"['POST', '/tickets/provider-rating', [ProviderRatingController::class, 'rate']]") ,
    'Existe POST de primera valoración');
ok(str_contains($routerBody,"['POST','/tickets/provider-rating/correct',[ProviderRatingController::class,'correct']]")
    ||str_contains($routerBody,"['POST', '/tickets/provider-rating/correct', [ProviderRatingController::class, 'correct']]") ,
    'Existe POST de corrección');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Persistencia y autorización backend de valoraciones.'.PHP_EOL;
