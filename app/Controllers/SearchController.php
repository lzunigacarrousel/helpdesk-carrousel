<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,SearchText,View};
use App\Services\RequesterTopicService;

final class SearchController
{
    public function index(): void
    {
        Auth::requireLogin();
        $pdo=Database::pdo();
        $user=Auth::user();
        $uid=(int)Auth::id();
        $email=strtolower((string)($user['email']??''));
        $q=trim((string)($_GET['q']??''));
        $tokens=SearchText::tokens($q);
        $tickets=[];$problems=[];$articles=[];$helpTopics=[];

        if($q!==''&&$tokens!==[]){
            $isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
            $canViewAll=Auth::can('tickets.view_all');
            $isSupport=!$isExternal&&($canViewAll||Auth::can('tickets.view_queue')||Auth::can('tickets.change_status'));

            $ticketSql="SELECT t.id,t.ticket_number,t.subject,t.description,t.status,t.priority,t.created_at,COALESCE(req.full_name,t.requester_name) requester_name,COALESCE(req.email,t.requester_email) requester_email,
                p.name park_name,a.name area_name,c.name category_name,u.full_name assigned_name
                FROM tickets t
                LEFT JOIN parks p ON p.id=t.park_id
                LEFT JOIN areas a ON a.id=t.area_id
                LEFT JOIN ticket_categories c ON c.id=t.category_id
                LEFT JOIN users u ON u.id=t.assigned_to
                LEFT JOIN users req ON req.id=t.requester_user_id AND req.deleted_at IS NULL ";
            $params=[];$where=['t.deleted_at IS NULL'];

            if($isExternal){
                $ticketSql.=" JOIN external_ticket_access eta ON eta.ticket_id=t.id AND eta.user_id=? AND eta.revoked_at IS NULL ";
                $params[]=$uid;
                $where[]="t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED'";
            }elseif($canViewAll){
                // El permiso tickets.view_all ya determina el alcance total.
            }elseif($isSupport){
                $where[]="(t.assigned_to=? OR (t.assigned_to IS NULL AND t.status IN('NEW','AVAILABLE','REOPENED')) OR t.requester_user_id=? OR LOWER(t.requester_email)=?)";
                array_push($params,$uid,$uid,$email);
            }else{
                $where[]='(t.requester_user_id=? OR LOWER(t.requester_email)=?)';
                array_push($params,$uid,$email);
            }

            foreach($tokens as $token){
                $like='%'.$token.'%';
                $parts=[
                    't.ticket_number LIKE ?',
                    't.subject LIKE ?',
                    't.description LIKE ?',
                    'COALESCE(req.full_name,t.requester_name) LIKE ?',
                    'COALESCE(req.email,t.requester_email) LIKE ?',
                    'p.name LIKE ?',
                    'a.name LIKE ?',
                    'c.name LIKE ?',
                    'u.full_name LIKE ?',
                ];
                for($i=0;$i<9;$i++)$params[]=$like;

                if($isSupport||$canViewAll){
                    $parts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.body LIKE ?)";
                    $params[]=$like;
                    $parts[]="EXISTS(SELECT 1 FROM ticket_resolutions tr2 WHERE tr2.ticket_id=t.id AND (tr2.root_cause LIKE ? OR tr2.solution_applied LIKE ? OR tr2.preventive_action LIKE ?))";
                    array_push($params,$like,$like,$like);
                }elseif($isExternal){
                    $parts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.visibility='EXTERNAL' AND tc.body LIKE ?)";
                    $params[]=$like;
                }else{
                    $parts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.visibility='PUBLIC' AND tc.body LIKE ?)";
                    $params[]=$like;
                }
                $where[]='('.implode(' OR ',$parts).')';
            }

            $ticketSql.=' WHERE '.implode(' AND ',$where).' ORDER BY (t.ticket_number=?) DESC,t.updated_at DESC LIMIT 60';
            $params[]=$q;
            $stmt=$pdo->prepare($ticketSql);$stmt->execute($params);$tickets=$stmt->fetchAll();

            if(Auth::can('problems.view')&&!$isExternal){
                $problemWhere=[];$problemParams=[];
                foreach($tokens as $token){
                    $like='%'.$token.'%';
                    $problemWhere[]='(kp.title LIKE ? OR kp.description LIKE ? OR kp.root_cause LIKE ? OR kp.workaround LIKE ? OR kp.permanent_solution LIKE ? OR kp.problem_number LIKE ? OR p.name LIKE ? OR c.name LIKE ?)';
                    for($i=0;$i<8;$i++)$problemParams[]=$like;
                }
                $s=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,kp.description,kp.root_cause,kp.workaround,kp.permanent_solution,kp.status,p.name park_name,c.name category_name,kp.occurrence_count
                    FROM known_problems kp
                    LEFT JOIN parks p ON p.id=kp.park_id
                    LEFT JOIN ticket_categories c ON c.id=kp.category_id
                    WHERE ".implode(' AND ',$problemWhere)."
                    ORDER BY kp.updated_at DESC LIMIT 20");
                $s->execute($problemParams);$problems=$s->fetchAll();
            }

            if(Auth::can('knowledge.view')&&!$isExternal){
                $editor=Auth::can('knowledge.draft_manage')
                    ||Auth::can('knowledge.review')
                    ||Auth::can('knowledge.publish_internal')
                    ||Auth::can('knowledge.publish_public')
                    ||Auth::can('knowledge.history')
                    ||Auth::can('knowledge.restore');
                $internal=(($user['access_type']??'INTERNAL')==='INTERNAL'&&Auth::role()!=='REQUESTER');
                $knowledgeWhere=[];$knowledgeParams=[];

                if($editor){
                    foreach($tokens as $token){
                        $like='%'.$token.'%';
                        $knowledgeWhere[]='(COALESCE(wr.title,ir.title,lr.title) LIKE ? OR COALESCE(wr.summary,ir.summary,lr.summary) LIKE ? OR COALESCE(wr.content,ir.content,lr.content) LIKE ? OR ka.article_number LIKE ? OR c.name LIKE ?)';
                        for($i=0;$i<5;$i++)$knowledgeParams[]=$like;
                    }
                    $s=$pdo->prepare(
                        "SELECT ka.id,ka.article_number,
                                COALESCE(wr.title,ir.title,lr.title) title,
                                COALESCE(wr.summary,ir.summary,lr.summary) summary,
                                COALESCE(wr.content,ir.content,lr.content) content,
                                CASE WHEN ka.lifecycle_status='ARCHIVED' THEN 'ARCHIVED'
                                     ELSE COALESCE(wr.state,ir.state,lr.state) END status,
                                COALESCE(wr.updated_at,ir.updated_at,lr.updated_at,ka.updated_at) updated_at,
                                c.name category_name,
                                (ka.current_public_revision_id IS NOT NULL) public_available
                         FROM knowledge_articles ka
                         LEFT JOIN knowledge_revisions wr ON wr.id=(
                             SELECT x.id FROM knowledge_revisions x
                             WHERE x.article_id=ka.id AND x.state IN('DRAFT','IN_REVIEW')
                             ORDER BY x.revision_number DESC LIMIT 1
                         )
                         LEFT JOIN knowledge_revisions ir ON ir.id=ka.current_internal_revision_id
                         LEFT JOIN knowledge_revisions lr ON lr.id=(
                             SELECT y.id FROM knowledge_revisions y
                             WHERE y.article_id=ka.id ORDER BY y.revision_number DESC LIMIT 1
                         )
                         LEFT JOIN ticket_categories c ON c.id=COALESCE(wr.category_id,ir.category_id,lr.category_id)
                         WHERE ".implode(' AND ',$knowledgeWhere)."
                         ORDER BY (ka.lifecycle_status='ARCHIVED'),COALESCE(wr.updated_at,ir.updated_at,lr.updated_at,ka.updated_at) DESC
                         LIMIT 20"
                    );
                }else{
                    $pointer=$internal?'current_internal_revision_id':'current_public_revision_id';
                    $knowledgeWhere=["ka.lifecycle_status='ACTIVE'"];
                    foreach($tokens as $token){
                        $like='%'.$token.'%';
                        $knowledgeWhere[]='(kr.title LIKE ? OR kr.summary LIKE ? OR kr.content LIKE ? OR ka.article_number LIKE ? OR c.name LIKE ?)';
                        for($i=0;$i<5;$i++)$knowledgeParams[]=$like;
                    }
                    $s=$pdo->prepare(
                        "SELECT ka.id,ka.article_number,kr.title,kr.summary,kr.content,
                                kr.state status,kr.updated_at,c.name category_name,
                                (ka.current_public_revision_id IS NOT NULL) public_available
                         FROM knowledge_articles ka
                         JOIN knowledge_revisions kr ON kr.id=ka.{$pointer}
                         LEFT JOIN ticket_categories c ON c.id=kr.category_id
                         WHERE ".implode(' AND ',$knowledgeWhere)."
                         ORDER BY kr.updated_at DESC
                         LIMIT 20"
                    );
                }
                $s->execute($knowledgeParams);
                $articles=$s->fetchAll();
            }

            $categoryRows=$pdo->query(
                "SELECT c.id,c.code,c.name,c.parent_id,c.sort_order,
                        p.name parent_name,p.code parent_code,p.sort_order parent_sort_order
                 FROM ticket_categories c
                 LEFT JOIN ticket_categories p ON p.id=c.parent_id
                 WHERE c.is_active=1
                 ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name"
            )->fetchAll();
            foreach(RequesterTopicService::options($categoryRows) as $topic){
                $haystack=implode(' ',[
                    (string)($topic['label']??''),
                    (string)($topic['group']??''),
                    (string)($topic['category_code']??''),
                    (string)($topic['help']??''),
                    (string)($topic['placeholder']??''),
                ]);
                if(!SearchText::matches($haystack,$q))continue;
                $helpTopics[]=$topic;
                if(count($helpTopics)>=20)break;
            }
        }

        View::render('search/index',[
            'user'=>$user,'q'=>$q,'tickets'=>$tickets,'problems'=>$problems,
            'articles'=>$articles,'helpTopics'=>$helpTopics
        ]);
    }
}
