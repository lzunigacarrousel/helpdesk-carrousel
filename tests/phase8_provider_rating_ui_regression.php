<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controllerPath=$root.'/app/Controllers/TicketController.php';
$showPath=$root.'/app/Views/tickets/show.php';
$externalPath=$root.'/app/Views/tickets/show_external.php';
$servicePath=$root.'/app/Services/ProviderRatingService.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$controllerBody=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$showBody=is_file($showPath)?(string)file_get_contents($showPath):'';
$externalBody=is_file($externalPath)?(string)file_get_contents($externalPath):'';
$serviceBody=is_file($servicePath)?(string)file_get_contents($servicePath):'';

ok($controllerBody!=='','Existe TicketController');
ok($showBody!=='','Existe vista interna del ticket');
ok($externalBody!=='','Existe vista externa del ticket');

ok(str_contains($controllerBody,'ProviderParticipationService'),'Ticket carga ProviderParticipationService');
ok(str_contains($controllerBody,'ProviderRatingService'),'Ticket carga ProviderRatingService');
ok(str_contains($controllerBody,'rowsForTicket('),'Ticket obtiene ciclos del caso');
ok(str_contains($controllerBody,'enrichRows('),'Ticket enriquece ciclos con valoración');
ok(str_contains($controllerBody,"'providerCycles'"),'Controller expone providerCycles a la vista');
ok(str_contains($controllerBody,"'providerRatingLabels'"),'Controller expone etiquetas de valoración');

ok(str_contains($showBody,'id="provider-quality"'),'Ticket interno contiene bloque Calidad del proveedor');
ok(str_contains($showBody,'Calidad del proveedor'),'Bloque usa título operativo');
ok(str_contains($showBody,'Evaluar proveedor'),'Ciclo finalizado sin rating permite evaluar');
ok(str_contains($showBody,'Registrar corrección'),'Ciclo evaluado permite corrección');
ok(str_contains($showBody,'Podrás evaluar cuando finalice la participación.'),'Ciclo activo explica por qué no se evalúa');
ok(str_contains($showBody,'ProviderRatingService::isCycleEvaluable'),'Vista reutiliza criterio central de ciclo evaluable');

ok(str_contains($showBody,'/tickets/provider-rating"'),'Formulario usa endpoint de primera valoración');
ok(str_contains($showBody,'/tickets/provider-rating/correct"'),'Formulario usa endpoint de corrección');
ok(str_contains($showBody,'name="score"'),'Formulario captura score');
ok(str_contains($showBody,'name="comment"'),'Formulario captura comentario');
ok(str_contains($showBody,'name="external_user_id"'),'Formulario conserva proveedor exacto');
ok(str_contains($showBody,'name="grant_event_id"'),'Formulario conserva ciclo exacto');
ok(str_contains($showBody,'name="corrected_rating_event_id"'),'Corrección referencia valoración vigente');
ok(str_contains($showBody,'Comentario obligatorio para 1–2 estrellas y para toda corrección.'),'UI explica regla de comentario');

ok(str_contains($showBody,'provider_rating_score'),'Vista muestra valoración vigente');
ok(str_contains($showBody,'provider_rating_comment'),'Vista interna puede mostrar comentario de IT');
ok(str_contains($showBody,'provider_rating_actor'),'Vista interna muestra quién calificó');
ok(str_contains($showBody,'provider_rating_at'),'Vista interna muestra fecha de valoración');

ok(!str_contains($externalBody,'PROVIDER_RATED'),'Vista externa no expone evento de valoración');
ok(!str_contains($externalBody,'provider_rating_score'),'Proveedor no recibe score interno');
ok(!str_contains($externalBody,'provider_rating_comment'),'Proveedor no recibe comentario interno');
ok(!str_contains($externalBody,'provider-quality'),'Vista externa no renderiza bloque de calidad');

ok(str_contains($showBody,'if($isSupport&&!empty($providerCycles))')
    ||str_contains($showBody,'if ($isSupport && !empty($providerCycles))'),
    'Solicitante queda fuera del bloque de calidad interna');
ok(str_contains($controllerBody,'if($isSupport)')||str_contains($controllerBody,'if ($isSupport)'),
    'Controller carga calidad solo para soporte interno');
ok($serviceBody!==''&&str_contains($serviceBody,'function isCycleEvaluable('),'UI depende de servicio con criterio evaluable central');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] UI interna de calidad de proveedores.'.PHP_EOL;
