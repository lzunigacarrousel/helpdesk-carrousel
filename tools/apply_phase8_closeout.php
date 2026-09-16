<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ciPath=$root.'/.github/workflows/helpdesk-ci.yml';
$readmePath=$root.'/README.md';
$changelogPath=$root.'/CHANGELOG.md';
$manualPath=$root.'/app/Views/help/manual.php';

function readLf(string $path): string
{
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe: '.$path.PHP_EOL);
        exit(1);
    }
    return str_replace(["\r\n","\r"],"\n",(string)file_get_contents($path));
}

function writeLf(string $path,string $body): void
{
    file_put_contents($path,$body);
}

function replaceOnce(string $body,string $old,string $new,string $label): string
{
    $count=substr_count($body,$old);
    if($count===0){
        if(str_contains($body,$new)){
            echo '[OK] '.$label.': ya aplicado.'.PHP_EOL;
            return $body;
        }
        fwrite(STDERR,'[ERROR] '.$label.': no se encontro el ancla.'.PHP_EOL);
        exit(1);
    }
    if($count!==1){
        fwrite(STDERR,'[ERROR] '.$label.': esperaba 1 coincidencia y encontro '.$count.'.'.PHP_EOL);
        exit(1);
    }
    echo '[OK] '.$label.'.'.PHP_EOL;
    return str_replace($old,$new,$body);
}

$ci=readLf($ciPath);
$readme=readLf($readmePath);
$changelog=readLf($changelogPath);
$manual=readLf($manualPath);

$ciAnchor="      - name: Route, view and CSS quality gate\n        run: php tests/project_quality.php\n";
$ciBlock="      - name: Phase 8 provider rating - cycle identity\n        run: php tests/phase8_provider_cycle_identity_regression.php\n\n      - name: Phase 8 provider rating - service\n        run: php tests/phase8_provider_rating_service_regression.php\n\n      - name: Phase 8 provider rating - controller\n        run: php tests/phase8_provider_rating_controller_regression.php\n\n      - name: Phase 8 provider rating - UI\n        run: php tests/phase8_provider_rating_ui_regression.php\n\n      - name: Phase 8 provider rating - reports\n        run: php tests/phase8_provider_rating_report_regression.php\n\n      - name: Phase 8 provider rating - closeout\n        run: php tests/phase8_provider_rating_closeout_regression.php\n\n";
if(!str_contains($ci,'php tests/phase8_provider_rating_closeout_regression.php')){
    $ci=replaceOnce($ci,$ciAnchor,$ciBlock.$ciAnchor,'CI incorpora gates de Fase 8');
}else{
    echo '[OK] CI incorpora gates de Fase 8: ya aplicado.'.PHP_EOL;
}

$readme=replaceOnce(
    $readme,
    '| 8 | Calidad IT → proveedor | **Siguiente fase** |',
    '| 8 | Calidad IT → proveedor | **Implementada — pendiente validación integral Fase 12** |',
    'README cierra Fase 8'
);
$readme=replaceOnce(
    $readme,
    '| 9 | Conocimiento | Parcialmente adelantada; falta cierre formal |',
    '| 9 | Conocimiento | **Siguiente fase** |',
    'README mueve siguiente a Fase 9'
);

$readmeAnchor="## Flujo Git oficial\n";
$readmeSection="### Fase 8 — Calidad IT → proveedor\n\nEstado: **IMPLEMENTADA — pendiente validación integral Fase 12**.\n\nLa Fase 8 incorpora una valoración interna de IT sobre cada ciclo finalizado de participación de proveedor, sin convertirla en ranking público ni mezclarla con la satisfacción del solicitante:\n\n- escala interna de 1 a 5: 1 **Muy deficiente**, 2 **Deficiente**, 3 **Adecuado**, 4 **Bueno**, 5 **Excelente**;\n- solo ciclos cerrados mediante `EXTERNAL_REVOKED` son evaluables; ciclos activos o cerrados implícitamente por una nueva asignación no muestran formulario de valoración;\n- la primera valoración se registra como evento inmutable `PROVIDER_RATED`;\n- una corrección se registra como un nuevo evento `PROVIDER_RATING_CORRECTED`, conservando el historial anterior;\n- comentario obligatorio para 1–2 estrellas y para toda corrección;\n- `Sin evaluar` no equivale a cero y no participa en promedios;\n- solo ADMIN, SEMIADMIN y TECHNICIAN con scope válido pueden evaluar o corregir;\n- proveedor y solicitante no ven score ni comentario interno;\n- el Informe de proveedores y XLSX muestran valoración vigente, filtros y agregados de calidad por proveedor;\n- esta valoración es independiente de `ticket_feedback.nps_score`, que corresponde al feedback del solicitante.\n\n**BD: sin cambios.** La fase reutiliza `ticket_events` como fuente de verdad y no crea tablas, columnas, índices ni migraciones.\n\n";
if(!str_contains($readme,'### Fase 8 — Calidad IT → proveedor')){
    $readme=replaceOnce($readme,$readmeAnchor,$readmeSection.$readmeAnchor,'README documenta Fase 8');
}else{
    echo '[OK] README documenta Fase 8: ya aplicado.'.PHP_EOL;
}

$changelogAnchor="## Fase 7 · Proveedores · 2026-09-15\n";
$changelogSection="## Fase 8 · Calidad IT → proveedor · 2026-09-16\n- Cada ciclo finalizado explícitamente mediante `EXTERNAL_REVOKED` puede recibir una valoración interna de IT de 1 a 5 estrellas.\n- La primera valoración se registra de forma inmutable con `PROVIDER_RATED`; una corrección crea `PROVIDER_RATING_CORRECTED` y nunca edita ni elimina la valoración anterior.\n- 1–2 estrellas exigen comentario y toda corrección exige motivo; `Sin evaluar` no equivale a cero ni participa en promedios.\n- La captura vive dentro del ticket interno y respeta roles operativos, scope backend, CSRF y auditoría.\n- Proveedor y solicitante no reciben score ni comentario interno.\n- El Informe de proveedores incorpora filtro por valoración, valoración vigente por ciclo, promedio, ciclos evaluados/sin evaluar y exportación XLSX consistente con la pantalla.\n- La calidad IT → proveedor permanece separada de `ticket_feedback.nps_score`, que conserva el feedback del solicitante y no se usa para medir al proveedor.\n- **BD: sin cambios.** Se reutiliza `ticket_events`; no se agregan tablas, columnas, índices ni migraciones.\n\n";
if(!str_contains($changelog,'## Fase 8 · Calidad IT → proveedor')){
    $changelog=replaceOnce($changelog,$changelogAnchor,$changelogSection.$changelogAnchor,'CHANGELOG registra Fase 8');
}else{
    echo '[OK] CHANGELOG registra Fase 8: ya aplicado.'.PHP_EOL;
}

$manualAnchor='<?php if($isSupport): ?><section class="manual-section" id="soporte"';
$manualSection=<<<'HTML'
<?php if($isSupport): ?>
  <section class="manual-section" id="calidad-proveedor" data-manual-section>
    <div class="manual-section-head"><span>QP</span><div><h2>Calidad del proveedor</h2><p>Registra una valoración interna de IT cuando la participación del proveedor haya finalizado mediante revocación explícita.</p></div></div>
    <div class="manual-cards">
      <article><strong>Escala 1–5</strong><p>1★ Muy deficiente, 2★ Deficiente, 3★ Adecuado, 4★ Bueno y 5★ Excelente. La valoración describe la calidad observada por IT en ese ciclo concreto.</p></article>
      <article><strong>Comentario obligatorio</strong><p>En 1★ o 2★ debes explicar el motivo. Para 3★, 4★ y 5★ el comentario es opcional en la primera valoración.</p></article>
      <article><strong>Correcciones</strong><p>Si necesitas ajustar una valoración ya guardada, usa <b>Registrar corrección</b>. Toda corrección exige comentario y crea una nueva versión; el registro anterior se conserva.</p></article>
      <article><strong>Sin evaluar</strong><p>Un ciclo puede quedar Sin evaluar. Ese estado no vale cero y no entra al promedio del proveedor.</p></article>
      <article><strong>Cuándo se habilita</strong><p>Solo puedes evaluar una participación cerrada mediante revocación explícita. Un ciclo activo o cerrado implícitamente por una nueva asignación no es evaluable.</p></article>
      <article><strong>Privacidad</strong><p>La valoración es interna de IT: no es visible para el proveedor ni para el solicitante. El proveedor no la ve en su acceso externo.</p></article>
      <article><strong>Informe de proveedores</strong><p>El informe muestra valoración vigente, promedio, ciclos evaluados y Sin evaluar, además de filtros por estrellas. La exportación XLSX usa el mismo conjunto filtrado.</p></article>
    </div>
  </section>
  <?php endif; ?>

HTML;
if(!str_contains($manual,'id="calidad-proveedor"')){
    $manual=replaceOnce($manual,$manualAnchor,$manualSection.$manualAnchor,'Manual documenta Calidad del proveedor');
}else{
    echo '[OK] Manual documenta Calidad del proveedor: ya aplicado.'.PHP_EOL;
}

writeLf($ciPath,$ci);
writeLf($readmePath,$readme);
writeLf($changelogPath,$changelog);
writeLf($manualPath,$manual);

foreach([$manualPath] as $path){
    passthru('"'.PHP_BINARY.'" -l "'.$path.'"',$code);
    if($code!==0)exit($code);
}

echo '[OK] Cierre documental de Fase 8 aplicado. No se modifico la BD.'.PHP_EOL;
