<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,Flash,View};

final class TicketViewController
{
    private const STATUS_LABELS=['NEW'=>'Nuevo','AVAILABLE'=>'Pendiente de atención','IN_PROGRESS'=>'En proceso','PENDING'=>'En espera','RESOLVED'=>'Resuelto','CLOSED'=>'Cerrado','REOPENED'=>'Reabierto','CANCELLED'=>'Cancelado'];
    private const PRIORITY_LABELS=['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];

    public function show(): void
    {
        Auth::requireLogin();
        $user=Auth::user();

        if(($user['access_type']??'INTERNAL')!=='EXTERNAL'){
            (new TicketController())->show();
            return;
        }

        $id=(int)($_GET['id']??0);
        if($id<=0){
            Flash::set('No encontramos ese caso.','info');
            header('Location: '.APP_BASE_URL.'/mis-tickets');exit;
        }

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name,u.email assigned_email,
                COALESCE(NULLIF(eta.report_template,''),NULLIF(ep.report_template,''),'GENERAL_SUPPORT') report_template
            FROM external_ticket_access eta
            JOIN tickets t ON t.id=eta.ticket_id
            LEFT JOIN external_profiles ep ON ep.user_id=eta.user_id
            LEFT JOIN ticket_categories c ON c.id=t.category_id
            LEFT JOIN parks p ON p.id=t.park_id
            LEFT JOIN areas a ON a.id=t.area_id
            LEFT JOIN users u ON u.id=t.assigned_to
            WHERE eta.ticket_id=? AND eta.user_id=? AND eta.revoked_at IS NULL
              AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED' AND t.deleted_at IS NULL
            LIMIT 1");
        $q->execute([$id,(int)Auth::id()]);
        $ticket=$q->fetch();

        if(!$ticket){
            Flash::set('Ese caso no está disponible para tu cuenta.','info');
            header('Location: '.APP_BASE_URL.'/mis-tickets');exit;
        }

        View::render('tickets/show_external',[
            'user'=>$user,
            'ticket'=>$ticket,
            'flash'=>Flash::pull(),
            'statusLabels'=>self::STATUS_LABELS,
            'priorityLabels'=>self::PRIORITY_LABELS,
        ]);
    }
}
