<?php
declare(strict_types=1);

$root=dirname(__DIR__);

function replaceOnce(string $content,string $search,string $replace,string $label): string
{
    $count=substr_count($content,$search);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba exactamente 1 coincidencia y encontró {$count}.".PHP_EOL);
        exit(1);
    }
    return str_replace($search,$replace,$content);
}

function updateFile(string $path,callable $transform): void
{
    if(!is_file($path)){
        fwrite(STDERR,"[ERROR] No existe {$path}.".PHP_EOL);
        exit(1);
    }
    $before=(string)file_get_contents($path);
    $after=$transform($before);
    if($after===$before){
        echo "[OK] Sin cambios necesarios: {$path}".PHP_EOL;
        return;
    }
    if(file_put_contents($path,$after)===false){
        fwrite(STDERR,"[ERROR] No se pudo escribir {$path}.".PHP_EOL);
        exit(1);
    }
    echo "[OK] Actualizado: {$path}".PHP_EOL;
}

$ci=$root.'/.github/workflows/helpdesk-ci.yml';
updateFile($ci,static function(string $body):string{
    if(str_contains($body,'Phase 7 provider participation regression'))return $body;
    $anchor="      - name: Phase 6 agenda filter UX regression\n        run: php tests/phase6_agenda_filter_ux_regression.php\n";
    $insert=$anchor."\n      - name: Phase 7 provider participation regression\n        run: php tests/phase7_provider_participation_regression.php\n";
    return replaceOnce($body,$anchor,$insert,'CI Fase 7');
});

$readme=$root.'/README.md';
updateFile($readme,static function(string $body):string{
    $body=str_replace(
        '| 7 | Proveedores | **Siguiente fase** |',
        '| 7 | Proveedores | **Implementada — pendiente validación integral Fase 12** |',
        $body
    );
    $body=str_replace(
        '| 8 | Calidad IT → proveedor | Pendiente |',
        '| 8 | Calidad IT → proveedor | **Siguiente fase** |',
        $body
    );
    if(str_contains($body,'### Fase 7 — Proveedores'))return $body;
    $anchor="## Flujo Git oficial\n";
    $section=<<<'MD'
### Fase 7 — Proveedores

Estado: **IMPLEMENTADA — pendiente validación integral Fase 12**.

La Fase 7 consolida la medición operativa de proveedores externos sobre la información que ya genera el Helpdesk:

- cada asignación `EXTERNAL_GRANTED → EXTERNAL_REVOKED` se trata como un ciclo independiente, incluso si el mismo proveedor vuelve a participar en el mismo ticket;
- la primera respuesta se calcula con el primer mensaje externo independiente o el primer informe técnico del proveedor dentro del ciclo;
- duración de participación y tiempo de trabajo declarado (`time_spent_minutes`) se mantienen como métricas separadas;
- la actividad actual proviene del último `work_status` del informe técnico y, si no existe informe, se muestra **Sin actualización**;
- `READY_FOR_REVIEW` / **Listo para revisión** es una señal del proveedor y no modifica automáticamente el estado del ticket;
- las devoluciones se cuentan únicamente cuando, después de una entrega lista para revisión, el ticket vuelve a `REOPENED` o `IN_PROGRESS` dentro del mismo ciclo;
- el **Informe de proveedores** y su exportación XLSX consumen el mismo dataset filtrado, con proveedor, estado del ciclo, actividad y rango de fechas;
- la administración de colaboradores y sus accesos permanece separada del informe operativo.

**BD: sin cambios.** La fase reutiliza `ticket_events`, `ticket_comments`, `ticket_attachments`, `ticket_work_reports` y el control de acceso externo existente; no crea tablas, columnas ni migraciones.

MD;
    return replaceOnce($body,$anchor,$section.$anchor,'README sección Fase 7');
});

$changelog=$root.'/CHANGELOG.md';
updateFile($changelog,static function(string $body):string{
    if(str_contains($body,'## Fase 7 · Proveedores · 2026-09-15'))return $body;
    $anchor="## Fase 6 · Agenda · 2026-09-14\n";
    $entry=<<<'MD'
## Fase 7 · Proveedores · 2026-09-15
- `ProviderParticipationService` se convierte en la fuente única para reconstruir ciclos independientes de participación externa y calcular métricas sin duplicar lógica en controller o XLSX.
- El informe de proveedores incorpora primera respuesta por mensaje o informe técnico, duración de participación, tiempo de trabajo declarado, actividad actual, respuestas, archivos, informes y entregas listas para revisión.
- `READY_FOR_REVIEW` permanece como señal **Listo para revisión** del proveedor; no cambia automáticamente el estado técnico del ticket ni invoca el workflow de cierre.
- Las **devoluciones** se cuentan únicamente cuando existe una entrega previa `READY_FOR_REVIEW` y el ticket retorna después a `REOPENED` o `IN_PROGRESS` dentro del mismo ciclo; una misma reapertura no duplica varias entregas previas.
- El informe administrativo añade filtros por proveedor, estado del ciclo, actividad y fechas, resumen operativo y una tabla compacta de siete columnas.
- La exportación XLSX utiliza exactamente las mismas filas filtradas que la pantalla e incluye primera respuesta, actividad, trabajo declarado, informes, entregas, devoluciones y estado del ciclo.
- Se conservan permisos y aislamiento INTERNAL / EXTERNAL; las notas internas no participan en las métricas del proveedor.
- **BD: sin cambios.** Se reutilizan eventos, comentarios, adjuntos, informes técnicos y accesos externos existentes; no se agrega migración.

MD;
    return replaceOnce($body,$anchor,$entry.$anchor,'CHANGELOG Fase 7');
});

$manual=$root.'/app/Views/help/manual.php';
updateFile($manual,static function(string $body):string{
    if(str_contains($body,'id="proveedores"')&&str_contains($body,'Informe de proveedores')&&str_contains($body,'Devoluciones'))return $body;
    $anchor='  <?php if($canAdmin): ?><section class="manual-section" id="administracion"';
    $section=<<<'PHP'
  <?php if($canManagement||$canAdmin): ?>
  <section class="manual-section" id="proveedores" data-manual-section>
    <div class="manual-section-head"><span>PR</span><div><h2>Informe de proveedores</h2><p>Consulta cómo participó cada colaborador externo en los casos compartidos sin mezclar administración de accesos con rendimiento operativo.</p></div></div>
    <div class="manual-cards">
      <article><strong>Participación por ciclo</strong><p>Cada vez que un proveedor recibe acceso a un ticket inicia un ciclo nuevo. El informe muestra asignación, primera respuesta, duración, actividad actual, tiempo declarado, respuestas, archivos e informes de ese ciclo.</p></article>
      <article><strong>Listo para revisión</strong><p>Cuando el proveedor marca <b>Listo para revisión</b>, informa que su trabajo está listo para que Carrousel lo valide. Esa acción no resuelve ni cierra automáticamente el ticket; el equipo interno decide el siguiente paso.</p></article>
      <article><strong>Devoluciones</strong><p>Una devolución se registra cuando, después de una entrega lista para revisión, el ticket vuelve a atención o se reabre. Las reaperturas anteriores a la entrega no se atribuyen al proveedor.</p></article>
      <article><strong>Filtros y Excel</strong><p>Filtra por proveedor, estado del ciclo, actividad y fechas. <b>Descargar Excel</b> utiliza los mismos filtros y métricas que ves en pantalla.</p><a href="<?= APP_BASE_URL ?>/admin/externos/informe">Abrir Informe de proveedores →</a></article>
    </div>
  </section>
  <?php endif; ?>

PHP;
    return replaceOnce($body,$anchor,$section.$anchor,'Manual Informe de proveedores');
});

echo '[OK] Cierre documental de Fase 7 aplicado. No se modificó la base de datos.'.PHP_EOL;
