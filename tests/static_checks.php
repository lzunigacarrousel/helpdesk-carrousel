<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=[
    'bootstrap.php','config/config.php','config/local.php.example','database/INSTALAR_FASE1.sql',
    'database/ACTUALIZAR_FLUJO_ESPERA_P1.sql','database/VERIFICAR_ESTABILIDAD_V2.sql',
    'app/Services/AuthService.php','app/Services/ScopeService.php','app/Core/Auth.php',
    'app/Controllers/SearchController.php','app/Controllers/WorkflowController.php',
    'app/Views/search/index.php','public/index.php','HELPDESK_ADMIN.bat'
];
$ok=true;
foreach($required as $f){$exists=is_file($root.'/'.$f);echo ($exists?'[OK] ':'[FALTA] ').$f.PHP_EOL;$ok=$ok&&$exists;}
exit($ok?0:1);
