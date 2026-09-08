<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,View};

final class AuditController
{
    public function index(): void
    {
        Auth::requirePermission('audit.view');
        $pdo = Database::pdo();

        $q = trim((string)($_GET['q'] ?? ''));
        $action = trim((string)($_GET['action'] ?? ''));
        $source = trim((string)($_GET['source'] ?? ''));
        $from = trim((string)($_GET['from'] ?? ''));
        $to = trim((string)($_GET['to'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(actor_email LIKE ? OR action LIKE ? OR entity_type LIKE ? OR entity_id LIKE ? OR ip_address LIKE ?)';
            $like = '%'.$q.'%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($action !== '') { $where[] = 'action=?'; $params[] = $action; }
        if ($source !== '') { $where[] = 'source=?'; $params[] = $source; }
        if ($from !== '') { $where[] = 'created_at>=?'; $params[] = $from.' 00:00:00'; }
        if ($to !== '') { $where[] = 'created_at<=?'; $params[] = $to.' 23:59:59'; }

        $whereSql = implode(' AND ', $where);
        $count = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$whereSql}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id,actor_user_id,actor_email,action,entity_type,entity_id,source,ip_address,user_agent,old_values,new_values,metadata_json,created_at
             FROM audit_logs
             WHERE {$whereSql}
             ORDER BY created_at DESC,id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll();
        $stats = [
            'today' => (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE created_at>=CURDATE()")->fetchColumn(),
            'week' => (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn(),
            'logins' => (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='LOGIN_SUCCESS' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn(),
            'failures' => (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN('OTP_FAILED','LOGIN_FAILED') AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn(),
        ];

        View::render('admin/audit', [
            'user'=>Auth::user(),
            'logs'=>$logs,
            'actions'=>$actions,
            'stats'=>$stats,
            'filters'=>compact('q','action','source','from','to'),
            'page'=>$page,
            'perPage'=>$perPage,
            'total'=>$total,
        ]);
    }
}
