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
    'phase11_manual_profile_navigation_regression.php',
    'phase11_manual_profile_content_regression.php',
    'phase11_contextual_help_regression.php',
    'phase11_tutorial_depth_regression.php',
    'phase11_manual_accessibility_regression.php',
];

foreach($required as $file){
    ok(is_file($root.'/tests/'.$file),"Existe {$file}");
}

$readme=(string)file_get_contents($root.'/README.md');
$changelog=(string)file_get_contents($root.'/CHANGELOG.md');
$gate=(string)file_get_contents($root.'/VALIDAR_FASE11.bat');
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');
$log=(string)file_get_contents($root.'/docs/superpowers/logs/fase11-manual-progress.md');
$config=(string)file_get_contents($root.'/config/config.php');

ok(str_contains($readme,'### Fase 11 — Manual consolidado e interactivo'),'README documenta Fase 11');
ok(str_contains($readme,'| 11 | Manual | **Implementada — pendiente validación integral Fase 12** |'),'Roadmap marca Fase 11 implementada');
ok(str_contains($readme,'| 12 | Validación integral | **EN CURSO — cierre transversal y readiness** |'),'Roadmap reconoce Fase 12 en curso');
ok(str_contains($readme,'**BD: sin cambios.** Fase 11'),'README confirma BD sin cambios');

ok(str_contains($changelog,'## Fase 11 · Manual consolidado e interactivo · 2026-09-19'),'CHANGELOG registra Fase 11');
ok(str_contains($changelog,'**BD: sin cambios. Producción: sin cambios.'),'CHANGELOG preserva límite de producción');
ok((bool)preg_match("/define\\(\\s*['\"]APP_VERSION['\"]\\s*,\\s*['\"]2\\.4\\.0-dev['\"]\\s*\\)/",$config),'Versión de desarrollo permanece 2.4.0-dev');

foreach($required as $file){
    ok(str_contains($gate,'tests\\'.$file),"Gate incluye {$file}");
    ok(str_contains($ci,'tests/'.$file),"CI incluye {$file}");
}

ok(str_contains($gate,'tests\\phase11_closeout_regression.php'),'Gate incluye closeout final');
ok(str_contains($ci,'tests/phase11_closeout_regression.php'),'CI incluye closeout final');
ok(str_contains($gate,'Gate final de Fase 11 Manual.'),'Gate declara cierre final de Fase 11');
ok(str_contains($gate,'Siguiente fase funcional: Fase 12 Validacion integral.'),'Gate declara Fase 12 como siguiente');

ok(str_contains($log,'Task 1 — Perfil + navegación por tareas · IMPLEMENTADA'),'Log conserva Task 1');
ok(str_contains($log,'Task 2 — Contenido final por perfil · IMPLEMENTADA'),'Log conserva Task 2');
ok(str_contains($log,'Task 3 — FAQ contextual + accesos directos · IMPLEMENTADA'),'Log conserva Task 3');
ok(str_contains($log,'Task 4 — Tutoriales flotantes completos · IMPLEMENTADA'),'Log conserva Task 4');
ok(str_contains($log,'Task 5 — UI / responsive / accesibilidad · IMPLEMENTADA'),'Log conserva Task 5');
ok(str_contains($log,'Task 6 — Closeout final · IMPLEMENTADA EN CÓDIGO'),'Log registra Task 6 y closeout');

ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Fase 11 no introduce migración de BD');
ok(!is_file($root.'/database/ACTUALIZAR_FASE11_MANUAL.sql'),'Fase 11 no introduce parche de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Closeout documental del Manual Fase 11 preparado.'.PHP_EOL;
