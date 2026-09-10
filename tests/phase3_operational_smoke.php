<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ok=true;
function phase3Check(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

$slaFile=$root.'/app/Services/SlaPresentationService.php';
$lifeFile=$root.'/app/Services/TicketLifecycleService.php';
$controller=(string)@file_get_contents($root.'/app/Controllers/TicketController.php');
$queue=(string)@file_get_contents($root.'/app/Views/tickets/queue.php');
$show=(string)@file_get_contents($root.'/app/Views/tickets/show.php');
$management=(string)@file_get_contents($root.'/app/Controllers/ManagementController.php');
$xlsx=(string)@file_get_contents($root.'/app/Controllers/XlsxExportController.php');

phase3Check(is_file($slaFile),'Existe servicio central de presentación SLA');
phase3Check(is_file($lifeFile),'Existe servicio central de ciclo de vida del ticket');

if(is_file($slaFile)){
    require_once $slaFile;
    $ticket=['status'=>'IN_PROGRESS','created_at'=>'2026-09-10 12:00:00','resolution_due_at'=>'2026-09-10 14:00:00'];
    $inside=\App\Services\SlaPresentationService::summary($ticket,strtotime('2026-09-10 13:00:00'));
    $attention=\App\Services\SlaPresentationService::summary($ticket,strtotime('2026-09-10 13:40:00'));
    $near=\App\Services\SlaPresentationService::summary($ticket,strtotime('2026-09-10 13:55:00'));
    $late=\App\Services\SlaPresentationService::summary($ticket,strtotime('2026-09-10 14:01:00'));
    phase3Check(($inside['utilization_percent']??null)===50,'SLA calcula porcentaje utilizado');
    phase3Check(($inside['state_label']??'')==='Dentro de objetivo','SLA identifica caso dentro de objetivo');
    phase3Check(($attention['state_label']??'')==='Atención requerida','SLA identifica atención requerida');
    phase3Check(($near['state_label']??'')==='Próximo a vencer','SLA identifica proximidad al vencimiento');
    phase3Check(($late['state_label']??'')==='Vencido','SLA identifica vencimiento');
    phase3Check(str_contains((string)($inside['remaining_label']??''),'restante'),'SLA presenta tiempo restante en lenguaje operativo');
}

if(is_file($lifeFile)){
    require_once $lifeFile;
    $ticket=['status'=>'IN_PROGRESS','created_at'=>'2026-09-10 10:00:00','closed_at'=>'2026-09-10 11:00:00'];
    $events=[
        ['event_type'=>'CREATED','actor_type'=>'PUBLIC','old_value'=>null,'new_value'=>'{"status":"AVAILABLE"}','created_at'=>'2026-09-10 10:00:00','actor_name'=>null],
        ['event_type'=>'STATUS_CHANGED','actor_type'=>'USER','old_value'=>'{"status":"AVAILABLE"}','new_value'=>'{"status":"PENDING","pending_reason_code":"WAITING_USER"}','created_at'=>'2026-09-10 10:10:00','actor_name'=>'Tecnico'],
        ['event_type'=>'PENDING_REASON_CHANGED','actor_type'=>'USER','old_value'=>'{"status":"PENDING","pending_reason_code":"WAITING_USER"}','new_value'=>'{"status":"PENDING","pending_reason_code":"WAITING_PROVIDER"}','created_at'=>'2026-09-10 10:30:00','actor_name'=>'Tecnico'],
        ['event_type'=>'STATUS_CHANGED','actor_type'=>'USER','old_value'=>'{"status":"PENDING","pending_reason_code":"WAITING_PROVIDER"}','new_value'=>'{"status":"IN_PROGRESS"}','created_at'=>'2026-09-10 11:00:00','actor_name'=>'Tecnico'],
    ];
    $life=\App\Services\TicketLifecycleService::analyze($ticket,$events,strtotime('2026-09-10 11:00:00'));
    phase3Check(($life['pending_minutes']??-1)===50,'Ciclo calcula minutos totales en espera');
    phase3Check((($life['pending_reason_minutes']['WAITING_USER']??-1)===20),'Ciclo separa espera del usuario');
    phase3Check((($life['pending_reason_minutes']['WAITING_PROVIDER']??-1)===30),'Ciclo separa espera del proveedor aunque cambie el motivo sin cambiar de estado');
}

phase3Check(str_contains($controller,'ScopeService'),'Cola utiliza ScopeService');
phase3Check(str_contains($controller,"ticketConstraint('t')")||str_contains($controller,'ticketConstraint("t")'),'Cola aplica restricción de alcance real');
phase3Check(str_contains($controller,'assigned_name'),'Cola recupera nombre del responsable');
phase3Check(str_contains($queue,'sla_summary'),'Cola consume resumen SLA centralizado');
phase3Check(str_contains($show,'sla_summary'),'Workspace consume resumen SLA centralizado');
phase3Check(!str_contains($show,'name="status" value="CLOSED"'),'Workspace no ofrece cierre IT cuando la solución espera confirmación del solicitante');
phase3Check(str_contains($management,'TicketLifecycleService'),'Informes de gestión reutilizan ciclo de vida centralizado');
phase3Check(str_contains($xlsx,'TicketLifecycleService'),'XLSX reutiliza ciclo de vida centralizado');
phase3Check(!str_contains($controller,"DROP TABLE")&&!str_contains($controller,"ALTER TABLE"),'Fase 3 no introduce DDL desde controlador');

exit($ok?0:1);
