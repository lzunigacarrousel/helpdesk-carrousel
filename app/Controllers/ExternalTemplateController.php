<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\NotificationService;
use PDO;

final class ExternalTemplateController
{
    private const TEMPLATES=[
        'GENERAL_SUPPORT'=>'Soporte general',
        'SOFTWARE_SUPPORT'=>'Soporte de software',
        'SOFTWARE_DEVELOPMENT'=>'Desarrollo de software',
        'AUDIT_ADVISORY'=>'Auditoría / asesoría',
    ];

    public function updateProviderTemplate(): void
    {
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);

        $userId=(int)Http::post('user_id');
        $reportTemplate=$this->normalizeTemplate(Http::post('report_template'));
        if($userId<=0)$this->reject('Proveedor no válido.');

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT u.id,u.full_name,u.email,ep.report_template
            FROM users u
            JOIN roles r ON r.id=u.role_id AND r.code='EXTERNAL'
            LEFT JOIN external_profiles ep ON ep.user_id=u.id
            WHERE u.id=? AND u.access_type='EXTERNAL' AND u.deleted_at IS NULL LIMIT 1");
        $q->execute([$userId]);
        $before=$q->fetch();
        if(!$before)$this->reject('Ese proveedor ya no está disponible.');

        $pdo->prepare("UPDATE external_profiles SET report_template=?,updated_at=NOW() WHERE user_id=?")
            ->execute([$reportTemplate,$userId]);

        Audit::log('EXTERNAL_REPORT_TEMPLATE_UPDATED','user',$userId,$before,[
            'report_template'=>$reportTemplate,
        ]);
        Flash::set('Plantilla de documentación actualizada.','success');
        header('Location: '.APP_BASE_URL.'/admin/externos#external-provider-'.$userId);
        exit;
    }

    public function grant(): void
    {
        Auth::requirePermission('external.manage');
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        $userId=(int)Http::post('user_id');
        $canComment=Http::post('can_comment','0')==='1'?1:0;
        $canUpload=Http::post('can_upload','0')==='1'?1:0;
        if($ticketId<=0||$userId<=0)throw new \RuntimeException('Selecciona el caso y el proveedor.');

        $pdo=Database::pdo();
        $u=$pdo->prepare("SELECT u.id,u.email,u.full_name,COALESCE(ep.report_template,'GENERAL_SUPPORT') report_template
            FROM users u
            JOIN roles r ON r.id=u.role_id
            LEFT JOIN external_profiles ep ON ep.user_id=u.id
            WHERE u.id=? AND u.access_type='EXTERNAL' AND r.code='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL LIMIT 1");
        $u->execute([$userId]);
        $external=$u->fetch();
        if(!$external)throw new \RuntimeException('Ese usuario no está disponible.');

        $requested=trim(Http::post('report_template'));
        $reportTemplate=$requested!==''?$this->normalizeTemplate($requested):$this->normalizeTemplate((string)$external['report_template']);

        $t=$pdo->prepare('SELECT id,ticket_number,subject FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $t->execute([$ticketId]);
        $ticket=$t->fetch();
        if(!$ticket)throw new \RuntimeException('No encontramos ese caso.');

        $active=$pdo->prepare('SELECT 1 FROM external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL LIMIT 1');
        $active->execute([$ticketId,$userId]);
        if($active->fetchColumn())throw new \RuntimeException('Ese proveedor ya tiene acceso vigente a este caso.');

        Database::transaction(function(PDO $pdo)use($ticketId,$userId,$canComment,$canUpload,$reportTemplate):void{
            $pdo->prepare("UPDATE tickets SET case_type='SPECIAL',visibility_mode='EXTERNAL_ALLOWED',updated_at=NOW() WHERE id=?")
                ->execute([$ticketId]);
            $pdo->prepare("INSERT INTO external_ticket_access(ticket_id,user_id,can_comment,can_upload,report_template,granted_by,granted_at,revoked_at)
                VALUES(?,?,?,?,?,?,NOW(),NULL)
                ON DUPLICATE KEY UPDATE can_comment=VALUES(can_comment),can_upload=VALUES(can_upload),report_template=VALUES(report_template),granted_by=VALUES(granted_by),granted_at=NOW(),revoked_at=NULL")
                ->execute([$ticketId,$userId,$canComment,$canUpload,$reportTemplate,Auth::id()]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at)
                VALUES(?,'EXTERNAL_GRANTED',?,'USER',?,?,NOW())")
                ->execute([
                    $ticketId,
                    Auth::id(),
                    json_encode(['external_user_id'=>$userId],JSON_UNESCAPED_UNICODE),
                    json_encode(['can_comment'=>$canComment,'can_upload'=>$canUpload,'report_template'=>$reportTemplate],JSON_UNESCAPED_UNICODE),
                ]);
        });

        Audit::log('EXTERNAL_TICKET_GRANTED','ticket',$ticketId,null,[
            'external_user_id'=>$userId,
            'can_comment'=>$canComment,
            'can_upload'=>$canUpload,
            'report_template'=>$reportTemplate,
        ]);

        $result=['email_status'=>null];
        try{
            $result=(new NotificationService())->notifyUser(
                (int)$external['id'],(string)$external['email'],$ticketId,'EXTERNAL_GRANTED',
                'Nuevo caso compartido · '.$ticket['ticket_number'],
                'Necesitamos tu apoyo en “'.$ticket['subject'].'”. Abre el caso para revisar el contexto y documentar la atención solicitada.',
                APP_BASE_URL.'/tickets/view?id='.$ticketId,true,true
            );
        }catch(\Throwable $e){Logger::error($e);$result['email_status']='FAILED';}

        $status=$result['email_status']??null;
        $message=match($status){
            'SENT'=>'Caso compartido. Correo enviado correctamente.',
            'SKIPPED'=>'Caso compartido. En PC TEST el correo quedó registrado en modo de prueba.',
            'FAILED'=>'Caso compartido. El correo no pudo enviarse; revisa Correo y notificaciones.',
            default=>'Caso compartido.',
        };
        $type=$status==='FAILED'?'danger':($status==='SKIPPED'?'info':'success');
        Flash::set($message,$type);
        header('Location: '.APP_BASE_URL.'/admin/externos');
        exit;
    }

    private function normalizeTemplate(string $value): string
    {
        $value=strtoupper(trim($value));
        if(!isset(self::TEMPLATES[$value]))$value='GENERAL_SUPPORT';
        return $value;
    }

    private function reject(string $message): never
    {
        Flash::set($message,'danger');
        header('Location: '.APP_BASE_URL.'/admin/externos');
        exit;
    }
}
