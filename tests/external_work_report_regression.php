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

$canonicalSchema=body($root.'/database/INSTALAR.sql');
$activeMigration=body($root.'/database/MIGRAR_TICKET_WORK_REPORTS_20260912.sql');
$schema=$canonicalSchema."\n".$activeMigration;
$router=body($root.'/public/index.php');
$controller=body($root.'/app/Controllers/WorkReportController.php');
$view=body($root.'/app/Views/tickets/show_external.php');

// Fuente de verdad genérica: sirve hoy para proveedores y mañana para usuarios internos.
ok(str_contains($schema,'CREATE TABLE')&&str_contains($schema,'ticket_work_reports'),'Existe fuente estructurada de informes de trabajo');
ok(str_contains($schema,'report_type'),'Informe conserva el estado del trabajo');
ok(str_contains($schema,'author_user_id'),'Informe conserva quién documentó el trabajo');
ok(str_contains($schema,'author_access_type'),'Informe conserva si en ese momento era usuario interno o externo');
ok(str_contains($schema,'progress_percent'),'Informe registra porcentaje de avance');
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
ok(str_contains($schema,'commitment_at'),'Informe registra fecha compromiso');
ok(str_contains($schema,'ready_for_review'),'Informe puede quedar listo para revisión interna');
ok(str_contains($schema,'comment_id'),'Informe puede quedar ligado a conversación/evidencia existente');

// Endpoint separado de la conversación libre.
ok(str_contains($router,"['POST','/tickets/work-report',[WorkReportController::class,'store']]"),'Existe endpoint de informe técnico');
ok(str_contains($controller,'final class WorkReportController'),'Existe controlador dedicado para informes técnicos');
ok(str_contains($controller,"'PROGRESS'"),'Controlador reconoce trabajo en atención');
ok(str_contains($controller,"'INFO_REQUEST'"),'Controlador reconoce espera de información');
ok(str_contains($controller,"'WORK_COMPLETED'"),'Controlador reconoce atención terminada');
ok(str_contains($controller,'external_ticket_access'),'Controlador respeta acceso externo al ticket');
ok(str_contains($controller,'can_comment'),'Controlador respeta permiso de participación');
ok(str_contains($controller,'WORK_REPORT_ADDED'),'Informe deja evento auditable');
ok(!str_contains($controller,"SET status='RESOLVED'"),'Proveedor no resuelve automáticamente el ticket');
ok(!str_contains($controller,"SET status='CLOSED'"),'Proveedor no cierra automáticamente el ticket');

// UX dirigida por Carrousel: el proveedor no decide qué quiere contar.
ok(str_contains($view,'external-work-report'),'Ticket externo contiene módulo de informe técnico');
ok(str_contains($view,'Informe técnico requerido'),'Carrousel presenta un informe técnico obligatorio');
ok(str_contains($view,'Estado actual del trabajo'),'Carrousel exige indicar el estado actual del trabajo');
ok(!str_contains($view,'¿Qué deseas informar?'),'Se elimina la pregunta abierta sobre qué desea informar el proveedor');
ok(!str_contains($view,'data-external-report-choice'),'Se eliminan decisiones de contenido controladas por el proveedor');
ok(str_contains($view,'name="report_type"'),'Formulario conserva estado del trabajo para trazabilidad');
ok(str_contains($view,'name="progress_percent"'),'Carrousel exige porcentaje de avance');
ok(str_contains($view,'name="diagnosis"'),'Carrousel exige diagnóstico técnico');
ok(str_contains($view,'name="root_cause"'),'Carrousel exige causa raíz');
ok(str_contains($view,'name="actions_performed"'),'Carrousel exige acciones realizadas');
ok(str_contains($view,'name="parts_materials"'),'Carrousel exige declarar materiales o repuestos');
ok(str_contains($view,'name="configuration_changes"'),'Carrousel exige declarar cambios de configuración');
ok(str_contains($view,'name="tests_performed"'),'Carrousel exige pruebas realizadas');
ok(str_contains($view,'name="result_summary"'),'Carrousel exige resultado obtenido');
ok(str_contains($view,'name="pending_items"'),'Carrousel exige declarar pendientes');
ok(str_contains($view,'name="preventive_recommendation"'),'Carrousel exige recomendación preventiva');
ok(str_contains($view,'name="provider_reference"'),'Carrousel exige referencia del proveedor');
ok(str_contains($view,'name="time_spent_minutes"'),'Carrousel exige tiempo invertido');
ok(str_contains($view,'name="commitment_at"'),'Carrousel exige fecha compromiso cuando corresponda');
ok(str_contains($view,'name="ready_for_review"'),'Atención terminada puede enviarse a revisión de Carrousel');
ok(str_contains($view,'Si un campo no aplica'),'Formulario obliga a declarar explícitamente cuando algo no aplica');

// Los campos críticos deben ser obligatorios: queremos información completa, no texto libre mínimo.
ok((bool)preg_match('/name="diagnosis"[^>]*required/s',$view),'Diagnóstico es obligatorio');
ok((bool)preg_match('/name="root_cause"[^>]*required/s',$view),'Causa raíz es obligatoria');
ok((bool)preg_match('/name="actions_performed"[^>]*required/s',$view),'Acciones realizadas son obligatorias');
ok((bool)preg_match('/name="parts_materials"[^>]*required/s',$view),'Materiales/repuestos deben declararse');
ok((bool)preg_match('/name="configuration_changes"[^>]*required/s',$view),'Cambios de configuración deben declararse');
ok((bool)preg_match('/name="tests_performed"[^>]*required/s',$view),'Pruebas realizadas son obligatorias');
ok((bool)preg_match('/name="result_summary"[^>]*required/s',$view),'Resultado obtenido es obligatorio');
ok((bool)preg_match('/name="pending_items"[^>]*required/s',$view),'Pendientes deben declararse');
ok((bool)preg_match('/name="preventive_recommendation"[^>]*required/s',$view),'Recomendación preventiva debe declararse');
ok((bool)preg_match('/name="provider_reference"[^>]*required/s',$view),'Referencia del proveedor es obligatoria');
ok((bool)preg_match('/name="time_spent_minutes"[^>]*required/s',$view),'Tiempo invertido es obligatorio');

// La colaboración externa debe ser visible para el propio proveedor, pero nunca la interna.
ok(str_contains($view,"tc.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver conversación pública y externa');
ok(str_contains($view,"ta.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver evidencias públicas y externas');
ok(!str_contains($view,"tc.visibility='PUBLIC'"),'Vista externa no limita al proveedor solo a PUBLIC');

// Se conserva la resolución canónica interna existente.
ok(str_contains($schema,'CREATE TABLE ticket_resolutions'),'Se conserva ticket_resolutions como solución canónica interna');
ok(str_contains($schema,'solution_applied'),'Solución interna conserva solución aplicada');
ok(str_contains($schema,'preventive_action'),'Solución interna conserva prevención/seguimiento');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión informe técnico exigido por Carrousel completada.".PHP_EOL;