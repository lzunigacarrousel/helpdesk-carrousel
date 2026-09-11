<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,View};
use App\Services\ProblemService;
use PDO;

final class ProblemController
{
    private const STATUSES=['OPEN'=>'Abierto','INVESTIGATING'=>'En investigación','WORKAROUND'=>'Workaround disponible','PERMANENT_SOLUTION'=>'Solución permanente','CLOSED'=>'Cerrado'];

    public function index(): void
    {
        Auth::requirePermission('problems.view');$pdo=Database::pdo();
        $q=trim((string)($_GET['q']??''));$status=trim((string)($_GET['status']??''));$category=(int)($_GET['category_id']??0);$park=(int)($_GET['park_id']??0);$owner=(int)($_GET['owner_id']??0);
        $where=['1=1'];$params=[];
        if($q!==''){$where[]='(kp.problem_number LIKE ? OR kp.title LIKE ? OR kp.description LIKE ? OR kp.root_cause LIKE ? OR kp.workaround LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like,$like,$like,$like);}
        if(isset(self::STATUSES[$status])){$where[]='kp.status=?';$params[]=$status;}
        if($category>0){$where[]='kp.category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)';$params[]=$category;$params[]=$category;}
        if($park>0){$where[]='kp.park_id=?';$params[]=$park;}
        if($owner>0){$where[]='kp.owner_user_id=?';$params[]=$owner;}
        $sql="SELECT kp.*,c.name category_name,p.name park_name,u.full_name owner_name
              FROM known_problems kp LEFT JOIN ticket_categories c ON c.id=kp.category_id LEFT JOIN parks p ON p.id=kp.park_id LEFT JOIN users u ON u.id=kp.owner_user_id
              WHERE ".implode(' AND ',$where)." ORDER BY FIELD(kp.status,'INVESTIGATING','OPEN','WORKAROUND','PERMANENT_SOLUTION','CLOSED'),kp.updated_at DESC LIMIT 250";
        $s=$pdo->prepare($sql);$s->execute($params);
        View::render('problems/index',['user'=>Auth::user(),'problems'=>$s->fetchAll(),'filters'=>compact('q','status','category','park','owner'),'statuses'=>self::STATUSES,'categories'=>$this->categories(),'parks'=>$this->parks(),'owners'=>$this->owners(),'flash'=>Flash::pull()]);
    }

    public function form(): void
    {
        Auth::requirePermission('problems.manage');$pdo=Database::pdo();$ticketId=(int)($_GET['ticket_id']??0);$prefill=['title'=>'','description'=>'','category_id'=>null,'park_id'=>null,'related_tickets'=>'','related_ticket_id'=>0];
        if($ticketId>0){$s=$pdo->prepare('SELECT ticket_number,subject,description,category_id,park_id FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');$s->execute([$ticketId]);if($t=$s->fetch())$prefill=['title'=>$t['subject'],'description'=>$t['description'],'category_id'=>$t['category_id'],'park_id'=>$t['park_id'],'related_tickets'=>(string)$ticketId,'related_ticket_id'=>$ticketId];}
        View::render('problems/form',['user'=>Auth::user(),'problem'=>null,'prefill'=>$prefill,'statuses'=>self::STATUSES,'categories'=>$this->categories(),'parks'=>$this->parks(),'owners'=>$this->owners(),'availableTickets'=>$this->availableTickets($pdo),'flash'=>Flash::pull()]);
    }

    public function create(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);
        $title=trim((string)($_POST['title']??''));$description=trim((string)($_POST['description']??''));$status=(string)($_POST['status']??'INVESTIGATING');
        if(mb_strlen($title)<5||mb_strlen($description)<10)throw new \RuntimeException('Completa un título y una descripción suficientemente claros.');if(!isset(self::STATUSES[$status]))$status='INVESTIGATING';
        $data=$this->problemData();$service=new ProblemService();$relatedIds=$service->resolveTickets((string)($_POST['related_tickets']??''));
        $problemId=Database::transaction(function(PDO $pdo)use($title,$description,$status,$data,$service,$relatedIds){
            $s=$pdo->prepare("INSERT INTO known_problems(problem_number,title,description,root_cause,workaround,permanent_solution,status,category_id,park_id,owner_user_id,created_by,created_at,updated_at) VALUES('',?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
            $s->execute([$title,$description,$data['root_cause']?:null,$data['workaround']?:null,$data['permanent_solution']?:null,$status,$data['category_id']?:null,$data['park_id']?:null,$data['owner_user_id']?:null,(int)Auth::id()]);
            $id=(int)$pdo->lastInsertId();$number='PRB-'.date('Y').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT);$pdo->prepare('UPDATE known_problems SET problem_number=? WHERE id=?')->execute([$number,$id]);
            foreach($relatedIds as $ticketId){if($service->linkTicket($id,$ticketId,(int)Auth::id()))$this->ticketEvent($ticketId,'PROBLEM_LINKED',['problem_id'=>$id,'problem_number'=>$number]);}
            return $id;
        });
        Audit::log('PROBLEM_CREATED','problem',$problemId,null,['title'=>$title,'status'=>$status],['related_ticket_ids'=>$relatedIds]);Flash::set('Problema conocido creado correctamente.','success');header('Location: '.APP_BASE_URL.'/problems/view?id='.$problemId);exit;
    }

    public function view(): void
    {
        Auth::requirePermission('problems.view');$id=(int)($_GET['id']??0);$pdo=Database::pdo();
        $q=$pdo->prepare("SELECT kp.*,c.name category_name,p.name park_name,u.full_name owner_name,cb.full_name created_by_name FROM known_problems kp LEFT JOIN ticket_categories c ON c.id=kp.category_id LEFT JOIN parks p ON p.id=kp.park_id LEFT JOIN users u ON u.id=kp.owner_user_id LEFT JOIN users cb ON cb.id=kp.created_by WHERE kp.id=? LIMIT 1");$q->execute([$id]);$problem=$q->fetch();if(!$problem)throw new \RuntimeException('Problema conocido no encontrado.');
        $o=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.status,t.priority,t.created_at,t.updated_at,p.name park_name,c.name category_name FROM problem_occurrences po JOIN tickets t ON t.id=po.ticket_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN ticket_categories c ON c.id=t.category_id WHERE po.problem_id=? AND t.deleted_at IS NULL ORDER BY t.created_at DESC");$o->execute([$id]);
        $a=$pdo->prepare("SELECT ka.*,ps.is_primary,u.full_name author_name FROM problem_solutions ps JOIN knowledge_articles ka ON ka.id=ps.article_id LEFT JOIN users u ON u.id=ka.author_user_id WHERE ps.problem_id=? ORDER BY ps.is_primary DESC,ka.updated_at DESC");$a->execute([$id]);
        $tl=$pdo->prepare("SELECT al.*,u.full_name actor_name FROM audit_logs al LEFT JOIN users u ON u.id=al.actor_user_id WHERE al.entity_type='problem' AND al.entity_id=? ORDER BY al.created_at DESC LIMIT 40");$tl->execute([(string)$id]);
        $availableTickets=Auth::can('problems.manage')?$this->availableTickets($pdo,$id):[];
        $availableArticles=[];if(Auth::can('problems.manage')&&Auth::can('knowledge.view')){$s=$pdo->prepare("SELECT ka.id,ka.article_number,ka.title FROM knowledge_articles ka WHERE ka.status<>'ARCHIVED' AND NOT EXISTS(SELECT 1 FROM problem_solutions ps WHERE ps.problem_id=? AND ps.article_id=ka.id) ORDER BY ka.updated_at DESC LIMIT 100");$s->execute([$id]);$availableArticles=$s->fetchAll();}
        View::render('problems/show',['user'=>Auth::user(),'problem'=>$problem,'occurrences'=>$o->fetchAll(),'articles'=>$a->fetchAll(),'timeline'=>$tl->fetchAll(),'availableArticles'=>$availableArticles,'availableTickets'=>$availableTickets,'statuses'=>self::STATUSES,'categories'=>$this->categories(),'parks'=>$this->parks(),'owners'=>$this->owners(),'flash'=>Flash::pull()]);
    }

    public function update(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);$id=(int)($_POST['problem_id']??0);$pdo=Database::pdo();$q=$pdo->prepare('SELECT * FROM known_problems WHERE id=? LIMIT 1');$q->execute([$id]);$old=$q->fetch();if(!$old)throw new \RuntimeException('Problema no encontrado.');
        $title=trim((string)($_POST['title']??''));$description=trim((string)($_POST['description']??''));$status=(string)($_POST['status']??$old['status']);if(mb_strlen($title)<5||mb_strlen($description)<10)throw new \RuntimeException('Completa título y descripción.');if(!isset(self::STATUSES[$status]))$status=$old['status'];$d=$this->problemData();
        $u=$pdo->prepare('UPDATE known_problems SET title=?,description=?,root_cause=?,workaround=?,permanent_solution=?,status=?,category_id=?,park_id=?,owner_user_id=?,updated_at=NOW() WHERE id=?');$u->execute([$title,$description,$d['root_cause']?:null,$d['workaround']?:null,$d['permanent_solution']?:null,$status,$d['category_id']?:null,$d['park_id']?:null,$d['owner_user_id']?:null,$id]);
        Audit::log('PROBLEM_UPDATED','problem',$id,$old,['title'=>$title,'description'=>$description,'status'=>$status,'root_cause'=>$d['root_cause'],'workaround'=>$d['workaround'],'permanent_solution'=>$d['permanent_solution'],'category_id'=>$d['category_id']?:null,'park_id'=>$d['park_id']?:null,'owner_user_id'=>$d['owner_user_id']?:null]);Flash::set('Problema actualizado.','success');header('Location: '.APP_BASE_URL.'/problems/view?id='.$id);exit;
    }

    public function linkTicket(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);$problemId=(int)($_POST['problem_id']??0);$token=trim((string)($_POST['ticket']??($_POST['ticket_id']??'')));$returnTicket=(int)($_POST['return_ticket_id']??0);$pdo=Database::pdo();
        $exists=$pdo->prepare('SELECT problem_number FROM known_problems WHERE id=? LIMIT 1');$exists->execute([$problemId]);$problemNumber=$exists->fetchColumn();if(!$problemNumber)throw new \RuntimeException('Problema conocido no válido.');
        $service=new ProblemService();$ids=$service->resolveTickets($token);if(!$ids)throw new \RuntimeException('Indica un ticket válido.');
        foreach($ids as $ticketId){if($service->linkTicket($problemId,$ticketId,(int)Auth::id())){$this->ticketEvent($ticketId,'PROBLEM_LINKED',['problem_id'=>$problemId,'problem_number'=>$problemNumber]);Audit::log('TICKET_PROBLEM_LINKED','problem',$problemId,null,['ticket_id'=>$ticketId]);}}
        Flash::set('Ticket relacionado con el problema.','success');$this->redirectAfterRelation($problemId,$returnTicket);exit;
    }

    public function unlinkTicket(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);$problemId=(int)($_POST['problem_id']??0);$ticketId=(int)($_POST['ticket_id']??0);$returnTicket=(int)($_POST['return_ticket_id']??0);$service=new ProblemService();if($service->unlinkTicket($problemId,$ticketId)){$this->ticketEvent($ticketId,'PROBLEM_UNLINKED',['problem_id'=>$problemId]);Audit::log('TICKET_PROBLEM_UNLINKED','problem',$problemId,['ticket_id'=>$ticketId],null);}Flash::set('Relación eliminada.','success');$this->redirectAfterRelation($problemId,$returnTicket);exit;
    }

    public function linkArticle(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);$problemId=(int)($_POST['problem_id']??0);$articleId=(int)($_POST['article_id']??0);$primary=!empty($_POST['is_primary'])?1:0;if($problemId<=0||$articleId<=0)throw new \RuntimeException('Selecciona un artículo válido.');$pdo=Database::pdo();
        $check=$pdo->prepare("SELECT COUNT(*) FROM knowledge_articles WHERE id=? AND status<>'ARCHIVED'");$check->execute([$articleId]);if((int)$check->fetchColumn()!==1)throw new \RuntimeException('El artículo seleccionado no está disponible.');
        if($primary)$pdo->prepare('UPDATE problem_solutions SET is_primary=0 WHERE problem_id=?')->execute([$problemId]);$s=$pdo->prepare('INSERT INTO problem_solutions(problem_id,article_id,is_primary,linked_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE is_primary=VALUES(is_primary)');$s->execute([$problemId,$articleId,$primary]);Audit::log('PROBLEM_ARTICLE_LINKED','problem',$problemId,null,['article_id'=>$articleId,'is_primary'=>$primary]);Flash::set('Artículo relacionado.','success');header('Location: '.APP_BASE_URL.'/problems/view?id='.$problemId);exit;
    }

    public function unlinkArticle(): void
    {
        Auth::requirePermission('problems.manage');Csrf::verify($_POST['_csrf']??null);$problemId=(int)($_POST['problem_id']??0);$articleId=(int)($_POST['article_id']??0);Database::pdo()->prepare('DELETE FROM problem_solutions WHERE problem_id=? AND article_id=?')->execute([$problemId,$articleId]);Audit::log('PROBLEM_ARTICLE_UNLINKED','problem',$problemId,['article_id'=>$articleId],null);Flash::set('Artículo desvinculado.','success');header('Location: '.APP_BASE_URL.'/problems/view?id='.$problemId);exit;
    }

    private function availableTickets(PDO $pdo,int $problemId=0): array
    {
        $sql="SELECT t.id,t.ticket_number,t.subject,t.status,t.created_at,COALESCE(p.name,'Sin parque') park_name,COALESCE(c.name,'Sin categoría') category_name
              FROM tickets t
              LEFT JOIN parks p ON p.id=t.park_id
              LEFT JOIN ticket_categories c ON c.id=t.category_id
              WHERE t.deleted_at IS NULL";
        $params=[];
        if($problemId>0){$sql.=" AND NOT EXISTS(SELECT 1 FROM problem_occurrences po WHERE po.problem_id=? AND po.ticket_id=t.id)";$params[]=$problemId;}
        $sql.=" ORDER BY t.created_at DESC LIMIT 300";
        $q=$pdo->prepare($sql);$q->execute($params);return $q->fetchAll()?:[];
    }
    private function redirectAfterRelation(int $problemId,int $returnTicket):void{header('Location: '.APP_BASE_URL.($returnTicket>0?'/tickets/view?id='.$returnTicket:'/problems/view?id='.$problemId));}
    private function problemData(): array{return['root_cause'=>trim((string)($_POST['root_cause']??'')),'workaround'=>trim((string)($_POST['workaround']??'')),'permanent_solution'=>trim((string)($_POST['permanent_solution']??'')),'category_id'=>(int)($_POST['category_id']??0),'park_id'=>(int)($_POST['park_id']??0),'owner_user_id'=>(int)($_POST['owner_user_id']??0)];}
    private function categories():array{return Database::pdo()->query("SELECT c.id,c.code,c.parent_id,CASE WHEN p.id IS NULL THEN c.name ELSE CONCAT(p.name,' · ',c.name) END name FROM ticket_categories c LEFT JOIN ticket_categories p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name")->fetchAll();}
    private function parks():array{return Database::pdo()->query('SELECT id,name FROM parks WHERE is_active=1 ORDER BY name')->fetchAll();}
    private function owners():array{return Database::pdo()->query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll();}
    private function ticketEvent(int $ticketId,string $type,array $payload):void{$s=Database::pdo()->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?, ?, ?, 'USER', ?, NOW())");$s->execute([$ticketId,$type,(int)Auth::id(),json_encode($payload,JSON_UNESCAPED_UNICODE)]);}
}
