<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Controllers\{AuthController,DashboardController,AdminController,TicketController};
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
$base=rtrim(APP_PUBLIC_PATH,'/');
if(str_starts_with($path,$base))$path=substr($path,strlen($base))?:'/';
$method=$_SERVER['REQUEST_METHOD']??'GET';
$routes=[
    ['GET','/',[TicketController::class,'publicHome']],
    ['GET','/crear-ticket',[TicketController::class,'publicCreate']],
    ['POST','/crear-ticket',[TicketController::class,'publicStore']],
    ['GET','/ticket-enviado',[TicketController::class,'publicDone']],
    ['GET','/mis-tickets',[TicketController::class,'index']],
    ['GET','/login',[AuthController::class,'home']],
    ['POST','/auth/request',[AuthController::class,'requestOtp']],
    ['GET','/register',[AuthController::class,'register']],
    ['POST','/auth/register',[AuthController::class,'createUser']],
    ['GET','/otp',[AuthController::class,'otp']],
    ['POST','/auth/verify',[AuthController::class,'verify']],
    ['POST','/auth/resend',[AuthController::class,'resend']],
    ['POST','/logout',[AuthController::class,'logout']],
    ['GET','/dashboard',[DashboardController::class,'index']],
    ['GET','/tickets',[TicketController::class,'index']],
    ['GET','/tickets/queue',[TicketController::class,'queue']],
    ['GET','/tickets/view',[TicketController::class,'show']],
    ['POST','/tickets/claim',[TicketController::class,'claim']],
    ['GET','/admin/users',[AdminController::class,'users']],
    ['POST','/admin/users/assign',[AdminController::class,'assign']],
];
foreach($routes as [$m,$p,$h]){
    if($method===$m&&$path===$p){[$c,$a]=$h;(new $c())->$a();exit;}
}
http_response_code(404);
echo '404 - Ruta no encontrada';
