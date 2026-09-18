<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');
$bat=(string)@file_get_contents($root.'/VALIDAR_FASE9.bat');
$changelog=(string)file_get_contents($root.'/CHANGELOG.md');
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$required=[
    'phase9_knowledge_schema_regression.php',
    'phase9_knowledge_revision_service_regression.php',
    'phase9_knowledge_permissions_regression.php',
    'phase9_knowledge_workflow_controller_regression.php',
    'phase9_knowledge_candidate_regression.php',
    'phase9_knowledge_reference_regression.php',
    'phase9_solution_suggestions_regression.php',
    'phase9_self_service_regression.php',
    'phase9_knowledge_history_regression.php',
    'phase9_knowledge_metrics_regression.php',
    'phase9_knowledge_ui_regression.php',
    'button_semantics_regression.php',
    'phase9_knowledge_legacy_read_regression.php',
];

ok($bat!=='','Existe VALIDAR_FASE9.bat');
ok(str_contains($bat,'HELPDESK CARROUSEL - GATE FINAL FASE 9'),'BAT identifica Fase 9');
ok(str_contains($bat,'Rama esperada: main'),'BAT identifica main como rama esperada');

foreach($required as $test){
    ok(str_contains($ci,$test),"CI incluye {$test}");
    ok(str_contains($bat,$test),"BAT incluye {$test}");
}

foreach(['static_checks.php','project_quality.php','xlsx_smoke.php'] as $baseline){
    ok(str_contains($bat,$baseline),"BAT conserva {$baseline}");
}

ok(str_contains($bat,'git diff --check'),'BAT ejecuta git diff --check');
ok(str_contains($bat,'git status --short'),'BAT muestra estado Git');
ok(str_contains($ci,'= "43"'),'CI espera 43 tablas canónicas');
ok(str_contains($changelog,'Fase 9 · Conocimiento versionado'),'CHANGELOG documenta Fase 9');
ok(str_contains(strtolower($changelog),'producción'),'CHANGELOG deja explícito estado de producción');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Gate documental y CI de Fase 9 preparados.'.PHP_EOL;
