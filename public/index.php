<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Controllers\{AuthController,DashboardController,AdminController,AuditController,TicketController,TicketViewController,ManagementController,XlsxExportController,ExternalController,ResolutionController,ConversationController,SearchController,WorkflowController,ProblemController,KnowledgeController,HelpController};
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
    ['GET','/buscar',[SearchController::class,'index']],
    ['GET','/manual',[HelpController::class,'manual']],
    ['GET','/tickets',[TicketController::class,'index']],
    ['GET','/tickets/queue',[TicketController::class,'queue']],
    ['GET','/tickets/view',[TicketViewController::class,'show']],
    ['POST','/tickets/claim',[TicketController::class,'claim']],
    ['POST','/tickets/assign',[TicketController::class,'assign']],
    ['POST','/tickets/release',[TicketController::class,'release']],
    ['POST','/tickets/status',[WorkflowController::class,'changeStatus']],
    ['POST','/tickets/resolve',[ResolutionController::class,'store']],
    ['POST','/tickets/respond',[ConversationController::class,'respond']],
    ['GET','/tickets/attachment',[ConversationController::class,'download']],

    ['GET','/problems',[ProblemController::class,'index']],
    ['GET','/problems/new',[ProblemController::class,'form']],
    ['POST','/problems/create',[ProblemController::class,'create']],
    ['GET','/problems/view',[ProblemController::class,'view']],
    ['POST','/problems/update',[ProblemController::class,'update']],
    ['POST','/problems/link-ticket',[ProblemController::class,'linkTicket']],
    ['POST','/problems/unlink-ticket',[ProblemController::class,'unlinkTicket']],
    ['POST','/problems/link-article',[ProblemController::class,'linkArticle']],
    ['POST','/problems/unlink-article',[ProblemController::class,'unlinkArticle']],

    ['GET','/knowledge',[KnowledgeController::class,'index']],
    ['GET','/knowledge/new',[KnowledgeController::class,'form']],
    ['GET','/knowledge/edit',[KnowledgeController::class,'form']],
    ['POST','/knowledge/create',[KnowledgeController::class,'create']],
    ['POST','/knowledge/update',[KnowledgeController::class,'update']],
    ['GET','/knowledge/view',[KnowledgeController::class,'view']],
    ['POST','/knowledge/publish',[KnowledgeController::class,'publish']],
    ['POST','/knowledge/archive',[KnowledgeController::class,'archive']],

    ['GET','/gestion',[ManagementController::class,'dashboard']],
    ['GET','/gestion/informes',[ManagementController::class,'reports']],
    ['GET','/gestion/informes/exportar',[XlsxExportController::class,'export']],
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