<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$serviceBody=is_file($servicePath)?(string)file_get_contents($servicePath):'';
$controllerBody=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';

ok($serviceBody!=='','Existe ProviderParticipationService');
ok($controllerBody!=='','Existe ExternalReportController');
ok(str_contains($serviceBody,'function registeredProviders('),'Servicio expone catalogo de proveedores registrados');
ok(str_contains($controllerBody,'$service->registeredProviders()'),'Informe usa proveedores registrados para el filtro');
ok(!str_contains($controllerBody,'ProviderParticipationService::providers($allRows)'),'Informe no limita el filtro a proveedores con participaciones');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Catalogo de proveedores independiente de participaciones.'.PHP_EOL;
