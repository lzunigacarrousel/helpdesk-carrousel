<?php
$helpContext = $helpContext ?? 'general';
$help = [
    'public_home' => [
        'title' => '¿Necesitas ayuda?',
        'steps' => ['Reporta un problema sin iniciar sesión.', 'Guarda el número de caso que recibirás.', 'Usa “Ver mis solicitudes” para consultar el seguimiento.'],
    ],
    'public_create' => [
        'title' => 'Cómo registrar una solicitud',
        'steps' => ['Indica dónde ocurre el problema.', 'Selecciona el tipo de solicitud más parecido.', 'Describe qué pasa y envía el caso.'],
    ],
    'requester_home' => [
        'title' => 'Tu espacio de soporte',
        'steps' => ['Consulta tus solicitudes abiertas.', 'Abre cualquier caso para ver su avance.', 'Usa “Nueva solicitud” solo cuando necesites reportar algo nuevo.'],
    ],
    'my_tickets' => [
        'title' => 'Mis solicitudes',
        'steps' => ['Aquí aparecen los casos asociados a tu correo.', 'Toca un caso para ver el seguimiento completo.', 'Las novedades importantes también llegan por correo.'],
    ],
    'support_center' => [
        'title' => 'Centro de soporte',
        'steps' => ['Continúa primero tus casos activos.', 'Toma un caso disponible cuando puedas atenderlo.', 'Actualiza el estado desde el mismo caso.'],
    ],
    'ticket' => [
        'title' => 'Seguimiento del caso',
        'steps' => ['Arriba verás el estado actual.', 'En el centro aparece la información principal.', 'El historial muestra los movimientos del caso.'],
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
