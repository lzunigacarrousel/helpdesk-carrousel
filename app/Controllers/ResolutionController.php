<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\MailService;
use PDO;

final class ResolutionController
{
    private const TYPES = [
        'CONFIGURATION'=>'Configuración',
        'RESTART'=>'Reinicio / restablecimiento',
        'REPLACEMENT'=>'Cambio o reemplazo',
        'PROVIDER'=>'Gestión con proveedor',
        'USER_GUIDANCE'=>'Orientación al usuario',
        'SOFTWARE'=>'Software / aplicación',
        'NETWORK'=>'Red / conectividad',
        'HARDWARE'=>'Hardware / equipo',
        'PERMISSION'=>'Acceso / permisos',
        'MAINTENANCE'=>'Mantenimiento',
        'OTHER'=>'Otro',
    ];

    public function store(): void
    {
        Auth::requirePermission('tickets.change_status');
        Csrf::verify($_POST['_csrf'] ?? null);

        $ticketId=(int)Http::post('ticket_id');
        $type=strtoupper(trim(Http::post('resolution_type')));
        $rootCause=trim(Http::post('root_cause'));
        $solution=trim(Http::post('solution_applied'));
        $preventive=trim(Http::post('preventive_action'));
        // Toda resolución documentada pasa automáticamente a formar parte del conocimiento reutilizable.
        $reusable=1;

        if($ticketId<=0) throw new \RuntimeException('Caso no válido.');
        if(!isset(self::TYPES[$type])) throw new \RuntimeException('Selecciona cómo se resolvió el caso.');
        if(mb_strlen($rootCause)<5) throw new \RuntimeException('Indica qué originó o causó el problema.');
        if(mb_strlen($solution)<10) throw new \RuntimeException('Describe con claridad qué se hizo para resolverlo.');

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT id,ticket_number,subject,status,assigned_to,requester_email,requester_name FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1");
        $q->execute([$ticketId]);
        $ticket=$q->fetch();
        if(!$ticket) throw new \RuntimeException('No encontramos el caso.');

        if(!Auth::can('tickets.reassign') && (int)($ticket['assigned_to']??0)!==(int)Auth::id()){
            Flash::set('Solo la persona responsable puede registrar la solución de este caso.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);exit;
        }
        if(in_array((string)$ticket['status'],['CLOSED','CANCELLED'],true)){
            Flash::set('Este caso ya está finalizado.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);exit;
        }

        $before=['status'=>$ticket['status']];
        Database::transaction(function(PDO $pdo)use($ticketId,$type,$rootCause,$solution,$preventive,$reusable,$before):void{
            $pdo->prepare("INSERT INTO ticket_resolutions(ticket_id,resolution_type,root_cause,solution_applied,preventive_action,is_reusable,resolved_by,created_at,updated_at)
                VALUES(?,?,?,?,?,?,?,NOW(),NOW())
                ON DUPLICATE KEY UPDATE resolution_type=VALUES(resolution_type),root_cause=VALUES(root_cause),solution_applied=VALUES(solution_applied),preventive_action=VALUES(preventive_action),is_reusable=VALUES(is_reusable),resolved_by=VALUES(resolved_by),updated_at=NOW()")
                ->execute([$ticketId,$type,$rootCause,$solution,$preventive?:null,$reusable,Auth::id()]);
            $pdo->prepare("UPDATE tickets SET status='RESOLVED',resolved_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$ticketId]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
                VALUES(?,'RESOLUTION_RECORDED',?,'USER',?,?,?,NOW())")
                ->execute([$ticketId,Auth::id(),json_encode(['status'=>$before['status']],JSON_UNESCAPED_UNICODE),json_encode(['status'=>'RESOLVED'],JSON_UNESCAPED_UNICODE),json_encode(['resolution_type'=>$type,'reusable'=>true],JSON_UNESCAPED_UNICODE)]);
        });

        Audit::log('TICKET_RESOLUTION_RECORDED','ticket',$ticketId,$before,[
            'status'=>'RESOLVED','resolution_type'=>$type,'is_reusable'=>1
        ]);

        $publicMessage='Tu solicitud fue resuelta.'.PHP_EOL.PHP_EOL.'Solución aplicada: '.$solution;
        $this->notify((string)$ticket['requester_email'],'Solicitud resuelta · '.$ticket['ticket_number'],'Solicitud resuelta',$publicMessage,$ticketId);
        $this->notifyExternal($ticketId,'Caso resuelto',$publicMessage);

        Flash::set('Solución registrada y caso marcado como resuelto.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);exit;
    }

    private function notify(string $email,string $subject,string $title,string $message,int $ticketId):void
    {
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))return;
        try{(new MailService())->sendTicketNotification($email,$subject,$title,$message,APP_BASE_URL.'/tickets/view?id='.$ticketId);}catch(\Throwable $e){Logger::error($e);}
    }

    private function notifyExternal(int $ticketId,string $title,string $message):void
    {
        try{
            $q=Database::pdo()->prepare("SELECT u.email FROM external_ticket_access eta JOIN users u ON u.id=eta.user_id WHERE eta.ticket_id=? AND eta.revoked_at IS NULL AND u.access_type='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL");
            $q->execute([$ticketId]);
            foreach($q->fetchAll() as $row){
                $email=(string)$row['email'];
                $this->notify($email,$title,$title,$message,$ticketId);
            }
        }catch(\Throwable $e){Logger::error($e);}
    }
}
