<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$controllerPath=$root.'/app/Controllers/ExternalReportController.php';

function readNormalized(string $path): string
{
    if(!is_file($path)){
        fwrite(STDERR,"[ERROR] No existe {$path}.".PHP_EOL);
        exit(1);
    }
    return str_replace("\r\n","\n",(string)file_get_contents($path));
}

$service=readNormalized($servicePath);
if(!str_contains($service,'function registeredProviders(')){
    $anchor='    public static function providers(array $rows): array';
    $position=strpos($service,$anchor);
    if($position===false){
        fwrite(STDERR,'[ERROR] No se encontro el metodo providers() donde insertar registeredProviders().'.PHP_EOL);
        exit(1);
    }

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

    $service=substr($service,0,$position).$method.substr($service,$position);
    if(file_put_contents($servicePath,$service)===false){
        fwrite(STDERR,'[ERROR] No se pudo escribir ProviderParticipationService.php'.PHP_EOL);
        exit(1);
    }
    echo '[OK] Agregado registeredProviders() al servicio.'.PHP_EOL;
}else{
    echo '[OK] registeredProviders() ya existe en el servicio.'.PHP_EOL;
}

$controller=readNormalized($controllerPath);
$new="            'providers'=>\$service->registeredProviders(),";
if(!str_contains($controller,$new)){
    $old="            'providers'=>ProviderParticipationService::providers(\$allRows),";
    $count=substr_count($controller,$old);
    if($count!==1){
        fwrite(STDERR,"[ERROR] Controller: esperaba 1 referencia al catalogo derivado y encontro {$count}.".PHP_EOL);
        exit(1);
    }
    $controller=str_replace($old,$new,$controller);
    if(file_put_contents($controllerPath,$controller)===false){
        fwrite(STDERR,'[ERROR] No se pudo escribir ExternalReportController.php'.PHP_EOL);
        exit(1);
    }
    echo '[OK] Controller usa catalogo de proveedores registrados.'.PHP_EOL;
}else{
    echo '[OK] Controller ya usa catalogo de proveedores registrados.'.PHP_EOL;
}

passthru('"'.PHP_BINARY.'" -l "'.$servicePath.'"',$serviceLint);
if($serviceLint!==0)exit($serviceLint);
passthru('"'.PHP_BINARY.'" -l "'.$controllerPath.'"',$controllerLint);
if($controllerLint!==0)exit($controllerLint);

echo '[OK] Catalogo de proveedores desacoplado de las participaciones.'.PHP_EOL;
