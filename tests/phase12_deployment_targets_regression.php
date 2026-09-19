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

$config=(string)file_get_contents($root.'/config/config.php');
$example=(string)file_get_contents($root.'/config/local.php.example');
$readme=(string)file_get_contents($root.'/README.md');

ok(str_contains($config,"define('PORTAL_URL', 'https://portal.carrousel-apps.com/portal/')"),'Portal conserva destino canónico');
ok(str_contains($config,"$" . "host = preg_replace"),'APP_BASE_URL deriva del host actual');
ok(str_contains($config,"$" . "basePath = APP_PUBLIC_PATH"),'APP_BASE_URL conserva subruta pública');
ok(str_contains($config,"define('APP_BASE_URL', $" . "scheme.'://'.$" . "host.$" . "basePath)"),'APP_BASE_URL no hardcodea host del Helpdesk');
ok(str_contains($config,"$" . "local['app_url']"),'URL canónica de correo es configurable por entorno');
ok(str_contains($config,"HTTP_X_FORWARDED_PROTO"),'APP_BASE_URL reconoce HTTPS detrás de proxy/túnel');
ok(str_contains($config,"$" . "canonicalHttps"),'Host canónico HTTPS conserva esquema externo aunque Apache reciba HTTP');
ok(str_contains($config,'APP_CANONICAL_CONFIGURED'),'Configuración distingue URL canónica explícita');

foreach([
    'http://localhost/HelpdeskCarrousel/public/',
    'http://94.74.71.96/HelpdeskCarrousel/public/',
    'https://portal.carrousel-apps.com/HelpdeskCarrousel/public/',
] as $target){
    ok(str_contains($readme,$target),'README documenta destino exacto '.$target);
    ok(str_contains($example,$target),'local.php.example documenta destino exacto '.$target);
}

ok(str_contains($readme,'/HelpdeskCarrousel/public/'),'README fija la ruta pública del Helpdesk');
ok(str_contains($example,'SIEMPRE /HelpdeskCarrousel/public/'),'Ejemplo fija la misma ruta en todos los entornos');
ok(str_contains($config,"'/HelpdeskCarrousel/public/index.php'"),'Config conserva fallback con la ruta canónica');
ok(str_contains($readme,'SMTP real Gmail/Outlook')&&str_contains($readme,'`smtp` en producción'),'README diferencia SMTP real');
ok(str_contains($readme,'La aplicación deriva `APP_BASE_URL` del host actual'),'README deja claro que el host se deriva por entorno');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Destinos de despliegue Fase 12 documentados sin hardcodear el Helpdesk.'.PHP_EOL;
