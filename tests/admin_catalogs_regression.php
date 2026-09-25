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
catalogCheck(str_contains($router,"['GET','/admin/catalogos',[CatalogAdminController::class,'index']]"),'Existe portada de Catálogos');
catalogCheck(str_contains($router,"['GET','/admin/catalogos/regiones',[CatalogAdminController::class,'regions']]"),'Regiones tiene página propia');
catalogCheck(str_contains($router,"['GET','/admin/catalogos/parques',[CatalogAdminController::class,'parks']]"),'Parques tiene página propia');
catalogCheck(str_contains($router,"['GET','/admin/catalogos/areas',[CatalogAdminController::class,'areas']]"),'Áreas tiene página propia');
catalogCheck(str_contains($controller,'public function regions()')&&str_contains($controller,'public function parks()')&&str_contains($controller,'public function areas()'),'Controlador carga cada catálogo de forma independiente');
catalogCheck(str_contains($view,"$section==='home'"),'Vista tiene portada independiente');
catalogCheck(str_contains($view,'data-catalog-home'),'Portada presenta módulos separados');
catalogCheck(str_contains($view,'Administrar regiones')&&str_contains($view,'Administrar parques')&&str_contains($view,'Administrar áreas'),'Portada permite entrar a cada catálogo');
catalogCheck(str_contains($view,"$section==='regions'")&&str_contains($view,"$section==='parks'")&&str_contains($view,"$section==='areas'"),'Solo se renderiza el catálogo seleccionado');
catalogCheck(str_contains($view,'catalog-subnav'),'Páginas internas conservan navegación entre catálogos');
catalogCheck(str_contains($nav,'Catálogos'),'Navegación administrativa expone Catálogos');
catalogCheck(str_contains($nav,'$isAdmin||$canCatalogs'),'Administrador ve Catálogos explícitamente');
catalogCheck(substr_count($view,'data-table-shell')>=3,'Tablas usan componente canónico');
catalogCheck(substr_count($view,'data-table catalog-table')>=3,'Tablas usan geometría canónica');
catalogCheck(str_contains($view,'data-catalog-search'),'Cada listado conserva buscador');
catalogCheck(str_contains($view,'badge-success')&&str_contains($view,'badge-secondary'),'Estados usan badges estándar');
catalogCheck(str_contains($controller,'REGION_CREATED')&&str_contains($controller,'PARK_CREATED')&&str_contains($controller,'AREA_CREATED'),'Altas quedan auditadas');
catalogCheck(str_contains($controller,'REGION_UPDATED')&&str_contains($controller,'PARK_UPDATED')&&str_contains($controller,'AREA_UPDATED'),'Ediciones quedan auditadas');
catalogCheck(str_contains($controller,'REGION_DISABLED')&&str_contains($controller,'PARK_DISABLED')&&str_contains($controller,'AREA_DISABLED'),'Desactivaciones quedan auditadas');
catalogCheck(!preg_match('/DELETE\s+FROM\s+(regions|parks|areas)/i',$controller),'No existe borrado físico de catálogos');
catalogCheck(str_contains($controller,"status NOT IN('RESOLVED','CLOSED','CANCELLED')"),'Parques y áreas protegen tickets abiertos');
catalogCheck(str_contains($controller,"status='ACTIVE' AND ends_at IS NULL"),'Desactivación protege asignaciones activas');
catalogCheck(str_contains($controller,'UPDATE user_assignments SET region_id=? WHERE park_id=?'),'Cambiar región de parque sincroniza asignaciones activas');
catalogCheck(str_contains($schema,"('catalogs.manage','Administrar catalogos'"),'Permiso catalogs.manage sigue siendo canónico');
catalogCheck(str_contains($controller,"'/admin/catalogos/'.$section"),'POST regresa al catálogo específico');

exit($ok?0:1);
