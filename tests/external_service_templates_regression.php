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
        body($root.'/database/MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql');
$controller=body($root.'/app/Controllers/WorkReportController.php');
$ticketViewController=body($root.'/app/Controllers/TicketViewController.php');
$externalController=body($root.'/app/Controllers/ExternalController.php');
$adminView=body($root.'/app/Views/admin/externals.php');
$view=body($root.'/app/Views/tickets/show_external.php');
$js=body($root.'/public/assets/js/external-work-report.js');

// Plantillas por tipo de servicio, no por nombre de proveedor.
foreach([
    'GENERAL_SUPPORT'=>'Soporte general',
    'SOFTWARE_SUPPORT'=>'Soporte de software',
    'SOFTWARE_DEVELOPMENT'=>'Desarrollo de software',
    'AUDIT_ADVISORY'=>'Auditoría / asesoría',
] as $code=>$label){
    ok(str_contains($schema,$code),'Esquema contempla '.$label);
    ok(str_contains($adminView,$code),'Administración ofrece '.$label);
}
ok(!str_contains($schema,'ACSE_SEMNOX'),'Esquema no amarra plantilla a ACSE/SEMNOX');
ok(!str_contains($adminView,'ACSE_SEMNOX'),'Administración no amarra plantilla a ACSE/SEMNOX');

// Plantilla por defecto en proveedor y override por caso compartido.
ok(str_contains($schema,'external_profiles')&&str_contains($schema,'report_template'),'Proveedor conserva plantilla por defecto');
ok(str_contains($schema,'external_ticket_access')&&str_contains($schema,'report_template'),'Caso compartido conserva plantilla efectiva');
ok(str_contains($externalController,'report_template'),'Administración persiste plantilla');
ok(str_contains($ticketViewController,'report_template'),'Vista recibe plantilla efectiva');
ok(str_contains($controller,'report_template'),'Informe guarda plantilla utilizada');

// El formulario cambia por estado real del trabajo, sin porcentaje manual.
foreach(['ANALYSIS','WAITING_CARROUSEL','WAITING_THIRD_PARTY','IN_PROGRESS','VALIDATING','READY_FOR_REVIEW'] as $status){
    ok(str_contains($view,$status)||str_contains($js,$status),'Flujo contempla estado '.$status);
}
ok(str_contains($view,'name="work_status"'),'Proveedor informa estado real del trabajo');
ok(!str_contains($view,'name="progress_percent"'),'No se solicita porcentaje manual');
ok(str_contains($view,'data-report-template'),'Vista identifica plantilla activa');
ok(str_contains($view,'data-work-status'),'Vista permite bloques dinámicos por estado');
ok(str_contains($js,'data-work-status'),'JS controla bloques por estado');
ok(str_contains($js,'hidden'),'JS muestra solo bloques pertinentes');

// Soporte general: mínimo transversal.
foreach(['diagnosis','actions_performed','result_summary','pending_items','provider_reference'] as $field){
    ok(str_contains($view,'name="'.$field.'"'),'Soporte general puede capturar '.$field);
}

// Soporte de software: captura conocimiento técnico reusable.
foreach(['system_module','environment','error_symptom','reproduction_steps','root_cause','configuration_changes','tests_performed','procedure_steps','tools_access_used','rollback_steps','escalation_criteria'] as $field){
    ok(str_contains($view,'name="'.$field.'"'),'Soporte de software puede capturar '.$field);
}

// Desarrollo de software: código, BD, versión y despliegue.
foreach(['code_changes','database_changes','release_version','deployment_notes','rollback_steps'] as $field){
    ok(str_contains($view,'name="'.$field.'"'),'Desarrollo de software puede capturar '.$field);
}

// Auditoría / asesoría: hallazgo, riesgo, impacto y recomendación.
foreach(['review_scope','finding','evidence_summary','risk_level','business_impact','recommendation','recommendation_priority','suggested_owner','follow_up','conclusion'] as $field){
    ok(str_contains($view,'name="'.$field.'"'),'Auditoría / asesoría puede capturar '.$field);
}

// Materiales físicos solo cuando el tipo de servicio lo requiera; nunca universal.
ok(!preg_match('/name="parts_materials"[^>]*required/s',$view),'Materiales/repuestos no son obligatorios para todos');

// La evaluación estratégica de Carrousel jamás aparece al proveedor.
$externalText=mb_strtolower($view."\n".$js);
foreach(['internalización','reemplazar','dependencia actual','reproducible internamente','madurez del conocimiento','carrousel podría resolverlo'] as $forbidden){
    ok(!str_contains($externalText,mb_strtolower($forbidden)),'Proveedor no ve: '.$forbidden);
}

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión plantillas externas por tipo de servicio completada.".PHP_EOL;
