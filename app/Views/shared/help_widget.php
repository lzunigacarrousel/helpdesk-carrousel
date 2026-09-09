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
    'my_tickets' => [
        'title' => 'Mis solicitudes',
        'steps' => ['Aquí aparecen los casos asociados a tu correo.', 'Abre un caso para leer primero qué se reportó y después su seguimiento.', 'Las novedades importantes también llegan por correo.'],
    ],
    'support_center' => [
        'title' => 'Centro de soporte',
        'steps' => ['Continúa primero tus casos activos.', 'Lee el problema antes de tomar un caso nuevo.', 'Si debe esperar, registra el motivo para que los tiempos queden explicados.'],
    ],
    'ticket' => [
        'title' => 'Atención del caso',
        'steps' => ['Empieza por el problema reportado: es la información principal.', 'Usa el seguimiento para dejar respuestas o notas internas.', 'Antes de resolver documenta causa y solución; esa información alimenta informes y casos similares.'],
    ],
    'search' => [
        'title' => 'Búsqueda global',
        'steps' => ['Busca por número de caso, problema, solicitante, correo, parque o categoría.', 'El buscador respeta los permisos de tu cuenta.', 'El equipo de soporte también puede encontrar problemas conocidos y documentación relacionada.'],
    ],
    'management' => [
        'title' => 'Dashboard interno',
        'steps' => ['Usa los filtros para analizar el periodo, parque, categoría o responsable.', 'Revisa carga, SLA, tiempos y distribución de casos.', 'Abre Informes cuando necesites el detalle o exportar datos.'],
    ],
    'reports' => [
        'title' => 'Informes',
        'steps' => ['Aplica filtros por período, parque, categoría, responsable, estado o prioridad.', 'Revisa tiempos, cambios de estado, motivos de espera y resolución documentada.', 'Descarga el archivo Excel (.xlsx); la exportación queda auditada.'],
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
    'general' => [
        'title' => 'Ayuda de Helpdesk Carrousel',
        'steps' => ['Reporta o consulta solicitudes desde esta aplicación.', 'Los botones principales cambian según tu perfil.', 'Si tienes dudas, contacta al equipo de Sistemas.'],
    ],
];
$current = $help[$helpContext] ?? $help['general'];
?>
<button class="help-fab" type="button" data-help-open aria-label="Abrir ayuda" title="Ayuda">?</button>
<div class="help-backdrop" data-help-backdrop hidden></div>
<aside class="help-panel" data-help-panel aria-hidden="true">
    <div class="help-panel-head">
        <div><span class="help-kicker">Ayuda</span><h2><?= htmlspecialchars($current['title']) ?></h2></div>
        <button class="help-close" type="button" data-help-close aria-label="Cerrar ayuda">×</button>
    </div>
    <ol class="help-steps">
        <?php foreach ($current['steps'] as $step): ?><li><?= htmlspecialchars($step) ?></li><?php endforeach; ?>
    </ol>
    <div class="help-contact">¿Aún necesitas ayuda? <strong>sistemas@carrousel.com.gt</strong></div>
</aside>