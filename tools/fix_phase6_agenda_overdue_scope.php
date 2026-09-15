<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$view = $root . '/app/Views/shared/app_start.php';
$test = $root . '/tests/phase6_agenda_view_scope_regression.php';
$php = 'C:\\xampp\\php\\php.exe';

function fail(string $message): never {
    fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
    exit(1);
}

function run(string $command): int {
    passthru($command, $code);
    return $code;
}

chdir($root) || fail('No se pudo entrar al repositorio.');

$body = @file_get_contents($view);
if (!is_string($body)) {
    fail('No se pudo leer app/Views/shared/app_start.php');
}

$old = rtrim(<<<'PHP'
if($canManagement){$overdue=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')")->fetchColumn();if($overdue>0)$attentionItems[]=['label'=>'SLA vencidos','detail'=>'Casos fuera del tiempo objetivo.','count'=>$overdue,'href'=>APP_BASE_URL.'/tickets/queue?view=overdue'];}
PHP
, "\r\n");

$new = rtrim(<<<'PHP'
if($canManagement){$slaOverdueCount=(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE deleted_at IS NULL AND resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')")->fetchColumn();if($slaOverdueCount>0)$attentionItems[]=['label'=>'SLA vencidos','detail'=>'Casos fuera del tiempo objetivo.','count'=>$slaOverdueCount,'href'=>APP_BASE_URL.'/tickets/queue?view=overdue'];}
PHP
, "\r\n");

$count = substr_count($body, $old);
if ($count !== 1) {
    fail('Se esperaba exactamente 1 bloque SLA con $overdue y se encontraron ' . $count . '.');
}

$updated = str_replace($old, $new, $body);
if (file_put_contents($view, $updated) === false) {
    fail('No se pudo actualizar app_start.php');
}

echo '[OK] Variable SLA renombrada a $slaOverdueCount sin tocar la colección $overdue de Agenda.' . PHP_EOL;

if (run('"' . $php . '" -l "' . $view . '"') !== 0) {
    fail('Falló PHP lint en app_start.php');
}

if (run('"' . $php . '" "' . $test . '"') !== 0) {
    fail('La regresión de scope de Agenda no quedó en verde.');
}

echo '[OK] GREEN confirmado: el shell ya no pisa $overdue de Agenda.' . PHP_EOL;
