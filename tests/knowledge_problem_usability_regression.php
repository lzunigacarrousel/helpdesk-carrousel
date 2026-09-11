<?php
declare(strict_types=1);
$root=$argv[1]??dirname(__DIR__);
$ok=true;
function chk(bool $c,string $m):void{global $ok;echo ($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;$ok=$ok&&$c;}
function f(string $root,string $rel):string{return (string)@file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel));}
$router=f($root,'public/index.php');
$knowledge=f($root,'app/Controllers/KnowledgeController.php');
$problem=f($root,'app/Controllers/ProblemController.php');
$show=f($root,'app/Views/problems/show.php');
$problemForm=f($root,'app/Views/problems/form.php');
$form=f($root,'app/Views/knowledge/form.php');
chk(str_contains($router,'$path=strtolower($path);')||str_contains($router,'$path=mb_strtolower($path'),'Router normaliza mayúsculas/minúsculas');
chk(!str_contains($knowledge,"throw new \\RuntimeException('Completa un título y contenido suficientemente claros.')"),'Knowledge no convierte validación normal en error 500');
chk(str_contains($knowledge,'knowledge_form_old'),'Knowledge conserva el formulario cuando falta información');
chk(str_contains($knowledge,"Escribe un título de al menos 5 caracteres."),'Knowledge devuelve mensaje específico para título corto');
chk(str_contains($knowledge,"Agrega una explicación de al menos 20 caracteres"),'Knowledge devuelve mensaje específico para contenido corto');
chk(str_contains($form,'knowledge-validation-message'),'Formulario Knowledge muestra el mensaje de validación');
chk(str_contains($form,'minlength="5"'),'Título declara mínimo visible de 5 caracteres');
chk(str_contains($form,'minlength="20"'),'Contenido declara mínimo visible de 20 caracteres');
chk(str_contains($problem,'availableTickets'),'Problema carga tickets existentes disponibles');
chk(str_contains($show,'name="ticket_id"'),'Detalle de problema relaciona mediante ticket_id real');
chk(!str_contains($show,'name="ticket" placeholder="HD-'),'Detalle ya no exige escribir manualmente número de ticket');
chk(str_contains($problemForm,'<select class="form-control" name="related_tickets">'),'Crear problema también ofrece tickets existentes');
chk(!str_contains($problemForm,'placeholder="HD-2026-000120'),'Crear problema ya no exige escribir números manualmente');
exit($ok?0:1);
