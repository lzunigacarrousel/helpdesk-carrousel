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

$visual=(string)file_get_contents($root.'/public/assets/css/visual-system.css');
$dark=(string)file_get_contents($root.'/public/assets/css/dark-refinement.css');
$standard=(string)file_get_contents($root.'/docs/ESTANDAR_VISUAL_CARROUSEL.md');

ok(str_contains($standard,'## Patrón de botones y acciones'),'Existe contrato visual canónico de botones');
ok(str_contains($visual,'.btn:not(.btn-sm):not(.theme-btn)'),'Botones normales comparten geometría');
ok(str_contains($visual,'.btn.btn-primary'),'Existe variante principal');
ok(str_contains($visual,'.btn.btn-outline-secondary'),'Existe variante secundaria');
ok(str_contains($visual,'.btn.btn-danger'),'Existe variante destructiva');
ok(str_contains($dark,'html[data-theme="dark"] .btn-danger'),'Modo oscuro conserva variante destructiva');

$targets=[
    $root.'/app/Views/knowledge/show.php'=>[
        'Archivar artículo'=>'btn-danger',
    ],
    $root.'/app/Views/admin/users.php'=>[
        'Retirar acceso'=>'btn-danger',
    ],
    $root.'/app/Views/admin/externals.php'=>[
        'Desactivar proveedor'=>'btn-danger',
        'Revocar acceso'=>'btn-danger',
    ],
    $root.'/app/Views/management/support_team.php'=>[
        'Retirar'=>'btn-danger',
    ],
];

foreach($targets as $file=>$expectations){
    $body=(string)file_get_contents($file);
    foreach($expectations as $label=>$requiredClass){
        $pattern='/<(?:button|a)\b[^>]*class="([^"]*)"[^>]*>\s*'.preg_quote($label,'/').'\s*<\/(?:button|a)>/ui';
        if(!preg_match($pattern,$body,$m)){
            ok(false,basename($file)." contiene acción {$label}");
            continue;
        }
        ok(str_contains($m[1],$requiredClass),basename($file)." usa {$requiredClass} para {$label}");
    }
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Jerarquía visual de botones consistente.'.PHP_EOL;
