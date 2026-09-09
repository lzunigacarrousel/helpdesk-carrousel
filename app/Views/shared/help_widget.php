<?php
$helpContext = $helpContext ?? 'general';
$help = [
    'public_home' => [
        'title' => '¿Necesitas ayuda?',
        'steps' => ['Reporta un problema sin iniciar sesión.', 'Guarda el número de caso que recibirás.', 'Usa “Ver mis solicitudes” para consultar el seguimiento.'],
    ],
    'public_create' => [
        'title' => 'Cómo registrar una solicitud',
        'steps' => ['Indica dónde ocurre el problema.', 'Selecciona el tipo de solicitud más parecido.', 'Describe qué pasa, desde cuándo y qué está afectando.'],
    ],
    'requester_home' => [
        'title' => 'Tu espacio de soporte',
        'steps' => ['Consulta tus solicitudes abiertas.', 'Abre cualquier caso para ver su avance.', 'Usa “Nueva solicitud” solo cuando necesites reportar algo nuevo.'],
    ],
    'external_home' => [
        'title' => 'Colaboración con Carrousel',
        'steps' => ['Aquí aparecen únicamente los casos que Carrousel comparte contigo.', 'Abre el caso y revisa primero qué apoyo necesitamos.', 'Responde y adjunta evidencias desde el seguimiento del mismo caso.'],
    ],
    'my_tickets' => [
        'title' => 'Mis solicitudes',
        'steps' => ['Aquí aparecen los casos asociados a tu correo.', 'Abre un caso para leer primero qué se reportó y después su seguimiento.', 'Las novedades importantes también llegan por correo.'],
    ],
    'support_dashboard' => [
        'title' => 'Centro de trabajo',
        'steps' => ['Empieza por “Requiere acción ahora”.', 'Continúa los casos que ya están en tus manos antes de tomar uno nuevo.', 'Revisa problemas y conocimiento para reutilizar soluciones existentes.'],
    ],
    'support_center' => [
        'title' => 'Centro de soporte',
        'steps' => ['Usa los filtros rápidos para separar tus casos, disponibles, críticos o vencidos.', 'Lee el problema antes de tomar un caso nuevo.', 'Si debe esperar, registra el motivo para que los tiempos queden explicados.'],
    ],
    'ticket' => [
        'title' => 'Atención del caso',
        'steps' => ['Empieza por el problema reportado: es la información principal.', 'Revisa problema conocido y posibles soluciones antes de empezar desde cero.', 'Usa el seguimiento para respuestas públicas o notas internas y documenta la solución antes de resolver.'],
    ],
    'search' => [
        'title' => 'Búsqueda global',
        'steps' => ['Busca por número de caso, problema, solicitante, correo, parque o categoría.', 'Los resultados se agrupan en Tickets, Problemas conocidos y Conocimiento.', 'El buscador respeta los permisos y la visibilidad de tu cuenta.'],
    ],
    'management' => [
        'title' => 'Dashboard interno',
        'steps' => ['Usa los filtros para analizar el periodo, parque, categoría o responsable.', 'Revisa carga, SLA, tiempos y distribución de casos.', 'Abre Informes cuando necesites reconstruir un caso o exportar datos.'],
    ],
    'reports' => [
        'title' => 'Informes',
        'steps' => ['Empieza por el resumen del período y luego filtra.', 'Cada ticket muestra contexto, tiempos, cambios de estado y resolución documentada.', 'Descarga Excel (.xlsx) cuando necesites análisis fuera del sistema.'],
    ],
    'problems' => [
        'title' => 'Problemas conocidos',
        'steps' => ['Agrupa tickets que repiten la misma falla.', 'Documenta workaround mientras investigas la causa raíz.', 'Relaciona conocimiento cuando exista una solución reutilizable.'],
    ],
    'knowledge' => [
        'title' => 'Base de conocimiento',
        'steps' => ['Los artículos nuevos comienzan como borrador.', 'Revisa contenido y visibilidad antes de publicar.', 'Usa artículos para reutilizar procedimientos y soluciones comprobadas.'],
    ],
    'externals' => [
        'title' => 'Proveedores externos',
        'steps' => ['Crea la cuenta externa con empresa, contacto y correo.', 'Comparte únicamente los casos en los que el proveedor deba participar.', 'Revoca el acceso cuando termine su participación.'],
    ],
    'users' => [
        'title' => 'Usuarios y estructura',
        'steps' => ['El rol define los permisos dentro del Helpdesk.', 'La asignación define parque o área, puesto y responsable.', 'Un supervisor o gerente operativo no necesita ser técnico del Helpdesk.'],
    ],
    'audit' => [
        'title' => 'Auditoría y trazabilidad',
        'steps' => ['Filtra por persona, acción, origen o fecha.', 'Abre el detalle para comparar valores anteriores y nuevos.', 'Usa este registro para investigar cambios, accesos y movimientos de tickets.'],
    ],
    'manual' => [
        'title' => 'Manual del Helpdesk',
        'steps' => ['Usa el índice para ir directamente al tema que necesitas.', 'El contenido se adapta a las funciones disponibles para tu perfil.', 'Puedes volver al tutorial flotante desde el botón de ayuda en cualquier pantalla.'],
    ],
    'general' => [
        'title' => 'Ayuda de Helpdesk Carrousel',
        'steps' => ['Reporta o consulta solicitudes desde esta aplicación.', 'Los botones principales cambian según tu perfil.', 'Si tienes dudas, contacta al equipo de Sistemas.'],
    ],
];
$current = $help[$helpContext] ?? $help['general'];
$canOpenManual = \App\Core\Auth::check();
?>
<button class="help-fab" type="button" data-help-open aria-label="Abrir ayuda" title="Ayuda">?</button>
<button class="help-tour-hint" type="button" data-tour-start data-tour-hint hidden>Ver guía de esta pantalla</button>
<div class="help-backdrop" data-help-backdrop hidden></div>
<aside class="help-panel" data-help-panel data-help-context="<?= htmlspecialchars($helpContext) ?>" aria-hidden="true">
    <div class="help-panel-head">
        <div><span class="help-kicker">Ayuda contextual</span><h2><?= htmlspecialchars($current['title']) ?></h2></div>
        <button class="help-close" type="button" data-help-close aria-label="Cerrar ayuda">×</button>
    </div>
    <p class="help-intro">Esta guía explica la pantalla que estás usando, sin mezclar funciones que no corresponden a tu perfil.</p>
    <ol class="help-steps">
        <?php foreach ($current['steps'] as $step): ?><li><?= htmlspecialchars($step) ?></li><?php endforeach; ?>
    </ol>
    <div class="help-actions">
        <button class="btn btn-primary" type="button" data-tour-start>Iniciar tutorial guiado</button>
        <?php if($canOpenManual): ?><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/manual">Abrir manual completo</a><?php endif; ?>
    </div>
    <div class="help-shortcuts"><span><kbd>Ctrl K</kbd> Buscar</span><span><kbd>Esc</kbd> Cerrar ayuda</span></div>
    <div class="help-contact">¿Aún necesitas ayuda? <strong>sistemas@carrousel.com.gt</strong></div>
</aside>