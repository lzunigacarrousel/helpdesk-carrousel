<?php
declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/app/Services/ProviderParticipationService.php';
require_once $root.'/app/Services/ProviderRatingService.php';

use App\Services\ProviderParticipationService;
use App\Services\ProviderRatingService;

$errors=0;
function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$rows=[
    [
        'ticket_id'=>101,'user_id'=>10,'organization'=>'Proveedor Uno','contact'=>'A','email'=>'a@example.test',
        'ticket_number'=>'HD-1','subject'=>'Caso 1','granted_at'=>'2026-09-01 08:00:00','revoked_at'=>'2026-09-01 10:00:00',
        'work_status'=>'READY_FOR_REVIEW','provider_rating_score'=>5,'provider_rating_label'=>'Excelente',
    ],
    [
        'ticket_id'=>102,'user_id'=>10,'organization'=>'Proveedor Uno','contact'=>'A','email'=>'a@example.test',
        'ticket_number'=>'HD-2','subject'=>'Caso 2','granted_at'=>'2026-09-02 08:00:00','revoked_at'=>'2026-09-02 10:00:00',
        'work_status'=>'ANALYSIS','provider_rating_score'=>3,'provider_rating_label'=>'Adecuado',
    ],
    [
        'ticket_id'=>103,'user_id'=>10,'organization'=>'Proveedor Uno','contact'=>'A','email'=>'a@example.test',
        'ticket_number'=>'HD-3','subject'=>'Caso 3','granted_at'=>'2026-09-03 08:00:00','revoked_at'=>'2026-09-03 10:00:00',
        'work_status'=>null,'provider_rating_score'=>null,'provider_rating_label'=>'Sin evaluar',
    ],
    [
        'ticket_id'=>104,'user_id'=>10,'organization'=>'Proveedor Uno','contact'=>'A','email'=>'a@example.test',
        'ticket_number'=>'HD-4','subject'=>'Caso 4','granted_at'=>'2026-09-04 08:00:00','revoked_at'=>'2026-09-04 10:00:00',
        'work_status'=>'VALIDATING','provider_rating_score'=>4,'provider_rating_label'=>'Bueno',
        'provider_rating_comment'=>'Corrección vigente','provider_rating_revisions'=>1,
    ],
];

$filtered=ProviderParticipationService::applyFilters($rows,['rating'=>'UNRATED']);
ok(count($filtered)===1,'Filtro Sin evaluar selecciona solo ciclos sin rating');
ok(isset($filtered[0])&&array_key_exists('provider_rating_score',$filtered[0])&&$filtered[0]['provider_rating_score']===null,'Filtro Sin evaluar conserva ciclo no evaluado');

$filtered=ProviderParticipationService::applyFilters($rows,['rating'=>'4']);
ok(count($filtered)===1,'Filtro 4 estrellas usa valoración vigente');
ok((int)($filtered[0]['ticket_id']??0)===104,'Filtro 4 estrellas conserva ciclo correcto');

$summary=ProviderRatingService::providerSummary($rows);
ok(isset($summary[10]),'Resumen por proveedor existe');
ok((float)($summary[10]['average_score']??0)===4.0,'Promedio usa solo ratings vigentes y evaluados');
ok((int)($summary[10]['rated_cycles']??0)===3,'Cuenta ciclos evaluados');
ok((int)($summary[10]['unrated_cycles']??0)===1,'Cuenta ciclos sin evaluar');

$controllerPath=$root.'/app/Controllers/ExternalReportController.php';
$viewPath=$root.'/app/Views/management/external_report.php';
$adminViewPath=$root.'/app/Views/admin/externals.php';
$controllerBody=is_file($controllerPath)?(string)file_get_contents($controllerPath):'';
$viewBody=is_file($viewPath)?(string)file_get_contents($viewPath):'';
$adminViewBody=is_file($adminViewPath)?(string)file_get_contents($adminViewPath):'';

ok(str_contains($controllerBody,'ProviderRatingService'),'Informe usa ProviderRatingService');
ok(str_contains($controllerBody,'enrichRows('),'Informe enriquece participaciones antes de filtrar');
ok(str_contains($controllerBody,"'rating'"),'Controller normaliza filtro de valoración');
ok(str_contains($controllerBody,'ratingOptions'),'Controller expone opciones de valoración');
ok(str_contains($controllerBody,'providerSummary'),'Controller construye resumen por proveedor');
ok(str_contains($controllerBody,"'providerRatingSummary'"),'Controller expone resumen de calidad a la vista');

ok(str_contains($viewBody,'name="rating"'),'Vista expone filtro valoración');
ok(str_contains($viewBody,'Promedio de calidad'),'Vista muestra promedio de calidad');
ok(str_contains($viewBody,'Ciclos evaluados'),'Vista muestra ciclos evaluados');
ok(str_contains($viewBody,'Sin evaluar'),'Vista muestra ciclos sin evaluar');
ok(str_contains($viewBody,'provider_rating_score'),'Tabla muestra valoración vigente');
ok(str_contains($viewBody,'providerRatingSummary'),'Vista consume resumen por proveedor');

ok(str_contains($controllerBody,"'Valoración proveedor'"),'XLSX exporta score de proveedor');
ok(str_contains($controllerBody,"'Comentario valoración'"),'XLSX exporta comentario interno');
ok(str_contains($controllerBody,"'Promedio calidad'"),'XLSX incluye promedio de calidad por proveedor');
ok(str_contains($controllerBody,"'Ciclos evaluados'"),'XLSX incluye ciclos evaluados');
ok(str_contains($controllerBody,"'Ciclos sin evaluar'"),'XLSX incluye ciclos sin evaluar');

ok(str_contains($adminViewBody,'/admin/externos/informe?provider='),'Directorio enlaza historial filtrado por proveedor');
ok(str_contains($adminViewBody,'>Historial</a>'),'Directorio muestra acción Historial por proveedor');
ok(str_contains($adminViewBody,'/admin/externos/informe/exportar?provider='),'Directorio enlaza Excel filtrado por proveedor');
ok(str_contains($adminViewBody,'>Excel</a>'),'Directorio muestra acción Excel por proveedor');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Informe de calidad de proveedores.'.PHP_EOL;
