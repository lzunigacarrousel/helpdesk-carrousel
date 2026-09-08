<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use PDO;

final class TicketController
{
    public function index(): void
    {
        Auth::requireLogin();
        $pdo = Database::pdo();
        $uid = (int) Auth::id();
        $canAll = Auth::can('tickets.view.all');
        $canAssigned = Auth::can('tickets.view.assigned');

        if ($canAll) {
            $sql = "SELECT t.*,c.name category_name,p.name park_name,a.name area_name,
                           u.name requester_name,au.name assigned_name
                    FROM tickets t
                    JOIN ticket_categories c ON c.id=t.category_id
                    JOIN users u ON u.id=t.requester_id
                    LEFT JOIN users au ON au.id=t.assigned_to
                    LEFT JOIN parks p ON p.id=t.park_id
                    LEFT JOIN areas a ON a.id=t.area_id
                    WHERE t.deleted_at IS NULL
                    ORDER BY t.created_at DESC LIMIT 200";
            $s = $pdo->query($sql);
        } elseif ($canAssigned) {
            $sql = "SELECT t.*,c.name category_name,p.name park_name,a.name area_name,
                           u.name requester_name,au.name assigned_name
                    FROM tickets t
                    JOIN ticket_categories c ON c.id=t.category_id
                    JOIN users u ON u.id=t.requester_id
                    LEFT JOIN users au ON au.id=t.assigned_to
                    LEFT JOIN parks p ON p.id=t.park_id
                    LEFT JOIN areas a ON a.id=t.area_id
                    WHERE t.deleted_at IS NULL AND (t.requester_id=? OR t.assigned_to=?)
                    ORDER BY t.created_at DESC LIMIT 200";
            $s = $pdo->prepare($sql);
            $s->execute([$uid,$uid]);
        } else {
            $sql = "SELECT t.*,c.name category_name,p.name park_name,a.name area_name,
                           u.name requester_name,au.name assigned_name
                    FROM tickets t
                    JOIN ticket_categories c ON c.id=t.category_id
                    JOIN users u ON u.id=t.requester_id
                    LEFT JOIN users au ON au.id=t.assigned_to
                    LEFT JOIN parks p ON p.id=t.park_id
                    LEFT JOIN areas a ON a.id=t.area_id
                    WHERE t.deleted_at IS NULL AND t.requester_id=?
                    ORDER BY t.created_at DESC LIMIT 200";
            $s = $pdo->prepare($sql);
            $s->execute([$uid]);
        }

        View::render('tickets/index', [
            'user' => Auth::user(),
            'tickets' => $s->fetchAll(),
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('tickets.create');
        $pdo = Database::pdo();
        $categories = $pdo->query("SELECT id,name FROM ticket_categories WHERE active=1 ORDER BY sort_order,name")->fetchAll();
        $parks = $pdo->query("SELECT id,name FROM parks WHERE active=1 AND deleted_at IS NULL ORDER BY name")->fetchAll();
        $areas = $pdo->query("SELECT id,name FROM areas WHERE active=1 AND deleted_at IS NULL ORDER BY name")->fetchAll();
        View::render('tickets/create', [
            'user' => Auth::user(),
            'categories' => $categories,
            'parks' => $parks,
            'areas' => $areas,
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('tickets.create');
        Csrf::verify($_POST['_csrf'] ?? null);

        $categoryId = (int) Http::post('category_id');
        $parkId = (int) Http::post('park_id');
        $areaId = (int) Http::post('area_id');
        $subject = trim(Http::post('subject'));
        $description = trim(Http::post('description'));
        $priority = strtoupper(Http::post('priority') ?: 'MEDIA');
        $allowedPriorities = ['BAJA','MEDIA','ALTA','CRITICA'];

        if ($categoryId <= 0) throw new \RuntimeException('Selecciona una categoría.');
        if (mb_strlen($subject) < 5) throw new \RuntimeException('El asunto debe tener al menos 5 caracteres.');
        if (mb_strlen($description) < 10) throw new \RuntimeException('Describe el caso con más detalle.');
        if (!in_array($priority,$allowedPriorities,true)) $priority='MEDIA';

        $pdo = Database::pdo();
        $ticket = Database::transaction(function(PDO $pdo) use ($categoryId,$parkId,$areaId,$subject,$description,$priority) {
            $s = $pdo->prepare("INSERT INTO tickets(requester_id,category_id,park_id,area_id,subject,description,priority,status,source,created_at,updated_at)
                               VALUES(?,?,?,?,?,?,?,'NUEVO','WEB',NOW(),NOW())");
            $s->execute([(int)Auth::id(),$categoryId,$parkId?:null,$areaId?:null,$subject,$description,$priority]);
            $id = (int) $pdo->lastInsertId();
            $number = 'HD-'.date('Y').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);
            $pdo->prepare('UPDATE tickets SET ticket_number=? WHERE id=?')->execute([$number,$id]);
            $pdo->prepare("INSERT INTO ticket_events(ticket_id,actor_user_id,event_type,new_values,note,created_at)
                           VALUES(?,?, 'CREATED', ?, ?, NOW())")
                ->execute([$id,(int)Auth::id(),json_encode(['status'=>'NUEVO','priority'=>$priority],JSON_UNESCAPED_UNICODE),'Ticket creado desde portal web']);
            return ['id'=>$id,'ticket_number'=>$number];
        });

        Audit::log('TICKET_CREATED','ticket',(int)$ticket['id'],null,['ticket_number'=>$ticket['ticket_number'],'subject'=>$subject]);
        Flash::set('Solicitud '.$ticket['ticket_number'].' creada correctamente.','success');
        header('Location: '.APP_BASE_URL.'/tickets');
        exit;
    }
}
