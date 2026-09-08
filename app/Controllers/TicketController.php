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

        $baseSql = "SELECT t.*,c.name category_name,p.name park_name,a.name area_name,
                           u.name requester_name,au.name assigned_name
                    FROM tickets t
                    JOIN ticket_categories c ON c.id=t.category_id
                    JOIN users u ON u.id=t.requester_id
                    LEFT JOIN users au ON au.id=t.assigned_to
                    LEFT JOIN parks p ON p.id=t.park_id
                    LEFT JOIN areas a ON a.id=t.area_id";

        if ($canAll) {
            $s = $pdo->query($baseSql." WHERE t.deleted_at IS NULL ORDER BY t.created_at DESC LIMIT 200");
        } elseif ($canAssigned) {
            $s = $pdo->prepare($baseSql." WHERE t.deleted_at IS NULL AND (t.requester_id=? OR t.assigned_to=?) ORDER BY t.created_at DESC LIMIT 200");
            $s->execute([$uid,$uid]);
        } else {
            $s = $pdo->prepare($baseSql." WHERE t.deleted_at IS NULL AND t.requester_id=? ORDER BY t.created_at DESC LIMIT 200");
            $s->execute([$uid]);
        }

        View::render('tickets/index', [
            'user' => Auth::user(),
            'tickets' => $s->fetchAll(),
            'flash' => Flash::pull(),
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new \RuntimeException('Ticket no válido.');

        $pdo = Database::pdo();
        $s = $pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,
                                   u.name requester_name,u.email requester_email,
                                   au.name assigned_name,st.name assigned_team_name
                            FROM tickets t
                            JOIN ticket_categories c ON c.id=t.category_id
                            JOIN users u ON u.id=t.requester_id
                            LEFT JOIN users au ON au.id=t.assigned_to
                            LEFT JOIN support_teams st ON st.id=t.assigned_team_id
                            LEFT JOIN parks p ON p.id=t.park_id
                            LEFT JOIN areas a ON a.id=t.area_id
                            WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");
        $s->execute([$id]);
        $ticket = $s->fetch();
        if (!$ticket) throw new \RuntimeException('Ticket no encontrado.');
        $this->assertVisible($ticket);

        $canInternal = Auth::can('tickets.internal_comment');
        $commentsSql = "SELECT tc.*,u.name user_name,r.name role_name
                        FROM ticket_comments tc
                        JOIN users u ON u.id=tc.user_id
                        JOIN roles r ON r.id=u.role_id
                        WHERE tc.ticket_id=? AND tc.deleted_at IS NULL";
        if (!$canInternal) $commentsSql .= " AND tc.visibility='PUBLIC'";
        $commentsSql .= " ORDER BY tc.created_at ASC";
        $c = $pdo->prepare($commentsSql);
        $c->execute([$id]);

        $e = $pdo->prepare("SELECT te.*,u.name actor_name FROM ticket_events te LEFT JOIN users u ON u.id=te.actor_user_id WHERE te.ticket_id=? ORDER BY te.created_at ASC,te.id ASC");
        $e->execute([$id]);

        $assignees = [];
        if (Auth::can('tickets.assign') || Auth::can('tickets.reassign')) {
            $assignees = $pdo->query("SELECT u.id,u.name,u.email,r.name role_name
                                      FROM users u JOIN roles r ON r.id=u.role_id
                                      WHERE u.deleted_at IS NULL AND u.active=1 AND u.account_status='ACTIVE'
                                        AND r.code IN('TECNICO','SUPERVISOR_IT','ADMINISTRADOR')
                                      ORDER BY r.sort_order DESC,u.name")->fetchAll();
        }

        View::render('tickets/show', [
            'user' => Auth::user(),
            'ticket' => $ticket,
            'comments' => $c->fetchAll(),
            'events' => $e->fetchAll(),
            'assignees' => $assignees,
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
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.(int)$ticket['id']);
        exit;
    }

    public function assign(): void
    {
        Auth::requireLogin();
        if (!Auth::can('tickets.assign') && !Auth::can('tickets.reassign')) throw new \RuntimeException('No tienes permiso para asignar tickets.');
        Csrf::verify($_POST['_csrf'] ?? null);
        $id = (int) Http::post('ticket_id');
        $assignedTo = (int) Http::post('assigned_to');
        if ($id<=0 || $assignedTo<=0) throw new \RuntimeException('Selecciona un técnico válido.');

        $pdo = Database::pdo();
        $ticket = $this->findTicket($id);
        $this->assertVisible($ticket);
        $u = $pdo->prepare("SELECT u.id,u.name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.active=1 AND u.account_status='ACTIVE' AND r.code IN('TECNICO','SUPERVISOR_IT','ADMINISTRADOR') LIMIT 1");
        $u->execute([$assignedTo]);
        $assignee = $u->fetch();
        if (!$assignee) throw new \RuntimeException('El usuario seleccionado no puede recibir tickets.');

        $oldAssigned = $ticket['assigned_to'] ? (int)$ticket['assigned_to'] : null;
        $newStatus = in_array($ticket['status'],['NUEVO','REABIERTO'],true) ? 'ASIGNADO' : $ticket['status'];
        $pdo->prepare('UPDATE tickets SET assigned_to=?,status=?,updated_at=NOW() WHERE id=?')->execute([$assignedTo,$newStatus,$id]);
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,actor_user_id,event_type,old_values,new_values,note,created_at) VALUES(?,?,?,?,?,?,NOW())")
            ->execute([$id,(int)Auth::id(),'ASSIGNED',json_encode(['assigned_to'=>$oldAssigned,'status'=>$ticket['status']]),json_encode(['assigned_to'=>$assignedTo,'status'=>$newStatus]),'Asignado a '.$assignee['name']]);
        Audit::log('TICKET_ASSIGNED','ticket',$id,['assigned_to'=>$oldAssigned],['assigned_to'=>$assignedTo]);
        Flash::set('Ticket asignado a '.$assignee['name'].'.','success');
        $this->redirectToTicket($id);
    }

    public function changeStatus(): void
    {
        Auth::requirePermission('tickets.change_status');
        Csrf::verify($_POST['_csrf'] ?? null);
        $id = (int) Http::post('ticket_id');
        $status = strtoupper(Http::post('status'));
        $allowed = ['ASIGNADO','EN_PROCESO','PENDIENTE_USUARIO','PENDIENTE_TERCERO','RESUELTO','CERRADO','REABIERTO','CANCELADO'];
        if ($id<=0 || !in_array($status,$allowed,true)) throw new \RuntimeException('Estado no válido.');
        if ($status==='CERRADO' && !Auth::can('tickets.close')) throw new \RuntimeException('No tienes permiso para cerrar tickets.');
        if ($status==='REABIERTO' && !Auth::can('tickets.reopen')) throw new \RuntimeException('No tienes permiso para reabrir tickets.');
        if ($status==='RESUELTO' && !Auth::can('tickets.resolve')) throw new \RuntimeException('No tienes permiso para resolver tickets.');

        $pdo = Database::pdo();
        $ticket = $this->findTicket($id);
        $this->assertVisible($ticket);
        $old = $ticket['status'];
        if ($old === $status) { Flash::set('El ticket ya se encuentra en ese estado.','info'); $this->redirectToTicket($id); }

        $extra = '';
        if ($status==='RESUELTO') $extra=',resolved_at=NOW()';
        elseif ($status==='CERRADO') $extra=',closed_at=NOW()';
        elseif ($status==='REABIERTO') $extra=',resolved_at=NULL,closed_at=NULL';
        $pdo->prepare("UPDATE tickets SET status=?,updated_at=NOW(){$extra} WHERE id=?")->execute([$status,$id]);
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,actor_user_id,event_type,old_values,new_values,note,created_at) VALUES(?,?,?,?,?,?,NOW())")
            ->execute([$id,(int)Auth::id(),'STATUS_CHANGED',json_encode(['status'=>$old]),json_encode(['status'=>$status]),'Cambio de estado']);
        Audit::log('TICKET_STATUS_CHANGED','ticket',$id,['status'=>$old],['status'=>$status]);
        Flash::set('Estado actualizado a '.str_replace('_',' ',$status).'.','success');
        $this->redirectToTicket($id);
    }

    public function comment(): void
    {
        Auth::requirePermission('tickets.comment');
        Csrf::verify($_POST['_csrf'] ?? null);
        $id = (int) Http::post('ticket_id');
        $body = trim(Http::post('body'));
        $visibility = strtoupper(Http::post('visibility') ?: 'PUBLIC');
        if ($id<=0) throw new \RuntimeException('Ticket no válido.');
        if (mb_strlen($body)<2) throw new \RuntimeException('Escribe un comentario.');
        if ($visibility==='INTERNAL' && !Auth::can('tickets.internal_comment')) throw new \RuntimeException('No tienes permiso para crear notas internas.');
        if (!in_array($visibility,['PUBLIC','INTERNAL'],true)) $visibility='PUBLIC';

        $pdo = Database::pdo();
        $ticket = $this->findTicket($id);
        $this->assertVisible($ticket);
        $pdo->prepare('INSERT INTO ticket_comments(ticket_id,user_id,visibility,body,created_at) VALUES(?,?,?,?,NOW())')->execute([$id,(int)Auth::id(),$visibility,$body]);
        if (!$ticket['first_response_at'] && (int)$ticket['requester_id'] !== (int)Auth::id()) {
            $pdo->prepare('UPDATE tickets SET first_response_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$id]);
        } else {
            $pdo->prepare('UPDATE tickets SET updated_at=NOW() WHERE id=?')->execute([$id]);
        }
        $pdo->prepare("INSERT INTO ticket_events(ticket_id,actor_user_id,event_type,new_values,note,created_at) VALUES(?,?,?,?,?,NOW())")
            ->execute([$id,(int)Auth::id(),'COMMENTED',json_encode(['visibility'=>$visibility]),$visibility==='INTERNAL'?'Nota interna agregada':'Respuesta pública agregada']);
        Audit::log('TICKET_COMMENTED','ticket',$id,null,['visibility'=>$visibility]);
        Flash::set($visibility==='INTERNAL'?'Nota interna agregada.':'Respuesta agregada.','success');
        $this->redirectToTicket($id);
    }

    private function findTicket(int $id): array
    {
        $s = Database::pdo()->prepare('SELECT * FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $s->execute([$id]);
        $ticket = $s->fetch();
        if (!$ticket) throw new \RuntimeException('Ticket no encontrado.');
        return $ticket;
    }

    private function assertVisible(array $ticket): void
    {
        if (Auth::can('tickets.view.all')) return;
        $uid = (int)Auth::id();
        if ((int)$ticket['requester_id']===$uid) return;
        if (Auth::can('tickets.view.assigned') && (int)($ticket['assigned_to']??0)===$uid) return;
        throw new \RuntimeException('No tienes acceso a este ticket.');
    }

    private function redirectToTicket(int $id): never
    {
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$id);
        exit;
    }
}
