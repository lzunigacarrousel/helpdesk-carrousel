<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fails=0;
function ok(bool $cond,string $msg):void{
    global $fails;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    if(!$cond)$fails++;
}
function body(string $path):string{
    $v=@file_get_contents($path);
    return is_string($v)?$v:'';
}

$view=body($root.'/app/Views/admin/users.php');

// Nuevo contrato visual: alta y edición comparten una retícula coherente.
ok((bool)preg_match('/\.admin-access-grid\s*\{[^}]*grid-template-columns:repeat\(3,minmax\(0,1fr\)\)/s',$view), 'Alta usa tres columnas coherentes en escritorio');
ok((bool)preg_match('/\.admin-access-org\s*\{[^}]*grid-template-columns:repeat\(3,minmax\(0,1fr\)\)/s',$view), 'Bloque organizacional usa tres columnas coherentes en escritorio');
ok(str_contains($view,'.admin-user-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))'), 'Edición conserva tres columnas en escritorio');

ok(substr_count($view,'class="admin-account-field"')>=2, 'Qué representa esta cuenta está alineado en alta y edición');
ok(substr_count($view,'class="admin-manager-field"')>=2, 'Responsable directo usa la misma alineación en alta y edición');
ok(str_contains($view,'.admin-manager-field{grid-column:span 2'), 'Responsable directo ocupa ancho amplio sin quedar flotante');
ok(str_contains($view,'.admin-account-field{align-self:start'), 'Qué representa mantiene alineación superior con su fila');

// Responsive: las clases nuevas no deben romper tablet ni móvil.
ok((bool)preg_match('/@media\(max-width:1180px\).*?\.admin-access-grid.*?grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/s',$view), 'Tablet reduce alta a dos columnas');
ok((bool)preg_match('/@media\(max-width:760px\).*?\.admin-access-grid.*?grid-template-columns:1fr/s',$view), 'Móvil reduce alta a una columna');
ok(str_contains($view,'@media(max-width:760px)')&&str_contains($view,'.admin-manager-field{grid-column:auto'), 'Responsable directo vuelve a una columna en móvil');

// Contrato funcional que no cambia.
ok(str_contains($view,'name="requester_entity_type"'), 'Se conserva requester_entity_type');
ok(str_contains($view,'name="manager_user_id"'), 'Se conserva manager_user_id');
ok(str_contains($view,'name="assignment_type"'), 'Se conserva assignment_type');
ok(str_contains($view,"/admin/users/create"), 'Se conserva endpoint de alta');
ok(str_contains($view,"/admin/users/assign"), 'Se conserva endpoint de edición');
ok(str_contains($view,"/admin/users/convertir-externo"), 'Se conserva conversión a proveedor externo');
ok(str_contains($view,"/admin/users/backfill-park-tickets"), 'Se conserva actualización histórica de parque');

if($fails){
    fwrite(STDERR,PHP_EOL."[ERROR] {$fails} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo PHP_EOL."[OK] Regresión alineación de Usuarios completada.".PHP_EOL;
