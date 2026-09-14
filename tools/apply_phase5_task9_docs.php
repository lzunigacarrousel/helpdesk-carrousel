<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$php=PHP_BINARY;

function fail(string $message): never {
    fwrite(STDERR,"[ERROR] {$message}".PHP_EOL);
    exit(1);
}
function ok(string $message): void {
    echo "[OK] {$message}".PHP_EOL;
}
function readFileStrict(string $path): string {
    $value=@file_get_contents($path);
    if(!is_string($value)) fail("No se pudo leer {$path}");
    return $value;
}
function writeFileStrict(string $path,string $content): void {
    if(@file_put_contents($path,$content)===false) fail("No se pudo escribir {$path}");
}
function replaceOnce(string $content,string $search,string $replace,string $label): string {
    $count=substr_count($content,$search);
    if($count===0) fail("No se encontro el ancla: {$label}");
    if($count>1) fail("El ancla no es unica: {$label} ({$count} coincidencias)");
    return str_replace($search,$replace,$content);
}
function runPhpTest(string $php,string $testPath): int {
    $command=escapeshellarg($php).' '.escapeshellarg($testPath);
    passthru($command,$exit);
    return (int)$exit;
}

$testPath=$root.'/tests/phase5_activities_ui_regression.php';
$manualPath=$root.'/app/Views/help/manual.php';
$readmePath=$root.'/README.md';
$changelogPath=$root.'/CHANGELOG.md';
$roadmapPath=$root.'/docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md';

$test=readFileStrict($testPath);
$manual=readFileStrict($manualPath);
$readme=readFileStrict($readmePath);
$changelog=readFileStrict($changelogPath);
$roadmap=readFileStrict($roadmapPath);

// -----------------------------------------------------------------------------
// STEP 1 — RED documental
// -----------------------------------------------------------------------------
if(!str_contains($test,"$manual=body($root.'/app/Views/help/manual.php');")){
    $anchor="$activityJs=body($root.'/public/assets/js/ticket-activities.js');";
    $replacement=$anchor."\n".
        "$manual=body($root.'/app/Views/help/manual.php');\n".
        "$readme=body($root.'/README.md');\n".
        "$changelog=body($root.'/CHANGELOG.md');\n".
        "$roadmap=body($root.'/docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md');";
    $test=replaceOnce($test,$anchor,$replacement,'carga de archivos documentales en test');
}

$docChecks=<<<'PHP'

// Task 9: documentación funcional y roadmap de Fase 5.
ok(str_contains($manual,'Actividades y visitas'),'Manual documenta Actividades y visitas');
ok(str_contains($manual,'Programar'),'Manual explica Programar actividades');
ok(str_contains($manual,'Reprogramar'),'Manual explica Reprogramar actividades');
ok(str_contains($manual,'Finalizar'),'Manual explica Finalizar actividades');
ok(str_contains($manual,'Finalizar una actividad no resuelve ni cierra el ticket'),'Manual aclara independencia entre actividad y estado del ticket');
ok(str_contains($manual,'Próxima atención')||str_contains($manual,'Proxima atención'),'Manual explica Próxima atención al solicitante');
ok(str_contains($readme,'Implementada — pendiente validación integral Fase 12'),'README marca Fase 5 implementada');
ok(str_contains($readme,'Fase 6 | Agenda | **Siguiente fase**'),'README marca Agenda como siguiente fase');
ok(str_contains($changelog,'Fase 5 · Actividades / visitas'),'CHANGELOG registra cierre funcional de Fase 5');
ok(str_contains($roadmap,'Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN INTEGRAL FASE 12**.'),'Roadmap maestro marca Fase 5 implementada');
PHP;

if(!str_contains($test,'// Task 9: documentación funcional y roadmap de Fase 5.')){
    $test=replaceOnce($test,"\nif($fails){",$docChecks."\n\nif($fails){",'checks Task 9 antes del cierre del test');
}
writeFileStrict($testPath,$test);
ok('Gate documental Task 9 agregado al test.');

echo PHP_EOL."=== RED esperado: la documentación todavía no fue actualizada ===".PHP_EOL;
$redExit=runPhpTest($php,$testPath);
if($redExit===0){
    fail('El gate documental no entro en RED; revisa si Task 9 ya estaba aplicada.');
}
ok('RED confirmado: el test detecta documentación pendiente.');

// -----------------------------------------------------------------------------
// STEP 2 — Manual por perfil
// -----------------------------------------------------------------------------
$manual=readFileStrict($manualPath);

$oldNav='    <a href="#inicio">Inicio</a><a href="#notificaciones">Notificaciones</a><a href="#solicitudes">Solicitudes</a><?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?><?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?><?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?><?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?><a href="#preguntas">Preguntas frecuentes</a>';
$newNav='    <a href="#inicio">Inicio</a><a href="#notificaciones">Notificaciones</a><a href="#solicitudes">Solicitudes</a><?php if(!$isExternal): ?><a href="#actividades">Actividades</a><?php endif; ?><?php if($isSupport): ?><a href="#soporte">Soporte</a><?php endif; ?><?php if($canProblems||$canKnowledge): ?><a href="#conocimiento">Conocimiento</a><?php endif; ?><?php if($canManagement): ?><a href="#gestion">Gestión</a><?php endif; ?><?php if($canAdmin): ?><a href="#administracion">Administración</a><?php endif; ?><a href="#preguntas">Preguntas frecuentes</a>';
if(!str_contains($manual,'href="#actividades">Actividades</a>')){
    $manual=replaceOnce($manual,$oldNav,$newNav,'indice del Manual');
}

$solicitudesEnd=<<<'PHP'
  </section>

  <?php if($isSupport): ?><section class="manual-section" id="soporte"
PHP;
$activitiesSection=<<<'PHP'
  </section>

  <?php if($isSupport): ?>
  <section class="manual-section" id="actividades" data-manual-section>
    <div class="manual-section-head"><span>A</span><div><h2>Actividades y visitas</h2><p>Programa y documenta trabajo operativo ligado al caso sin confundir la actividad con el estado del ticket.</p></div></div>
    <div class="manual-cards">
      <article><strong>Programar</strong><p>Dentro del caso usa <b>+ Programar actividad</b>. Elige visita en sitio, soporte remoto, seguimiento, intervención de proveedor u otra atención; define responsable, fecha de inicio, fin estimado y objetivo.</p></article>
      <article><strong>Responsable y participantes</strong><p>La actividad tiene un responsable principal y puede incluir participantes internos. Una intervención de proveedor usa únicamente colaboradores con acceso vigente al caso.</p></article>
      <article><strong>Reprogramar</strong><p>Si cambia la fecha, usa <b>Reprogramar</b> y registra el motivo. La actividad se conserva y el cambio queda trazado; no crees otra actividad para ocultar una reprogramación.</p></article>
      <article><strong>Iniciar y Finalizar</strong><p>Usa <b>Iniciar</b> cuando comience el trabajo y <b>Finalizar</b> para registrar resultado, trabajo realizado y pendientes. Finalizar una actividad no resuelve ni cierra el ticket: el caso continúa con su propio flujo.</p></article>
      <article><strong>Cancelar</strong><p>Cancela únicamente cuando la atención ya no corresponda y deja un motivo claro. La cancelación queda registrada para trazabilidad.</p></article>
      <article><strong>Información para el solicitante</strong><p>Activa la visibilidad solo cuando quieras publicar una actualización. Escribe un resumen claro para el usuario; preparación, responsable interno, proveedor, trabajo técnico y otros detalles privados permanecen dentro de soporte.</p></article>
    </div>
  </section>
  <?php elseif(!$isExternal): ?>
  <section class="manual-section" id="actividades" data-manual-section>
    <div class="manual-section-head"><span>A</span><div><h2>Actividades y visitas</h2><p>Cuando soporte publique una atención programada para tu solicitud, aparecerá dentro del caso como <b>Próxima atención</b>.</p></div></div>
    <div class="manual-cards">
      <article><strong>Próxima atención</strong><p>Puede indicar el tipo de atención, estado, fecha programada, fin estimado, ubicación y el resumen que soporte preparó para ti.</p></article>
      <article><strong>Información segura</strong><p>Solo verás la información que el equipo de soporte decidió publicar. La preparación interna, responsables técnicos y detalles privados de trabajo no se muestran en tu solicitud.</p></article>
      <article><strong>Actividad y solicitud son diferentes</strong><p>Una visita o seguimiento puede finalizar y tu solicitud continuar abierta mientras el equipo completa la solución, validaciones o pasos pendientes.</p></article>
    </div>
  </section>
  <?php endif; ?>

  <?php if($isSupport): ?><section class="manual-section" id="soporte"
PHP;
if(!str_contains($manual,'<h2>Actividades y visitas</h2>')){
    $manual=replaceOnce($manual,$solicitudesEnd,$activitiesSection,'seccion Actividades y visitas del Manual');
}
writeFileStrict($manualPath,$manual);
ok('Manual actualizado por perfil con Actividades y Próxima atención.');

// -----------------------------------------------------------------------------
// STEP 3 — README / roadmap visible
// -----------------------------------------------------------------------------
$readme=readFileStrict($readmePath);
if(!str_contains($readme,'MIGRAR_FASE5_ACTIVIDADES_20260913.sql')){
    $readme=replaceOnce(
        $readme,
        "- `MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`",
        "- `MIGRAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`\n- `MIGRAR_FASE5_ACTIVIDADES_20260913.sql`",
        'migración Fase 5 en README'
    );
}
if(!str_contains($readme,'VERIFICAR_FASE5_ACTIVIDADES_20260913.sql')){
    $readme=replaceOnce(
        $readme,
        "- `VERIFICAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`",
        "- `VERIFICAR_EXTERNAL_REPORT_TEMPLATES_20260912.sql`\n- `VERIFICAR_FASE5_ACTIVIDADES_20260913.sql`",
        'verificador Fase 5 en README'
    );
}
if(!str_contains($readme,'actividades operativas ligadas a tickets')){
    $readme=replaceOnce(
        $readme,
        "- documentación estructurada de trabajo externo por tipo de servicio;\n- manual y ayuda integrada.",
        "- documentación estructurada de trabajo externo por tipo de servicio;\n- actividades operativas ligadas a tickets: visitas, soporte remoto, seguimientos e intervenciones de proveedor;\n- manual y ayuda integrada.",
        'capacidad de actividades en README'
    );
}
$readme=str_replace(
    '| 5 | Actividades / visitas | **Actual — en diseño** |',
    '| 5 | Actividades / visitas | **Implementada — pendiente validación integral Fase 12** |',
    $readme
);
$readme=str_replace(
    '| 6 | Agenda | Pendiente; depende de Fase 5 |',
    '| 6 | Agenda | **Siguiente fase**; depende de `ticket_activities` |',
    $readme
);
$oldPhase5=<<<'MD'
### Fase 5 — Actividades / visitas

La Fase 5 tiene hard gate de base de datos. No debe modificarse el esquema hasta aprobar el diseño completo de la entidad de actividades.

Acuerdos de diseño ya aprobados:

- una actividad pertenece a un ticket;
- un ticket puede tener varias actividades simultáneas;
- tipos iniciales: visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra;
- estados iniciales: programada, en curso, finalizada y cancelada;
- una reprogramación mantiene la actividad y deja historial de fecha anterior, nueva fecha, motivo y actor;
- no se crearán tablas distintas por tipo de actividad;
- `ticket_events` seguirá siendo bitácora/auditoría, no sustituto de la entidad operativa.
MD;
$newPhase5=<<<'MD'
### Fase 5 — Actividades / visitas

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 5 incorpora una entidad operativa reutilizable para trabajo ligado obligatoriamente a tickets:

- `ticket_activities` mantiene el estado actual de visitas, soporte remoto, seguimientos, intervenciones de proveedor y otras atenciones;
- `ticket_activity_participants` registra participantes y `ticket_attachments.activity_id` permite asociar evidencia sin crear almacenamiento paralelo;
- estados: programada, en curso, finalizada y cancelada;
- resultados: resuelta, parcial, sin resolver y requiere seguimiento;
- una reprogramación conserva la actividad y registra fecha anterior, nueva fecha, motivo y actor;
- las operaciones respetan permisos, scope, CSRF, auditoría y trazabilidad mediante `ticket_events`;
- crear, reprogramar, iniciar, finalizar o cancelar una actividad **no cambia automáticamente el estado del ticket**;
- el solicitante solo recibe el resumen publicado explícitamente por soporte mediante **Próxima atención**;
- Fase 6 reutilizará `ticket_activities` para Agenda y no debe crear otra entidad de calendario.
MD;
if(str_contains($readme,$oldPhase5)){
    $readme=replaceOnce($readme,$oldPhase5,$newPhase5,'bloque Fase 5 del README');
}elseif(!str_contains($readme,'Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.')){
    fail('No se pudo reconocer el bloque Fase 5 del README.');
}
writeFileStrict($readmePath,$readme);
ok('README actualizado: Fase 5 implementada y Fase 6 siguiente.');

// -----------------------------------------------------------------------------
// STEP 3b — roadmap maestro
// -----------------------------------------------------------------------------
$roadmap=readFileStrict($roadmapPath);
$oldRoadmapPhase5=<<<'MD'
## Fase 5 — Actividades / visitas

Estado: **PENDIENTE / HARD GATE DE BD**.

- [ ] Evaluar formalmente si `ticket_events` resuelve el caso.
- [ ] Si no, presentar diseño completo de `ticket_activities` antes de modificar BD.
- [ ] Cubrir programación, técnico, parque, inicio/fin estimados, motivo, reprogramación, inicio, finalización y resultado.
- [ ] Reutilizar comentarios, adjuntos, eventos, auditoría, notificaciones, resolución y motivos de espera.
- [ ] No crear tablas separadas para visitas/remoto/proveedor/follow-up.
MD;
$newRoadmapPhase5=<<<'MD'
## Fase 5 — Actividades / visitas

Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN INTEGRAL FASE 12**.

- [x] Evaluar formalmente `ticket_events`: se conserva como historial inmutable y no sustituye el estado operativo consultable.
- [x] Diseñar e implementar `ticket_activities` y `ticket_activity_participants` con migración incremental, esquema canónico y verificadores.
- [x] Cubrir programación, responsable, participantes, parque, inicio/fin estimados, objetivo, reprogramación, inicio, finalización, cancelación y resultado.
- [x] Reutilizar `ticket_attachments` mediante `activity_id` opcional y conservar `ticket_events`, auditoría y notificaciones como trazabilidad.
- [x] Implementar permisos `activities.view/create/manage/cancel`, validación de scope y CSRF.
- [x] Implementar UI interna con visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra atención.
- [x] Mantener independencia entre actividad y ticket: ninguna transición de actividad cambia automáticamente el estado del caso.
- [x] Implementar resumen seguro **Próxima atención** para solicitante, limitado a información publicada por soporte.
- [x] No crear tablas separadas por tipo de actividad.
- [ ] Validación responsive acumulada y cierre transversal se consolidan en Fase 12.
MD;
if(str_contains($roadmap,$oldRoadmapPhase5)){
    $roadmap=replaceOnce($roadmap,$oldRoadmapPhase5,$newRoadmapPhase5,'Fase 5 del roadmap maestro');
}elseif(!str_contains($roadmap,'Estado: **IMPLEMENTADA / PENDIENTE VALIDACIÓN INTEGRAL FASE 12**.')){
    fail('No se pudo reconocer el bloque Fase 5 del roadmap maestro.');
}
$roadmap=str_replace(
    '## Fase 6 — Agenda\n\nEstado: **PENDIENTE Y DEPENDE DE FASE 5**.',
    '## Fase 6 — Agenda\n\nEstado: **SIGUIENTE FASE / PENDIENTE; REUTILIZA `ticket_activities`**.',
    $roadmap
);
writeFileStrict($roadmapPath,$roadmap);
ok('Roadmap maestro actualizado: Fase 5 cumplida y Fase 6 pendiente.');

// -----------------------------------------------------------------------------
// STEP 4 — CHANGELOG sin tocar la nota histórica de v2.4.0-dev
// -----------------------------------------------------------------------------
$changelog=readFileStrict($changelogPath);
if(!str_contains($changelog,'## Fase 5 · Actividades / visitas · 2026-09-14')){
    $anchor='## v2.4.0-dev · ARCHIVADO / NO INTEGRADO EN MAIN · Pulido operativo + comunicación simple · 2026-09-09';
    $entry=<<<'MD'
## Fase 5 · Actividades / visitas · 2026-09-14
- Nueva entidad operativa `ticket_activities` ligada obligatoriamente a tickets, con tipos visita en sitio, soporte remoto, seguimiento, intervención de proveedor y otra atención.
- Nueva tabla `ticket_activity_participants` para participantes; `ticket_attachments.activity_id` permite asociar evidencia reutilizando el almacenamiento existente.
- Permisos `activities.view`, `activities.create`, `activities.manage` y `activities.cancel` integrados a los perfiles operativos y de consulta correspondientes.
- `TicketActivityService` centraliza reglas, scope y transiciones; `TicketActivityController` expone operaciones POST con CSRF para programar, reprogramar, iniciar, finalizar, cancelar y gestionar participantes.
- Reprogramaciones, inicio, finalización y cancelación conservan trazabilidad mediante `ticket_events` y auditoría; no se crean tablas paralelas de historial.
- Crear, reprogramar, iniciar, finalizar o cancelar una actividad no cambia automáticamente el estado ni cierra el ticket.
- Workspace de soporte incorpora Actividades del caso, próximas/activas, historial, formulario progresivo y acciones según estado.
- Solicitante incorpora **Próxima atención** y recibe únicamente tipo/estado/fechas/ubicación y `requester_summary` cuando soporte publica la actividad; no se exponen responsable, proveedor ni detalle técnico interno.
- Manual, README y roadmap documentan el flujo de actividades y dejan Fase 6 — Agenda como siguiente fase, todavía no implementada y dependiente de `ticket_activities`.

MD;
    $changelog=replaceOnce($changelog,$anchor,$entry.$anchor,'entrada archivada v2.4.0-dev en CHANGELOG');
}
writeFileStrict($changelogPath,$changelog);
ok('CHANGELOG actualizado sin alterar la advertencia histórica de v2.4.0-dev.');

// -----------------------------------------------------------------------------
// STEP 5 — GREEN
// -----------------------------------------------------------------------------
echo PHP_EOL."=== Sintaxis Manual ===".PHP_EOL;
passthru(escapeshellarg($php).' -l '.escapeshellarg($manualPath),$lintExit);
if((int)$lintExit!==0) fail('El Manual tiene error de sintaxis PHP.');

echo PHP_EOL."=== GREEN esperado: gate documental Task 9 ===".PHP_EOL;
$greenExit=runPhpTest($php,$testPath);
if($greenExit!==0) fail('El gate Task 9 sigue fallando despues de actualizar documentación.');

ok('Task 9 aplicada: Manual, README, CHANGELOG, roadmap y gate documental.');
ok('No se modifico la base de datos ni la funcionalidad de actividades.');

echo PHP_EOL."Siguiente paso recomendado:".PHP_EOL;
echo "  git diff --check".PHP_EOL;
echo "  git status".PHP_EOL;
echo "  git diff -- app/Views/help/manual.php README.md CHANGELOG.md docs/superpowers/plans/2026-09-10-helpdesk-functional-maturation-implementation.md tests/phase5_activities_ui_regression.php".PHP_EOL;
