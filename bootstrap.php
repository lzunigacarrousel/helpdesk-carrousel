<?php
declare(strict_types=1);
require __DIR__.'/config/config.php';
define('APP_ROOT',__DIR__);define('STORAGE_PATH',__DIR__.'/storage');define('CSP_NONCE',bin2hex(random_bytes(18)));
spl_autoload_register(static function(string $class):void{if(!str_starts_with($class,'App\\'))return;$path=APP_ROOT.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($path))require $path;});
header_remove('X-Powered-By');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin');header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
set_exception_handler(static function(Throwable $e):void{\App\Core\Logger::error($e);http_response_code(500);echo '<h1>Error interno</h1><p>Referencia: '.htmlspecialchars(\App\Core\Logger::requestId()).'</p>';});
\App\Core\Auth::bootstrap();
