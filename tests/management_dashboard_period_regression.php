<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$service=(string)file_get_contents($root.'/app/Services/TicketReportFilterService.php');
$controller=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$view=(string)file_get_contents($root.'/app/Views/management/dashboard.php');
$ok=true;

function periodCheck(bool $condition,string $message):void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

periodCheck(str_contains($service,'filters(array $input,?string $defaultFrom=null)'),'Servicio permite inicio predeterminado configurable');
periodCheck(str_contains($service,'$periodStart=$defaultFrom!==null&&$this->validDate($defaultFrom)?$defaultFrom:$monthStart'),'Servicio conserva mes como fallback canónico');
periodCheck(str_contains($controller,"filters(\$_GET,date('Y-01-01'))"),'Dashboard usa inicio del año actual');
periodCheck(substr_count($controller,'$reportFilters->filters($_GET);')>=1,'Informes conservan su comportamiento por defecto');
periodCheck(str_contains($view,'name="from" value="<?= htmlspecialchars($filters[\'from\']) ?>"'),'Dashboard muestra fecha inicial resuelta');
periodCheck(str_contains($view,'name="to" value="<?= htmlspecialchars($filters[\'to\']) ?>"'),'Dashboard muestra fecha final resuelta');
periodCheck(str_contains($view,'href="<?= APP_BASE_URL ?>/gestion">Limpiar</a>'),'Limpiar regresa al rango anual predeterminado');

exit($ok?0:1);
