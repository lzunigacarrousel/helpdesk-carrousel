<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use App\Services\{MailService,NotificationService};

final class MailAdminController
{
    public function index(): void
    {
        Auth::requirePermission('audit.view');
        $pdo=Database::pdo();
        $stats=[
            'sent_today'=>(int)$pdo->query("SELECT COUNT(*) FROM notification_deliveries WHERE channel='EMAIL' AND status='SENT' AND sent_at>=CURDATE()")->fetchColumn(),
            'failed'=>(int)$pdo->query("SELECT COUNT(*) FROM notification_deliveries WHERE channel='EMAIL' AND status='FAILED'")->fetchColumn(),
            'pending'=>(int)$pdo->query("SELECT COUNT(*) FROM notification_deliveries WHERE channel='EMAIL' AND status='PENDING'")->fetchColumn(),
            'test_today'=>(int)$pdo->query("SELECT COUNT(*) FROM notification_deliveries WHERE channel='EMAIL' AND status='SKIPPED' AND created_at>=CURDATE()")->fetchColumn(),
        ];
        $deliveries=$pdo->query(
            "SELECT d.id,d.recipient_email,d.title,d.status,d.attempts,d.last_error,d.sent_at,d.created_at,e.event_key,e.ticket_id,t.ticket_number
             FROM notification_deliveries d
             JOIN notification_events e ON e.id=d.event_id
             LEFT JOIN tickets t ON t.id=e.ticket_id
             WHERE d.channel='EMAIL'
             ORDER BY d.created_at DESC,d.id DESC LIMIT 80"
        )->fetchAll();
        $health=MailService::configurationHealth();
        $host=(string)(parse_url((string)$health['canonical_url'],PHP_URL_HOST)?:'No definido');
        View::render('admin/mail',[
            'user'=>Auth::user(),'stats'=>$stats,'deliveries'=>$deliveries,'health'=>$health,'canonicalHost'=>$host,'flash'=>Flash::pull(),
            'eventLabels'=>self::eventLabels(),
        ]);
    }

    public function test(): void
    {
        Auth::requirePermission('audit.view');Csrf::verify($_POST['_csrf']??null);
        $email=strtolower(trim(Http::post('email')));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Ingresa un correo válido para la prueba.');
        $pdo=Database::pdo();$q=$pdo->prepare('SELECT id FROM users WHERE LOWER(email)=? AND deleted_at IS NULL LIMIT 1');$q->execute([$email]);$uid=(int)$q->fetchColumn()?:null;
        $result=(new NotificationService())->notifyUser(
            $uid,$email,null,'MAIL_TEST','Prueba de correo del Helpdesk',
            'Este mensaje confirma que el canal de correo está disponible. No necesitas realizar ninguna acción.',
            APP_BASE_URL.'/dashboard',true,false
        );
        $status=(string)($result['email_status']??'');
        Audit::log('MAIL_TEST','notification_delivery',(int)($result['email_delivery_id']??0),null,['recipient_email'=>$email,'status'=>$status]);
        Flash::set(match($status){
            'SENT'=>'Correo de prueba enviado correctamente.',
            'SKIPPED'=>'Modo de prueba activo: el correo quedó registrado en storage/logs/mail.log.',
            'FAILED'=>'La prueba no pudo enviarse. Revisa el estado mostrado en esta pantalla.',
            default=>'La prueba fue registrada.',
        },$status==='FAILED'?'danger':'success');
        header('Location: '.APP_BASE_URL.'/admin/correo');exit;
    }

    public function retry(): void
    {
        Auth::requirePermission('audit.view');Csrf::verify($_POST['_csrf']??null);
        $deliveryId=(int)Http::post('delivery_id');
        $status=(new NotificationService())->retryEmailDelivery($deliveryId);
        Audit::log('MAIL_RETRIED','notification_delivery',$deliveryId,null,['status'=>$status]);
        Flash::set($status==='SENT'?'Correo reenviado correctamente.':'La entrega fue actualizada.','success');
        header('Location: '.APP_BASE_URL.'/admin/correo');exit;
    }

    private static function eventLabels(): array
    {
        return [
            'OTP_REQUESTED'=>'Código de acceso','MAIL_TEST'=>'Prueba de correo','TICKET_CREATED_REQUESTER'=>'Solicitud recibida',
            'TICKET_CREATED_SUPPORT'=>'Nueva solicitud para soporte','TICKET_CLAIMED'=>'Caso tomado','TICKET_REASSIGNED'=>'Responsable actualizado',
            'TICKET_RELEASED'=>'Caso devuelto a cola','STATUS_CHANGED'=>'Cambio de estado','PENDING_REASON_CHANGED'=>'Motivo de espera actualizado',
            'PUBLIC_RESPONSE_ADDED'=>'Nueva respuesta','INTERNAL_NOTE_ADDED'=>'Nota interna','RESOLUTION_RECORDED'=>'Caso resuelto',
            'EXTERNAL_USER_CREATED'=>'Acceso de colaborador','EXTERNAL_GRANTED'=>'Caso compartido','EXTERNAL_REVOKED'=>'Participación finalizada',
        ];
    }
}
