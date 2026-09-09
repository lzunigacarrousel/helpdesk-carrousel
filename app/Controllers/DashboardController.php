<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,Flash,View};

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $pdo = Database::pdo();
        $user = Auth::user();
        $uid = (int)Auth::id();
        $isExternal = (($user['access_type'] ?? 'INTERNAL') === 'EXTERNAL');
        $isSupport = !$isExternal && (Auth::can('tickets.view_queue') || Auth::can('tickets.change_status') || Auth::can('tickets.view_all'));

        $stmt = $pdo->prepare(
            "SELECT ua.*,p.name park_name,a.name area_name,rg.name region_name,pos.name position_name,m.full_name manager_name
             FROM user_assignments ua
             LEFT JOIN parks p ON p.id=ua.park_id
             LEFT JOIN areas a ON a.id=ua.area_id
             LEFT JOIN regions rg ON rg.id=ua.region_id
             LEFT JOIN positions pos ON pos.id=ua.position_id
             LEFT JOIN users m ON m.id=ua.manager_user_id
             WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             ORDER BY ua.id DESC LIMIT 1"
        );
        $stmt->execute([$uid]);
        $assignment = $stmt->fetch() ?: null;

        if ($isExternal) {
            $stats = $pdo->prepare(
                "SELECT COUNT(*) total,
                        SUM(t.status IN ('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')) open_count,
                        SUM(t.status='RESOLVED') resolved_count,
                        SUM(t.status='CLOSED') closed_count
                 FROM external_ticket_access eta
                 JOIN tickets t ON t.id=eta.ticket_id
                 WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL
                   AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED'"
            );
            $stats->execute([$uid]);
            $ticketStats = $stats->fetch() ?: ['total'=>0,'open_count'=>0,'resolved_count'=>0,'closed_count'=>0];
            $recent = $pdo->prepare(
                "SELECT t.id,t.ticket_number,t.subject,t.description,t.status,t.created_at
                 FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id
                 WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL
                   AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED'
                 ORDER BY t.updated_at DESC,t.created_at DESC LIMIT 5"
            );
            $recent->execute([$uid]);
        } else {
            $stats = $pdo->prepare(
                "SELECT COUNT(*) total,
                        SUM(status IN ('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')) open_count,
                        SUM(status='RESOLVED') resolved_count,
                        SUM(status='CLOSED') closed_count
                 FROM tickets WHERE deleted_at IS NULL AND (requester_user_id=? OR LOWER(requester_email)=LOWER(?))"
            );
            $stats->execute([$uid,(string)$user['email']]);
            $ticketStats = $stats->fetch() ?: ['total'=>0,'open_count'=>0,'resolved_count'=>0,'closed_count'=>0];
            $recent = $pdo->prepare(
                "SELECT id,ticket_number,subject,description,status,created_at
                 FROM tickets WHERE deleted_at IS NULL AND (requester_user_id=? OR LOWER(requester_email)=LOWER(?))
                 ORDER BY updated_at DESC,created_at DESC LIMIT 5"
            );
            $recent->execute([$uid,(string)$user['email']]);
        }
        $recentTickets = $recent->fetchAll();

        $supportStats = [
            'mine'=>0,'available'=>0,'near_due'=>0,'overdue'=>0,
            'critical'=>0,'pending'=>0,'reopened'=>0,'in_progress'=>0,
        ];
        $supportActivity = [];
        if ($isSupport) {
            $support = $pdo->prepare(
                "SELECT
                    SUM(assigned_to=? AND status IN('IN_PROGRESS','PENDING','REOPENED')) mine,
                    SUM(assigned_to IS NULL AND status IN('NEW','AVAILABLE','REOPENED')) available,
                    SUM(status='IN_PROGRESS') in_progress,
                    SUM(status='PENDING') pending,
                    SUM(status='REOPENED') reopened,
                    SUM(priority='CRITICAL' AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) critical,
                    SUM(resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) overdue,
                    SUM(resolution_due_at IS NOT NULL AND resolution_due_at>=NOW() AND resolution_due_at<=DATE_ADD(NOW(),INTERVAL 2 HOUR) AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) near_due
                 FROM tickets
                 WHERE deleted_at IS NULL"
            );
            $support->execute([$uid]);
            $row = $support->fetch() ?: [];
            foreach ($supportStats as $key=>$value) {
                $supportStats[$key] = (int)($row[$key] ?? 0);
            }

            $activity = $pdo->query(
                "SELECT te.event_type,te.created_at,t.id ticket_id,t.ticket_number,t.subject,u.full_name actor_name
                 FROM ticket_events te
                 JOIN tickets t ON t.id=te.ticket_id
                 LEFT JOIN users u ON u.id=te.actor_user_id
                 WHERE t.deleted_at IS NULL
                   AND te.event_type IN('CREATED','CLAIMED','REASSIGNED','COMMENTED','RESOLUTION_RECORDED','RESOLVED','CLOSED','REOPENED','STATUS_CHANGED')
                 ORDER BY te.created_at DESC,te.id DESC
                 LIMIT 8"
            );
            $supportActivity = $activity->fetchAll();
        }

        View::render('dashboard/index', [
            'user' => $user,
            'assignment' => $assignment,
            'ticketStats' => $ticketStats,
            'recentTickets' => $recentTickets,
            'supportStats' => $supportStats,
            'supportActivity' => $supportActivity,
            'flash' => Flash::pull(),
        ]);
    }
}
