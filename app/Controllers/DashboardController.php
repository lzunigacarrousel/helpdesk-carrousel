<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,Flash,View};

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        if(Auth::isManagementViewer()){
            header('Location: '.APP_BASE_URL.'/gestion');
            exit;
        }

        $pdo=Database::pdo();$user=Auth::user();$uid=(int)Auth::id();
        $isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
        $isSupport=Auth::isSupportOperator();

        $stmt=$pdo->prepare("SELECT ua.*,p.name park_name,a.name area_name,rg.name region_name,pos.name position_name,m.full_name manager_name FROM user_assignments ua LEFT JOIN parks p ON p.id=ua.park_id LEFT JOIN areas a ON a.id=ua.area_id LEFT JOIN regions rg ON rg.id=ua.region_id LEFT JOIN positions pos ON pos.id=ua.position_id LEFT JOIN users m ON m.id=ua.manager_user_id WHERE ua.user_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL ORDER BY ua.id DESC LIMIT 1");$stmt->execute([$uid]);$assignment=$stmt->fetch()?:null;
        if($isExternal){$stats=$pdo->prepare("SELECT COUNT(*) total,SUM(t.status IN ('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')) open_count,SUM(t.status='RESOLVED') resolved_count,SUM(t.status='CLOSED') closed_count FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED'");$stats->execute([$uid]);$ticketStats=$stats->fetch()?:['total'=>0,'open_count'=>0,'resolved_count'=>0,'closed_count'=>0];$recent=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.description,t.status,t.created_at FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED' ORDER BY t.updated_at DESC,t.created_at DESC LIMIT 5");$recent->execute([$uid]);}
        else{$stats=$pdo->prepare("SELECT COUNT(*) total,SUM(status IN ('NEW','AVAILABLE','IN_PROGRESS','PENDING','REOPENED')) open_count,SUM(status='RESOLVED') resolved_count,SUM(status='CLOSED') closed_count FROM tickets WHERE deleted_at IS NULL AND (requester_user_id=? OR LOWER(requester_email)=LOWER(?))");$stats->execute([$uid,(string)$user['email']]);$ticketStats=$stats->fetch()?:['total'=>0,'open_count'=>0,'resolved_count'=>0,'closed_count'=>0];$recent=$pdo->prepare("SELECT id,ticket_number,subject,description,status,created_at FROM tickets WHERE deleted_at IS NULL AND (requester_user_id=? OR LOWER(requester_email)=LOWER(?)) ORDER BY updated_at DESC,created_at DESC LIMIT 5");$recent->execute([$uid,(string)$user['email']]);}
        $recentTickets=$recent->fetchAll();
        $supportStats=['mine'=>0,'available'=>0,'near_due'=>0,'overdue'=>0,'critical'=>0,'pending'=>0,'reopened'=>0,'in_progress'=>0];$supportActivity=[];$itsmStats=['problems_open'=>0,'problems_investigating'=>0,'knowledge_drafts'=>0];
        $externalCollabStats=['active_cases'=>0,'waiting_provider'=>0,'active_providers'=>0,'responses_today'=>0];
        if($isSupport){$support=$pdo->prepare("SELECT SUM(assigned_to=? AND status IN('IN_PROGRESS','PENDING','REOPENED')) mine,SUM(assigned_to IS NULL AND status IN('NEW','AVAILABLE','REOPENED')) available,SUM(status='IN_PROGRESS') in_progress,SUM(status='PENDING') pending,SUM(status='REOPENED') reopened,SUM(priority='CRITICAL' AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) critical,SUM(resolution_due_at IS NOT NULL AND resolution_due_at<NOW() AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) overdue,SUM(resolution_due_at IS NOT NULL AND resolution_due_at>=NOW() AND resolution_due_at<=DATE_ADD(NOW(),INTERVAL 2 HOUR) AND status NOT IN('RESOLVED','CLOSED','CANCELLED')) near_due FROM tickets WHERE deleted_at IS NULL AND (assigned_to=? OR assigned_to IS NULL)");$support->execute([$uid,$uid]);$row=$support->fetch()?:[];foreach($supportStats as $key=>$value)$supportStats[$key]=(int)($row[$key]??0);
            $activity=$pdo->prepare("SELECT te.event_type,te.new_value,te.created_at,t.id ticket_id,t.ticket_number,t.subject,u.full_name actor_name,u.access_type actor_access_type,r.code actor_role FROM ticket_events te JOIN tickets t ON t.id=te.ticket_id LEFT JOIN users u ON u.id=te.actor_user_id LEFT JOIN roles r ON r.id=u.role_id WHERE t.deleted_at IS NULL AND (t.assigned_to=? OR t.assigned_to IS NULL) AND te.event_type IN('CREATED','CLAIMED','REASSIGNED','COMMENTED','RESOLUTION_RECORDED','RESOLVED','CLOSED','REOPENED','STATUS_CHANGED','PROBLEM_LINKED','KNOWLEDGE_CREATED') ORDER BY te.created_at DESC,te.id DESC LIMIT 8");$activity->execute([$uid]);$supportActivity=$activity->fetchAll();
            if(Auth::can('problems.view')){$itsmStats['problems_open']=(int)$pdo->query("SELECT COUNT(*) FROM known_problems WHERE status<>'CLOSED'")->fetchColumn();$itsmStats['problems_investigating']=(int)$pdo->query("SELECT COUNT(*) FROM known_problems WHERE status='INVESTIGATING'")->fetchColumn();}
            $canSeeKnowledgeWork=Auth::can('knowledge.draft_manage')
                ||Auth::can('knowledge.review')
                ||Auth::can('knowledge.publish_internal');
            if($canSeeKnowledgeWork){
                $itsmStats['knowledge_drafts']=(int)$pdo->query(
                    "SELECT COUNT(DISTINCT kr.article_id)
                     FROM knowledge_revisions kr
                     JOIN knowledge_articles ka ON ka.id=kr.article_id
                     WHERE ka.lifecycle_status='ACTIVE'
                       AND kr.state IN('DRAFT','IN_REVIEW')"
                )->fetchColumn();
            }
            if(Auth::role()==='ADMIN'||Auth::role()==='SEMIADMIN'||Auth::can('external.manage')){
                $ex=$pdo->query("SELECT COUNT(DISTINCT CASE WHEN t.status NOT IN('RESOLVED','CLOSED','CANCELLED') THEN t.id END) active_cases,COUNT(DISTINCT CASE WHEN t.status='PENDING' AND t.pending_reason_code='WAITING_PROVIDER' THEN t.id END) waiting_provider,COUNT(DISTINCT CASE WHEN t.status NOT IN('RESOLVED','CLOSED','CANCELLED') THEN eta.user_id END) active_providers,COUNT(DISTINCT CASE WHEN tc.created_at>=CURDATE() AND cu.access_type='EXTERNAL' THEN tc.id END) responses_today FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_comments tc ON tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.visibility='EXTERNAL' LEFT JOIN users cu ON cu.id=tc.author_user_id WHERE eta.revoked_at IS NULL AND t.deleted_at IS NULL")->fetch()?:[];
                foreach($externalCollabStats as $key=>$value)$externalCollabStats[$key]=(int)($ex[$key]??0);
            }
        }
        View::render('dashboard/index',['user'=>$user,'assignment'=>$assignment,'ticketStats'=>$ticketStats,'recentTickets'=>$recentTickets,'supportStats'=>$supportStats,'supportActivity'=>$supportActivity,'itsmStats'=>$itsmStats,'externalCollabStats'=>$externalCollabStats,'flash'=>Flash::pull()]);
    }
}
