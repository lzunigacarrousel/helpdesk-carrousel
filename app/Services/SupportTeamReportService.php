<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class SupportTeamReportService
{
    private const TEAM_CODE='IT';

    public function __construct(private ?PDO $pdo=null){}

    private function db(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }

    public function activeTeamId(): int
    {
        $q=$this->db()->prepare('SELECT id FROM support_teams WHERE code=? AND is_active=1 LIMIT 1');
        $q->execute([self::TEAM_CODE]);
        return (int)($q->fetchColumn()?:0);
    }

    public function members(int $teamId): array
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

        $q=$this->db()->prepare($sql);
        $q->execute([$teamId]);
        $rows=$q->fetchAll()?:[];

        foreach($rows as &$row){
            $responses=(int)$row['nps_responses'];
            $row['nps_value']=$responses>0
                ?(int)round((((int)$row['nps_promoters']-(int)$row['nps_detractors'])/$responses)*100)
                :null;
        }
        unset($row);

        return $rows;
    }

    public function summary(array $members): array
    {
        $s=[
            'members'=>count($members),
            'active_cases'=>0,
            'in_progress'=>0,
            'pending_cases'=>0,
            'resolved_30'=>0,
            'nps_responses'=>0,
            'nps_promoters'=>0,
            'nps_passives'=>0,
            'nps_detractors'=>0,
            'nps_value'=>null,
            'avg_rating'=>null,
        ];

        $ratingTotal=0.0;
        $ratingCount=0;

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

            if($responses>0&&$m['avg_rating']!==null){
                $ratingTotal+=(float)$m['avg_rating']*$responses;
                $ratingCount+=$responses;
            }
        }

        if($s['nps_responses']>0){
            $s['nps_value']=(int)round(
                (($s['nps_promoters']-$s['nps_detractors'])/$s['nps_responses'])*100
            );
        }

        $s['avg_rating']=$ratingCount>0?round($ratingTotal/$ratingCount,1):null;
        return $s;
    }

    public function reportSummary(array $filters): array
    {
        $teamId=$this->activeTeamId();
        $members=$this->members($teamId);
        $memberIds=array_values(array_filter(
            array_map(static fn(array $row):int=>(int)($row['id']??0),$members),
            static fn(int $id):bool=>$id>0
        ));

        $result=[
            'members'=>count($memberIds),
            'tickets_period'=>0,
            'resolved_period'=>0,
            'active_cases'=>0,
            'in_progress'=>0,
            'pending_cases'=>0,
            'avg_first_response_min'=>null,
            'avg_resolution_hours'=>null,
            'nps_responses'=>0,
            'nps_value'=>null,
            'avg_rating'=>null,
        ];

        if($memberIds===[])return $result;

        $pdo=$this->db();
        $filterService=new TicketReportFilterService();
        [$where,$params]=$filterService->where($filters,'t');
        $memberPlaceholders=implode(',',array_fill(0,count($memberIds),'?'));

        $q=$pdo->prepare(
            "SELECT
                COUNT(*) tickets_period,
                COALESCE(SUM(t.resolved_at IS NOT NULL OR t.status IN('RESOLVED','CLOSED')),0) resolved_period,
                COALESCE(SUM(t.status NOT IN('RESOLVED','CLOSED','CANCELLED')),0) active_cases,
                COALESCE(SUM(t.status='IN_PROGRESS'),0) in_progress,
                COALESCE(SUM(t.status='PENDING'),0) pending_cases,
                AVG(CASE WHEN t.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.first_response_at) END) avg_first_response_min,
                AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,t.created_at,t.resolved_at)/60 END) avg_resolution_hours
             FROM tickets t
             {$where}
             AND t.assigned_to IN ({$memberPlaceholders})"
        );
        $q->execute(array_merge($params,$memberIds));
        $row=$q->fetch()?:[];

        foreach(['tickets_period','resolved_period','active_cases','in_progress','pending_cases'] as $key){
            $result[$key]=(int)($row[$key]??0);
        }
        $result['avg_first_response_min']=$row['avg_first_response_min']!==null
            ?round((float)$row['avg_first_response_min'],1)
            :null;
        $result['avg_resolution_hours']=$row['avg_resolution_hours']!==null
            ?round((float)$row['avg_resolution_hours'],1)
            :null;

        $feedback=$pdo->prepare(
            "SELECT tf.nps_score
             FROM ticket_feedback tf
             JOIN tickets t ON t.id=tf.ticket_id
             {$where}
             AND t.assigned_to IN ({$memberPlaceholders})"
        );
        $feedback->execute(array_merge($params,$memberIds));
        $scores=array_values(array_filter(
            array_map(static fn(array $r):?int=>isset($r['nps_score'])?(int)$r['nps_score']:null,$feedback->fetchAll()?:[]),
            static fn(?int $score):bool=>$score!==null
        ));

        if($scores!==[]){
            $promoters=count(array_filter($scores,static fn(int $score):bool=>$score>=9));
            $detractors=count(array_filter($scores,static fn(int $score):bool=>$score<=6));
            $result['nps_responses']=count($scores);
            $result['nps_value']=(int)round((($promoters-$detractors)/count($scores))*100);
            $result['avg_rating']=round(array_sum($scores)/count($scores),1);
        }

        return $result;
    }
}
