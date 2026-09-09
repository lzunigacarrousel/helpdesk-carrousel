<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database,View};
use PDO;

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
            $ticketSql="SELECT t.id,t.ticket_number,t.subject,t.description,t.status,t.priority,t.created_at,
                    t.requester_name,t.requester_email,p.name park_name,c.name category_name,u.full_name assigned_name
                FROM tickets t
                LEFT JOIN parks p ON p.id=t.park_id
                LEFT JOIN ticket_categories c ON c.id=t.category_id
                LEFT JOIN users u ON u.id=t.assigned_to ";
            $params=[];$where=['t.deleted_at IS NULL'];
            $isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
            $isSupport=!$isExternal&&(Auth::can('tickets.view_all')||Auth::can('tickets.view_queue')||Auth::can('tickets.change_status'));

            if($isExternal){
                $ticketSql.=" JOIN external_ticket_access eta ON eta.ticket_id=t.id AND eta.user_id=? AND eta.revoked_at IS NULL ";
                $params[]=$uid;
                $where[]="t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED'";
            }elseif(Auth::can('tickets.view_all')){
                // Administradores / alcance global.
            }elseif($isSupport){
                $where[]="(t.assigned_to=? OR (t.assigned_to IS NULL AND t.status IN('NEW','AVAILABLE','REOPENED')) OR t.requester_user_id=? OR LOWER(t.requester_email)=?)";
                array_push($params,$uid,$uid,$email);
            }else{
                $where[]='(t.requester_user_id=? OR LOWER(t.requester_email)=?)';
                array_push($params,$uid,$email);
            }

            $where[]='(t.ticket_number LIKE ? OR t.subject LIKE ? OR t.description LIKE ? OR t.requester_name LIKE ? OR t.requester_email LIKE ? OR p.name LIKE ? OR c.name LIKE ?)';
            for($i=0;$i<7;$i++)$params[]=$like;
            $ticketSql.=' WHERE '.implode(' AND ',$where).' ORDER BY (t.ticket_number=?) DESC,t.updated_at DESC LIMIT 40';
            $params[]=$q;
            $stmt=$pdo->prepare($ticketSql);$stmt->execute($params);$tickets=$stmt->fetchAll();

            if($isSupport){
                try{
                    if(Auth::can('problems.view')||Auth::can('problems.manage')||Auth::can('tickets.view_all')){
                        $s=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,kp.description,kp.root_cause,kp.workaround,kp.permanent_solution,kp.status,p.name park_name,c.name category_name,kp.occurrence_count
                            FROM known_problems kp LEFT JOIN parks p ON p.id=kp.park_id LEFT JOIN ticket_categories c ON c.id=kp.category_id
                            WHERE kp.title LIKE ? OR kp.description LIKE ? OR kp.root_cause LIKE ? OR kp.workaround LIKE ? OR kp.permanent_solution LIKE ? OR kp.problem_number LIKE ? OR p.name LIKE ? OR c.name LIKE ?
                            ORDER BY kp.updated_at DESC LIMIT 12");
                        $s->execute(array_fill(0,8,$like));$problems=$s->fetchAll();
                    }
                    if(Auth::can('knowledge.view')||Auth::can('knowledge.manage')||Auth::can('tickets.view_all')){
                        $s=$pdo->prepare("SELECT ka.id,ka.article_number,ka.title,ka.summary,ka.content,ka.status,ka.visibility,ka.updated_at,c.name category_name
                            FROM knowledge_articles ka LEFT JOIN ticket_categories c ON c.id=ka.category_id
                            WHERE ka.status<>'ARCHIVED' AND (ka.title LIKE ? OR ka.summary LIKE ? OR ka.content LIKE ? OR ka.article_number LIKE ? OR c.name LIKE ?)
                            ORDER BY (ka.status='PUBLISHED') DESC,ka.updated_at DESC LIMIT 12");
                        $s->execute(array_fill(0,5,$like));$articles=$s->fetchAll();
                    }
                }catch(\Throwable){
                    $problems=[];$articles=[];
                }
            }
        }

        View::render('search/index',[
            'user'=>$user,'q'=>$q,'tickets'=>$tickets,'problems'=>$problems,'articles'=>$articles,
        ]);
    }
}
