<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\NotificationService;
use PDO;

final class ResolutionController
{
    private const TYPES=[
        'CONFIGURATION'=>'Configuración','RESTART'=>'Reinicio / restablecimiento','REPLACEMENT'=>'Cambio o reemplazo','PROVIDER'=>'Gestión con proveedor',
        'USER_GUIDANCE'=>'Orientación al usuario','SOFTWARE'=>'Software / aplicación','NETWORK'=>'Red / conectividad','HARDWARE'=>'Hardware / equipo',
        'PERMISSION'=>'Acceso / permisos','MAINTENANCE'=>'Mantenimiento','OTHER'=>'Otro',
    ];

    public function index(): void
    {
        Auth::requirePermission('tickets.resolve');
        $ticketId=(int)($_GET['ticket_id']??0);
        Flash::set('Para resolver un caso, ábrelo y completa la sección “Documentar solución”.','info');
        if($ticketId>0)$this->redirectTicket($ticketId);
        $this->redirectQueue();
    }

    public function store(): void
    {
        Auth::requirePermission('tickets.resolve');
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        if($ticketId<=0){
            Flash::set('No pudimos identificar el caso que deseas resolver.','warning');
            $this->redirectQueue();
        }

        $type=strtoupper(trim(Http::post('resolution_type')));
        $rootCause=trim(Http::post('root_cause'));
        $solution=trim(Http::post('solution_applied'));
        $preventive=trim(Http::post('preventive_action'));

        if(!isset(self::TYPES[$type]))$this->fail($ticketId,'Selecciona cómo se resolvió el caso.');
        if(mb_strlen($rootCause)<5)$this->fail($ticketId,'Explica brevemente qué originó el problema.');
        if(mb_strlen($solution)<10)$this->fail($ticketId,'Describe con un poco más de detalle qué se hizo para resolverlo.');
        if(mb_strlen($preventive)>4000)$this->fail($ticketId,'La recomendación preventiva es demasiado extensa.');

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT id,ticket_number,subject,status,assigned_to,requester_email,requester_name FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1");
        $q->execute([$ticketId]);
        $ticket=$q->fetch();
        if(!$ticket)$this->fail($ticketId,'No encontramos el caso.');

        if(!Auth::can('tickets.reassign')&&(int)($ticket['assigned_to']??0)!==(int)Auth::id()){
            $this->fail($ticketId,'Solo la persona responsable puede registrar la solución de este caso.','info');
        }
        if(in_array((string)$ticket['status'],['CLOSED','CANCELLED'],true)){
            $this->fail($ticketId,'Este caso ya está finalizado.','info');
        }

        $before=['status'=>$ticket['status']];
        try{
            Database::transaction(function(PDO $pdo)use($ticketId,$type,$rootCause,$solution,$preventive,$before):void{
                $pdo->prepare("INSERT INTO ticket_resolutions(ticket_id,resolution_type,root_cause,solution_applied,preventive_action,is_reusable,resolved_by,created_at,updated_at)
                    VALUES(?,?,?,?,?,1,?,NOW(),NOW())
                    ON DUPLICATE KEY UPDATE resolution_type=VALUES(resolution_type),root_cause=VALUES(root_cause),solution_applied=VALUES(solution_applied),preventive_action=VALUES(preventive_action),is_reusable=1,resolved_by=VALUES(resolved_by),updated_at=NOW()")
                    ->execute([$ticketId,$type,$rootCause,$solution,$preventive!==''?$preventive:null,Auth::id()]);

                $pdo->prepare("UPDATE tickets SET status='RESOLVED',pending_reason_code=NULL,pending_note=NULL,resolved_at=NOW(),updated_at=NOW() WHERE id=?")
                    ->execute([$ticketId]);

                $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
                    VALUES(?,'RESOLUTION_RECORDED',?,'USER',?,?,?,NOW())")
                    ->execute([
                        $ticketId,
                        Auth::id(),
                        json_encode(['status'=>$before['status']],JSON_UNESCAPED_UNICODE),
                        json_encode(['status'=>'RESOLVED'],JSON_UNESCAPED_UNICODE),
                        json_encode(['resolution_type'=>$type,'reusable'=>true],JSON_UNESCAPED_UNICODE),
                    ]);
            });
        }catch(\Throwable $e){
            Logger::error($e);
            $this->fail($ticketId,'No pudimos guardar la solución. Intenta nuevamente; si continúa, revisa el registro técnico de la aplicación.','warning');
        }

        Audit::log('TICKET_RESOLUTION_RECORDED','ticket',$ticketId,$before,['status'=>'RESOLVED','resolution_type'=>$type,'is_reusable'=>1]);
        try{
            (new NotificationService())->publishTicket(
                $ticketId,'RESOLUTION_RECORDED','Caso resuelto · '.$ticket['ticket_number'],
                'El caso fue resuelto. Solución aplicada: '.$solution,
                ['requester','externals','admins'],APP_BASE_URL.'/tickets/view?id='.$ticketId,
                ['resolution_type'=>$type,'root_cause'=>$rootCause]
            );
        }catch(\Throwable $e){Logger::error($e);}

        Flash::set('Solución guardada. El caso quedó resuelto.','success');
        $this->redirectTicket($ticketId);
    }

    private function fail(int $ticketId,string $message,string $type='warning'): never
    {
        Flash::set($message,$type);
        $this->redirectTicket($ticketId);
    }

    private function redirectTicket(int $ticketId): never
    {
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);
        exit;
    }

    private function redirectQueue(): never
    {
        header('Location: '.APP_BASE_URL.'/tickets/queue');
        exit;
    }
}