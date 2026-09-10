<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database,View};
use App\Services\XlsxExportService;
use PDO;

final class SupportTeamController
{
    public function index(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $members=$this->members($pdo);
        View::render('management/support_team',[
            'user'=>Auth::user(),
            'members'=>$members,
            'summary'=>$this->summary($members),
        ]);
    }

    public function export(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $members=$this->members($pdo);
        $summary=$this->summary($members);

        $rows=[];
        foreach($members as $m){
            $rows[]=[
                $m['full_name'],$m['email'],$m['role_name'],$m['position_name']?:'—',$m['assignment_name']?:'—',
                (int)$m['active_cases'],(int)$m['in_progress'],(int)$m['pending_cases'],(int)$m['resolved_30'],
                $m['avg_first_response_min']!==null?round((float)$m['avg_first_response_min'],1):'',
                $m['avg_resolution_hours']!==null?round((float)$m['avg_resolution_hours'],1):'',
                $m['avg_nps']!==null?round((float)$m['avg_nps'],1):'',(int)$m['nps_responses'],
                $m['last_login_at']?date('d/m/Y H:i',strtotime((string)$m['last_login_at'])):'Nunca'
            ];
        }

        Audit::log('SUPPORT_TEAM_EXPORTED_XLSX','report',null,null,null,['rows'=>count($rows)]);
        XlsxExportService::download('helpdesk_equipo_soporte_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Equipo de soporte','subtitle'=>'Generado '.date('d/m/Y H:i:s'),'headers'=>['Indicador','Valor'],'rows'=>[
                ['Integrantes',(int)$summary['members']],['Casos activos',(int)$summary['active_cases']],['En proceso',(int)$summary['in_progress']],['En espera',(int)$summary['pending_cases']],['Resueltos últimos 30 días',(int)$summary['resolved_30']],['Respuestas NPS',(int)$summary['nps_responses']],['NPS promedio',$summary['avg_nps']!==null?round((float)$summary['avg_nps'],1).'/10':''],
            ]],
            ['name'=>'Tecnicos','title'=>'Helpdesk Carrousel · Carga y desempeño del equipo','subtitle'=>'Administradores, semiadministradores y técnicos activos','headers'=>['Nombre','Correo','Perfil','Puesto','Ubicación / área','Casos activos','En proceso','En espera','Resueltos 30 días','Primera respuesta prom. (min)','Resolución prom. (h)','Calificación prom.','Respuestas NPS','Último acceso'],'rows'=>$rows],
        ]);
    }

    private function members(PDO $pdo): array
    {
        $sql="SELECT u.id,u.full_name,u.email,u.last_login_at,r.code role_code,r.name role_name,
            COALESCE(pos.name,'') position_name,
            COALESCE(p.name,a.name,rg.name,'') assignment_name,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')) active_cases,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status='IN_PROGRESS') in_progress,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status='PENDING') pending_cases,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.resolved_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)) resolved_30,
            (SELECT AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at)) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.first_response_at IS NOT NULL) avg_first_response_min,
            (SELECT AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at))/60 FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.resolved_at IS NOT NULL) avg_resolution_hours,
            (SELECT AVG(tf.nps_score) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL) avg_nps,
            (SELECT COUNT(*) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL) nps_responses
            FROM users u
            JOIN roles r ON r.id=u.role_id AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
            LEFT JOIN user_assignments ua ON ua.id=(SELECT MAX(x.id) FROM user_assignments x WHERE x.user_id=u.id AND x.status='ACTIVE' AND x.ends_at IS NULL)
            LEFT JOIN positions pos ON pos.id=ua.position_id
            LEFT JOIN parks p ON p.id=ua.park_id
            LEFT JOIN areas a ON a.id=ua.area_id
            LEFT JOIN regions rg ON rg.id=ua.region_id
            WHERE u.deleted_at IS NULL AND u.access_type='INTERNAL' AND u.status='ACTIVE'
            ORDER BY FIELD(r.code,'TECHNICIAN','SEMIADMIN','ADMIN'),u.full_name";
        return $pdo->query($sql)->fetchAll()?:[];
    }

    private function summary(array $members): array
    {
        $s=['members'=>count($members),'active_cases'=>0,'in_progress'=>0,'pending_cases'=>0,'resolved_30'=>0,'nps_responses'=>0,'avg_nps'=>null];
        $weightedNps=0.0;$npsCount=0;
        foreach($members as $m){
            $s['active_cases']+=(int)$m['active_cases'];$s['in_progress']+=(int)$m['in_progress'];$s['pending_cases']+=(int)$m['pending_cases'];$s['resolved_30']+=(int)$m['resolved_30'];
            $count=(int)$m['nps_responses'];if($count>0&&$m['avg_nps']!==null){$weightedNps+=(float)$m['avg_nps']*$count;$npsCount+=$count;}
        }
        $s['nps_responses']=$npsCount;$s['avg_nps']=$npsCount>0?round($weightedNps/$npsCount,1):null;
        return $s;
    }

    private function requireAccess(): void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('users.manage')&&!Auth::can('management.view')){
            header('Location: '.APP_BASE_URL.'/dashboard');exit;
        }
    }
}
