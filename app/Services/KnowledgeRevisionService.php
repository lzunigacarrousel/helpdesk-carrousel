<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class KnowledgeRevisionService
{
    private const TRANSITIONS=[
        'DRAFT'=>['IN_REVIEW'],
        'IN_REVIEW'=>['DRAFT','PUBLISHED'],
        'PUBLISHED'=>[],
    ];

    public static function canTransition(string $from,string $to): bool
    {
        return in_array($to,self::TRANSITIONS[$from]??[],true);
    }

    public static function nextRevisionNumber(array $revisionNumbers): int
    {
        if($revisionNumbers===[])return 1;
        $numbers=array_map(static fn($n):(int)=>(int)$n,$revisionNumbers);
        return max($numbers)+1;
    }

    public function createArticle(array $data,int $userId): array
    {
        $title=trim((string)($data['title']??''));
        $summary=trim((string)($data['summary']??''));
        $content=trim((string)($data['content']??''));
        $categoryId=(int)($data['category_id']??0);
        $sourceTicketId=(int)($data['source_ticket_id']??0);
        $sourceProblemId=(int)($data['source_problem_id']??0);
        $changeNote=trim((string)($data['change_note']??''));
        $this->validateContent($title,$content);

        return Database::transaction(function(PDO $pdo)use(
            $title,$summary,$content,$categoryId,$sourceTicketId,$sourceProblemId,$changeNote,$userId
        ):array{
            $article=$pdo->prepare(
                "INSERT INTO knowledge_articles(
                    article_number,lifecycle_status,current_internal_revision_id,current_public_revision_id,
                    created_by_user_id,title,summary,content,status,visibility,category_id,author_user_id,
                    published_at,archived_at,created_at,updated_at
                 ) VALUES('', 'ACTIVE',NULL,NULL,?,?,?,?,?,'DRAFT','INTERNAL',?,?,NULL,NULL,NOW(),NOW())"
            );
            $article->execute([
                $userId,$title,$summary!==''?$summary:null,$content,$categoryId>0?$categoryId:null,$userId
            ]);
            $articleId=(int)$pdo->lastInsertId();
            $number='KB-'.date('Y').'-'.str_pad((string)$articleId,4,'0',STR_PAD_LEFT);
            $pdo->prepare('UPDATE knowledge_articles SET article_number=? WHERE id=?')->execute([$number,$articleId]);

            $revision=$pdo->prepare(
                "INSERT INTO knowledge_revisions(
                    article_id,revision_number,state,title,summary,content,category_id,based_on_revision_id,
                    created_by_user_id,change_note,created_at,updated_at
                 ) VALUES(?,1,'DRAFT',?,?,?,?,NULL,?,?,NOW(),NOW())"
            );
            $revision->execute([
                $articleId,$title,$summary!==''?$summary:null,$content,$categoryId>0?$categoryId:null,
                $userId,$changeNote!==''?$changeNote:null
            ]);
            $revisionId=(int)$pdo->lastInsertId();

            if($sourceTicketId>0){
                $this->insertSource($pdo,$articleId,'TICKET',$sourceTicketId,null,$userId);
            }
            if($sourceProblemId>0){
                $this->insertSource($pdo,$articleId,'PROBLEM',null,$sourceProblemId,$userId);
                $pdo->prepare(
                    'INSERT IGNORE INTO problem_solutions(problem_id,article_id,is_primary,linked_at) VALUES(?,?,0,NOW())'
                )->execute([$sourceProblemId,$articleId]);
            }
            if($sourceTicketId<=0&&$sourceProblemId<=0){
                $this->insertSource($pdo,$articleId,'MANUAL',null,null,$userId);
            }

            return[
                'article_id'=>$articleId,
                'article_number'=>$number,
                'revision_id'=>$revisionId,
                'revision_number'=>1,
                'state'=>'DRAFT',
            ];
        });
    }

    public function createDraftFromRevision(
        int $articleId,
        int $revisionId,
        int $userId,
        ?string $changeNote=null
    ): array {
        if($articleId<=0||$revisionId<=0)throw new RuntimeException('Artículo o revisión no válidos.');

        return Database::transaction(function(PDO $pdo)use($articleId,$revisionId,$userId,$changeNote):array{
            $article=$this->articleForUpdate($pdo,$articleId);
            if(($article['lifecycle_status']??'ACTIVE')!=='ACTIVE'){
                throw new RuntimeException('No puedes editar un artículo archivado.');
            }

            $source=$this->revision($pdo,$revisionId,true);
            if((int)$source['article_id']!==$articleId){
                throw new RuntimeException('La revisión no pertenece al artículo indicado.');
            }

            $numbers=$pdo->prepare('SELECT revision_number FROM knowledge_revisions WHERE article_id=? ORDER BY revision_number');
            $numbers->execute([$articleId]);
            $next=self::nextRevisionNumber(array_map('intval',$numbers->fetchAll(PDO::FETCH_COLUMN)));

            $insert=$pdo->prepare(
                "INSERT INTO knowledge_revisions(
                    article_id,revision_number,state,title,summary,content,category_id,based_on_revision_id,
                    created_by_user_id,change_note,created_at,updated_at
                 ) VALUES(?,?,'DRAFT',?,?,?,?,?,?,?,NOW(),NOW())"
            );
            $note=trim((string)$changeNote);
            $insert->execute([
                $articleId,$next,$source['title'],$source['summary'],$source['content'],$source['category_id'],
                $revisionId,$userId,$note!==''?$note:null
            ]);

            return[
                'article_id'=>$articleId,
                'article_number'=>(string)$article['article_number'],
                'revision_id'=>(int)$pdo->lastInsertId(),
                'revision_number'=>$next,
                'state'=>'DRAFT',
                'based_on_revision_id'=>$revisionId,
            ];
        });
    }

    public function updateDraft(int $revisionId,array $data,int $userId): void
    {
        $title=trim((string)($data['title']??''));
        $summary=trim((string)($data['summary']??''));
        $content=trim((string)($data['content']??''));
        $categoryId=(int)($data['category_id']??0);
        $changeNote=trim((string)($data['change_note']??''));
        $this->validateContent($title,$content);

        Database::transaction(function(PDO $pdo)use(
            $revisionId,$title,$summary,$content,$categoryId,$changeNote,$userId
        ):void{
            $revision=$this->revision($pdo,$revisionId,true);
            if($revision['state']!=='DRAFT'){
                throw new RuntimeException('Solo puedes editar una revisión en borrador.');
            }

            $update=$pdo->prepare(
                "UPDATE knowledge_revisions
                 SET title=?,summary=?,content=?,category_id=?,change_note=?,updated_at=NOW()
                 WHERE id=? AND state='DRAFT'"
            );
            $update->execute([
                $title,$summary!==''?$summary:null,$content,$categoryId>0?$categoryId:null,
                $changeNote!==''?$changeNote:$revision['change_note'],$revisionId
            ]);

            $articleId=(int)$revision['article_id'];
            $shadow=$pdo->prepare(
                "UPDATE knowledge_articles
                 SET title=?,summary=?,content=?,category_id=?,author_user_id=?,updated_at=NOW()
                 WHERE id=? AND current_internal_revision_id IS NULL AND status='DRAFT'"
            );
            $shadow->execute([
                $title,$summary!==''?$summary:null,$content,$categoryId>0?$categoryId:null,$userId,$articleId
            ]);
        });
    }

    public function submitForReview(int $revisionId,int $userId): void
    {
        Database::transaction(function(PDO $pdo)use($revisionId,$userId):void{
            $revision=$this->revision($pdo,$revisionId,true);
            $this->assertTransition((string)$revision['state'],'IN_REVIEW');
            $pdo->prepare(
                "UPDATE knowledge_revisions
                 SET state='IN_REVIEW',submitted_by_user_id=?,submitted_at=NOW(),
                     reviewed_by_user_id=NULL,reviewed_at=NULL,review_note=NULL,updated_at=NOW()
                 WHERE id=?"
            )->execute([$userId,$revisionId]);
        });
    }

    public function returnToDraft(int $revisionId,int $reviewerId,string $note): void
    {
        $note=trim($note);
        if($note==='')throw new RuntimeException('Indica qué debe corregirse antes de devolver el borrador.');

        Database::transaction(function(PDO $pdo)use($revisionId,$reviewerId,$note):void{
            $revision=$this->revision($pdo,$revisionId,true);
            $this->assertTransition((string)$revision['state'],'DRAFT');
            $pdo->prepare(
                "UPDATE knowledge_revisions
                 SET state='DRAFT',reviewed_by_user_id=?,reviewed_at=NOW(),review_note=?,updated_at=NOW()
                 WHERE id=?"
            )->execute([$reviewerId,$note,$revisionId]);
        });
    }

    public function publishInternal(int $revisionId,int $reviewerId): void
    {
        Database::transaction(function(PDO $pdo)use($revisionId,$reviewerId):void{
            $revision=$this->revision($pdo,$revisionId,true);
            if($revision['state']!=='IN_REVIEW'){
                throw new RuntimeException('La revisión debe estar en revisión antes de publicarse.');
            }
            $article=$this->articleForUpdate($pdo,(int)$revision['article_id']);
            if(($article['lifecycle_status']??'ACTIVE')!=='ACTIVE'){
                throw new RuntimeException('No puedes publicar un artículo archivado.');
            }

            $pdo->prepare(
                "UPDATE knowledge_revisions
                 SET state='PUBLISHED',reviewed_by_user_id=?,reviewed_at=NOW(),
                     internal_published_by_user_id=?,internal_published_at=NOW(),updated_at=NOW()
                 WHERE id=?"
            )->execute([$reviewerId,$reviewerId,$revisionId]);

            $pdo->prepare(
                "UPDATE knowledge_articles
                 SET current_internal_revision_id=?,
                     title=?,summary=?,content=?,category_id=?,status='PUBLISHED',
                     author_user_id=COALESCE(author_user_id,?),
                     published_at=COALESCE(published_at,NOW()),updated_at=NOW()
                 WHERE id=?"
            )->execute([
                $revisionId,$revision['title'],$revision['summary'],$revision['content'],$revision['category_id'],
                $reviewerId,(int)$revision['article_id']
            ]);
        });
    }

    public function publishPublic(int $revisionId,int $reviewerId): void
    {
        Database::transaction(function(PDO $pdo)use($revisionId,$reviewerId):void{
            $revision=$this->revision($pdo,$revisionId,true);
            if($revision['state']!=='PUBLISHED'||empty($revision['internal_published_at'])){
                throw new RuntimeException('Solo una revisión publicada para soporte puede habilitarse para solicitantes.');
            }
            $article=$this->articleForUpdate($pdo,(int)$revision['article_id']);
            if(($article['lifecycle_status']??'ACTIVE')!=='ACTIVE'){
                throw new RuntimeException('No puedes habilitar un artículo archivado.');
            }

            $pdo->prepare(
                "UPDATE knowledge_revisions
                 SET public_published_by_user_id=?,public_published_at=COALESCE(public_published_at,NOW()),updated_at=NOW()
                 WHERE id=?"
            )->execute([$reviewerId,$revisionId]);

            $pdo->prepare(
                'UPDATE knowledge_articles SET current_public_revision_id=?,updated_at=NOW() WHERE id=?'
            )->execute([$revisionId,(int)$revision['article_id']]);

            if((int)($article['current_internal_revision_id']??0)===$revisionId){
                $pdo->prepare("UPDATE knowledge_articles SET visibility='PUBLIC',updated_at=NOW() WHERE id=?")
                    ->execute([(int)$revision['article_id']]);
            }
        });
    }

    public function restoreAsDraft(
        int $articleId,
        int $sourceRevisionId,
        int $userId,
        string $note
    ): array {
        $note=trim($note);
        if($note==='')throw new RuntimeException('Indica por qué se restaurará esta versión.');
        return $this->createDraftFromRevision($articleId,$sourceRevisionId,$userId,$note);
    }

    public function archiveArticle(int $articleId,int $userId): void
    {
        if($articleId<=0)throw new RuntimeException('Artículo no válido.');

        Database::transaction(function(PDO $pdo)use($articleId,$userId):void{
            $article=$this->articleForUpdate($pdo,$articleId);
            if(($article['lifecycle_status']??'ACTIVE')==='ARCHIVED')return;

            $pdo->prepare(
                "UPDATE knowledge_articles
                 SET lifecycle_status='ARCHIVED',status='ARCHIVED',archived_at=NOW(),updated_at=NOW()
                 WHERE id=?"
            )->execute([$articleId]);
        });
    }

    private function validateContent(string $title,string $content): void
    {
        if(mb_strlen($title)<5)throw new RuntimeException('Escribe un título de al menos 5 caracteres.');
        if(mb_strlen($content)<20)throw new RuntimeException('Agrega una explicación de al menos 20 caracteres.');
    }

    private function assertTransition(string $from,string $to): void
    {
        if(!self::canTransition($from,$to)){
            throw new RuntimeException("No se puede cambiar la revisión de {$from} a {$to}.");
        }
    }

    private function revision(PDO $pdo,int $revisionId,bool $forUpdate=false): array
    {
        if($revisionId<=0)throw new RuntimeException('Revisión no válida.');
        $sql='SELECT * FROM knowledge_revisions WHERE id=? LIMIT 1'.($forUpdate?' FOR UPDATE':'');
        $q=$pdo->prepare($sql);
        $q->execute([$revisionId]);
        $revision=$q->fetch();
        if(!$revision)throw new RuntimeException('Revisión de conocimiento no encontrada.');
        return $revision;
    }

    private function articleForUpdate(PDO $pdo,int $articleId): array
    {
        $q=$pdo->prepare('SELECT * FROM knowledge_articles WHERE id=? LIMIT 1 FOR UPDATE');
        $q->execute([$articleId]);
        $article=$q->fetch();
        if(!$article)throw new RuntimeException('Artículo de conocimiento no encontrado.');
        return $article;
    }

    private function insertSource(
        PDO $pdo,
        int $articleId,
        string $sourceType,
        ?int $ticketId,
        ?int $problemId,
        int $userId
    ): void {
        $q=$pdo->prepare(
            "INSERT INTO knowledge_article_sources(
                article_id,source_type,source_ticket_id,source_problem_id,created_by_user_id,created_at
             ) VALUES(?,?,?,?,?,NOW())"
        );
        $q->execute([$articleId,$sourceType,$ticketId,$problemId,$userId]);
    }
}
