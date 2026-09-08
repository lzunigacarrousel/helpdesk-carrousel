<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Controllers\{AuthController,DashboardController,AdminController,AuditController,TicketController,ManagementController,ExternalController,ResolutionController};
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
    ['POST','/tickets/assign',[TicketController::class,'assign']],
    ['POST','/tickets/release',[TicketController::class,'release']],
    ['POST','/tickets/status',[TicketController::class,'changeStatus']],
    ['POST','/tickets/resolve',[ResolutionController::class,'store']],
    ['GET','/gestion',[ManagementController::class,'dashboard']],
    ['GET','/gestion/informes',[ManagementController::class,'reports']],
    ['GET','/gestion/informes/exportar',[ManagementController::class,'export']],
    ['GET','/admin/users',[AdminController::class,'users']],
    ['POST','/admin/users/assign',[AdminController::class,'assign']],
    ['GET','/admin/externos',[ExternalController::class,'index']],
    ['POST','/admin/externos/crear',[ExternalController::class,'createUser']],
    ['POST','/admin/externos/asignar',[ExternalController::class,'grant']],
    ['POST','/admin/externos/revocar',[ExternalController::class,'revoke']],
    ['GET','/admin/audit',[AuditController::class,'index']],
];
foreach($routes as [$m,$p,$h]){
    if($method===$m&&$path===$p){[$c,$a]=$h;(new $c())->$a();exit;}
}
http_response_code(404);
\App\Core\View::render('errors/friendly',['user'=>\App\Core\Auth::user(),'title'=>'Página no disponible','message'=>'La página que intentaste abrir no está disponible o cambió de ubicación.']);
