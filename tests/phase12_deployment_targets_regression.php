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
ok(str_contains($config,'APP_CANONICAL_CONFIGURED'),'Configuración distingue URL canónica explícita');

foreach(['localhost','94.74.71.96','https://portal.carrousel-apps.com/'] as $target){
    ok(str_contains($readme,$target),'README documenta destino '.$target);
    ok(str_contains($example,$target),'local.php.example documenta destino '.$target);
}

ok(str_contains($readme,'No asumir una subruta final del Helpdesk'),'README evita inventar ruta final');
ok(str_contains($example,'No inventes la subruta'),'Ejemplo evita hardcodear montaje final');
ok(str_contains($readme,'mail_mode=smtp'),'README diferencia SMTP real');
ok(str_contains($readme,'no usar `localhost` como `app_url`'),'README evita localhost en correo a otros equipos');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Destinos de despliegue Fase 12 documentados sin hardcodear el Helpdesk.'.PHP_EOL;
