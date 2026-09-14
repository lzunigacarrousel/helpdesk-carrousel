<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php='C:\\xampp\\php\\php.exe';
$test=$root.'/tests/phase5_activities_ui_regression.php';
$service=$root.'/app/Services/TicketActivityService.php';
$controller=$root.'/app/Controllers/TicketController.php';
$index=$root.'/app/Views/tickets/index.php';
$show=$root.'/app/Views/tickets/show.php';

function fail(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}
function readFileStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail('No se pudo leer '.$path);
    return $value;
}
function writeFileStrict(string $path,string $content): void {
    if(file_put_contents($path,$content)===false) fail('No se pudo escribir '.$path);
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count!==1) fail($label.' esperaba exactamente 1 coincidencia y encontró '.$count.'.');
    return str_replace($search,$replace,$content);
}
function run(string $command): int {
    passthru($command,$code);
    return $code;
}

chdir($root) || fail('No se pudo entrar al repositorio.');
exec('git status --porcelain',$status,$statusCode);
if($statusCode!==0) fail('No se pudo consultar git status.');
if($status) fail('El working tree debe estar limpio antes de aplicar este ajuste.');

// RED: agregamos primero los gates de privacidad solicitante.
$testBody=readFileStrict($test);
$testSource=<<<'PHP'
$activityJs=body($root.'/public/assets/js/ticket-activities.js');
PHP;
$testReplacement=<<<'PHP'
$activityJs=body($root.'/public/assets/js/ticket-activities.js');
$activityService=body($root.'/app/Services/TicketActivityService.php');
$ticketIndex=body($root.'/app/Views/tickets/index.php');
PHP;
$testBody=replaceOnce($testBody,$testSource,$testReplacement,'Carga de fuentes para privacidad');
$privacyChecks=<<<'PHP'
// Hardening final Fase 5: privacidad integral del solicitante.
ok(str_contains($activityService,"a.status IN('PROGRAMADA','EN_CURSO')"),'Próxima atención solo recibe actividades próximas o activas');
ok(str_contains($ticketIndex,'Seguimiento por equipo de soporte'),'Lista del solicitante no expone responsable interno');
ok(str_contains($ticketView,"$isSupport?'Responsable':'Atención'"),'Detalle del solicitante reemplaza Responsable por Atención');
ok(str_contains($ticketView,"$isSupport?htmlspecialchars($ticket['assigned_name']??'Aún sin asignar'):'Equipo de soporte'"),'Detalle del solicitante no imprime assigned_name interno');
ok(str_contains($ticketController,"$requesterEventTypes=['CREATED','STATUS_CHANGED','RESOLVED','CLOSED','REOPENED'];"),'Solicitante usa whitelist segura de eventos');
ok(str_contains($ticketController,"$event['actor_name']=null"),'Historial del solicitante oculta nombres de actores internos');
ok(str_contains($ticketController,"'events'=>$events"),'Vista recibe historial filtrado según perfil');

PHP;
$testBody=replaceOnce($testBody,"// Task 8: resumen seguro para solicitante.",$privacyChecks."// Task 8: resumen seguro para solicitante.",'Inserción gates privacidad');
writeFileStrict($test,$testBody);

echo "=== RED esperado: la vista del solicitante aún expone contexto interno ===".PHP_EOL;
$red=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($red===0) fail('El RED no falló; los gates nuevos no están comprobando el problema observado.');
echo '[OK] RED confirmado: los nuevos gates detectan el problema real.'.PHP_EOL.PHP_EOL;

// GREEN 1: solo actividades visibles y todavía activas bajo "Próxima atención".
$serviceBody=readFileStrict($service);
$serviceSearch=<<<'PHP'
WHERE a.ticket_id=? AND a.requester_visible=1
             ORDER BY FIELD(a.status,'EN_CURSO','PROGRAMADA','FINALIZADA','CANCELADA'),
PHP;
$serviceReplacement=<<<'PHP'
WHERE a.ticket_id=? AND a.requester_visible=1
               AND a.status IN('PROGRAMADA','EN_CURSO')
             ORDER BY FIELD(a.status,'EN_CURSO','PROGRAMADA'),
PHP;
$serviceBody=replaceOnce($serviceBody,$serviceSearch,$serviceReplacement,'Filtro requesterVisibleForTicket');
writeFileStrict($service,$serviceBody);

// GREEN 2: listado del solicitante sin nombre del responsable interno.
$indexBody=readFileStrict($index);
$indexSearch=<<<'PHP'
<div class="ticket-list-bottom"><span><?= !empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Sin asignar' ?></span><span class="ticket-open-text"><?= $openText ?></span></div>
PHP;
$indexReplacement=<<<'PHP'
<div class="ticket-list-bottom"><span><?= $isExternal?(!empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Sin asignar'):'Seguimiento por equipo de soporte' ?></span><span class="ticket-open-text"><?= $openText ?></span></div>
PHP;
$indexBody=replaceOnce($indexBody,$indexSearch,$indexReplacement,'Listado seguro del solicitante');
writeFileStrict($index,$indexBody);

// GREEN 3: contexto del caso sin nombre del técnico para solicitante.
$showBody=readFileStrict($show);
$showSearch=<<<'PHP'
<div><dt>Responsable</dt><dd><?= htmlspecialchars($ticket['assigned_name']??'Aún sin asignar') ?></dd></div>
PHP;
$showReplacement=<<<'PHP'
<div><dt><?= $isSupport?'Responsable':'Atención' ?></dt><dd><?= $isSupport?htmlspecialchars($ticket['assigned_name']??'Aún sin asignar'):'Equipo de soporte' ?></dd></div>
PHP;
$showBody=replaceOnce($showBody,$showSearch,$showReplacement,'Contexto seguro del solicitante');
writeFileStrict($show,$showBody);

// GREEN 4: timeline del solicitante con whitelist pública y sin actor interno.
$controllerBody=readFileStrict($controller);
$marker=<<<'PHP'
        $activityService=new TicketActivityService();
PHP;
$eventFilter=<<<'PHP'
        $events=$e->fetchAll();
        if(!$isSupport){
            $requesterEventTypes=['CREATED','STATUS_CHANGED','RESOLVED','CLOSED','REOPENED'];
            $events=array_values(array_filter($events,static fn(array $event):bool=>in_array((string)$event['event_type'],$requesterEventTypes,true)));
            foreach($events as &$event)$event['actor_name']=null;
            unset($event);
        }

PHP;
$controllerBody=replaceOnce($controllerBody,$marker,$eventFilter.$marker,'Filtro de eventos del solicitante');
$renderSearch=<<<'PHP'
            'events'=>$e->fetchAll(),
PHP;
$renderReplacement=<<<'PHP'
            'events'=>$events,
PHP;
$controllerBody=replaceOnce($controllerBody,$renderSearch,$renderReplacement,'Entrega de eventos filtrados');
writeFileStrict($controller,$controllerBody);

echo "=== GREEN esperado: privacidad integral del solicitante ===".PHP_EOL;
$green=run('"'.$php.'" tests\\phase5_activities_ui_regression.php');
if($green!==0) fail('La regresión de Fase 5 no quedó en verde.');

foreach([$service,$controller,$index,$show,$test] as $file){
    if(run('"'.$php.'" -l "'.$file.'"')!==0) fail('Falló PHP lint en '.$file);
}

echo '[OK] Hardening de privacidad del solicitante aplicado.'.PHP_EOL;
echo '[OK] No se modificó la base de datos ni el dominio de estados del ticket.'.PHP_EOL;
