<?php
declare(strict_types=1);
$root=dirname(__DIR__);$router=file_get_contents($root.'/public/index.php');
$cp=$root.'/app/Controllers/AgendaController.php';$controller=is_file($cp)?file_get_contents($cp):'';$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
ok(str_contains($router,'AgendaController'),'Router conoce AgendaController');
ok(str_contains($router,"['GET','/agenda'"),'Existe GET /agenda');
ok($controller!=='','Existe AgendaController');
ok(str_contains($controller,"['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR']"),'Roles permitidos explicitos');
ok(str_contains($controller,'http_response_code(403)'),'No autorizado recibe 403');
ok(str_contains($controller,'AgendaService'),'Controller delega al servicio');
ok(!str_contains($controller,'SELECT '),'Controller no ejecuta SQL');
if($errors)exit(1);
