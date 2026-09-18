<?php
$helpContext = $helpContext ?? 'general';
$help = [
    'public_home' => [
        'title' => '¿Necesitas ayuda?',
        'steps' => ['Puedes reportar un problema sin iniciar sesión.', 'Guarda el número de caso que recibirás por correo.', 'Usa “Mis solicitudes” para consultar respuestas, archivos y solución.'],
    ],
    'public_create' => [
        'title' => 'Cómo registrar una solicitud',
        'steps' => ['Completa únicamente los datos que conozcas; la ubicación puede quedar sin definir.', 'Elige en qué necesitas ayuda usando el catálogo en lenguaje cotidiano.', 'Cuéntanos qué sucede, desde cuándo y qué intentaste si ya hiciste alguna prueba.', 'Después de enviar recibirás un número de caso y podrás seguir las novedades desde Mis solicitudes.'],
    ],
    'requester_home' => [
        'title' => 'Tu espacio de soporte',
        'steps' => ['Consulta primero tus solicitudes abiertas.', 'Abre cualquier caso para ver el problema, las respuestas y su avance.', 'Usa “Nueva solicitud” solamente cuando necesites reportar algo diferente.'],
    ],
    'external_home' => [
        'title' => 'Tus casos compartidos',
        'steps' => ['Aquí aparecen únicamente los casos asignados a tu cuenta.', 'Abre el caso y revisa primero qué apoyo se necesita.', 'Responde y adjunta evidencias desde el seguimiento del mismo caso.'],
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
        'steps' => ['Empieza por el problema reportado: es la información principal.', 'Revisa problema conocido y posibles soluciones antes de empezar desde cero.', 'Usa el seguimiento para respuestas públicas o notas internas.', 'Documenta causa y solución antes de resolver para que el caso pueda reutilizarse como aprendizaje.'],
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
        'steps' => ['Agrupa tickets que repiten la misma falla.', 'Documenta una solución temporal mientras investigas la causa.', 'Relaciona conocimiento cuando exista una solución reutilizable.'],
    ],
    'knowledge' => [
        'title' => 'Base de conocimiento',
        'steps' => ['Los artículos nuevos comienzan como borrador.', 'Revisa contenido y visibilidad antes de publicar.', 'Usa artículos para reutilizar procedimientos y soluciones comprobadas.'],
    ],
    'externals' => [
        'title' => 'Proveedores externos',
        'steps' => ['Crea la cuenta del colaborador con empresa, contacto y correo.', 'Comparte únicamente los casos en los que deba participar.', 'Revoca el acceso cuando termine su participación.'],
    ],
    'users' => [
        'title' => 'Administrar usuarios',
        'steps' => ['Usa “Dar acceso” para crear un acceso interno.', 'Abre “Editar” para cambiar datos, perfil, estado o asignación.', 'Antes de retirar el acceso a un usuario con casos activos, reasigna esos casos.', 'Retirar acceso conserva tickets, historial y auditoría.'],
    ],
    'audit' => [
        'title' => 'Auditoría y trazabilidad',
        'steps' => ['Filtra por persona, acción, origen o fecha.', 'Abre el detalle para comparar valores anteriores y nuevos.', 'Usa este registro para investigar cambios, accesos y movimientos de tickets.', 'Desde aquí puedes abrir Correo y notificaciones para comprobar entregas.'],
    ],
    'mail' => [
        'title' => 'Correo y notificaciones',
        'steps' => ['Comprueba primero si el canal está en SMTP activo o en modo de prueba.', 'Usa Enviar correo de prueba para validar conexión y entrega antes de depender del correo en operación.', 'Los estados Enviado, Falló, Pendiente y Modo prueba permiten distinguir una entrega real de un registro local.', 'Reintenta solo correos fallidos o pendientes; los códigos OTP siempre deben solicitarse nuevamente.'],
    ],
    'manual' => [
        'title' => 'Manual del Helpdesk',
        'steps' => ['Empieza por las tarjetas de tareas frecuentes o usa el índice.', 'Abre solo las preguntas frecuentes que necesites; no es necesario leer todo el manual.', 'El contenido se adapta a las funciones disponibles para tu perfil.', 'Puedes volver al tutorial flotante desde el botón de ayuda en cualquier pantalla.'],
    ],
    'general' => [
        'title' => 'Ayuda del Helpdesk',
        'steps' => ['Reporta o consulta solicitudes desde esta aplicación.', 'Las opciones visibles cambian según tu perfil y alcance.', 'Si tienes dudas, contacta al equipo de Sistemas.'],
    ],
];
$current = $help[$helpContext] ?? $help['general'];
$canOpenManual = \App\Core\Auth::check();

$manualTargets=[
    'requester_home'=>['anchor'=>'solicitudes','topic'=>'solicitudes','faq'=>'solicitud'],
    'external_home'=>['anchor'=>'solicitudes','topic'=>'solicitudes','faq'=>'casos'],
    'my_tickets'=>['anchor'=>'solicitudes','topic'=>'solicitudes'],
    'support_dashboard'=>['anchor'=>'soporte','topic'=>'soporte','faq'=>'SLA'],
    'support_center'=>['anchor'=>'soporte','topic'=>'soporte','faq'=>'espera'],
    'ticket'=>['anchor'=>'solicitudes','topic'=>'solicitudes'],
    'search'=>['anchor'=>'inicio','topic'=>'all'],
    'management'=>['anchor'=>'gestion','topic'=>'gestion'],
    'reports'=>['anchor'=>'gestion','topic'=>'gestion'],
    'problems'=>['anchor'=>'conocimiento','topic'=>'conocimiento','faq'=>'problema conocido'],
    'knowledge'=>['anchor'=>'conocimiento','topic'=>'conocimiento'],
    'externals'=>['anchor'=>'administracion','topic'=>'administracion'],
    'users'=>['anchor'=>'administracion','topic'=>'administracion','faq'=>'Perfil, asignación'],
    'audit'=>['anchor'=>'administracion','topic'=>'administracion'],
    'mail'=>['anchor'=>'administracion','topic'=>'administracion','faq'=>'correo'],
    'manual'=>['anchor'=>'inicio','topic'=>'all'],
    'agenda'=>['anchor'=>'agenda','topic'=>'actividades'],
    'general'=>['anchor'=>'inicio','topic'=>'all'],
];

$manualTarget=$manualTargets[$helpContext]??$manualTargets['general'];
$role=(string)\App\Core\Auth::role();
$isExternalHelp=$canOpenManual&&((\App\Core\Auth::user()['access_type']??'INTERNAL')==='EXTERNAL');
$isSupportHelp=$canOpenManual&&\App\Core\Auth::isSupportOperator();
$isManagementHelp=$canOpenManual&&\App\Core\Auth::isManagementViewer();

if($helpContext==='ticket'){
    if($isSupportHelp){
        $manualTarget=['anchor'=>'soporte','topic'=>'soporte','faq'=>'conversación interna'];
    }elseif($isExternalHelp){
        $manualTarget=['anchor'=>'solicitudes','topic'=>'solicitudes','faq'=>'notas internas'];
    }else{
        $manualTarget=['anchor'=>'solicitudes','topic'=>'solicitudes','faq'=>'cierra'];
    }
}elseif($helpContext==='my_tickets'&&$isExternalHelp){
    $manualTarget=['anchor'=>'solicitudes','topic'=>'solicitudes','faq'=>'casos'];
}elseif(in_array($helpContext,['management','reports','agenda'],true)&&$isManagementHelp){
    if($role==='SUPERVISOR'){
        $manualTarget['faq']='botones';
        $manualTarget['topic']='gestion';
    }elseif($role==='MANAGEMENT'){
        $manualTarget['faq']='Gerencia';
        $manualTarget['topic']='gestion';
    }
}

if(($activeNav??'')==='external-report'){
    $manualTarget=['anchor'=>'proveedores','topic'=>'gestion'];
}elseif(($activeNav??'')==='support-team'){
    $manualTarget=['anchor'=>'gestion','topic'=>'gestion'];
}

if($helpContext==='knowledge'&&\App\Core\Auth::can('knowledge.draft_manage')){
    $manualTarget['faq']='artículo';
}

$manualHref='';
$faqHref='';
if($canOpenManual){
    $manualParams=[];
    if(($manualTarget['topic']??'all')!=='all')$manualParams['topic']=$manualTarget['topic'];
    $manualHref=APP_BASE_URL.'/manual'.($manualParams?'?'.http_build_query($manualParams):'').'#'.rawurlencode((string)($manualTarget['anchor']??'inicio'));

    if(!empty($manualTarget['faq'])){
        $faqParams=['topic'=>'ayuda','q'=>(string)$manualTarget['faq']];
        $faqHref=APP_BASE_URL.'/manual?'.http_build_query($faqParams).'#preguntas';
    }
}
?>
<button class="help-fab" type="button" data-help-open aria-label="Abrir ayuda" title="Ayuda">?</button>
<div class="help-backdrop" data-help-backdrop hidden></div>
<aside class="help-panel" data-help-panel data-help-context="<?= htmlspecialchars($helpContext) ?>" aria-hidden="true">
    <div class="help-panel-head">
        <div><span class="help-kicker">Ayuda de esta pantalla</span><h2><?= htmlspecialchars($current['title']) ?></h2></div>
        <button class="help-close" type="button" data-help-close aria-label="Cerrar ayuda">×</button>
    </div>
    <p class="help-intro">Consulta la guía rápida o inicia el recorrido guiado para ver cada parte directamente sobre la pantalla.</p>
    <ol class="help-steps">
        <?php foreach ($current['steps'] as $step): ?><li><?= htmlspecialchars($step) ?></li><?php endforeach; ?>
    </ol>
    <div class="help-actions">
        <button class="btn btn-primary" type="button" data-tour-start>Iniciar tutorial</button>
        <?php if($canOpenManual): ?><a class="btn btn-outline-secondary" href="<?= htmlspecialchars($manualHref) ?>">Abrir manual aquí</a><?php endif; ?>
        <?php if($canOpenManual&&$faqHref!==''): ?><a class="btn btn-outline-secondary" href="<?= htmlspecialchars($faqHref) ?>">Preguntas de esta pantalla</a><?php endif; ?>
    </div>
    <div class="help-shortcuts"><span><kbd>Ctrl K</kbd> Buscar</span><span><kbd>Esc</kbd> Cerrar</span></div>
    <div class="help-contact">¿Aún necesitas ayuda? <strong>sistemas@carrousel.com.gt</strong></div>
</aside>