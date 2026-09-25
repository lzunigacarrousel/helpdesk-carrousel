<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$ok=true;
function catalogCheck(bool $condition,string $message):void{global $ok;echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;$ok=$ok&&$condition;}

$controller=(string)file_get_contents($root.'/app/Controllers/CatalogAdminController.php');
$view=(string)file_get_contents($root.'/app/Views/admin/catalogs.php');
$router=(string)file_get_contents($root.'/public/index.php');
$nav=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$schema=(string)file_get_contents($root.'/database/INSTALAR.sql');

catalogCheck(str_contains($controller,"requirePermission('catalogs.manage')"),'Controlador exige catalogs.manage');
catalogCheck(str_contains($router,'/admin/catalogos'),'Router expone administración de catálogos');
catalogCheck(str_contains($nav,'Catálogos'),'Navegación administrativa expone Catálogos');
catalogCheck(str_contains($view,'id="regiones"')&&str_contains($view,'id="parques"')&&str_contains($view,'id="areas"'),'Vista administra Regiones, Parques y Áreas');
catalogCheck(str_contains($view,'data-catalog-search'),'Catálogos tienen buscadores');
catalogCheck(str_contains($view,'Activos')&&str_contains($view,'Inactivos'),'Catálogos filtran activos/inactivos');
catalogCheck(str_contains($controller,'REGION_CREATED')&&str_contains($controller,'PARK_CREATED')&&str_contains($controller,'AREA_CREATED'),'Altas quedan auditadas');
catalogCheck(str_contains($controller,'REGION_UPDATED')&&str_contains($controller,'PARK_UPDATED')&&str_contains($controller,'AREA_UPDATED'),'Ediciones quedan auditadas');
catalogCheck(str_contains($controller,'REGION_DISABLED')&&str_contains($controller,'PARK_DISABLED')&&str_contains($controller,'AREA_DISABLED'),'Desactivaciones quedan auditadas');
catalogCheck(str_contains($controller,'UPDATE regions SET is_active=?')&&str_contains($controller,'UPDATE parks SET is_active=?')&&str_contains($controller,'UPDATE areas SET is_active=?'),'Baja lógica usa is_active');
catalogCheck(!preg_match('/DELETE\s+FROM\s+(regions|parks|areas)/i',$controller),'No existe borrado físico de catálogos');
catalogCheck(str_contains($controller,"status NOT IN('RESOLVED','CLOSED','CANCELLED')"),'Parques y áreas protegen tickets abiertos');
catalogCheck(str_contains($controller,"status='ACTIVE' AND ends_at IS NULL"),'Desactivación protege asignaciones activas');
catalogCheck(str_contains($controller,'UPDATE user_assignments SET region_id=? WHERE park_id=?'),'Cambiar región de parque sincroniza asignaciones activas');
catalogCheck(str_contains($schema,"('catalogs.manage','Administrar catalogos'"),'Permiso catalogs.manage sigue siendo canónico');
catalogCheck(str_contains($view,'El código interno se genera automáticamente'),'UI no obliga a manipular códigos internos');

exit($ok?0:1);
