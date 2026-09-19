<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ciPath=$root.'/.github/workflows/helpdesk-ci.yml';
$readmePath=$root.'/README.md';
$changelogPath=$root.'/CHANGELOG.md';
$manualPath=$root.'/app/Views/help/manual.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$ciBody=is_file($ciPath)?(string)file_get_contents($ciPath):'';
$readmeBody=is_file($readmePath)?(string)file_get_contents($readmePath):'';
$changelogBody=is_file($changelogPath)?(string)file_get_contents($changelogPath):'';
$manualBody=is_file($manualPath)?(string)file_get_contents($manualPath):'';

ok($ciBody!=='','Existe workflow de CI');
ok($readmeBody!=='','Existe README');
ok($changelogBody!=='','Existe CHANGELOG');
ok($manualBody!=='','Existe Manual integrado');

ok(str_contains($ciBody,'Phase 8 provider rating'),'CI declara Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_cycle_identity_regression.php'),'CI ejecuta identidad de ciclos Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_service_regression.php'),'CI ejecuta servicio Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_controller_regression.php'),'CI ejecuta controller Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_ui_regression.php'),'CI ejecuta UI Fase 8');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_report_regression.php'),'CI ejecuta reportes Fase 8');
ok(str_contains($ciBody,'php tests/phase8_external_case_history_regression.php'),'CI ejecuta historial propio del proveedor');
ok(str_contains($ciBody,'php tests/phase8_external_case_export_regression.php'),'CI ejecuta Excel propio del proveedor');
ok(str_contains($ciBody,'php tests/phase8_provider_rating_closeout_regression.php'),'CI ejecuta cierre Fase 8');

ok(str_contains($readmeBody,'| 8 | Calidad IT → proveedor | **Cerrada técnicamente en PC TEST** |'),'README cierra técnicamente Fase 8');
ok(str_contains($readmeBody,'| 9 | Conocimiento | **Cerrada técnicamente en PC TEST** |'),'README conserva Fase 9 cerrada técnicamente');
ok(str_contains($readmeBody,'Calidad IT → proveedor'),'README documenta alcance de Fase 8');
ok(str_contains($readmeBody,'BD: sin cambios'),'README confirma cero cambios de BD');
ok(str_contains($readmeBody,'PROVIDER_RATED')&&str_contains($readmeBody,'PROVIDER_RATING_CORRECTED'),'README documenta eventos inmutables');

ok(str_contains($changelogBody,'Fase 8'),'CHANGELOG registra Fase 8');
ok(str_contains($changelogBody,'PROVIDER_RATED'),'CHANGELOG documenta primera valoración');
ok(str_contains($changelogBody,'PROVIDER_RATING_CORRECTED'),'CHANGELOG documenta correcciones');
ok(str_contains($changelogBody,'ticket_feedback.nps_score'),'CHANGELOG separa valoración de NPS');
ok(str_contains($changelogBody,'BD')&&str_contains($changelogBody,'sin cambios'),'CHANGELOG documenta cero cambios de BD');

ok(str_contains($manualBody,'Calidad del proveedor'),'Manual documenta calidad del proveedor');
ok(str_contains($manualBody,'Muy deficiente'),'Manual explica escala 1 estrella');
ok(str_contains($manualBody,'Excelente'),'Manual explica escala 5 estrellas');
ok(str_contains($manualBody,'Comentario obligatorio'),'Manual explica comentario obligatorio');
ok(str_contains($manualBody,'Sin evaluar'),'Manual explica Sin evaluar');
ok(str_contains($manualBody,'corrección')||str_contains($manualBody,'Corrección'),'Manual explica correcciones');
ok(str_contains($manualBody,'no es visible para el proveedor')||str_contains($manualBody,'no la ve el proveedor'),'Manual explica privacidad frente al proveedor');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Cierre documental y CI de Fase 8.'.PHP_EOL;
