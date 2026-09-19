<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$required=[
    'phase10_reports_hub_regression.php',
    'phase10_knowledge_report_regression.php',
    'phase10_activity_report_regression.php',
    'phase10_ticket_sla_xlsx_regression.php',
    'phase10_provider_report_regression.php',
    'phase10_support_team_report_regression.php',
    'phase10_specialized_exports_regression.php',
    'phase10_ui_responsive_regression.php',
];

foreach($required as $file){
    ok(is_file($root.'/tests/'.$file),"Existe {$file}");
}

$readme=(string)file_get_contents($root.'/README.md');
$changelog=(string)file_get_contents($root.'/CHANGELOG.md');
$gate=(string)file_get_contents($root.'/VALIDAR_FASE10.bat');
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');
$log=(string)file_get_contents($root.'/docs/superpowers/logs/fase10-reportes-progress.md');
$config=(string)file_get_contents($root.'/config/config.php');

ok(str_contains($readme,'### Fase 10 — Reportes consolidados'),'README documenta Fase 10');
ok(str_contains($readme,'| 10 | Reportes | **Cerrada técnicamente en PC TEST** |'),'Roadmap marca Fase 10 cerrada técnicamente');
ok(str_contains($readme,'| 11 | Manual | **Cerrada técnicamente en PC TEST** |'),'Roadmap conserva Fase 11 cerrada técnicamente');
ok(str_contains($readme,'**BD: sin cambios.** Fase 10'),'README confirma BD sin cambios');

ok(str_contains($changelog,'## Fase 10 · Reportes consolidados · 2026-09-18'),'CHANGELOG registra Fase 10');
ok(str_contains($changelog,'**BD: sin cambios. Producción: sin cambios.'),'CHANGELOG preserva límite de producción');
ok((bool)preg_match("/define\\(\\s*['\"]APP_VERSION['\"]\\s*,\\s*['\"]2\\.4\\.0-dev['\"]\\s*\\)/",$config),'Versión de desarrollo permanece 2.4.0-dev');

foreach($required as $file){
    ok(str_contains($gate,'tests\\'.$file),"Gate incluye {$file}");
    ok(str_contains($ci,'tests/'.$file),"CI incluye {$file}");
}

ok(str_contains($log,'Task 7 — Exportaciones especializadas · IMPLEMENTADA'),'Log conserva Task 7');
ok(str_contains($log,'Task 8 — UI / responsive / closeout · IMPLEMENTADA EN CÓDIGO'),'Log registra Task 8 y closeout');
ok(!is_file($root.'/database/MIGRAR_FASE10_REPORTES.sql'),'Fase 10 no introduce migración de BD');
ok(!is_file($root.'/database/ACTUALIZAR_FASE10_REPORTES.sql'),'Fase 10 no introduce parche de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Closeout documental de Reportes Fase 10 preparado.'.PHP_EOL;
