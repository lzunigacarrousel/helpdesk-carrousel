<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$css=(string)@file_get_contents($root.'/public/assets/css/dark-refinement.css');
$ok=true;

function darkCheck(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

darkCheck(str_contains($css,'html[data-theme="dark"] .public-request-topbar'),'Reportar problema tiene topbar oscuro');
darkCheck(str_contains($css,'html[data-theme="dark"] .public-request-card'),'Reportar problema tiene tarjetas oscuras');
darkCheck(str_contains($css,'html[data-theme="dark"] .public-panel'),'Inicio público tiene panel oscuro');
darkCheck(str_contains($css,'html[data-theme="dark"] .choice-card'),'Inicio público tiene opciones oscuras');
darkCheck(str_contains($css,'html[data-theme="dark"] .public-result-card'),'Confirmación pública tiene tarjeta oscura');
darkCheck(str_contains($css,'html[data-theme="dark"] .friendly-error-card'),'Error amigable tiene tarjeta oscura');
darkCheck(str_contains($css,'html[data-theme="dark"] .auth-layout-v2'),'Acceso/OTP/registro usan layout oscuro');

exit($ok?0:1);
