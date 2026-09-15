<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';

function replaceExactlyOnce(string $body,string $search,string $replace,string $label): string
{
    $count=substr_count($body,$search);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba 1 coincidencia y encontro {$count}.".PHP_EOL);
        exit(1);
    }
    return str_replace($search,$replace,$body);
}

$service=(string)file_get_contents($servicePath);
if(!str_contains($service,'function registeredProviders(')){
    $anchor="    public static function providers(array \$rows): array\n    {";
    $method=<<<'PHP'
    public function registeredProviders(): array
    {
        $providers=[];
        $rows=$this->pdo()->query(
            "SELECT u.id,COALESCE(NULLIF(ep.organization_name,''),u.full_name) organization_name
             FROM users u
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.deleted_at IS NULL AND u.access_type='EXTERNAL'
             ORDER BY COALESCE(NULLIF(ep.organization_name,''),u.full_name),u.full_name"
        )->fetchAll();
        foreach($rows as $row){
            $userId=(int)($row['id']??0);
            if($userId<=0)continue;
            $providers[$userId]=(string)($row['organization_name']??'');
        }
        return $providers;
    }

PHP;
    $service=replaceExactlyOnce($service,$anchor,$method.$anchor,'ProviderParticipationService::providers');
    file_put_contents($servicePath,$service);
}

$controller=(string)file_get_contents($controllerPath);
$controller=replaceExactlyOnce(
    $controller,
    "            'providers'=>ProviderParticipationService::providers(\$allRows),",
    "            'providers'=>\$service->registeredProviders(),",
    'catalogo de proveedores del controller'
);
file_put_contents($controllerPath,$controller);

passthru('"'.PHP_BINARY.'" -l "'.$servicePath.'"',$serviceLint);
if($serviceLint!==0)exit($serviceLint);
passthru('"'.PHP_BINARY.'" -l "'.$controllerPath.'"',$controllerLint);
if($controllerLint!==0)exit($controllerLint);

echo '[OK] Catalogo de proveedores desacoplado de las participaciones.'.PHP_EOL;
