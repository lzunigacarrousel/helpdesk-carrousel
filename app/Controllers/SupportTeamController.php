<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use App\Services\{SupportTeamReportService,TicketReportFilterService,XlsxExportService};
use PDO;

final class SupportTeamController
{
    public function index(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $teamService=new SupportTeamReportService($pdo);
        $teamId=$teamService->activeTeamId();
        $members=$teamService->members($teamId);
        $periodFilters=(new TicketReportFilterService())->filters($_GET);
        $periodSummary=$teamService->reportSummary($periodFilters);
        View::render('management/support_team',[
            'user'=>Auth::user(),
            'members'=>$members,
            'summary'=>$teamService->summary($members),
            'periodFilters'=>$periodFilters,
            'periodSummary'=>$periodSummary,
            'candidates'=>$this->candidates($pdo,$teamId),
            'pendingSupport'=>$this->pendingSupport($pdo),
            'canManage'=>$this->canManage(),
            'flash'=>Flash::pull(),
        ]);
    }

    public function addMember(): void
    {
        $this->requireManage();
        Csrf::verify($_POST['_csrf']??null);
        $userId=(int)Http::post('user_id');
        if($userId<=0)throw new \RuntimeException('Selecciona un integrante válido.');

        $pdo=Database::pdo();$teamService=new SupportTeamReportService($pdo);$teamId=$teamService->activeTeamId();
        if($teamId<=0)throw new \RuntimeException('El equipo IT no está disponible.');
        $q=$pdo->prepare("SELECT u.id,u.full_name,u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') LIMIT 1");
        $q->execute([$userId]);$member=$q->fetch();
        if(!$member)throw new \RuntimeException('La persona seleccionada no está disponible para el equipo de soporte.');

        $pdo->prepare("INSERT INTO support_team_members(team_id,user_id,is_active,joined_at,ended_at) VALUES(?,?,1,NOW(),NULL) ON DUPLICATE KEY UPDATE is_active=1,joined_at=NOW(),ended_at=NULL")
            ->execute([$teamId,$userId]);
        Audit::log('SUPPORT_TEAM_MEMBER_ADDED','support_team',$teamId,null,['user_id'=>$userId,'email'=>$member['email']]);
        Flash::set($member['full_name'].' ahora forma parte del equipo de soporte y recibirá sus avisos.','success');
        $this->redirectTeam();
    }

    public function removeMember(): void
    {
        $this->requireManage();
        Csrf::verify($_POST['_csrf']??null);
        $userId=(int)Http::post('user_id');
        if($userId<=0)throw new \RuntimeException('Integrante no válido.');

        $pdo=Database::pdo();$teamService=new SupportTeamReportService($pdo);$teamId=$teamService->activeTeamId();
        if($teamId<=0)throw new \RuntimeException('El equipo IT no está disponible.');
        $count=$pdo->prepare("SELECT COUNT(*) FROM support_team_members stm JOIN users u ON u.id=stm.user_id WHERE stm.team_id=? AND stm.is_active=1 AND stm.ended_at IS NULL AND u.status='ACTIVE' AND u.deleted_at IS NULL");
        $count->execute([$teamId]);
        if((int)$count->fetchColumn()<=1){
            Flash::set('El equipo debe conservar al menos un integrante activo para recibir avisos de soporte.','info');
            $this->redirectTeam();
        }

        $q=$pdo->prepare("SELECT u.full_name,u.email FROM support_team_members stm JOIN users u ON u.id=stm.user_id WHERE stm.team_id=? AND stm.user_id=? AND stm.is_active=1 AND stm.ended_at IS NULL LIMIT 1");
        $q->execute([$teamId,$userId]);$member=$q->fetch();
        if(!$member){Flash::set('Ese usuario ya no forma parte del equipo de soporte.','info');$this->redirectTeam();}

        $pdo->prepare("UPDATE support_team_members SET is_active=0,ended_at=NOW() WHERE team_id=? AND user_id=? AND is_active=1")
            ->execute([$teamId,$userId]);
        Audit::log('SUPPORT_TEAM_MEMBER_REMOVED','support_team',$teamId,['user_id'=>$userId,'email'=>$member['email']],null);
        Flash::set($member['full_name'].' fue retirado del equipo de soporte y dejará de recibir esos avisos.','success');
        $this->redirectTeam();
    }

    public function export(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $teamService=new SupportTeamReportService($pdo);
        $members=$teamService->members($teamService->activeTeamId());
        $summary=$teamService->summary($members);
        $periodFilters=(new TicketReportFilterService())->filters($_GET);
        $periodSummary=$teamService->reportSummary($periodFilters);
        $periodLabel='Período '.$periodFilters['from'].' a '.$periodFilters['to'];

        $rows=[];
        foreach($members as $m){
            $rows[]=[
                $m['full_name'],$m['email'],$m['role_name'],$m['position_name']?:'—',$m['assignment_name']?:'—',
                (int)$m['active_cases'],(int)$m['in_progress'],(int)$m['pending_cases'],(int)$m['resolved_30'],
                $m['avg_first_response_min']!==null?round((float)$m['avg_first_response_min'],1):'',
                $m['avg_resolution_hours']!==null?round((float)$m['avg_resolution_hours'],1):'',
                $m['nps_value']!==null?(int)$m['nps_value']:'',
                $m['avg_rating']!==null?round((float)$m['avg_rating'],1):'',
                (int)$m['nps_responses'],(int)$m['nps_promoters'],(int)$m['nps_passives'],(int)$m['nps_detractors'],
                $m['last_login_at']?date('d/m/Y H:i',strtotime((string)$m['last_login_at'])):'Sin ingreso todavía'
            ];
        }

        Audit::log('SUPPORT_TEAM_EXPORTED_XLSX','report',null,null,null,['rows'=>count($rows),'filters'=>$periodFilters]);
        XlsxExportService::download('helpdesk_equipo_soporte_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Equipo de soporte','subtitle'=>$periodLabel.' · Generado '.date('d/m/Y H:i:s'),'headers'=>['Indicador','Valor'],'rows'=>[
                ['Período',$periodFilters['from'].' a '.$periodFilters['to']],
                ['Integrantes',(int)$periodSummary['members']],
                ['Tickets del período',(int)$periodSummary['tickets_period']],
                ['Resueltos del período',(int)$periodSummary['resolved_period']],
                ['Primera respuesta promedio (min)',$periodSummary['avg_first_response_min']??''],
                ['Resolución promedio (h)',$periodSummary['avg_resolution_hours']??''],
                ['Respuestas NPS del período',(int)$periodSummary['nps_responses']],
                ['NPS del período',$periodSummary['nps_value']!==null?(int)$periodSummary['nps_value']:''],
                ['Calificación promedio del período',$periodSummary['avg_rating']!==null?round((float)$periodSummary['avg_rating'],1).'/10':''],
                ['Carga actual - casos activos',(int)$summary['active_cases']],
                ['Carga actual - en proceso',(int)$summary['in_progress']],
                ['Carga actual - en espera',(int)$summary['pending_cases']],
                ['Resueltos últimos 30 días',(int)$summary['resolved_30']],
            ]],
            ['name'=>'Tecnicos','title'=>'Helpdesk Carrousel · Carga y desempeño del equipo','subtitle'=>'Integrantes activos del equipo IT · carga actual e histórico por integrante','headers'=>[
                'Nombre','Correo','Perfil','Puesto','Ubicación / área','Casos activos','En proceso','En espera','Resueltos 30 días',
                'Primera respuesta prom. (min)','Resolución prom. (h)','NPS','Calificación prom. (0-10)','Respuestas NPS','Promotores','Pasivos','Detractores','Último acceso'
            ],'rows'=>$rows],
        ]);
    }

    private function candidates(PDO $pdo,int $teamId): array
    {
        if($teamId<=0)return[];
        $q=$pdo->prepare("SELECT u.id,u.full_name,u.email,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') AND NOT EXISTS(SELECT 1 FROM support_team_members stm WHERE stm.team_id=? AND stm.user_id=u.id AND stm.is_active=1 AND stm.ended_at IS NULL) ORDER BY u.full_name");
        $q->execute([$teamId]);return$q->fetchAll()?:[];
    }

    private function pendingSupport(PDO $pdo): array
    {
        $q=$pdo->query("SELECT u.id,u.full_name,u.email,u.status,r.name role_name
            FROM users u
            JOIN roles r ON r.id=u.role_id
            WHERE u.access_type='INTERNAL' AND u.deleted_at IS NULL
              AND u.status='PENDING' AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
            ORDER BY u.full_name");
        return $q->fetchAll()?:[];
    }
    private function canManage(): bool
    {
        return in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)||Auth::can('users.manage');
    }

    private function requireManage(): void
    {
        Auth::requireLogin();
        if(!$this->canManage()){header('Location: '.APP_BASE_URL.'/dashboard');exit;}
    }

    private function requireAccess(): void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('users.manage')&&!Auth::can('management.view')){
            header('Location: '.APP_BASE_URL.'/dashboard');exit;
        }
    }

    private function redirectTeam(): never
    {
        header('Location: '.APP_BASE_URL.'/gestion/equipo');exit;
    }
}
