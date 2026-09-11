<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,View};
use App\Services\XlsxExportService;
use PDO;

final class SupportTeamController
{
    private const TEAM_CODE='IT';

    public function index(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $teamId=$this->activeTeamId($pdo);
        $members=$this->members($pdo,$teamId);
        View::render('management/support_team',[
            'user'=>Auth::user(),
            'members'=>$members,
            'summary'=>$this->summary($members),
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

        $pdo=Database::pdo();$teamId=$this->activeTeamId($pdo);
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

        $pdo=Database::pdo();$teamId=$this->activeTeamId($pdo);
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
        $members=$this->members($pdo,$this->activeTeamId($pdo));
        $summary=$this->summary($members);

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

        Audit::log('SUPPORT_TEAM_EXPORTED_XLSX','report',null,null,null,['rows'=>count($rows)]);
        XlsxExportService::download('helpdesk_equipo_soporte_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Equipo de soporte','subtitle'=>'Generado '.date('d/m/Y H:i:s'),'headers'=>['Indicador','Valor'],'rows'=>[
                ['Integrantes',(int)$summary['members']],
                ['Casos activos',(int)$summary['active_cases']],
                ['En proceso',(int)$summary['in_progress']],
                ['En espera',(int)$summary['pending_cases']],
                ['Resueltos últimos 30 días',(int)$summary['resolved_30']],
                ['Respuestas NPS',(int)$summary['nps_responses']],
                ['Promotores (9-10)',(int)$summary['nps_promoters']],
                ['Pasivos (7-8)',(int)$summary['nps_passives']],
                ['Detractores (0-6)',(int)$summary['nps_detractors']],
                ['NPS',$summary['nps_value']!==null?(int)$summary['nps_value']:''],
                ['Calificación promedio',$summary['avg_rating']!==null?round((float)$summary['avg_rating'],1).'/10':''],
            ]],
            ['name'=>'Tecnicos','title'=>'Helpdesk Carrousel · Carga y desempeño del equipo','subtitle'=>'Integrantes activos del equipo IT','headers'=>[
                'Nombre','Correo','Perfil','Puesto','Ubicación / área','Casos activos','En proceso','En espera','Resueltos 30 días',
                'Primera respuesta prom. (min)','Resolución prom. (h)','NPS','Calificación prom. (0-10)','Respuestas NPS','Promotores','Pasivos','Detractores','Último acceso'
            ],'rows'=>$rows],
        ]);
    }

    private function members(PDO $pdo,int $teamId): array
    {
        if($teamId<=0)return[];
        $sql="SELECT u.id,u.full_name,u.email,u.last_login_at,r.code role_code,r.name role_name,
            COALESCE(pos.name,'') position_name,
            COALESCE(p.name,a.name,rg.name,'') assignment_name,
            stm.joined_at,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status NOT IN('RESOLVED','CLOSED','CANCELLED')) active_cases,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status='IN_PROGRESS') in_progress,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.status='PENDING') pending_cases,
            (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.resolved_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)) resolved_30,
            (SELECT AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at)) FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.first_response_at IS NOT NULL) avg_first_response_min,
            (SELECT AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at))/60 FROM tickets t WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND t.resolved_at IS NOT NULL) avg_resolution_hours,
            (SELECT AVG(tf.nps_score) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL) avg_rating,
            (SELECT COUNT(*) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL) nps_responses,
            (SELECT COUNT(*) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND tf.nps_score>=9) nps_promoters,
            (SELECT COUNT(*) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND tf.nps_score BETWEEN 7 AND 8) nps_passives,
            (SELECT COUNT(*) FROM tickets t JOIN ticket_feedback tf ON tf.ticket_id=t.id WHERE t.assigned_to=u.id AND t.deleted_at IS NULL AND tf.nps_score<=6) nps_detractors
            FROM support_team_members stm
            JOIN support_teams st ON st.id=stm.team_id AND st.is_active=1
            JOIN users u ON u.id=stm.user_id AND u.deleted_at IS NULL AND u.access_type='INTERNAL' AND u.status='ACTIVE'
            JOIN roles r ON r.id=u.role_id AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
            LEFT JOIN user_assignments ua ON ua.id=(SELECT MAX(x.id) FROM user_assignments x WHERE x.user_id=u.id AND x.status='ACTIVE' AND x.ends_at IS NULL)
            LEFT JOIN positions pos ON pos.id=ua.position_id
            LEFT JOIN parks p ON p.id=ua.park_id
            LEFT JOIN areas a ON a.id=ua.area_id
            LEFT JOIN regions rg ON rg.id=ua.region_id
            WHERE stm.team_id=? AND stm.is_active=1 AND stm.ended_at IS NULL
            ORDER BY FIELD(r.code,'TECHNICIAN','SEMIADMIN','ADMIN'),u.full_name";
        $q=$pdo->prepare($sql);$q->execute([$teamId]);$rows=$q->fetchAll()?:[];
        foreach($rows as &$row){
            $responses=(int)$row['nps_responses'];
            $row['nps_value']=$responses>0?(int)round((((int)$row['nps_promoters']-(int)$row['nps_detractors'])/$responses)*100):null;
        }
        unset($row);
        return $rows;
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
    private function activeTeamId(PDO $pdo): int
    {
        $q=$pdo->prepare('SELECT id FROM support_teams WHERE code=? AND is_active=1 LIMIT 1');$q->execute([self::TEAM_CODE]);return(int)($q->fetchColumn()?:0);
    }

    private function summary(array $members): array
    {
        $s=[
            'members'=>count($members),'active_cases'=>0,'in_progress'=>0,'pending_cases'=>0,'resolved_30'=>0,
            'nps_responses'=>0,'nps_promoters'=>0,'nps_passives'=>0,'nps_detractors'=>0,'nps_value'=>null,'avg_rating'=>null
        ];
        $ratingTotal=0.0;$ratingCount=0;
        foreach($members as $m){
            $s['active_cases']+=(int)$m['active_cases'];
            $s['in_progress']+=(int)$m['in_progress'];
            $s['pending_cases']+=(int)$m['pending_cases'];
            $s['resolved_30']+=(int)$m['resolved_30'];
            $responses=(int)$m['nps_responses'];
            $s['nps_responses']+=$responses;
            $s['nps_promoters']+=(int)$m['nps_promoters'];
            $s['nps_passives']+=(int)$m['nps_passives'];
            $s['nps_detractors']+=(int)$m['nps_detractors'];
            if($responses>0&&$m['avg_rating']!==null){$ratingTotal+=(float)$m['avg_rating']*$responses;$ratingCount+=$responses;}
        }
        if($s['nps_responses']>0)$s['nps_value']=(int)round((($s['nps_promoters']-$s['nps_detractors'])/$s['nps_responses'])*100);
        $s['avg_rating']=$ratingCount>0?round($ratingTotal/$ratingCount,1):null;
        return $s;
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
