<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,View};

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
        $tickets=[];$problems=[];$articles=[];

        if($q!==''){
            $like='%'.$q.'%';
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
                // Acceso total: el filtro de visibilidad ya lo resuelve el permiso.
            }elseif($isSupport){
                $where[]="(t.assigned_to=? OR (t.assigned_to IS NULL AND t.status IN('NEW','AVAILABLE','REOPENED')) OR t.requester_user_id=? OR LOWER(t.requester_email)=?)";
                array_push($params,$uid,$uid,$email);
            }else{
                $where[]='(t.requester_user_id=? OR LOWER(t.requester_email)=?)';
                array_push($params,$uid,$email);
            }

            $searchParts=[
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
                $searchParts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.body LIKE ?)";
                $params[]=$like;
                $searchParts[]="EXISTS(SELECT 1 FROM ticket_resolutions tr2 WHERE tr2.ticket_id=t.id AND (tr2.root_cause LIKE ? OR tr2.solution_applied LIKE ? OR tr2.preventive_action LIKE ?))";
                array_push($params,$like,$like,$like);
            }elseif($isExternal){
                $searchParts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.visibility='EXTERNAL' AND tc.body LIKE ?)";
                $params[]=$like;
            }else{
                $searchParts[]="EXISTS(SELECT 1 FROM ticket_comments tc WHERE tc.ticket_id=t.id AND tc.deleted_at IS NULL AND tc.visibility='PUBLIC' AND tc.body LIKE ?)";
                $params[]=$like;
            }

            $where[]='('.implode(' OR ',$searchParts).')';
            $ticketSql.=' WHERE '.implode(' AND ',$where).' ORDER BY (t.ticket_number=?) DESC,t.updated_at DESC LIMIT 60';
            $params[]=$q;
            $stmt=$pdo->prepare($ticketSql);$stmt->execute($params);$tickets=$stmt->fetchAll();

            if(Auth::can('problems.view')&&!$isExternal){
                $s=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,kp.description,kp.root_cause,kp.workaround,kp.permanent_solution,kp.status,p.name park_name,c.name category_name,kp.occurrence_count
                    FROM known_problems kp
                    LEFT JOIN parks p ON p.id=kp.park_id
                    LEFT JOIN ticket_categories c ON c.id=kp.category_id
                    WHERE kp.title LIKE ? OR kp.description LIKE ? OR kp.root_cause LIKE ? OR kp.workaround LIKE ? OR kp.permanent_solution LIKE ? OR kp.problem_number LIKE ? OR p.name LIKE ? OR c.name LIKE ?
                    ORDER BY kp.updated_at DESC LIMIT 20");
                $s->execute(array_fill(0,8,$like));$problems=$s->fetchAll();
            }

            if(Auth::can('knowledge.view')&&!$isExternal){
                $manage=Auth::can('knowledge.manage');
                $internal=(($user['access_type']??'INTERNAL')==='INTERNAL'&&Auth::role()!=='REQUESTER');
                $visibilityWhere=$manage?'1=1':($internal?"ka.status='PUBLISHED'":"ka.status='PUBLISHED' AND ka.visibility='PUBLIC'");
                $s=$pdo->prepare("SELECT ka.id,ka.article_number,ka.title,ka.summary,ka.content,ka.status,ka.visibility,ka.updated_at,c.name category_name
                    FROM knowledge_articles ka
                    LEFT JOIN ticket_categories c ON c.id=ka.category_id
                    WHERE {$visibilityWhere} AND (ka.title LIKE ? OR ka.summary LIKE ? OR ka.content LIKE ? OR ka.article_number LIKE ? OR c.name LIKE ?)
                    ORDER BY (ka.status='PUBLISHED') DESC,ka.updated_at DESC LIMIT 20");
                $s->execute(array_fill(0,5,$like));$articles=$s->fetchAll();
            }
        }

        View::render('search/index',['user'=>$user,'q'=>$q,'tickets'=>$tickets,'problems'=>$problems,'articles'=>$articles]);
    }
}
