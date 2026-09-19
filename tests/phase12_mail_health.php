<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/HelpdeskCarrousel/public/index.php';
$_SERVER['HTTP_HOST']='localhost';

require_once $root.'/bootstrap.php';

use App\Services\MailService;

$errors=0;
function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$health=MailService::configurationHealth();
$logo=$root.'/public/assets/images/logo.png';

ok(in_array($health['mode'],['log','smtp'],true),'Modo de correo reconocido');
ok(is_file($logo),'Logo local disponible para CID');
ok(filesize($logo)>0,'Logo local no está vacío');
ok(filter_var((string)$health['from'],FILTER_VALIDATE_EMAIL)!==false,'Remitente válido');

if($health['mode']==='smtp'){
    ok((bool)$health['ready'],'Configuración SMTP completa');
    ok(is_file($root.'/vendor/autoload.php'),'Autoload de Composer disponible para SMTP');
    if(is_file($root.'/vendor/autoload.php')){
        require_once $root.'/vendor/autoload.php';
        ok(class_exists('PHPMailer\\PHPMailer\\PHPMailer'),'PHPMailer disponible');
    }
    ok(APP_CANONICAL_CONFIGURED,'SMTP real exige URL canónica configurada');
    if(!APP_CANONICAL_CONFIGURED){
        echo "[ACCION] Configura 'app_url' en config/local.php con la URL estable del Helpdesk que abrirán los destinatarios.".PHP_EOL;
        echo "[ACCION] No uses localhost ni una IP privada/LAN para una prueba SMTP real.".PHP_EOL;
    }
}else{
    echo '[OK] Modo prueba activo: este health check no envía correo.'.PHP_EOL;
}

echo '[INFO] Canal: '.strtoupper((string)$health['mode']).PHP_EOL;
echo '[INFO] URL canónica: '.(string)$health['canonical_url'].PHP_EOL;
foreach($health['warnings'] as $warning)echo '[AVISO] '.$warning.PHP_EOL;
foreach($health['issues'] as $issue)echo '[PROBLEMA] '.$issue.PHP_EOL;

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Salud del canal de correo Fase 12 validada sin enviar mensajes.'.PHP_EOL;
