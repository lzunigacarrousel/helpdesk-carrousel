<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$appStart=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$agendaView=(string)file_get_contents($root.'/app/Views/agenda/index.php');
$errors=0;

function ok(bool $condition,string $label): void {
    global $errors;
    if($condition){echo '[OK] '.$label.PHP_EOL;return;}
    $errors++;
    echo '[FALLO] '.$label.PHP_EOL;
}

ok(str_contains($agendaView,'$overdue=$overdue??[];'),'Agenda conserva overdue como colección de actividades');
ok(!preg_match('/\$overdue\s*=\s*\(int\)\s*\$pdo->query/', $appStart),'Shell no pisa overdue con contador SLA');
ok(str_contains($appStart,'$slaOverdueCount='),'Shell usa nombre específico para contador SLA vencido');

if($errors){
    echo PHP_EOL.'[ERROR] '.$errors.' validación(es) fallaron.'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'[OK] Scope de variables compartidas compatible con Agenda.'.PHP_EOL;
