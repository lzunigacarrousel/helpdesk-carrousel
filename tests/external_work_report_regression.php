<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$schema=body($root.'/database/INSTALAR.sql');
$router=body($root.'/public/index.php');
$controller=body($root.'/app/Controllers/WorkReportController.php');
$view=body($root.'/app/Views/tickets/show.php');

// Fuente de verdad genérica: sirve hoy para proveedores y mañana para usuarios internos.
ok(str_contains($schema,'CREATE TABLE ticket_work_reports'),'Existe fuente estructurada de informes de trabajo');
ok(str_contains($schema,'report_type'),'Informe distingue avance, solicitud de información y trabajo realizado');
ok(str_contains($schema,'author_user_id'),'Informe conserva quién documentó el trabajo');
ok(str_contains($schema,'author_access_type'),'Informe conserva si en ese momento era usuario interno o externo');
ok(str_contains($schema,'progress_percent'),'Informe puede registrar porcentaje de avance');
ok(str_contains($schema,'diagnosis'),'Informe captura diagnóstico técnico');
ok(str_contains($schema,'root_cause'),'Informe captura causa raíz');
ok(str_contains($schema,'actions_performed'),'Informe captura acciones ejecutadas');
ok(str_contains($schema,'parts_materials'),'Informe captura repuestos o materiales');
ok(str_contains($schema,'configuration_changes'),'Informe captura cambios de configuración');
ok(str_contains($schema,'tests_performed'),'Informe captura pruebas realizadas');
ok(str_contains($schema,'result_summary'),'Informe captura resultado obtenido');
ok(str_contains($schema,'pending_items'),'Informe captura pendientes');
ok(str_contains($schema,'preventive_recommendation'),'Informe captura recomendación preventiva');
ok(str_contains($schema,'provider_reference'),'Informe conserva referencia del proveedor');
ok(str_contains($schema,'time_spent_minutes'),'Informe captura tiempo invertido');
ok(str_contains($schema,'commitment_at'),'Informe puede registrar fecha compromiso');
ok(str_contains($schema,'ready_for_review'),'Trabajo realizado puede quedar listo para revisión interna');
ok(str_contains($schema,'comment_id'),'Informe puede quedar ligado a conversación/evidencia existente');

// Endpoint separado de la conversación libre.
ok(str_contains($router,"['POST','/tickets/work-report',[WorkReportController::class,'store']]"),'Existe endpoint de informe técnico');
ok(str_contains($controller,'final class WorkReportController'),'Existe controlador dedicado para informes técnicos');
ok(str_contains($controller,"'PROGRESS'"),'Controlador acepta avance');
ok(str_contains($controller,"'INFO_REQUEST'"),'Controlador acepta solicitud de información');
ok(str_contains($controller,"'WORK_COMPLETED'"),'Controlador acepta trabajo realizado');
ok(str_contains($controller,'external_ticket_access'),'Controlador respeta acceso externo al ticket');
ok(str_contains($controller,'can_comment'),'Controlador respeta permiso de participación');
ok(str_contains($controller,'WORK_REPORT_ADDED'),'Informe deja evento auditable');
ok(!str_contains($controller,"SET status='RESOLVED'"),'Proveedor no resuelve automáticamente el ticket');
ok(!str_contains($controller,"SET status='CLOSED'"),'Proveedor no cierra automáticamente el ticket');

// UX: reemplaza plantillas de texto por captura real de datos.
ok(str_contains($view,'external-work-report'),'Ticket externo contiene módulo de informe técnico');
ok(str_contains($view,'¿Qué deseas informar?'),'Módulo empieza con una decisión humana');
ok(str_contains($view,'Enviar avance'),'Módulo ofrece avance');
ok(str_contains($view,'Solicitar información'),'Módulo ofrece solicitud de información');
ok(str_contains($view,'Trabajo realizado'),'Módulo ofrece trabajo realizado');
ok(str_contains($view,'name="progress_percent"'),'Avance captura porcentaje');
ok(str_contains($view,'name="diagnosis"'),'Trabajo captura diagnóstico');
ok(str_contains($view,'name="root_cause"'),'Trabajo captura causa raíz');
ok(str_contains($view,'name="actions_performed"'),'Trabajo captura acciones realizadas');
ok(str_contains($view,'name="parts_materials"'),'Trabajo captura materiales/repuestos');
ok(str_contains($view,'name="configuration_changes"'),'Trabajo captura cambios de configuración');
ok(str_contains($view,'name="tests_performed"'),'Trabajo captura pruebas');
ok(str_contains($view,'name="result_summary"'),'Trabajo captura resultado');
ok(str_contains($view,'name="pending_items"'),'Trabajo captura pendientes');
ok(str_contains($view,'name="preventive_recommendation"'),'Trabajo captura prevención');
ok(str_contains($view,'name="provider_reference"'),'Trabajo captura referencia del proveedor');
ok(str_contains($view,'name="time_spent_minutes"'),'Trabajo captura tiempo invertido');
ok(str_contains($view,'name="commitment_at"'),'Avance puede capturar fecha compromiso');
ok(str_contains($view,'name="ready_for_review"'),'Trabajo realizado puede marcarse listo para revisión');

// La colaboración externa debe ser visible para el propio proveedor, pero nunca la interna.
ok(str_contains($view,"tc.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver conversación pública y externa');
ok(str_contains($view,"ta.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver evidencias públicas y externas');
ok(!str_contains($view,'$isExternal?\'\':" AND tc.visibility=\'PUBLIC\'"'),'Vista ya no limita al proveedor solo a PUBLIC');

// Se conserva la resolución canónica interna existente.
ok(str_contains($schema,'CREATE TABLE ticket_resolutions'),'Se conserva ticket_resolutions como solución canónica interna');
ok(str_contains($schema,'solution_applied'),'Solución interna conserva solución aplicada');
ok(str_contains($schema,'preventive_action'),'Solución interna conserva prevención/seguimiento');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión informe técnico estructurado completada.".PHP_EOL;
