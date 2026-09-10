<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$entry=(string)@file_get_contents($root.'/public/assets/css/dark-entry.css');
$ok=true;

function checkEntry(bool $condition,string $message): void{
    global $ok;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    $ok=$ok&&$condition;
}

checkEntry($entry!=='','Existe dark-entry.css');
checkEntry(str_contains($entry,"@import url('./dark-refinement.css');"),'La entrada oscura consume dark-refinement.css');

exit($ok?0:1);
