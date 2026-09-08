<?php
declare(strict_types=1);
define('APP_NAME','Helpdesk Carrousel 360');
define('APP_VERSION','1.0.0-phase1');
define('APP_PUBLIC_PATH','/Helpdesk360/public');
define('PORTAL_URL','https://portal.carrousel-apps.com/portal/');
date_default_timezone_set('America/Guatemala');
$localFile=__DIR__.'/local.php';
if(!is_file($localFile)){throw new RuntimeException('Falta config/local.php. Copia local.php.example y ajusta PC TEST.');}
$local=require $localFile;
define('DB_HOST',(string)($local['db_host']??'127.0.0.1'));
define('DB_NAME',(string)($local['db_name']??'helpdesk360_test'));
define('DB_USER',(string)($local['db_user']??'root'));
define('DB_PASS',(string)($local['db_pass']??''));
define('DB_CHARSET','utf8mb4');
define('MAIL_MODE',(string)($local['mail_mode']??'log'));
define('SMTP_HOST',(string)($local['smtp_host']??''));
define('SMTP_PORT',(int)($local['smtp_port']??587));
define('SMTP_SECURE',(string)($local['smtp_secure']??'tls'));
define('SMTP_USERNAME',(string)($local['smtp_username']??''));
define('SMTP_PASSWORD',(string)($local['smtp_password']??''));
define('MAIL_FROM',(string)($local['mail_from']??'no-reply@carrousel.local'));
define('MAIL_FROM_NAME',(string)($local['mail_from_name']??APP_NAME));
$https=(!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off');
$host=preg_replace('/:\\d+$/','',(string)($_SERVER['HTTP_HOST']??'localhost')) ?: 'localhost';
$scheme=$https?'https':'http';
define('APP_BASE_URL',$scheme.'://'.$host.APP_PUBLIC_PATH);
define('APP_CAN_USE_SECURE_FEATURES',$https||in_array($host,['localhost','127.0.0.1'],true));
ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.cookie_httponly','1');
session_name('helpdesk360_session');
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(['lifetime'=>50400,'path'=>rtrim(APP_PUBLIC_PATH,'/').'/', 'domain'=>'','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);session_start();}
