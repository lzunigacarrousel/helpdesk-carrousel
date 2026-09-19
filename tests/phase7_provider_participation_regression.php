<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$viewPath=$root.'/app/Views/management/external_report.php';
$workReportPath=$root.'/app/Controllers/WorkReportController.php';
$ciPath=$root.'/.github/workflows/helpdesk-ci.yml';
$readmePath=$root.'/README.md';
$changelogPath=$root.'/CHANGELOG.md';
$manualPath=$root.'/app/Views/help/manual.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

function j(array $value):string
{
    return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$users=[
    10=>[
        'id'=>10,
        'full_name'=>'Proveedor Uno',
        'email'=>'proveedor1@example.com',
        'organization_name'=>'Proveedor Uno, S.A.',
    ],
    11=>[
        'id'=>11,
        'full_name'=>'Proveedor Dos',
        'email'=>'proveedor2@example.com',
        'organization_name'=>'Proveedor Dos, S.A.',
    ],
];

$events=[
    [
        'id'=>1,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_GRANTED',
        'old_value'=>null,
        'new_value'=>j(['external_user_id'=>10]),
        'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),
        'created_at'=>'2026-09-01 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>2,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_REVOKED',
        'old_value'=>j(['external_user_id'=>10]),
        'new_value'=>null,
        'metadata_json'=>'{}',
        'created_at'=>'2026-09-03 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
    [
        'id'=>3,
        'ticket_id'=>100,
        'event_type'=>'EXTERNAL_GRANTED',
        'old_value'=>null,
        'new_value'=>j(['external_user_id'=>10]),
        'metadata_json'=>j(['can_comment'=>true,'can_upload'=>true]),
        'created_at'=>'2026-09-04 08:00:00',
        'ticket_number'=>'HD-100',
        'subject'=>'Caso A',
        'ticket_status'=>'REOPENED',
        'actor_name'=>'Luis',
    ],
];

$serviceBody=is_file($servicePath)?(string)file_get_contents($servicePath):'';
$controllerBody=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$viewBody=is_file($viewPath)?(string)file_get_contents($viewPath):'';
$workBody=is_file($workReportPath)?(string)file_get_contents($workReportPath):'';
$ciBody=is_file($ciPath)?(string)file_get_contents($ciPath):'';
$readmeBody=is_file($readmePath)?(string)file_get_contents($readmePath):'';
$changelogBody=is_file($changelogPath)?(string)file_get_contents($changelogPath):'';
$manualBody=is_file($manualPath)?(string)file_get_contents($manualPath):'';

ok($serviceBody!=='','Existe ProviderParticipationService');
ok(str_contains($serviceBody,'function buildCycles('),'Expone buildCycles');
ok(str_contains($serviceBody,'function rows('),'Expone rows');
ok(!preg_match('/\b(INSERT|UPDATE|DELETE|ALTER|CREATE TABLE)\b/i',$serviceBody),'Servicio no escribe ni altera BD');

if($serviceBody!==''){
    require_once $servicePath;
    if(class_exists('App\\Services\\ProviderParticipationService')){
        $rows=\App\Services\ProviderParticipationService::buildCycles(
            $users,
            $events,
            [],
            [],
            [],
            strtotime('2026-09-05 08:00:00')
        );

        ok(count($rows)===2,'Dos asignaciones del mismo proveedor/ticket forman ciclos independientes');

        $closed=$rows[0]??[];
        $active=$rows[1]??[];
        ok(($closed['granted_at']??null)==='2026-09-01 08:00:00','Primer ciclo conserva fecha de asignación');
        ok(($closed['revoked_at']??null)==='2026-09-03 08:00:00','Primer ciclo conserva fecha de revocación');
        ok((int)($closed['duration_minutes']??-1)===2880,'Ciclo cerrado dura 48 horas');
        ok(($closed['revoked_by']??null)==='Luis','Ciclo cerrado conserva actor de revocación');
        ok(($active['granted_at']??null)==='2026-09-04 08:00:00','Segundo ciclo conserva nueva asignación');
        ok(array_key_exists('revoked_at',$active)&&$active['revoked_at']===null,'Segundo ciclo permanece activo');
        ok((int)($active['duration_minutes']??-1)===1440,'Ciclo activo usa ahora como fin');

        $duplicateGrantEvents=[
            [
                'id'=>10,
                'ticket_id'=>200,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j(['external_user_id'=>11]),
                'metadata_json'=>'{}',
                'created_at'=>'2026-09-01 10:00:00',
                'ticket_number'=>'HD-200',
                'subject'=>'Caso B',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Admin Uno',
            ],
            [
                'id'=>11,
                'ticket_id'=>200,
                'event_type'=>'EXTERNAL_GRANTED',
                'old_value'=>null,
                'new_value'=>j(['external_user_id'=>11]),
                'metadata_json'=>'{}',
                'created_at'=>'2026-09-02 10:00:00',
                'ticket_number'=>'HD-200',
                'subject'=>'Caso B',
                'ticket_status'=>'IN_PROGRESS',
                'actor_name'=>'Admin Dos',
            ],
        ];
        $duplicateRows=\App\Services\ProviderParticipationService::buildCycles(
            $users,
            $duplicateGrantEvents,
            [],
            [],
            [],
            strtotime('2026-09-03 10:00:00')
        );
        $implicitClosed=$duplicateRows[0]??[];
        ok(count($duplicateRows)===2,'Grant duplicado abre un ciclo nuevo');
        ok(($implicitClosed['revoked_at']??null)==='2026-09-02 10:00:00','Grant duplicado cierra el ciclo anterior en la nueva asignación');
        ok(($implicitClosed['revoked_by']??null)==='Nueva asignación','Cierre implícito identifica nueva asignación');
    }else{
        ok(false,'Clase ProviderParticipationService disponible');
    }
}

ok($controllerBody!=='','Existe ExternalReportController');
ok(str_contains($controllerBody,'ProviderParticipationService'),'Controller usa ProviderParticipationService');
ok(!str_contains($controllerBody,'private function history('),'Controller ya no reconstruye ciclos');
ok(str_contains($controllerBody,"'activity'"),'Controller normaliza filtro de actividad');
ok(str_contains($controllerBody,"'activityOptions'"),'Controller expone opciones de actividad');
ok(str_contains($controllerBody,"'ADMIN'")&&str_contains($controllerBody,"'SEMIADMIN'"),'Controller conserva roles administrativos');
ok(str_contains($controllerBody,"external.manage")&&str_contains($controllerBody,"reports.view"),'Controller conserva permisos de informe');
ok(str_contains($controllerBody,"'Primera respuesta'")&&str_contains($controllerBody,"'T. primera respuesta (min)'"),'XLSX exporta primera respuesta');
ok(str_contains($controllerBody,"'Actividad actual'")&&str_contains($controllerBody,"'Trabajo declarado (min)'"),'XLSX exporta actividad y trabajo declarado');
ok(str_contains($controllerBody,"'Informes'")&&str_contains($controllerBody,"'Entregas listas'")&&str_contains($controllerBody,"'Devoluciones'"),'XLSX exporta métricas técnicas');
ok(str_contains($controllerBody,"'Estado ciclo'"),'XLSX exporta estado del ciclo');
ok(str_contains($controllerBody,"['Sin respuesta'")&&str_contains($controllerBody,"['Promedio primera respuesta (min)'")&&str_contains($controllerBody,"['Devoluciones'"),'Resumen XLSX usa métricas Fase 7');

ok($viewBody!=='','Existe vista de informe de proveedores');
ok(str_contains($viewBody,'Sin respuesta'),'Resumen muestra Sin respuesta');
ok(str_contains($viewBody,'Primera respuesta'),'Tabla muestra Primera respuesta');
ok(str_contains($viewBody,'Actividad actual'),'Tabla muestra Actividad actual');
ok(str_contains($viewBody,'Devoluciones'),'Tabla muestra devoluciones');
ok(str_contains($viewBody,'name="activity"'),'Existe filtro Actividad actual');
ok(!str_contains($viewBody,'Permisos</th>'),'Permisos deja de ser columna principal');
ok(str_contains($viewBody,'Proveedor / Ticket')&&str_contains($viewBody,'Asignación')&&str_contains($viewBody,'Participación')&&str_contains($viewBody,'Trabajo')&&str_contains($viewBody,'Resultado'),'Tabla usa siete columnas operativas');

ok($workBody!=='','Existe WorkReportController');
ok(str_contains($workBody,"\$readyForReview=\$workStatus==='READY_FOR_REVIEW';"),'Work report conserva READY_FOR_REVIEW como señal');
ok(!str_contains($workBody,'UPDATE tickets SET status'),'READY_FOR_REVIEW no cambia estado del ticket');
ok(!str_contains($workBody,'WorkflowController'),'Informe externo no invoca WorkflowController');

ok(str_contains($ciBody,'Phase 7 provider participation regression'),'CI incluye regresión de Fase 7');
ok(str_contains($ciBody,'php tests/phase7_provider_participation_regression.php'),'CI ejecuta gate principal de Fase 7');

ok(str_contains($readmeBody,'| 7 | Proveedores | **Cerrada técnicamente en PC TEST** |'),'README marca Fase 7 cerrada técnicamente');
ok(str_contains($readmeBody,'| 8 | Calidad IT → proveedor |'),'README conserva Fase 8 en roadmap');
ok(str_contains($readmeBody,'### Fase 7 — Proveedores'),'README documenta alcance de Fase 7');
ok(str_contains($readmeBody,'BD: sin cambios'),'README documenta cero cambios de BD en Fase 7');

ok(str_contains($changelogBody,'## Fase 7 · Proveedores · 2026-09-15'),'CHANGELOG registra cierre de Fase 7');
ok(str_contains($changelogBody,'READY_FOR_REVIEW')&&str_contains($changelogBody,'devoluciones'),'CHANGELOG documenta entrega y devoluciones');
ok(str_contains($changelogBody,'BD: sin cambios'),'CHANGELOG documenta cero cambios de BD');

ok(str_contains($manualBody,'Informe de proveedores'),'Manual documenta informe de proveedores');
ok(str_contains($manualBody,'Listo para revisión')&&str_contains($manualBody,'Devoluciones'),'Manual explica READY_FOR_REVIEW y devoluciones');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Contrato base de participación de proveedores.'.PHP_EOL;
