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

        $stmt = $pdo->prepare(
            "SELECT ua.*,p.name park_name,a.name area_name,m.full_name manager_name
             FROM user_assignments ua
             LEFT JOIN parks p ON p.id=ua.park_id
             LEFT JOIN areas a ON a.id=ua.area_id
             LEFT JOIN users m ON m.id=ua.manager_user_id
             WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
             ORDER BY ua.id DESC LIMIT 1"
        );
        $stmt->execute([Auth::id()]);
        $assignment = $stmt->fetch() ?: null;

        $own = $pdo->prepare(
            "SELECT
                COUNT(*) total,
                SUM(status IN ('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')) open_count,
                SUM(status='RESOLVED') resolved_count,
                SUM(status='CLOSED') closed_count
             FROM tickets
             WHERE deleted_at IS NULL AND LOWER(requester_email)=LOWER(?)"
        );
        $own->execute([(string)$user['email']]);
        $ticketStats = $own->fetch() ?: ['total'=>0,'open_count'=>0,'resolved_count'=>0,'closed_count'=>0];

        View::render('dashboard/index', [
            'user' => $user,
            'assignment' => $assignment,
            'ticketStats' => $ticketStats,
            'flash' => Flash::pull(),
        ]);
    }
}
