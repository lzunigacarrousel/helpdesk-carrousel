<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,SearchText,View};
use App\Services\KnowledgeRevisionService;
use PDO;

final class KnowledgeController
{
    private const STATUSES=['DRAFT'=>'Borrador','IN_REVIEW'=>'En revisión','PUBLISHED'=>'Publicado para soporte','ARCHIVED'=>'Archivado'];

    public function index(): void
    {
        Auth::requirePermission('knowledge.view');
        $pdo=Database::pdo();
        $editor=$this->canEditKnowledge();
        $internal=$this->canSeeInternal();
        $q=trim((string)($_GET['q']??''));
        $status=trim((string)($_GET['status']??''));
        $category=(int)($_GET['category_id']??0);
        $params=[];

        if($editor){
            $workingExpr="COALESCE(wr.state,ir.state,lr.state)";
            $where=['1=1'];
            foreach(SearchText::tokens($q) as $token){
                $like='%'.$token.'%';
                $where[]="(ka.article_number LIKE ? OR COALESCE(wr.title,ir.title,lr.title) LIKE ? OR COALESCE(wr.summary,ir.summary,lr.summary) LIKE ? OR COALESCE(wr.content,ir.content,lr.content) LIKE ? OR c.name LIKE ?)";
                array_push($params,$like,$like,$like,$like,$like);
            }
            if(isset(self::STATUSES[$status])){
                if($status==='ARCHIVED')$where[]="ka.lifecycle_status='ARCHIVED'";
                else{$where[]="ka.lifecycle_status='ACTIVE' AND {$workingExpr}=?";$params[]=$status;}
            }
            if($category>0){
                $where[]='COALESCE(wr.category_id,ir.category_id,lr.category_id) IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)';
                $params[]=$category;$params[]=$category;
            }
            $sql="SELECT ka.*,
                         COALESCE(wr.id,ir.id,lr.id) revision_id,
                         COALESCE(wr.revision_number,ir.revision_number,lr.revision_number,1) revision_number,
                         CASE WHEN ka.lifecycle_status='ARCHIVED' THEN 'ARCHIVED' ELSE {$workingExpr} END status,
                         COALESCE(wr.title,ir.title,lr.title) title,
                         COALESCE(wr.summary,ir.summary,lr.summary) summary,
                         COALESCE(wr.content,ir.content,lr.content) content,
                         COALESCE(wr.category_id,ir.category_id,lr.category_id) category_id,
                         c.name category_name,
                         u.full_name author_name,
                         (ka.lifecycle_status='ACTIVE' AND ka.current_public_revision_id IS NOT NULL) public_available
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
                  LEFT JOIN users u ON u.id=COALESCE(wr.created_by_user_id,ir.created_by_user_id,lr.created_by_user_id,ka.created_by_user_id)
                  WHERE ".implode(' AND ',$where)."
                  ORDER BY (ka.lifecycle_status='ARCHIVED'),ka.updated_at DESC
                  LIMIT 250";
        }else{
            $pointer=$internal?'current_internal_revision_id':'current_public_revision_id';
            $where=["ka.lifecycle_status='ACTIVE'","ka.{$pointer} IS NOT NULL"];
            foreach(SearchText::tokens($q) as $token){
                $like='%'.$token.'%';
                $where[]='(ka.article_number LIKE ? OR kr.title LIKE ? OR kr.summary LIKE ? OR kr.content LIKE ? OR c.name LIKE ?)';
                array_push($params,$like,$like,$like,$like,$like);
            }
            if($category>0){
                $where[]='kr.category_id IN (SELECT id FROM ticket_categories WHERE id=? OR parent_id=?)';
                $params[]=$category;$params[]=$category;
            }
            $sql="SELECT ka.*,kr.id revision_id,kr.revision_number,kr.state status,
                         kr.title,kr.summary,kr.content,kr.category_id,c.name category_name,
                         u.full_name author_name,(ka.current_public_revision_id IS NOT NULL) public_available
                  FROM knowledge_articles ka
                  JOIN knowledge_revisions kr ON kr.id=ka.{$pointer}
                  LEFT JOIN ticket_categories c ON c.id=kr.category_id
                  LEFT JOIN users u ON u.id=COALESCE(kr.created_by_user_id,ka.created_by_user_id)
                  WHERE ".implode(' AND ',$where)."
                  ORDER BY ka.updated_at DESC LIMIT 250";
        }

        $s=$pdo->prepare($sql);$s->execute($params);
        View::render('knowledge/index',[
            'user'=>Auth::user(),
            'articles'=>$s->fetchAll(),
            'filters'=>compact('q','status','category'),
            'statuses'=>self::STATUSES,
            'categories'=>$this->categories(),
            'canManage'=>$editor,
            'flash'=>Flash::pull(),
        ]);
    }

    public function form(): void
    {
        Auth::requirePermission('knowledge.draft_manage');
        $pdo=Database::pdo();
        $id=(int)($_GET['id']??0);
        $article=null;
        $prefill=[
            'title'=>'','summary'=>'','content'=>'','category_id'=>null,
            'source_ticket_id'=>0,'source_problem_id'=>0,'change_note'=>''
        ];

        $oldForm=$_SESSION['knowledge_form_old']??null;
        unset($_SESSION['knowledge_form_old']);
        if(is_array($oldForm))$prefill=array_merge($prefill,$oldForm);

        if($id>0){
            $q=$pdo->prepare(
                "SELECT ka.id,ka.article_number,ka.lifecycle_status,
                        COALESCE(d.id,ci.id,lr.id) editing_revision_id,
                        COALESCE(d.revision_number,ci.revision_number,lr.revision_number,1) revision_number,
                        COALESCE(d.title,ci.title,lr.title) title,
                        COALESCE(d.summary,ci.summary,lr.summary) summary,
                        COALESCE(d.content,ci.content,lr.content) content,
                        COALESCE(d.category_id,ci.category_id,lr.category_id) category_id,
                        COALESCE(d.change_note,'') change_note
                 FROM knowledge_articles ka
                 LEFT JOIN knowledge_revisions d ON d.id=(
                     SELECT kr.id FROM knowledge_revisions kr
                     WHERE kr.article_id=ka.id AND kr.state='DRAFT'
                     ORDER BY kr.revision_number DESC LIMIT 1
                 )
                 LEFT JOIN knowledge_revisions ci ON ci.id=ka.current_internal_revision_id
                 LEFT JOIN knowledge_revisions lr ON lr.id=(
                     SELECT x.id FROM knowledge_revisions x
                     WHERE x.article_id=ka.id ORDER BY x.revision_number DESC LIMIT 1
                 )
                 WHERE ka.id=? LIMIT 1"
            );
            $q->execute([$id]);
            $article=$q->fetch();
            if(!$article)throw new \RuntimeException('Artículo no encontrado.');
            if(($article['lifecycle_status']??'ACTIVE')==='ARCHIVED'){
                throw new \RuntimeException('Un artículo archivado debe restaurarse antes de editarse.');
            }
        }

        $ticketId=(int)($_GET['ticket_id']??0);
        $problemId=(int)($_GET['problem_id']??0);
        if(!$article&&$ticketId>0){
            $q=$pdo->prepare(
                "SELECT t.id,t.subject,t.description,t.category_id,tr.root_cause,tr.solution_applied,tr.preventive_action
                 FROM tickets t
                 JOIN ticket_resolutions tr ON tr.ticket_id=t.id
                 WHERE t.id=? AND t.deleted_at IS NULL LIMIT 1"
            );
            $q->execute([$ticketId]);
            if($t=$q->fetch()){
                $problem=$pdo->prepare(
                    'SELECT problem_id FROM problem_occurrences WHERE ticket_id=? ORDER BY created_at DESC LIMIT 1'
                );
                $problem->execute([$ticketId]);
                $linkedProblem=(int)($problem->fetchColumn()?:0);
                $prefill=[
                    'title'=>$t['subject'],
                    'summary'=>mb_strimwidth((string)$t['description'],0,280,'…'),
                    'content'=>$this->ticketTemplate($t),
                    'category_id'=>$t['category_id'],
                    'source_ticket_id'=>$ticketId,
                    'source_problem_id'=>$linkedProblem,
                    'change_note'=>'',
                ];
            }
        }
        if(!$article&&$problemId>0){
            $q=$pdo->prepare('SELECT * FROM known_problems WHERE id=? LIMIT 1');
            $q->execute([$problemId]);
            if($p=$q->fetch()){
                $prefill=[
                    'title'=>$p['title'],
                    'summary'=>mb_strimwidth((string)$p['description'],0,280,'…'),
                    'content'=>$this->problemTemplate($p),
                    'category_id'=>$p['category_id'],
                    'source_ticket_id'=>0,
                    'source_problem_id'=>$problemId,
                    'change_note'=>'',
                ];
            }
        }

        View::render('knowledge/form',[
            'user'=>Auth::user(),
            'article'=>$article,
            'prefill'=>$prefill,
            'categories'=>$this->categories(),
            'flash'=>Flash::pull(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('knowledge.draft_manage');
        Csrf::verify($_POST['_csrf']??null);
        $title=trim((string)($_POST['title']??''));
        $summary=trim((string)($_POST['summary']??''));
        $content=trim((string)($_POST['content']??''));
        $category=(int)($_POST['category_id']??0);
        $sourceTicket=(int)($_POST['source_ticket_id']??0);
        $sourceProblem=(int)($_POST['source_problem_id']??0);

        if(mb_strlen($title)<5||mb_strlen($content)<20){
            $issues=[];
            if(mb_strlen($title)<5)$issues[]='Escribe un título de al menos 5 caracteres.';
            if(mb_strlen($content)<20)$issues[]='Agrega una explicación de al menos 20 caracteres en Contenido.';
            $_SESSION['knowledge_form_old']=[
                'title'=>$title,'summary'=>$summary,'content'=>$content,'category_id'=>$category?:null,
                'visibility'=>'INTERNAL','source_ticket_id'=>$sourceTicket,'source_problem_id'=>$sourceProblem
            ];
            Flash::set(implode(' ',$issues),'info');
            header('Location: '.APP_BASE_URL.'/knowledge/new');exit;
        }

        $created=(new KnowledgeRevisionService())->createArticle([
            'title'=>$title,
            'summary'=>$summary,
            'content'=>$content,
            'category_id'=>$category?:null,
            'source_ticket_id'=>$sourceTicket?:null,
            'source_problem_id'=>$sourceProblem?:null,
        ],(int)Auth::id());

        $articleId=(int)$created['article_id'];
        if($sourceTicket>0){
            $this->ticketEvent($sourceTicket,'KNOWLEDGE_CREATED',[
                'article_id'=>$articleId,
                'article_number'=>$created['article_number'],
                'revision_id'=>$created['revision_id'],
            ]);
        }

        Audit::log(
            'KNOWLEDGE_CREATED','knowledge_article',$articleId,null,
            ['article_number'=>$created['article_number'],'revision_id'=>$created['revision_id'],'state'=>'DRAFT'],
            ['source_ticket_id'=>$sourceTicket?:null,'source_problem_id'=>$sourceProblem?:null]
        );
        Flash::set('Artículo creado como borrador.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function update(): void
    {
        Auth::requirePermission('knowledge.draft_manage');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        if($articleId<=0)throw new \RuntimeException('Artículo no válido.');

        $pdo=Database::pdo();
        $revisionId=(int)($_POST['revision_id']??0);
        if($revisionId<=0)$revisionId=$this->latestRevisionId($pdo,$articleId,['DRAFT']);

        $service=new KnowledgeRevisionService();
        if($revisionId<=0){
            $sourceRevisionId=$this->sourceRevisionId($pdo,$articleId);
            if($sourceRevisionId<=0)throw new \RuntimeException('No existe una revisión base para editar.');
            $draft=$service->createDraftFromRevision(
                $articleId,$sourceRevisionId,(int)Auth::id(),'Nueva revisión editorial'
            );
            $revisionId=(int)$draft['revision_id'];
        }

        $service->updateDraft($revisionId,[
            'title'=>trim((string)($_POST['title']??'')),
            'summary'=>trim((string)($_POST['summary']??'')),
            'content'=>trim((string)($_POST['content']??'')),
            'category_id'=>(int)($_POST['category_id']??0),
            'change_note'=>trim((string)($_POST['change_note']??'')),
        ],(int)Auth::id());

        Audit::log(
            'KNOWLEDGE_REVISION_UPDATED','knowledge_revision',$revisionId,null,
            ['article_id'=>$articleId,'revision_id'=>$revisionId]
        );
        Flash::set('Borrador actualizado.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function submitReview(): void
    {
        Auth::requirePermission('knowledge.draft_manage');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        $revisionId=(int)($_POST['revision_id']??0);
        if($revisionId<=0)$revisionId=$this->latestRevisionId(Database::pdo(),$articleId,['DRAFT']);
        if($revisionId<=0)throw new \RuntimeException('No hay un borrador disponible para enviar a revisión.');

        (new KnowledgeRevisionService())->submitForReview($revisionId,(int)Auth::id());
        Audit::log('KNOWLEDGE_SUBMITTED_REVIEW','knowledge_revision',$revisionId,null,['article_id'=>$articleId]);
        Flash::set('Borrador enviado a revisión.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function returnDraft(): void
    {
        Auth::requirePermission('knowledge.review');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        $revisionId=(int)($_POST['revision_id']??0);
        $note=trim((string)($_POST['review_note']??''));
        if($revisionId<=0)$revisionId=$this->latestRevisionId(Database::pdo(),$articleId,['IN_REVIEW']);
        if($revisionId<=0)throw new \RuntimeException('No hay una revisión pendiente.');

        (new KnowledgeRevisionService())->returnToDraft($revisionId,(int)Auth::id(),$note);
        Audit::log(
            'KNOWLEDGE_RETURNED_DRAFT','knowledge_revision',$revisionId,null,
            ['article_id'=>$articleId,'review_note'=>$note]
        );
        Flash::set('La revisión volvió a borrador con observaciones.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function publishInternal(): void
    {
        Auth::requirePermission('knowledge.publish_internal');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        $revisionId=(int)($_POST['revision_id']??0);
        if($revisionId<=0)$revisionId=$this->latestRevisionId(Database::pdo(),$articleId,['IN_REVIEW']);
        if($revisionId<=0)throw new \RuntimeException('No hay una revisión lista para publicar.');

        (new KnowledgeRevisionService())->publishInternal($revisionId,(int)Auth::id());
        Audit::log('KNOWLEDGE_PUBLISHED_INTERNAL','knowledge_revision',$revisionId,null,['article_id'=>$articleId]);
        Flash::set('Versión publicada para soporte.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function publishPublic(): void
    {
        Auth::requirePermission('knowledge.publish_public');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        $revisionId=(int)($_POST['revision_id']??0);
        if($revisionId<=0){
            $q=Database::pdo()->prepare('SELECT current_internal_revision_id FROM knowledge_articles WHERE id=? LIMIT 1');
            $q->execute([$articleId]);
            $revisionId=(int)($q->fetchColumn()?:0);
        }
        if($revisionId<=0)throw new \RuntimeException('No existe una versión interna publicada.');

        (new KnowledgeRevisionService())->publishPublic($revisionId,(int)Auth::id());
        Audit::log('KNOWLEDGE_PUBLISHED_PUBLIC','knowledge_revision',$revisionId,null,['article_id'=>$articleId]);
        Flash::set('Versión disponible para solicitantes.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function restore(): void
    {
        Auth::requirePermission('knowledge.restore');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        $sourceRevisionId=(int)($_POST['source_revision_id']??0);
        $note=trim((string)($_POST['restore_note']??''));
        $created=(new KnowledgeRevisionService())->restoreAsDraft(
            $articleId,$sourceRevisionId,(int)Auth::id(),$note
        );
        Audit::log(
            'KNOWLEDGE_REVISION_RESTORED','knowledge_revision',(int)$created['revision_id'],null,
            ['article_id'=>$articleId,'source_revision_id'=>$sourceRevisionId,'note'=>$note]
        );
        Flash::set('Se creó un nuevo borrador a partir de la versión seleccionada.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function publish(): void
    {
        // Compatibilidad temporal con formularios anteriores. Nunca evita el permiso nuevo.
        $this->publishInternal();
    }

    public function archive(): void
    {
        Auth::requirePermission('knowledge.publish_internal');
        Csrf::verify($_POST['_csrf']??null);
        $articleId=(int)($_POST['article_id']??0);
        (new KnowledgeRevisionService())->archiveArticle($articleId,(int)Auth::id());
        Audit::log('KNOWLEDGE_ARCHIVED','knowledge_article',$articleId,null,['lifecycle_status'=>'ARCHIVED']);
        Flash::set('Artículo archivado.','success');
        header('Location: '.APP_BASE_URL.'/knowledge/view?id='.$articleId);exit;
    }

    public function view(): void
    {
        Auth::requirePermission('knowledge.view');
        $id=(int)($_GET['id']??0);
        $pdo=Database::pdo();

        $q=$pdo->prepare(
            "SELECT ka.*,creator.full_name article_creator_name
             FROM knowledge_articles ka
             LEFT JOIN users creator ON creator.id=ka.created_by_user_id
             WHERE ka.id=? LIMIT 1"
        );
        $q->execute([$id]);
        $identity=$q->fetch();
        if(!$identity)throw new \RuntimeException('Artículo no encontrado.');

        $editor=$this->canEditKnowledge();
        $internal=$this->canSeeInternal();
        if(($identity['lifecycle_status']??'ACTIVE')==='ARCHIVED'&&!$editor&&!Auth::can('knowledge.history')){
            Flash::set('Ese artículo no está disponible para tu perfil.','info');
            header('Location: '.APP_BASE_URL.'/knowledge');exit;
        }

        $rq=$pdo->prepare(
            "SELECT kr.*,c.name category_name,
                    creator.full_name created_by_name,
                    reviewer.full_name reviewed_by_name,
                    internal_pub.full_name internal_published_by_name,
                    public_pub.full_name public_published_by_name
             FROM knowledge_revisions kr
             LEFT JOIN ticket_categories c ON c.id=kr.category_id
             LEFT JOIN users creator ON creator.id=kr.created_by_user_id
             LEFT JOIN users reviewer ON reviewer.id=kr.reviewed_by_user_id
             LEFT JOIN users internal_pub ON internal_pub.id=kr.internal_published_by_user_id
             LEFT JOIN users public_pub ON public_pub.id=kr.public_published_by_user_id
             WHERE kr.article_id=?
             ORDER BY kr.revision_number DESC"
        );
        $rq->execute([$id]);
        $allRevisions=$rq->fetchAll();

        $byId=[];
        $working=null;
        foreach($allRevisions as $revision){
            $byId[(int)$revision['id']]=$revision;
            if($working===null&&in_array($revision['state'],['DRAFT','IN_REVIEW'],true))$working=$revision;
        }
        $currentInternal=$byId[(int)($identity['current_internal_revision_id']??0)]??null;
        $currentPublic=$byId[(int)($identity['current_public_revision_id']??0)]??null;

        if($editor){
            $display=$working?:($currentInternal?:($allRevisions[0]??null));
        }elseif($internal){
            $display=$currentInternal;
        }else{
            $display=$currentPublic;
        }

        if(!$display){
            Flash::set('Ese artículo todavía no está disponible para tu perfil.','info');
            header('Location: '.APP_BASE_URL.'/knowledge');exit;
        }

        $article=array_merge($identity,[
            'revision_id'=>(int)$display['id'],
            'revision_number'=>(int)$display['revision_number'],
            'title'=>$display['title'],
            'summary'=>$display['summary'],
            'content'=>$display['content'],
            'category_id'=>$display['category_id'],
            'category_name'=>$display['category_name'],
            'status'=>($identity['lifecycle_status']??'ACTIVE')==='ARCHIVED'?'ARCHIVED':$display['state'],
            'author_name'=>$display['created_by_name']?:($identity['article_creator_name']??null),
            'updated_at'=>$display['updated_at'],
            'published_at'=>$display['internal_published_at'],
        ]);

        $p=$pdo->prepare(
            "SELECT kp.id,kp.problem_number,kp.title,ps.is_primary
             FROM problem_solutions ps
             JOIN known_problems kp ON kp.id=ps.problem_id
             WHERE ps.article_id=?
             ORDER BY ps.is_primary DESC,kp.updated_at DESC"
        );
        $p->execute([$id]);

        View::render('knowledge/show',[
            'user'=>Auth::user(),
            'article'=>$article,
            'problems'=>$p->fetchAll(),
            'revisions'=>Auth::can('knowledge.history')?$allRevisions:[],
            'workingRevision'=>$working,
            'currentInternal'=>$currentInternal,
            'currentPublic'=>$currentPublic,
            'statuses'=>self::STATUSES,
            'canManage'=>$editor,
            'canEdit'=>Auth::can('knowledge.draft_manage'),
            'canReview'=>Auth::can('knowledge.review'),
            'canPublishInternal'=>Auth::can('knowledge.publish_internal'),
            'canPublishPublic'=>Auth::can('knowledge.publish_public'),
            'canHistory'=>Auth::can('knowledge.history'),
            'canRestore'=>Auth::can('knowledge.restore'),
            'flash'=>Flash::pull(),
        ]);
    }

    public function history(): void
    {
        Auth::requirePermission('knowledge.history');
        $articleId=(int)($_GET['id']??0);
        if($articleId<=0)throw new \RuntimeException('Artículo no válido.');

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT ka.id,ka.article_number,ka.lifecycle_status,
                    ka.current_internal_revision_id,ka.current_public_revision_id
             FROM knowledge_articles ka WHERE ka.id=? LIMIT 1"
        );
        $q->execute([$articleId]);
        $article=$q->fetch();
        if(!$article)throw new \RuntimeException('Artículo no encontrado.');

        $r=$pdo->prepare(
            "SELECT kr.*,c.name category_name,
                    creator.full_name created_by_name,
                    reviewer.full_name reviewed_by_name
             FROM knowledge_revisions kr
             LEFT JOIN ticket_categories c ON c.id=kr.category_id
             LEFT JOIN users creator ON creator.id=kr.created_by_user_id
             LEFT JOIN users reviewer ON reviewer.id=kr.reviewed_by_user_id
             WHERE kr.article_id=?
             ORDER BY kr.revision_number DESC"
        );
        $r->execute([$articleId]);

        View::render('knowledge/history',[
            'user'=>Auth::user(),
            'article'=>$article,
            'revisions'=>$r->fetchAll(),
            'statuses'=>self::STATUSES,
            'canRestore'=>Auth::can('knowledge.restore'),
            'flash'=>Flash::pull(),
        ]);
    }

    public function compare(): void
    {
        Auth::requirePermission('knowledge.history');
        $articleId=(int)($_GET['id']??0);
        $fromId=(int)($_GET['from']??0);
        $toId=(int)($_GET['to']??0);
        if($articleId<=0||$fromId<=0||$toId<=0){
            throw new \RuntimeException('Selecciona dos versiones válidas para comparar.');
        }

        $pdo=Database::pdo();
        $a=$pdo->prepare('SELECT id,article_number FROM knowledge_articles WHERE id=? LIMIT 1');
        $a->execute([$articleId]);
        $article=$a->fetch();
        if(!$article)throw new \RuntimeException('Artículo no encontrado.');

        $q=$pdo->prepare(
            "SELECT kr.*,c.name category_name
             FROM knowledge_revisions kr
             LEFT JOIN ticket_categories c ON c.id=kr.category_id
             WHERE kr.article_id=? AND kr.id IN(?,?)
             ORDER BY kr.revision_number"
        );
        $q->execute([$articleId,$fromId,$toId]);
        $rows=$q->fetchAll();
        $byId=[];
        foreach($rows as $row)$byId[(int)$row['id']]=$row;
        if(!isset($byId[$fromId],$byId[$toId])){
            throw new \RuntimeException('Una de las versiones no pertenece a este artículo.');
        }

        View::render('knowledge/compare',[
            'user'=>Auth::user(),
            'article'=>$article,
            'from'=>$byId[$fromId],
            'to'=>$byId[$toId],
            'statuses'=>self::STATUSES,
        ]);
    }

    private function latestRevisionId(PDO $pdo,int $articleId,array $states): int
    {
        if($articleId<=0||$states===[])return 0;
        $allowed=['DRAFT','IN_REVIEW','PUBLISHED'];
        $states=array_values(array_intersect($allowed,$states));
        if($states===[])return 0;
        $marks=implode(',',array_fill(0,count($states),'?'));
        $q=$pdo->prepare(
            "SELECT id FROM knowledge_revisions
             WHERE article_id=? AND state IN({$marks})
             ORDER BY revision_number DESC LIMIT 1"
        );
        $q->execute(array_merge([$articleId],$states));
        return (int)($q->fetchColumn()?:0);
    }

    private function sourceRevisionId(PDO $pdo,int $articleId): int
    {
        $q=$pdo->prepare(
            "SELECT COALESCE(
                current_internal_revision_id,
                (SELECT kr.id FROM knowledge_revisions kr
                 WHERE kr.article_id=knowledge_articles.id
                 ORDER BY kr.revision_number DESC LIMIT 1)
             )
             FROM knowledge_articles WHERE id=? LIMIT 1"
        );
        $q->execute([$articleId]);
        return (int)($q->fetchColumn()?:0);
    }

    private function categories(): array
    {
        return Database::pdo()->query(
            "SELECT c.id,c.code,c.parent_id,
                    CASE WHEN p.id IS NULL THEN c.name ELSE CONCAT(p.name,' · ',c.name) END name
             FROM ticket_categories c
             LEFT JOIN ticket_categories p ON p.id=c.parent_id
             WHERE c.is_active=1
             ORDER BY COALESCE(p.sort_order,c.sort_order),p.id IS NULL DESC,c.sort_order,c.name"
        )->fetchAll();
    }

    private function canSeeInternal(): bool
    {
        return (Auth::user()['access_type']??'INTERNAL')==='INTERNAL'&&Auth::role()!=='REQUESTER';
    }

    private function canEditKnowledge(): bool
    {
        return Auth::can('knowledge.draft_manage')
            ||Auth::can('knowledge.review')
            ||Auth::can('knowledge.publish_internal')
            ||Auth::can('knowledge.publish_public')
            ||Auth::can('knowledge.history')
            ||Auth::can('knowledge.restore');
    }

    private function ticketTemplate(array $t): string
    {
        return "Problema\n".$t['description']
            ."\n\nSíntomas\nDescribe señales o errores observados."
            ."\n\nCausa\n".($t['root_cause']?:'Pendiente de documentar')
            ."\n\nSolución\n".$t['solution_applied']
            ."\n\nPasos\n1. \n2. \n3. "
            ."\n\nValidación\nIndica cómo confirmar que quedó resuelto."
            ."\n\nNotas\n".($t['preventive_action']?:'');
    }

    private function problemTemplate(array $p): string
    {
        return "Problema\n".$p['description']
            ."\n\nSíntomas\nDescribe cómo se manifiesta."
            ."\n\nCausa\n".($p['root_cause']?:'En investigación')
            ."\n\nWorkaround\n".($p['workaround']?:'No disponible')
            ."\n\nSolución\n".($p['permanent_solution']?:'Pendiente')
            ."\n\nPasos\n1. \n2. \n3. "
            ."\n\nValidación\nIndica cómo confirmar la solución.";
    }

    private function ticketEvent(int $ticketId,string $type,array $payload): void
    {
        $s=Database::pdo()->prepare(
            "INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at)
             VALUES(?, ?, ?, 'USER', ?, NOW())"
        );
        $s->execute([
            $ticketId,$type,(int)Auth::id(),
            json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        ]);
    }
}
