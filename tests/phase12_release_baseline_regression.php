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

$readme=(string)file_get_contents($root.'/README.md');
$config=(string)file_get_contents($root.'/config/config.php');
$installer=(string)file_get_contents($root.'/INSTALAR_PC_TEST.bat');
$gate=(string)file_get_contents($root.'/VALIDAR_FASE12.bat');
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');

foreach([5,6,7,8,9,10,11] as $phase){
    ok((bool)preg_match('/\\| '.$phase.' \\| .*\\| \\*\\*Cerrada técnicamente en PC TEST\\*\\* \\|/', $readme),'Roadmap conserva Fase '.$phase.' cerrada técnicamente');
}
ok(str_contains($readme,'| 12 | Validación integral | **CIERRE TÉCNICO EN VALIDACIÓN FINAL — producción bloqueada** |'),'Roadmap marca Fase 12 en validación final');
ok((bool)preg_match("/define\\(\\s*['\"]APP_VERSION['\"]\\s*,\\s*['\"]2\\.4\\.0-dev['\"]\\s*\\)/",$config),'Versión permanece 2.4.0-dev durante validación');

foreach([
    'VALIDAR_FASE8.bat',
    'VALIDAR_FASE9.bat',
    'VALIDAR_FASE10.bat',
    'VALIDAR_FASE11.bat',
    'database/INSTALAR.sql',
    'database/VERIFICAR_INSTALACION.sql',
    'database/VERIFICAR_ESTABILIDAD_V2.sql',
    'database/VERIFICAR_FASE5_ACTIVIDADES_20260913.sql',
    'database/VERIFICAR_FASE9_CONOCIMIENTO_20260916.sql',
    'docs/superpowers/specs/2026-09-19-fase12-validacion-integral-design.md',
    'docs/superpowers/plans/2026-09-19-fase12-validacion-integral-implementation.md',
    'docs/superpowers/logs/fase12-validacion-integral-progress.md',
] as $path){
    ok(is_file($root.'/'.$path),'Existe '.$path);
}

foreach([
    'phase4_feedback_regression.php',
    'phase5_activities_service_regression.php',
    'phase6_agenda_service_regression.php',
    'phase7_provider_participation_regression.php',
    'phase8_provider_rating_closeout_regression.php',
    'phase9_closeout_regression.php',
    'phase10_closeout_regression.php',
    'phase11_closeout_regression.php',
    'dark_theme_smoke.php',
    'dark_theme_public_smoke.php',
    'project_quality.php',
    'xlsx_smoke.php',
] as $file){
    ok(is_file($root.'/tests/'.$file),'Existe regresión transversal '.$file);
}

ok(str_contains($installer,'PROTECTED_DB=helpdesk_carrousel'),'Instalador protege base histórica');
ok(str_contains($installer,'DB_NAME=carrousel_helpdesk'),'Instalador apunta a base V2');
ok(str_contains($readme,'Producción')||str_contains($readme,'producción'),'README conserva referencias de producción controlada');
ok(str_contains($gate,'phase12_release_baseline_regression.php'),'Gate Fase 12 incluye preflight');
ok(str_contains($gate,'phase5_activities_service_regression.php'),'Gate Fase 12 cubre Actividades');
ok(str_contains($gate,'phase6_agenda_service_regression.php'),'Gate Fase 12 cubre Agenda');
ok(str_contains($gate,'phase7_provider_participation_regression.php'),'Gate Fase 12 cubre Proveedores');
ok(str_contains($gate,'phase9_closeout_regression.php'),'Gate Fase 12 cubre Conocimiento');
ok(str_contains($gate,'phase10_closeout_regression.php'),'Gate Fase 12 cubre Reportes');
ok(str_contains($gate,'phase11_closeout_regression.php'),'Gate Fase 12 cubre Manual');
ok(str_contains($gate,'dark_theme_smoke.php'),'Gate Fase 12 cubre modo oscuro');
ok(str_contains($gate,'Produccion NO queda autorizada.'),'Gate no autoriza producción');
ok(str_contains($ci,'tests/phase12_release_baseline_regression.php'),'CI incluye preflight Fase 12');

ok(!is_file($root.'/database/MIGRAR_FASE12.sql'),'Task 1 no introduce migración de Fase 12');
ok(!is_file($root.'/database/ACTUALIZAR_FASE12.sql'),'Task 1 no introduce parche de Fase 12');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Preflight y baseline de liberación Fase 12 consolidados.'.PHP_EOL;
