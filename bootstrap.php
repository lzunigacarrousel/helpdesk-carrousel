<?php
declare(strict_types=1);
require __DIR__.'/config/config.php';
define('APP_ROOT',__DIR__);define('STORAGE_PATH',__DIR__.'/storage');define('CSP_NONCE',bin2hex(random_bytes(18)));

// Nunca exponer warnings/notices técnicos en la interfaz. Se conservan en el log del servidor.
ini_set('display_errors','0');
ini_set('display_startup_errors','0');
ini_set('log_errors','1');
error_reporting(E_ALL);

spl_autoload_register(static function(string $class):void{if(!str_starts_with($class,'App\\'))return;$path=APP_ROOT.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($path))require $path;});
header_remove('X-Powered-By');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin');header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-".CSP_NONCE."'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
set_exception_handler(static function(Throwable $e):void{
    \App\Core\Logger::error($e);
    http_response_code(500);
    $errorTitle='No pudimos completar la acción';
    $errorMessage='Intenta nuevamente. Si el inconveniente continúa, comunícate con el equipo de Sistemas.';
    $view=APP_ROOT.'/app/Views/errors/friendly.php';
    if(is_file($view)){require $view;return;}
    echo '<h1>No pudimos completar la acción</h1><p>Intenta nuevamente.</p>';
});
\App\Core\Auth::bootstrap();