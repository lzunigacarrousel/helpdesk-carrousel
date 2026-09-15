<?php
declare(strict_types=1);
$root=dirname(__DIR__);$path=$root.'/app/Services/AgendaService.php';
$body=is_file($path)?file_get_contents($path):'';$errors=0;
function ok(bool $c,string $m):void{global $errors;echo($c?'[OK] ':'[FALLO] ').$m.PHP_EOL;if(!$c)$errors++;}
final class AgendaRegressionStatement
{
    public function execute(array $params): void{}
    public function fetchAll(): array
    {
        return [[
            'activity_id'=>99,'ticket_id'=>7,'ticket_code'=>'T-7','ticket_subject'=>'Vencida',
            'activity_type'=>'OTRA','status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-01 09:00:00',
            'scheduled_end_at'=>'2026-09-01 10:00:00','responsible_user_id'=>1,'responsible_name'=>'Soporte',
            'park_id'=>1,'park_name'=>'Parque',
        ]];
    }
}
final class AgendaRegressionPdo
{
    public function prepare(string $sql): AgendaRegressionStatement{return new AgendaRegressionStatement;}
}
if(!class_exists('App\\Core\\Auth',false)){
    eval('namespace App\\Core; final class Auth { public static function role(): ?string{return "ADMIN";} public static function id(): ?int{return 1;} } final class Database { public static function pdo(): object{return new \\AgendaRegressionPdo;} }');
    eval('namespace App\\Services; final class ScopeService { public function ticketConstraint(string $alias="t", ?int $userId=null): array{return["1=1",[]];} }');
}
if(!defined('APP_BASE_URL'))define('APP_BASE_URL','');
ok($body!=='','Existe AgendaService');
ok(str_contains($body,'function activities('),'Expone activities');
ok(str_contains($body,'function overdueBefore('),'Expone overdueBefore');
ok(str_contains($body,'function filterOptions('),'Expone filterOptions');
ok(str_contains($body,'function searchTickets('),'Expone searchTickets');
ok(str_contains($body,'function markConflicts('),'Expone markConflicts');
ok(str_contains($body,'function hourWindow('),'Expone hourWindow');
ok(!str_contains($body,'INSERT INTO ticket_activities'),'Agenda no crea actividades');
ok(!str_contains($body,'UPDATE ticket_activities'),'Agenda no modifica actividades');
ok(str_contains($body,'new ScopeService'),'Usa ScopeService');
ok(str_contains($body,'JOIN tickets t ON t.id=a.ticket_id'),'Une actividades con tickets');
ok(str_contains($body,'t.deleted_at IS NULL'),'Excluye tickets eliminados');
ok(str_contains($body,'a.responsible_user_id'),'Filtra responsable');
ok(str_contains($body,'a.park_id'),'Filtra parque');
ok(str_contains($body,'a.activity_type'),'Filtra tipo');
ok(str_contains($body,'PROGRAMADA'),'Contempla estado PROGRAMADA');
ok(str_contains($body,'EN_CURSO'),'Contempla estado EN_CURSO');
ok(str_contains($body,'FINALIZADA'),'Contempla historial FINALIZADA');
ok(str_contains($body,'CANCELADA'),'Contempla historial CANCELADA');
ok(str_contains($body,'#actividades'),'Construye URL al bloque de actividades');
ok(str_contains($body,'LIMIT')&&str_contains($body,'searchTickets'),'Búsqueda de tickets es limitada');
if($body!==''){
    require_once $path;
    $rows=[
        ['activity_id'=>1,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:00:00','scheduled_end_at'=>'2026-09-14 10:00:00'],
        ['activity_id'=>2,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
        ['activity_id'=>3,'responsible_user_id'=>10,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 10:30:00','scheduled_end_at'=>'2026-09-14 11:00:00'],
        ['activity_id'=>4,'responsible_user_id'=>11,'status'=>'PROGRAMADA','scheduled_start_at'=>'2026-09-14 09:30:00','scheduled_end_at'=>'2026-09-14 10:30:00'],
    ];
    $marked=\App\Services\AgendaService::markConflicts($rows);
    ok($marked[0]['has_conflict']===true,'Solapamiento marca A');
    ok($marked[1]['has_conflict']===true,'Solapamiento marca B');
    ok($marked[2]['has_conflict']===false,'Límite exacto no es conflicto');
    ok($marked[3]['has_conflict']===false,'Responsable distinto no es conflicto');
    ok(\App\Services\AgendaService::hourWindow([])===['start_hour'=>8,'end_hour'=>18],'Ventana vacía 08–18');
    $w=\App\Services\AgendaService::hourWindow([['scheduled_start_at'=>'2026-09-14 06:20:00','scheduled_end_at'=>'2026-09-14 19:15:00']]);
    ok($w===['start_hour'=>5,'end_hour'=>21],'Ventana se expande y redondea');
    $overdue=(new \App\Services\AgendaService())->overdueBefore('2026-09-14',['status'=>'FINALIZADA','history'=>0]);
    ok($overdue===[],'Estado histórico explícito sin historial excluye atrasadas');
}
if($errors){fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);exit(1);}echo '[OK] Contrato base AgendaService.'.PHP_EOL;
