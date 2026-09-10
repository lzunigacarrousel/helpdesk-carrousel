<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$path = $root . '/INSTALAR_PC_TEST.bat';
$content = @file_get_contents($path);

$ok = true;
function installerCheck(bool $condition, string $message): void
{
    global $ok;
    echo ($condition ? '[OK] ' : '[FALLO] ') . $message . PHP_EOL;
    $ok = $ok && $condition;
}

installerCheck(is_string($content), 'Se puede leer INSTALAR_PC_TEST.bat');
if (is_string($content)) {
    installerCheck(str_contains($content, 'set "DB_NAME=carrousel_helpdesk"'), 'El instalador opera sobre carrousel_helpdesk');
    installerCheck(str_contains($content, 'set "PROTECTED_DB=helpdesk_carrousel"'), 'La base historica queda declarada como protegida');
    installerCheck(str_contains($content, "findstr /L /C:\"'db_name' => 'carrousel_helpdesk'\" \"config\\local.php\""), 'La configuracion local se valida sin ejecutar PHP dentro de FOR /F');
    installerCheck(!str_contains($content, 'for /f "usebackq delims=" %%D in (`"%PHP%" -r'), 'No existe la validacion PHP/CMD defectuosa');
    installerCheck(str_contains($content, 'if /I "%DB_NAME%"=="%PROTECTED_DB%"'), 'Existe bloqueo explicito contra la base historica');
}

exit($ok ? 0 : 1);
