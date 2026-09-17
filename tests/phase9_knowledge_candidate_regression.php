<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/KnowledgeCandidateService.php';
$view=(string)file_get_contents($root.'/app/Views/tickets/show.php');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$body=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($body!=='','Existe KnowledgeCandidateService');

if($body!==''){
    require_once $servicePath;
    $class='App\\Services\\KnowledgeCandidateService';
    ok(class_exists($class),'Clase KnowledgeCandidateService disponible');
    ok(method_exists($class,'evaluate'),'Expone evaluate');

    if(class_exists($class)){
        $open=['status'=>'IN_PROGRESS'];
        $resolved=['status'=>'RESOLVED'];
        $closed=['status'=>'CLOSED'];
        $good=[
            'solution_applied'=>'Se corrigió la configuración del servicio y se validó el funcionamiento.',
            'root_cause'=>'La configuración del puerto estaba incorrecta.',
            'preventive_action'=>'Documentar el puerto correcto para futuras instalaciones.',
        ];

        ok(!$class::evaluate($open,$good,[])['eligible'],'Ticket abierto no es candidato');
        ok(!$class::evaluate($resolved,['solution_applied'=>''],['ROOT_CAUSE_DOCUMENTED'])['eligible'],'Sin solución documentada no es candidato');
        ok(!$class::evaluate($resolved,['solution_applied'=>'Listo','root_cause'=>''],['KNOWN_PROBLEM'])['eligible'],'Texto trivial no cuenta como documentación');
        ok($class::evaluate($resolved,$good,['KNOWN_PROBLEM'])['eligible'],'Resuelto + documentado + señal sí es candidato');
        ok($class::evaluate($closed,$good,[])['eligible'],'Causa raíz documentada genera señal reutilizable');
        $result=$class::evaluate($resolved,$good,['REOPENED','REOPENED','KNOWN_PROBLEM']);
        ok(in_array('ROOT_CAUSE_DOCUMENTED',$result['reasons'],true),'Detecta causa raíz documentada');
        ok(in_array('REOPENED',$result['reasons'],true),'Conserva señal de reapertura');
        ok(count($result['reasons'])===count(array_unique($result['reasons'])),'No duplica señales');
        ok($result['documentation_ok']===true,'Expone documentation_ok');
    }
}

ok(str_contains($view,'Este caso puede convertirse en conocimiento reutilizable.'),'Ticket muestra sugerencia cuando corresponde');
ok(str_contains($view,'/knowledge/new?ticket_id='),'CTA crea borrador desde ticket');
ok(!str_contains($view,'Publicar automáticamente'),'CTA nunca promete publicación automática');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Elegibilidad de tickets para conocimiento.'.PHP_EOL;
