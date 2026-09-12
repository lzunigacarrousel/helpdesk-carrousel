<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fails=0;
function ok(bool $cond,string $msg):void{global $fails;echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$cond)$fails++;}
function body(string $p):string{$v=@file_get_contents($p);return is_string($v)?$v:'';}
$servicePath=$root.'/app/Services/TicketClassificationService.php';
ok(is_file($servicePath),'Existe TicketClassificationService');
if(is_file($servicePath)){
    require_once $servicePath;$svc='App\\Services\\TicketClassificationService';
    $expectedMatrix=['LOW'=>['INDIVIDUAL'=>'LOW','AREA'=>'LOW','PARK'=>'MEDIUM','MULTI_PARK'=>'MEDIUM'],'MEDIUM'=>['INDIVIDUAL'=>'LOW','AREA'=>'MEDIUM','PARK'=>'MEDIUM','MULTI_PARK'=>'HIGH'],'HIGH'=>['INDIVIDUAL'=>'MEDIUM','AREA'=>'MEDIUM','PARK'=>'HIGH','MULTI_PARK'=>'CRITICAL'],'CRITICAL'=>['INDIVIDUAL'=>'MEDIUM','AREA'=>'HIGH','PARK'=>'CRITICAL','MULTI_PARK'=>'CRITICAL']];
    foreach($expectedMatrix as $urgency=>$impacts)foreach($impacts as $impact=>$expected)ok($svc::calculatePriority($impact,$urgency)===$expected,"Matriz {$impact} + {$urgency} = {$expected}");
    $invalidRejected=false;try{$svc::calculatePriority('NO_EXISTE','LOW');}catch(InvalidArgumentException){$invalidRejected=true;}ok($invalidRejected,'Matriz rechaza impacto invalido');
    ok($svc::inferRequestType('ACCESS_PERMISSION','ACCESS')==='SERVICE_REQUEST','Acceso/permisos se infiere como solicitud de servicio');
    ok($svc::inferRequestType('NETWORK_OUTAGE','NETWORK')==='INCIDENT','Falla de red se infiere como incidente');
    $auto=$svc::inferInitialClassification(['code'=>'NETWORK_OUTAGE','parent_code'=>'NETWORK'],'Sin Internet','No hay Internet en todo el parque y no podemos operar.');
    ok(($auto['impact']??'')==='PARK','Texto de todo el parque eleva impacto a PARK');
    ok(($auto['urgency']??'')==='CRITICAL','Operación detenida eleva urgencia a CRITICAL');
    ok(($auto['priority']??'')==='CRITICAL','Inferencia crítica calcula prioridad crítica');
}
$schema=body($root.'/database/INSTALAR.sql');
ok(str_contains($schema,"request_type ENUM('INCIDENT','SERVICE_REQUEST')"),'Esquema conserva request_type separado de case_type');
ok(str_contains($schema,"impact ENUM('INDIVIDUAL','AREA','PARK','MULTI_PARK')"),'Esquema conserva impacto');
ok(str_contains($schema,"urgency ENUM('LOW','MEDIUM','HIGH','CRITICAL')"),'Esquema conserva urgencia');
ok(str_contains($schema,"priority_source ENUM('CALCULATED','MANUAL','LEGACY')"),'Esquema conserva origen de prioridad');
ok(str_contains($schema,"'tickets.classify'"),'Existe permiso tickets.classify');
$controller=body($root.'/app/Controllers/TicketController.php');
ok(!str_contains($controller,"Http::post('request_type')"),'Alta pública ya no exige tipo ITSM al solicitante');
ok(!str_contains($controller,"Http::post('impact')"),'Alta pública ya no exige impacto al solicitante');
ok(!str_contains($controller,"Http::post('urgency')"),'Alta pública ya no exige urgencia al solicitante');
ok(str_contains($controller,'TicketClassificationService::inferInitialClassification'),'Alta pública infiere clasificación internamente');
ok(str_contains($controller,"'CALCULATED'"),'Alta pública registra prioridad calculada');
$classController=body($root.'/app/Controllers/TicketClassificationController.php');
ok(str_contains($classController,"Auth::requirePermission('tickets.classify')"),'Reclasificación exige permiso');
ok(str_contains($classController,'CLASSIFICATION_CHANGED'),'Reclasificación deja evento');
ok(str_contains($classController,'TICKET_CLASSIFICATION_CHANGED'),'Reclasificación deja auditoría');
ok(str_contains($classController,'priority_override'),'Soporte puede ajustar prioridad');
ok(str_contains($classController,'resolution_due_at'),'Reclasificación mantiene SLA coherente');
$form=body($root.'/app/Views/tickets/public_create.php');
ok(!str_contains($form,'name="request_type"'),'Formulario público no pregunta incidente vs solicitud');
ok(!str_contains($form,'name="impact"'),'Formulario público no pregunta impacto');
ok(!str_contains($form,'name="urgency"'),'Formulario público no pregunta urgencia');
ok(!str_contains($form,'data-public-step="classification"'),'Tutorial público no expone clasificación ITSM');
$queue=body($root.'/app/Views/tickets/queue.php');ok(str_contains($queue,'request_type'),'Cola interna conserva tipo de caso');ok(str_contains($queue,'Impacto:'),'Cola interna conserva impacto y urgencia');
$show=body($root.'/app/Views/tickets/show.php');ok(str_contains($show,'Clasificación del caso'),'Detalle interno conserva clasificación ITSM');ok(str_contains($show,'/tickets/classification'),'Soporte puede reclasificar');ok(str_contains($show,'if($isSupport): ?><section class="card ticket-classification-card"'),'Clasificación ITSM se oculta al solicitante');
$help=body($root.'/public/assets/js/help-tour.js');ok(!str_contains($help,"['[data-public-step=\"classification\"]'"),'Tutorial público vuelve a flujo simple');
$verify=body($root.'/database/VERIFICAR_INSTALACION.sql');ok(str_contains($verify,"('tickets','request_type')"),'Verificador exige request_type');ok(str_contains($verify,"('tickets','priority_source')"),'Verificador exige priority_source');
if($fails){fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validacion(es) fallaron.".PHP_EOL);exit(1);}echo PHP_EOL."[OK] Regresion ITSM 2.1 automática completada.".PHP_EOL;
