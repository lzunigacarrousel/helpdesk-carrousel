<?php
declare(strict_types=1);
$root=dirname(__DIR__);$router=file_get_contents($root.'/public/index.php');
$cp=$root.'/app/Controllers/AgendaController.php';$controller=is_file($cp)?file_get_contents($cp):'';
$viewPath=$root.'/app/Views/agenda/index.php';$view=is_file($viewPath)?file_get_contents($viewPath):'';
$cssPath=$root.'/public/assets/css/agenda.css';$css=is_file($cssPath)?file_get_contents($cssPath):'';$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
ok(str_contains($router,'AgendaController'),'Router conoce AgendaController');
ok(str_contains($router,"['GET','/agenda'"),'Existe GET /agenda');
ok($controller!=='','Existe AgendaController');
ok(str_contains($controller,"['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR']"),'Roles permitidos explicitos');
ok(str_contains($controller,'http_response_code(403)'),'No autorizado recibe 403');
ok(str_contains($controller,'AgendaService'),'Controller delega al servicio');
ok(!str_contains($controller,'SELECT '),'Controller no ejecuta SQL');
ok($view!=='','Existe vista Agenda');
ok($css!=='','Existe CSS Agenda');
ok(str_contains($view,'Calendario'),'Selector Calendario');
ok(str_contains($view,'Lista'),'Selector Lista');
ok(str_contains($view,'name="responsible_user_id"'),'Filtro Responsable');
ok(str_contains($view,'name="park_id"'),'Filtro Parque');
ok(str_contains($view,'name="activity_type"'),'Filtro Tipo');
ok(str_contains($view,'name="status"'),'Filtro Estado');
ok(str_contains($view,'name="from"')&&str_contains($view,'name="to"'),'Filtro rango');
ok(str_contains($view,'Pendientes atrasadas'),'Bloque atrasadas');
ok(str_contains($view,'#actividades'),'Enlaces al ticket');
if($errors)exit(1);
