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
$controller=body($root.'/app/Controllers/WorkReportController.php');
$ticketViewController=body($root.'/app/Controllers/TicketViewController.php');
$view=body($root.'/app/Views/tickets/show_external.php');

// Plantilla específica para el flujo real ACSE -> SEMNOX.
ok(str_contains($schema,'ACSE_SEMNOX'),'Existe plantilla ACSE / SEMNOX');
ok(str_contains($schema,'system_module'),'Informe conserva producto o módulo SEMNOX');
ok(str_contains($schema,'affected_component'),'Informe conserva equipo o componente afectado');
ok(str_contains($schema,'error_symptom'),'Informe conserva error o síntoma exacto');
ok(str_contains($schema,'reproduction_steps'),'Informe conserva cómo reproducir el problema');
ok(str_contains($schema,'manufacturer_reference'),'Informe conserva referencia/caso SEMNOX');
ok(str_contains($schema,'manufacturer_diagnosis'),'Informe conserva diagnóstico recibido de SEMNOX');
ok(str_contains($schema,'manufacturer_instructions'),'Informe conserva instrucciones recibidas de SEMNOX');
ok(str_contains($schema,'procedure_steps'),'Informe conserva procedimiento realizado paso a paso');
ok(str_contains($schema,'tools_access_used'),'Informe conserva accesos o herramientas utilizadas');
ok(str_contains($schema,'risks_precautions'),'Informe conserva riesgos o precauciones');
ok(str_contains($schema,'rollback_steps'),'Informe conserva cómo revertir el cambio');
ok(str_contains($schema,'escalation_criteria'),'Informe conserva cuándo recomienda escalar nuevamente');

// La vista externa recibe la plantilla asignada y muestra campos pertinentes.
ok(str_contains($ticketViewController,'report_template'),'Vista del ticket conoce la plantilla del proveedor');
ok(str_contains($view,'ACSE_SEMNOX'),'Vista distingue plantilla ACSE / SEMNOX');
ok(str_contains($view,'Seguimiento técnico ACSE / SEMNOX'),'Proveedor ve un bloque específico de su servicio');
ok(str_contains($view,'name="system_module"'),'ACSE documenta producto o módulo SEMNOX');
ok(str_contains($view,'name="affected_component"'),'ACSE documenta equipo o componente afectado');
ok(str_contains($view,'name="error_symptom"'),'ACSE documenta error o síntoma');
ok(str_contains($view,'name="reproduction_steps"'),'ACSE documenta pasos de reproducción');
ok(str_contains($view,'name="provider_reference"'),'ACSE documenta su referencia interna');
ok(str_contains($view,'name="manufacturer_reference"'),'ACSE documenta caso/referencia SEMNOX');
ok(str_contains($view,'name="manufacturer_diagnosis"'),'ACSE documenta diagnóstico recibido de SEMNOX');
ok(str_contains($view,'name="manufacturer_instructions"'),'ACSE documenta instrucciones recibidas de SEMNOX');
ok(str_contains($view,'name="diagnosis"'),'ACSE documenta diagnóstico técnico consolidado');
ok(str_contains($view,'name="root_cause"'),'ACSE documenta causa raíz');
ok(str_contains($view,'name="actions_performed"'),'ACSE documenta acciones realizadas');
ok(str_contains($view,'name="configuration_changes"'),'ACSE documenta configuración/query/archivo/versión modificada');
ok(str_contains($view,'name="tests_performed"'),'ACSE documenta pruebas realizadas');
ok(str_contains($view,'name="result_summary"'),'ACSE documenta resultado');
ok(str_contains($view,'name="pending_items"'),'ACSE documenta pendientes');
ok(str_contains($view,'name="preventive_recommendation"'),'ACSE documenta recomendaciones');
ok(str_contains($view,'name="procedure_steps"'),'ACSE deja procedimiento paso a paso');
ok(str_contains($view,'name="tools_access_used"'),'ACSE deja herramientas o accesos utilizados');
ok(str_contains($view,'name="risks_precautions"'),'ACSE deja riesgos y precauciones');
ok(str_contains($view,'name="rollback_steps"'),'ACSE explica cómo revertir el cambio');
ok(str_contains($view,'name="escalation_criteria"'),'ACSE explica cuándo recomienda escalar nuevamente');

// Datos clave exigidos cuando la plantilla ACSE/SEMNOX está activa.
foreach(['system_module','error_symptom','diagnosis','actions_performed','tests_performed','result_summary','procedure_steps'] as $required){
    ok((bool)preg_match('/name="'.preg_quote($required,'/').'"[^>]*required/s',$view),$required.' es obligatorio en ACSE / SEMNOX');
}

// No usar métricas o preguntas que no aportan al flujo real.
ok(!str_contains($view,'name="progress_percent"'),'ACSE / SEMNOX no pide porcentaje manual');
ok(!preg_match('/name="parts_materials"[^>]*required/s',$view),'ACSE / SEMNOX no obliga materiales/repuestos');

// Nunca revelar la evaluación interna de Carrousel.
$lower=mb_strtolower($view);
foreach(['carrousel podría resolverlo','resolverlo internamente','internalización','dependencia actual','reproducible internamente','reemplazar'] as $forbidden){
    ok(!str_contains($lower,mb_strtolower($forbidden)),'ACSE no ve: '.$forbidden);
}

// Backend debe respetar la plantilla y persistir la información, sin resolver el caso.
ok(str_contains($controller,'ACSE_SEMNOX'),'Backend reconoce plantilla ACSE / SEMNOX');
ok(str_contains($controller,'manufacturer_reference'),'Backend persiste referencia SEMNOX');
ok(str_contains($controller,'procedure_steps'),'Backend persiste procedimiento paso a paso');
ok(str_contains($controller,'rollback_steps'),'Backend persiste reversión');
ok(str_contains($controller,'escalation_criteria'),'Backend persiste criterio de escalamiento');
ok(!str_contains($controller,"SET status='RESOLVED'"),'ACSE no resuelve automáticamente el ticket');
ok(!str_contains($controller,"SET status='CLOSED'"),'ACSE no cierra automáticamente el ticket');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión plantilla ACSE / SEMNOX completada.".PHP_EOL;
