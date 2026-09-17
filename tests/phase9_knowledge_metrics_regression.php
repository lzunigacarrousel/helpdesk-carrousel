<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/KnowledgeMetricsService.php';
$suggestions=(string)file_get_contents($root.'/app/Services/SolutionSuggestionService.php');
$references=(string)file_get_contents($root.'/app/Services/KnowledgeReferenceService.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$body=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($body!=='','Existe KnowledgeMetricsService');
if($body!==''){
    require_once $servicePath;
    $class='App\\Services\\KnowledgeMetricsService';
    ok(class_exists($class),'Clase KnowledgeMetricsService disponible');
    if(class_exists($class)){
        ok($class::isAllowedEvent('SUGGESTED'),'Permite SUGGESTED');
        ok($class::isAllowedEvent('OPENED'),'Permite OPENED');
        ok($class::isAllowedEvent('USED_REFERENCE'),'Permite USED_REFERENCE');
        ok(!$class::isAllowedEvent('TICKET_RESOLVED'),'No duplica resolución');
        ok(!$class::isAllowedEvent('TICKET_REOPENED'),'No duplica reapertura');
    }
    foreach(['recordSuggested','recordOpened','recordUsedReference','effectivenessSummary'] as $method){
        ok(str_contains($body,'function '.$method.'('),"Expone {$method}");
    }
    ok(str_contains($body,'solution_suggestion_events'),'Persiste eventos append-only');
    ok(str_contains($body,"event_type IN('RESOLUTION_RECORDED','RESOLVED','CLOSED','REOPENED')"),'Efectividad usa historial real del ticket');
}
ok(str_contains($suggestions,'recordSuggested'),'Motor registra sugerencias');
ok(str_contains($references,'recordUsedReference'),'Uso de referencia registra métrica');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Métricas de conocimiento y efectividad real.'.PHP_EOL;
