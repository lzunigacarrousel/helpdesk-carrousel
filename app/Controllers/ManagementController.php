<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database,View};
use PDO;

final class ManagementController
{
    private function requireManagement(): void
    {
        Auth::requireLogin();
        if (!in_array(Auth::role(), ['ADMIN','SEMIADMIN'], true) && !Auth::can('management.view')) {
            header('Location: '.APP_BASE_URL.'/dashboard');
            exit;
        }
    }

    public function dashboard(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();
        $filters=$this->filters();
        [$where,$params]=$this->where($filters);

        $k=$pdo->prepare("SELECT
            COUNT(*) total,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')),0) abiertos,
            COALESCE(SUM(t.status='RESOLVED'),0) resueltos,
            COALESCE(SUM(t.status='CLOSED'),0) cerrados,
            COALESCE(SUM(t.status IN('NEW','AVAILABLE','REOPENED') AND t.assigned_to IS NULL),0) sin_asignar,
            COALESCE(SUM(t.resolution_due_at IS NOT NULL AND t.resolution_due_at<NOW() AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')),0) vencidos,
            ROUND(AVG(CASE WHEN t.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at) END),1) promedio_primera_respuesta,
            ROUND(AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at)/60 END),1) promedio_resolucion_horas,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL AND t.resolved_at<=t.resolution_due_at THEN 1 ELSE 0 END),0) sla_ok,
            COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolution_due_at IS NOT NULL THEN 1 ELSE 0 END),0) sla_medidos
            FROM tickets t {$where}");
        $k->execute($params); $kpis=$k->fetch()?:[];
        $kpis['sla_porcentaje']=(int)($kpis['sla_medidos']??0)>0?round(((int)$kpis['sla_ok']/(int)$kpis['sla_medidos'])*100,1):null;

        $byStatus=$this->group($pdo,"SELECT t.status label,COUNT(*) total FROM tickets t {$where} GROUP BY t.status ORDER BY total DESC",$params);
        $byCategory=$this->group($pdo,"SELECT COALESCE(c.name,'Sin categoría') label,COUNT(*) total FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id {$where} GROUP BY c.id,c.name ORDER BY total DESC LIMIT 8",$params);
        $byPark=$this->group($pdo,"SELECT COALESCE(p.name,'Sin ubicación') label,COUNT(*) total FROM tickets t LEFT JOIN parks p ON p.id=t.park_id {$where} GROUP BY p.id,p.name ORDER BY total DESC LIMIT 8",$params);
        $byAssignee=$this->group($pdo,"SELECT COALESCE(u.full_name,'Sin asignar') label,COUNT(*) total,COALESCE(SUM(t.status IN('IN_PROGRESS','PENDING','REOPENED')),0) activos FROM tickets t LEFT JOIN users u ON u.id=t.assigned_to {$where} GROUP BY u.id,u.full_name ORDER BY total DESC LIMIT 8",$params);
        $trend=$this->group($pdo,"SELECT DATE_FORMAT(t.created_at,'%Y-%m') periodo,COUNT(*) total,COALESCE(SUM(t.status IN('RESOLVED','CLOSED')),0) completados FROM tickets t {$where} GROUP BY DATE_FORMAT(t.created_at,'%Y-%m') ORDER BY periodo",$params);

        $detail=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.status,t.priority,t.created_at,t.first_response_at,t.resolved_at,t.resolution_due_at,
            p.name park_name,c.name category_name,u.full_name assigned_name,t.requester_name
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN users u ON u.id=t.assigned_to
            {$where} ORDER BY t.created_at DESC LIMIT 80");
        $detail->execute($params);

        $catalogs=$this->catalogs($pdo);
        View::render('management/dashboard',[
            'user'=>Auth::user(),'filters'=>$filters,'kpis'=>$kpis,'byStatus'=>$byStatus,'byCategory'=>$byCategory,'byPark'=>$byPark,'byAssignee'=>$byAssignee,'trend'=>$trend,
            'tickets'=>$detail->fetchAll(),'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],
        ]);
    }

    public function reports(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();$filters=$this->filters();[$where,$params]=$this->where($filters);
        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.created_at,t.requester_name,t.requester_email,t.subject,t.priority,t.status,
            p.name park_name,a.name area_name,c.name category_name,u.full_name assigned_name,
            t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN users u ON u.id=t.assigned_to
            {$where} ORDER BY t.created_at DESC LIMIT 500");$q->execute($params);
        $catalogs=$this->catalogs($pdo);
        View::render('management/reports',[
            'user'=>Auth::user(),'filters'=>$filters,'rows'=>$q->fetchAll(),'parks'=>$catalogs['parks'],'categories'=>$catalogs['categories'],'supportUsers'=>$catalogs['supportUsers'],
        ]);
    }

    public function export(): void
    {
        $this->requireManagement();
        $pdo=Database::pdo();$filters=$this->filters();[$where,$params]=$this->where($filters);
        $q=$pdo->prepare("SELECT t.ticket_number,t.created_at,t.requester_name,t.requester_email,COALESCE(p.name,'') parque,COALESCE(a.name,'') area,COALESCE(c.name,'') categoria,
            t.subject,t.priority,t.status,COALESCE(u.full_name,'') responsable,t.first_response_at,t.resolved_at,t.closed_at,t.first_response_due_at,t.resolution_due_at
            FROM tickets t LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN users u ON u.id=t.assigned_to
            {$where} ORDER BY t.created_at DESC");$q->execute($params);
        Audit::log('REPORT_EXPORTED','report',null,null,null,['filters'=>$filters]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="helpdesk_informe_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";$out=fopen('php://output','w');
        fputcsv($out,['Ticket','Creado','Solicitante','Correo','Parque','Área','Categoría','Asunto','Prioridad','Estado','Responsable','Primera respuesta','Resuelto','Cerrado','Límite primera respuesta','Límite resolución'],';');
        while($r=$q->fetch(PDO::FETCH_ASSOC))fputcsv($out,array_values($r),';');
        fclose($out);exit;
    }

    private function catalogs(PDO $pdo):array
    {
        return [
            'parks'=>$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll(),
            'categories'=>$pdo->query("SELECT id,name FROM ticket_categories WHERE is_active=1 ORDER BY name")->fetchAll(),
            'supportUsers'=>$pdo->query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.status='ACTIVE' AND u.deleted_at IS NULL AND u.access_type='INTERNAL' AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll(),
        ];
    }

    private function filters(): array
    {
        return [
            'from'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['from']??''))?(string)$_GET['from']:date('Y-m-01'),
            'to'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['to']??''))?(string)$_GET['to']:date('Y-m-d'),
            'park_id'=>(int)($_GET['park_id']??0),'category_id'=>(int)($_GET['category_id']??0),'assigned_to'=>(int)($_GET['assigned_to']??0),
            'status'=>strtoupper(trim((string)($_GET['status']??''))),'priority'=>strtoupper(trim((string)($_GET['priority']??''))),
        ];
    }

    private function where(array $f): array
    {
        $w=['t.deleted_at IS NULL','t.created_at>=?','t.created_at<DATE_ADD(?,INTERVAL 1 DAY)'];$p=[$f['from'],$f['to']];
        if($f['park_id']>0){$w[]='t.park_id=?';$p[]=$f['park_id'];}
        if($f['category_id']>0){$w[]='t.category_id=?';$p[]=$f['category_id'];}
        if($f['assigned_to']>0){$w[]='t.assigned_to=?';$p[]=$f['assigned_to'];}
        if(in_array($f['status'],['NEW','AVAILABLE','IN_PROGRESS','PENDING','RESOLVED','CLOSED','REOPENED','CANCELLED'],true)){$w[]='t.status=?';$p[]=$f['status'];}
        if(in_array($f['priority'],['LOW','MEDIUM','HIGH','CRITICAL'],true)){$w[]='t.priority=?';$p[]=$f['priority'];}
        return [' WHERE '.implode(' AND ',$w),$p];
    }

    private function group(PDO $pdo,string $sql,array $params): array{$q=$pdo->prepare($sql);$q->execute($params);return$q->fetchAll();}
}
