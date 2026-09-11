<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,View};

final class KnowledgeController
{
    private const STATUSES=['DRAFT'=>'Borrador','PUBLISHED'=>'Publicado','ARCHIVED'=>'Archivado'];
    private const VISIBILITY=['INTERNAL'=>'Interno','PUBLIC'=>'Público'];

    public function index(): void
    {
        Auth::requirePermission('knowledge.view');$pdo=Database::pdo();$manage=Auth::can('knowledge.manage');$internal=$this->canSeeInternal();
        $q=trim((string)($_GET['q']??''));$status=trim((string)($_GET['status']??''));$visibility=trim((string)($_GET['visibility']??''));$category=(int)($_GET['category_id']??0);
        $where=['1=1'];$params=[];
        if(!$manage){$where[]="ka.status='PUBLISHED'";if(!$internal)$where[]="ka.visibility='PUBLIC'";}
        if($q!==''){$like='%'.$q.'%';$where[]='(ka.article_number LIKE ? OR ka.title LIKE ? OR ka.summary LIKE ? OR ka.content LIKE ?)';array_push($params,$like,$like,$like,$like);}
        if($manage&&isset(self::STATUSES[$status])){$where[]='ka.status=?';$params[]=$status;}
        if($manage&&isset(self::VISIBILITY[$visibility])){$where[]='ka.visibility=?';$params[]=$visibility;}
        if($category>0){$where[]='ka.category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)';$params[]=$category;$params[]=$category;}
        $sql="SELECT ka.*,c.name category_name,u.full_name author_name FROM knowledge_articles ka LEFT JOIN ticket_categories c ON c.id=ka.category_id LEFT JOIN users u ON u.id=ka.author_user_id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(ka.status,'DRAFT','PUBLISHED','ARCHIVED'),ka.updated_at DESC LIMIT 250";
        $s=$pdo->prepare($sql);$s->execute($params);
        View::render('knowledge/index',['user'=>Auth::user(),'articles'=>$s->fetchAll(),'filters'=>compact('q','status','visibility','category'),'statuses'=>self::STATUSES,'visibility'=>self::VISIBILITY,'categories'=>$this->categories(),'canManage'=>$manage,'flash'=>Flash::pull()]);
    }

    public function form(): void
    {
        Auth::requirePermission('knowledge.manage');$pdo=Database::pdo();$id=(int)($_GET['id']??0);$article=null;$prefill=['title'=>'','summary'=>'','content'=>'','category_id'=>null,'visibility'=>'INTERNAL','source_ticket_id'=>0,'source_problem_id'=>0];
        $oldForm=$_SESSION['knowledge_form_old']??null;unset($_SESSION['knowledge_form_old']);if(!$article&&is_array($oldForm))$prefill=array_merge($prefill,$oldForm);
        if($id>0){$q=$pdo->prepare('SELECT * FROM knowledge_articles WHERE id=? LIMIT 1');$q->execute([$id]);$article=$q->fetch();if(!$article)throw new \RuntimeException('Artículo no encontrado.');}
        $ticketId=(int)($_GET['ticket_id']??0);$problemId=(int)($_GET['problem_id']??0);
        if(!$article&&$ticketId>0){$q=$pdo->prepare("SELECT t.id,t.subject,t.description,t.category_id,tr.root_cause,tr.solution_applied,tr.preventive_action FROM tickets t JOIN ticket_resolutions tr ON tr.ticket_id=t.id WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1");$q->execute([$ticketId]);if($t=$q->fetch()){$problem=$pdo->prepare('SELECT problem_id FROM problem_occurrences WHERE ticket_id=? ORDER BY created_at DESC LIMIT 1');$problem->execute([$ticketId]);$linkedProblem=(int)($problem->fetchColumn()?:0);$prefill=['title'=>$t['subject'],'summary'=>mb_strimwidth((string)$t['description'],0,280,'…'),'content'=>$this->ticketTemplate($t),'category_id'=>$t['category_id'],'visibility'=>'INTERNAL','source_ticket_id'=>$ticketId,'source_problem_id'=>$linkedProblem];}}
        if(!$article&&$problemId>0){$q=$pdo->prepare('SELECT * FROM known_problems WHERE id=? LIMIT 1');$q->execute([$problemId]);if($p=$q->fetch())$prefill=['title'=>$p['title'],'summary'=>mb_strimwidth((string)$p['description'],0,280,'…'),'content'=>$this->problemTemplate($p),'category_id'=>$p['category_id'],'visibility'=>'INTERNAL','source_ticket_id'=>0,'source_problem_id'=>$problemId];}
        View::render('knowledge/form',['user'=>Auth::user(),'article'=>$article,'prefill'=>$prefill,'visibility'=>self::VISIBILITY,'categories'=>$this->categories(),'flash'=>Flash::pull()]);
    }

    public function create(): void
    {
        Auth::requirePermission('knowledge.manage');Csrf::verify($_POST['_csrf']??null);$title=trim((string)($_POST['title']??''));$summary=trim((string)($_POST['summary']??''));$content=trim((string)($_POST['content']??''));$visibility=(string)($_POST['visibility']??'INTERNAL');$category=(int)($_POST['category_id']??0);$sourceTicket=(int)($_POST['source_ticket_id']??0);$sourceProblem=(int)($_POST['source_problem_id']??0);if(mb_strlen($title)<5||mb_strlen($content)<20){$issues=[];if(mb_strlen($title)<5)$issues[]='Escribe un título de al menos 5 caracteres.';if(mb_strlen($content)<20)$issues[]='Agrega una explicación de al menos 20 caracteres en Contenido.';$_SESSION['knowledge_form_old']=['title'=>$title,'summary'=>$summary,'content'=>$content,'category_id'=>$category?:null,'visibility'=>$visibility,'source_ticket_id'=>$sourceTicket,'source_problem_id'=>$sourceProblem];Flash::set(implode(' ',$issues),'info');header('Location: '.APP_BASE_URL.'/knowledge/new');exit;}if(!isset(self::VISIBILITY[$visibility]))$visibility='INTERNAL';$pdo=Database::pdo();
        $s=$pdo->prepare("INSERT INTO knowledge_articles(article_number,title,summary,content,status,visibility,category_id,author_user_id,created_at,updated_at) VALUES('',?,?,?,'DRAFT',?,?,?,NOW(),NOW())");$s->execute([$title,$summary?:null,$content,$visibility,$category?:null,(int)Auth::id()]);$id=(int)$pdo->lastInsertId();$number='KB-'.date('Y').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT);$pdo->prepare('UPDATE knowledge_articles SET article_number=? WHERE id=?')->execute([$number,$id]);
        if($sourceProblem>0)$pdo->prepare('INSERT IGNORE INTO problem_solutions(problem_id,article_id,is_primary,linked_at) VALUES(?,?,0,NOW())')->execute([$sourceProblem,$id]);if($sourceTicket>0)$this->ticketEvent($sourceTicket,'KNOWLEDGE_CREATED',['article_id'=>$id,'article_number'=>$number]);
        Audit::log('KNOWLEDGE_CREATED','knowledge_article',$id,null,['article_number'=>$number,'title'=>$title,'status'=>'DRAFT','visibility'=>$visibility],['source_ticket_id'=>$sourceTicket?:null,'source_problem_id'=>$sourceProblem?:null]);Flash::set('Artículo creado como borrador. Revísalo antes de publicar.','success');header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$id);exit;
    }

    public function update(): void
    {
        Auth::requirePermission('knowledge.manage');Csrf::verify($_POST['_csrf']??null);$id=(int)($_POST['article_id']??0);$pdo=Database::pdo();$q=$pdo->prepare('SELECT * FROM knowledge_articles WHERE id=? LIMIT 1');$q->execute([$id]);$old=$q->fetch();if(!$old)throw new \RuntimeException('Artículo no encontrado.');$title=trim((string)($_POST['title']??''));$summary=trim((string)($_POST['summary']??''));$content=trim((string)($_POST['content']??''));$visibility=(string)($_POST['visibility']??$old['visibility']);$category=(int)($_POST['category_id']??0);if(mb_strlen($title)<5||mb_strlen($content)<20)throw new \RuntimeException('Completa título y contenido.');if(!isset(self::VISIBILITY[$visibility]))$visibility=$old['visibility'];$u=$pdo->prepare('UPDATE knowledge_articles SET title=?,summary=?,content=?,visibility=?,category_id=?,updated_at=NOW() WHERE id=?');$u->execute([$title,$summary?:null,$content,$visibility,$category?:null,$id]);Audit::log('KNOWLEDGE_UPDATED','knowledge_article',$id,$old,['title'=>$title,'summary'=>$summary,'visibility'=>$visibility,'category_id'=>$category?:null]);Flash::set('Artículo actualizado.','success');header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$id);exit;
    }

    public function view(): void
    {
        Auth::requirePermission('knowledge.view');$id=(int)($_GET['id']??0);$pdo=Database::pdo();$q=$pdo->prepare("SELECT ka.*,c.name category_name,u.full_name author_name FROM knowledge_articles ka LEFT JOIN ticket_categories c ON c.id=ka.category_id LEFT JOIN users u ON u.id=ka.author_user_id WHERE ka.id=? LIMIT 1");$q->execute([$id]);$article=$q->fetch();if(!$article)throw new \RuntimeException('Artículo no encontrado.');$manage=Auth::can('knowledge.manage');if(!$manage&&($article['status']!=='PUBLISHED'||(!$this->canSeeInternal()&&$article['visibility']!=='PUBLIC'))){Flash::set('Ese artículo no está disponible para tu perfil.','info');header('Location: '.APP_BASE_URL.'/knowledge');exit;}
        $p=$pdo->prepare("SELECT kp.id,kp.problem_number,kp.title,ps.is_primary FROM problem_solutions ps JOIN known_problems kp ON kp.id=ps.problem_id WHERE ps.article_id=? ORDER BY ps.is_primary DESC,kp.updated_at DESC");$p->execute([$id]);View::render('knowledge/show',['user'=>Auth::user(),'article'=>$article,'problems'=>$p->fetchAll(),'statuses'=>self::STATUSES,'visibility'=>self::VISIBILITY,'canManage'=>$manage,'flash'=>Flash::pull()]);
    }

    public function publish(): void
    {
        Auth::requirePermission('knowledge.manage');Csrf::verify($_POST['_csrf']??null);$id=(int)($_POST['article_id']??0);$this->setStatus($id,'PUBLISHED');Flash::set('Artículo publicado.','success');header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$id);exit;
    }
    public function archive(): void
    {
        Auth::requirePermission('knowledge.manage');Csrf::verify($_POST['_csrf']??null);$id=(int)($_POST['article_id']??0);$this->setStatus($id,'ARCHIVED');Flash::set('Artículo archivado.','success');header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$id);exit;
    }

    private function setStatus(int $id,string $status):void{$pdo=Database::pdo();$q=$pdo->prepare('SELECT status FROM knowledge_articles WHERE id=? LIMIT 1');$q->execute([$id]);$old=$q->fetchColumn();if(!$old)throw new \RuntimeException('Artículo no encontrado.');$published=$status==='PUBLISHED'?'published_at=COALESCE(published_at,NOW()),':'published_at=published_at,';$pdo->prepare("UPDATE knowledge_articles SET status=?,{$published}updated_at=NOW() WHERE id=?")->execute([$status,$id]);Audit::log('KNOWLEDGE_STATUS_CHANGED','knowledge_article',$id,['status'=>$old],['status'=>$status]);}
    private function categories():array{return Database::pdo()->query("SELECT c.id,c.code,c.parent_id,CASE WHEN p.id IS NULL THEN c.name ELSE CONCAT(p.name,' · ',c.name) END name FROM ticket_categories c LEFT JOIN ticket_categories p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name")->fetchAll();}
    private function canSeeInternal():bool{return (Auth::user()['access_type']??'INTERNAL')==='INTERNAL'&&Auth::role()!=='REQUESTER';}
    private function ticketTemplate(array $t):string{return "Problema\n".$t['description']."\n\nSíntomas\nDescribe señales o errores observados.\n\nCausa\n".($t['root_cause']?:'Pendiente de documentar')."\n\nSolución\n".$t['solution_applied']."\n\nPasos\n1. \n2. \n3. \n\nValidación\nIndica cómo confirmar que quedó resuelto.\n\nNotas\n".($t['preventive_action']?:'');}
    private function problemTemplate(array $p):string{return "Problema\n".$p['description']."\n\nSíntomas\nDescribe cómo se manifiesta.\n\nCausa\n".($p['root_cause']?:'En investigación')."\n\nWorkaround\n".($p['workaround']?:'No disponible')."\n\nSolución\n".($p['permanent_solution']?:'Pendiente')."\n\nPasos\n1. \n2. \n3. \n\nValidación\nIndica cómo confirmar la solución.";}
    private function ticketEvent(int $ticketId,string $type,array $payload):void{$s=Database::pdo()->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?, ?, ?, 'USER', ?, NOW())");$s->execute([$ticketId,$type,(int)Auth::id(),json_encode($payload,JSON_UNESCAPED_UNICODE)]);}
}
