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

$schema=body($root.'/database/INSTALAR.sql')."\n".
        body($root.'/database/MIGRAR_TICKET_WORK_REPORTS_20260912.sql')."\n".
        body($root.'/database/MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql');
$router=body($root.'/public/index.php');
$controller=body($root.'/app/Controllers/WorkReportController.php');
$ticketViewController=body($root.'/app/Controllers/TicketViewController.php');
$view=body($root.'/app/Views/tickets/show_external.php');
$externalController=body($root.'/app/Controllers/ExternalController.php');
$externalAdmin=body($root.'/app/Views/admin/externals.php');

// Fuente estructurada reutilizable para cualquier proveedor y cualquier servicio.
ok(str_contains($schema,'ticket_work_reports'),'Existe fuente estructurada de informes de trabajo');
ok(str_contains($schema,'author_user_id'),'Informe conserva quién documentó el trabajo');
ok(str_contains($schema,'author_access_type'),'Informe conserva si en ese momento era usuario interno o externo');
ok(str_contains($schema,'report_template'),'Informe conserva qué plantilla técnica se utilizó');
ok(str_contains($schema,'work_status'),'Informe conserva el estado real del trabajo');
ok(str_contains($schema,'diagnosis'),'Informe puede capturar diagnóstico técnico');
ok(str_contains($schema,'root_cause'),'Informe puede capturar causa raíz');
ok(str_contains($schema,'actions_performed'),'Informe puede capturar acciones ejecutadas');
ok(str_contains($schema,'configuration_changes'),'Informe puede capturar cambios de configuración');
ok(str_contains($schema,'tests_performed'),'Informe puede capturar pruebas realizadas');
ok(str_contains($schema,'result_summary'),'Informe puede capturar resultado obtenido');
ok(str_contains($schema,'pending_items'),'Informe puede capturar pendientes');
ok(str_contains($schema,'preventive_recommendation'),'Informe puede capturar recomendación preventiva');
ok(str_contains($schema,'provider_reference'),'Informe conserva referencia del proveedor');
ok(str_contains($schema,'time_spent_minutes'),'Informe puede capturar tiempo invertido');
ok(str_contains($schema,'comment_id'),'Informe puede quedar ligado a conversación/evidencia existente');

// Carrousel administra una plantilla por defecto por proveedor y puede cambiarla por caso.
ok(str_contains($schema,'external_profiles')&&str_contains($schema,'report_template'),'Perfil externo conserva plantilla por defecto');
ok(str_contains($schema,'external_ticket_access')&&str_contains($schema,'report_template'),'Caso compartido puede sobrescribir la plantilla por defecto');
ok(str_contains($externalController,'report_template'),'Administración lee y persiste la plantilla');
ok(str_contains($externalAdmin,'name="report_template"'),'Administración puede asignar plantilla al proveedor');
ok(str_contains($externalAdmin,'GENERAL_SUPPORT'),'Existe plantilla Soporte general');
ok(str_contains($externalAdmin,'SOFTWARE_SUPPORT'),'Existe plantilla Soporte de software');
ok(str_contains($externalAdmin,'SOFTWARE_DEVELOPMENT'),'Existe plantilla Desarrollo de software');
ok(str_contains($externalAdmin,'AUDIT_ADVISORY'),'Existe plantilla Auditoría / asesoría');
ok(!str_contains($externalAdmin,'ACSE_SEMNOX'),'Las plantillas no están amarradas a nombres de proveedores');

// La vista resuelve la plantilla efectiva del caso, no la identidad del proveedor.
ok(str_contains($ticketViewController,'report_template'),'Vista del ticket conoce la plantilla efectiva');
ok(str_contains($ticketViewController,'external_ticket_access'),'Vista puede resolver configuración específica del caso');
ok(str_contains($ticketViewController,'external_profiles'),'Vista puede usar la plantilla por defecto del proveedor');

// Endpoint separado de conversación libre y controlado por acceso externo.
ok(str_contains($router,"['POST','/tickets/work-report',[WorkReportController::class,'store']]"),'Existe endpoint de informe técnico');
ok(str_contains($controller,'final class WorkReportController'),'Existe controlador dedicado para informes técnicos');
ok(str_contains($controller,'external_profiles'),'Controlador conoce plantilla por defecto');
ok(str_contains($controller,'external_ticket_access'),'Controlador respeta acceso y plantilla del caso');
ok(str_contains($controller,'can_comment'),'Controlador respeta permiso de participación');
ok(str_contains($controller,'WORK_REPORT_ADDED'),'Informe deja evento auditable');
ok(!str_contains($controller,"SET status='RESOLVED'"),'Proveedor no resuelve automáticamente el ticket');
ok(!str_contains($controller,"SET status='CLOSED'"),'Proveedor no cierra automáticamente el ticket');

// UX dirigida por Carrousel: sin porcentaje manual ni campos físicos obligatorios para todos.
ok(str_contains($view,'external-work-report'),'Ticket externo contiene módulo de documentación técnica');
ok(str_contains($view,'Informe técnico requerido'),'Carrousel presenta documentación obligatoria');
ok(!str_contains($view,'name="progress_percent"'),'Formulario externo ya no exige porcentaje manual');
ok(!preg_match('/name="parts_materials"[^>]*required/s',$view),'Materiales/repuestos no son requisito universal');
ok(!str_contains($view,'¿Qué deseas informar?'),'Proveedor no decide libremente qué quiere contar');
ok(!str_contains($view,'data-external-report-choice'),'No existen decisiones de contenido controladas por el proveedor');

// Nunca revelar la estrategia interna de Carrousel al proveedor.
foreach(['resolverlo internamente','internalización','reemplazar al proveedor','dependencia actual','reproducible internamente','madurez del conocimiento'] as $forbidden){
    ok(!str_contains(mb_strtolower($view),mb_strtolower($forbidden)),'Vista externa no expone estrategia interna: '.$forbidden);
}

// La colaboración externa sigue visible para el propio proveedor, nunca la interna.
ok(str_contains($view,"tc.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver conversación pública y externa');
ok(str_contains($view,"ta.visibility IN('PUBLIC','EXTERNAL')"),'Proveedor puede ver evidencias públicas y externas');
ok(!str_contains($view,"tc.visibility='PUBLIC'"),'Vista externa no limita al proveedor solo a PUBLIC');

// Se conserva la resolución canónica interna.
ok(str_contains($schema,'ticket_resolutions'),'Se conserva ticket_resolutions como solución canónica interna');
ok(str_contains($schema,'solution_applied'),'Solución interna conserva solución aplicada');
ok(str_contains($schema,'preventive_action'),'Solución interna conserva prevención/seguimiento');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión base de documentación externa por tipo de servicio completada.".PHP_EOL;
