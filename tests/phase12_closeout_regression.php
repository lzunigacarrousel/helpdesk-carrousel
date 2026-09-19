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
$changelog=(string)file_get_contents($root.'/CHANGELOG.md');
$config=(string)file_get_contents($root.'/config/config.php');
$acceptance=(string)file_get_contents($root.'/docs/superpowers/checklists/fase12-aceptacion-operativa.md');
$visualChecklist=(string)file_get_contents($root.'/docs/superpowers/checklists/fase12-visual-responsive-validacion-manual.md');
$mailChecklist=(string)file_get_contents($root.'/docs/superpowers/checklists/fase12-comunicacion-validacion-manual.md');
$log=(string)file_get_contents($root.'/docs/superpowers/logs/fase12-validacion-integral-progress.md');
$gate=(string)file_get_contents($root.'/VALIDAR_FASE12.bat');
$closeoutGate=(string)file_get_contents($root.'/VALIDAR_FASE12_CLOSEOUT.bat');
$ci=(string)file_get_contents($root.'/.github/workflows/helpdesk-ci.yml');

ok((bool)preg_match('/define\(\s*[\'"]APP_VERSION[\'"]\s*,\s*[\'"]2\.4\.0-dev[\'"]\s*\)/',$config),'Versión permanece 2.4.0-dev');
ok(str_contains($readme,'| 12 | Validación integral | **CIERRE TÉCNICO EN VALIDACIÓN FINAL — producción bloqueada** |'),'Roadmap marca cierre técnico en validación final');

foreach([5,6,7,8,9,10,11] as $phase){
    ok((bool)preg_match('/\| '.$phase.' \| .*\| \*\*Cerrada técnicamente en PC TEST\*\* \|/',$readme),'Fase '.$phase.' cerrada técnicamente en roadmap');
}

foreach([
    'http://localhost/HelpdeskCarrousel/public/',
    'http://94.74.71.96/HelpdeskCarrousel/public/',
    'https://portal.carrousel-apps.com/HelpdeskCarrousel/public/',
] as $url){
    ok(str_contains($readme,$url),'README conserva destino '.$url);
}

ok(str_contains($changelog,'## Fase 12 · Validación integral y readiness · 2026-09-19'),'CHANGELOG registra Fase 12');
ok(str_contains($changelog,'**Pendientes preproducción:** matriz visual manual y SMTP real/Gmail/Outlook'),'CHANGELOG conserva pendientes manuales');
ok(str_contains($changelog,'**Producción: sin cambios. El cierre técnico no autoriza despliegue.**'),'CHANGELOG bloquea producción');
ok(str_contains($changelog,'no se crea tag ni release estable'),'CHANGELOG conserva versión de desarrollo');

foreach([
    'VALIDAR_FASE12.bat',
    'VALIDAR_FASE12_BD.bat',
    'VALIDAR_FASE12_SEGURIDAD.bat',
    'VALIDAR_FASE12_E2E.bat',
    'VALIDAR_FASE12_COMUNICACION.bat',
    'VALIDAR_FASE12_VISUAL.bat',
    'VALIDAR_FASE12_ESTABILIDAD.bat',
] as $gateName){
    ok(str_contains($acceptance,'[x] `'.$gateName.'` GREEN'),'Checklist registra GREEN: '.$gateName);
}

ok(str_contains($acceptance,'- [ ] Matriz `fase12-visual-responsive-validacion-manual.md` completada.'),'Matriz visual manual permanece pendiente');
ok(str_contains($acceptance,'- [ ] SMTP real probado desde `/admin/correo`.'),'SMTP real permanece pendiente');
ok(str_contains($acceptance,'- [ ] Gmail revisado.'),'Gmail permanece pendiente');
ok(str_contains($visualChecklist,'1920 × 1080'),'Checklist visual conserva 1920');
ok(str_contains($visualChecklist,'1024 × 768'),'Checklist visual conserva iPad horizontal');
ok(str_contains($visualChecklist,'768 × 1024'),'Checklist visual conserva iPad vertical');
ok(str_contains($mailChecklist,'### Gmail'),'Checklist comunicación conserva Gmail');
ok(str_contains($mailChecklist,'### Outlook'),'Checklist comunicación conserva Outlook');

foreach([
    'Task 1 — Preflight y baseline · CERRADA',
    'Task 2 — Integridad de BD · CERRADA',
    'Task 3 — Seguridad, perfiles, permisos y scopes · CERRADA',
    'Task 4 — Flujos E2E · CERRADA',
    'Task 5 — Correo y notificaciones · AUTOMATIZACIÓN CERRADA',
    'Task 6 — Visual / responsive integral · AUTOMATIZACIÓN CERRADA',
    'Task 7 — Estabilidad operativa y aceptación · AUTOMATIZACIÓN CERRADA',
] as $marker){
    ok(str_contains($log,$marker),'Log conserva '.$marker);
}

ok(str_contains($gate,'Resultado apto para closeout tecnico en PC TEST.'),'Gate transversal declara readiness técnico');
ok(str_contains($gate,'Produccion NO queda autorizada.'),'Gate transversal bloquea producción');

ok(str_contains($closeoutGate,'VALIDAR_FASE12.bat'),'Closeout ejecuta gate transversal');
ok(str_contains($closeoutGate,'phase12_closeout_regression.php'),'Closeout ejecuta regresión final');
ok(str_contains($closeoutGate,'git branch --show-current'),'Closeout exige rama main');
ok(str_contains($closeoutGate,'git status --porcelain'),'Closeout exige working tree limpio');
ok(str_contains($closeoutGate,'Produccion NO autorizada'),'Closeout no despliega producción');

ok(str_contains($ci,'tests/phase12_closeout_regression.php'),'CI incluye closeout Fase 12');
ok(!is_file($root.'/database/MIGRAR_FASE12.sql'),'Fase 12 no introduce migración monolítica');
ok(!is_file($root.'/database/ACTUALIZAR_FASE12.sql'),'Fase 12 no introduce parche monolítico');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Closeout técnico Fase 12 preparado; producción sigue bloqueada.'.PHP_EOL;
