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

$js=(string)file_get_contents($root.'/public/assets/js/help-tour.js');
$css=(string)file_get_contents($root.'/public/assets/css/help-tour-contrast.css');

foreach([
    "['see','Qué estás viendo']",
    "['purpose','Para qué sirve']",
    "['first','Qué hacer primero']",
    "['wait','Qué puede esperar']",
    "['next','Qué ocurre después']",
    "['mistakes','Errores comunes']",
    "['help','Dónde obtener más ayuda']",
] as $field){
    ok(str_contains($js,$field),'Tutorial define '.$field);
}

ok(str_contains($js,'const defaultGuide={'),'Existe guía conceptual por defecto');
ok(str_contains($js,'const tourGuides={'),'Existen guías contextuales');
ok(str_contains($js,'function guideFor(key)'),'Motor resuelve guía por contexto');
ok(str_contains($js,'function distributeGuide(found,key)'),'Motor distribuye los siete puntos');
ok(str_contains($js,'step.guideItems=items.slice'),'Cada paso recibe conceptos del tutorial');
ok(str_contains($js,'tour-guide-notes'),'Popover renderiza guía conceptual');
ok(str_contains($js,'tour-guide-note'),'Popover renderiza cada concepto');

foreach([
    'public_home',
    'public_create',
    'requester_home',
    'external_home',
    'my_tickets',
    'support_dashboard',
    'support_center',
    'ticket_support',
    'ticket_external',
    'ticket_requester',
    'management',
    'reports',
    'external_report',
    'support_team',
    'agenda',
    'problems',
    'knowledge',
    'users',
    'externals',
    'mail',
    'audit',
    'manual',
    'search',
    'general',
] as $context){
    ok(str_contains($js,$context.':{')||str_contains($js,$context.':{...defaultGuide}'),'Existe guía de siete puntos para '.$context);
}

ok(str_contains($js,"document.querySelector('.external-report-page')"),'Reportes detecta informe especializado de proveedores');
ok(str_contains($js,"document.querySelector('.support-team-page')"),'Reportes detecta informe especializado del equipo');
ok(str_contains($js,"context='ticket_external'"),'Ticket externo tiene tutorial propio');
ok(str_contains($js,"context='ticket_support'"),'Ticket de soporte tiene tutorial propio');
ok(str_contains($js,"context='ticket_requester'"),'Ticket solicitante tiene tutorial propio');

foreach(['agenda:[','external_report:[','support_team:[','reports:['] as $tour){
    ok(str_contains($js,$tour),'Existe recorrido visual '.$tour);
}
ok(str_contains($js,"['.agenda-toolbar','Agenda y período'"),'Agenda recorre encabezado y período');
ok(str_contains($js,"['.external-report-summary','Resumen ejecutivo'"),'Proveedor recorre resumen ejecutivo');
ok(str_contains($js,"['.support-period-card','Desempeño del período'"),'Equipo recorre desempeño del período');
ok(str_contains($js,"['.report-catalog-grid','Módulos disponibles'"),'Centro de informes recorre módulos');

ok(str_contains($css,'.tour-guide-notes'),'CSS cubre guía conceptual');
ok(str_contains($css,'.tour-guide-note'),'CSS cubre cada nota conceptual');
ok(str_contains($css,'html[data-theme="dark"] .tour-guide-note'),'Guía conceptual soporta modo oscuro');
ok(str_contains($css,'@media(max-width:520px)'),'Guía conserva adaptación móvil');
ok(str_contains($css,'.tour-guide-note{grid-template-columns:1fr'),'Notas pasan a una columna en móvil');

ok(!is_file($root.'/database/MIGRAR_FASE11_MANUAL.sql'),'Task 4 no introduce migración de BD');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Tutoriales flotantes de siete puntos Fase 11 consolidados.'.PHP_EOL;
