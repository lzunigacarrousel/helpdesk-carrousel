<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class KnowledgeMetricsService
{
    private const EVENTS=['SUGGESTED','OPENED','USED_REFERENCE'];
    private const CONTEXTS=['INTERNAL_TICKET','SELF_SERVICE'];
    private const TYPES=['KNOWLEDGE','PROBLEM','TICKET'];

    public static function isAllowedEvent(string $event): bool
    {
        return in_array(strtoupper($event),self::EVENTS,true);
    }

    public function recordSuggested(
        ?int $ticketId,
        ?int $actorUserId,
        string $context,
        array $reference,
        ?int $rank=null,
        ?int $score=null
    ): void {
        $this->record('SUGGESTED',$ticketId,$actorUserId,$context,$reference,$rank,$score);
    }

    public function recordOpened(
        ?int $ticketId,
        ?int $actorUserId,
        string $context,
        array $reference,
        ?int $rank=null,
        ?int $score=null
    ): void {
        $this->record('OPENED',$ticketId,$actorUserId,$context,$reference,$rank,$score);
    }

    public function recordUsedReference(
        int $ticketId,
        ?int $actorUserId,
        array $reference,
        string $context='INTERNAL_TICKET'
    ): void {
        $this->record('USED_REFERENCE',$ticketId,$actorUserId,$context,$reference,null,null);
    }

    public function effectivenessSummary(int $articleId,?int $revisionId=null): array
    {
        if($articleId<=0)throw new RuntimeException('Artículo no válido.');
        $pdo=Database::pdo();

        $sql="SELECT id,ticket_id,used_at
              FROM ticket_resolution_references
              WHERE reference_type='KNOWLEDGE' AND knowledge_article_id=?";
        $params=[$articleId];
        if($revisionId!==null){
            $sql.=' AND knowledge_revision_id=?';
            $params[]=$revisionId;
        }
        $sql.=' ORDER BY used_at,id';

        $q=$pdo->prepare($sql);$q->execute($params);
        $uses=$q->fetchAll();
        if(!$uses){
            return[
                'uses'=>0,
                'resolved_after_use'=>0,
                'reopened_after_resolution'=>0,
                'effective'=>0,
                'effectiveness_rate'=>null,
            ];
        }

        $ticketIds=array_values(array_unique(array_map(
            static fn(array $row):int=>(int)$row['ticket_id'],$uses
        )));
        $marks=implode(',',array_fill(0,count($ticketIds),'?'));
        $eventsQuery=$pdo->prepare(
            "SELECT ticket_id,event_type,created_at,id
             FROM ticket_events
             WHERE ticket_id IN({$marks})
               AND event_type IN('RESOLUTION_RECORDED','RESOLVED','CLOSED','REOPENED')
             ORDER BY ticket_id,created_at,id"
        );
        $eventsQuery->execute($ticketIds);

        $eventsByTicket=[];
        foreach($eventsQuery->fetchAll() as $event){
            $eventsByTicket[(int)$event['ticket_id']][]=$event;
        }

        $resolvedAfter=0;$reopenedAfter=0;$effective=0;
        foreach($uses as $use){
            $usedAt=strtotime((string)$use['used_at'])?:0;
            $resolutionTime=null;
            foreach($eventsByTicket[(int)$use['ticket_id']]??[] as $event){
                $time=strtotime((string)$event['created_at'])?:0;
                if($time<$usedAt)continue;
                if(in_array((string)$event['event_type'],['RESOLUTION_RECORDED','RESOLVED','CLOSED'],true)){
                    $resolutionTime=$time;
                    break;
                }
            }
            if($resolutionTime===null)continue;
            $resolvedAfter++;

            $wasReopened=false;
            foreach($eventsByTicket[(int)$use['ticket_id']]??[] as $event){
                $time=strtotime((string)$event['created_at'])?:0;
                if($time>$resolutionTime&&(string)$event['event_type']==='REOPENED'){
                    $wasReopened=true;
                    break;
                }
            }
            if($wasReopened)$reopenedAfter++;
            else $effective++;
        }

        return[
            'uses'=>count($uses),
            'resolved_after_use'=>$resolvedAfter,
            'reopened_after_resolution'=>$reopenedAfter,
            'effective'=>$effective,
            'effectiveness_rate'=>$resolvedAfter>0?round(($effective/$resolvedAfter)*100,2):null,
        ];
    }

    public function reportSummary(string $from,string $to): array
    {
        $pdo=Database::pdo();

        $articles=$pdo->query(
            "SELECT
                COUNT(*) total_articles,
                COALESCE(SUM(lifecycle_status='ACTIVE'),0) active_articles,
                COALESCE(SUM(lifecycle_status='ARCHIVED'),0) archived_articles,
                COALESCE(SUM(lifecycle_status='ACTIVE' AND current_internal_revision_id IS NOT NULL),0) internal_published,
                COALESCE(SUM(lifecycle_status='ACTIVE' AND current_public_revision_id IS NOT NULL),0) public_available
             FROM knowledge_articles"
        )->fetch()?:[];

        $editorial=$pdo->query(
            "SELECT
                COALESCE(SUM(state='DRAFT'),0) drafts,
                COALESCE(SUM(state='IN_REVIEW'),0) in_review
             FROM knowledge_revisions kr
             JOIN knowledge_articles ka ON ka.id=kr.article_id
             WHERE ka.lifecycle_status='ACTIVE'"
        )->fetch()?:[];

        $scope=new ScopeService();
        [$scopeSql,$scopeParams]=$scope->ticketConstraint('t');

        $where=[
            "sse.reference_type='KNOWLEDGE'",
            'sse.created_at>=?',
            'sse.created_at<DATE_ADD(?,INTERVAL 1 DAY)',
        ];
        $params=[$from,$to];

        if($scopeSql!=='1=1'){
            $where[]="sse.ticket_id IS NOT NULL";
            $where[]=$scopeSql;
            array_push($params,...$scopeParams);
        }

        $usage=$pdo->prepare(
            "SELECT
                COALESCE(SUM(sse.event_type='SUGGESTED'),0) suggested,
                COALESCE(SUM(sse.event_type='OPENED'),0) opened,
                COALESCE(SUM(sse.event_type='USED_REFERENCE'),0) used_reference,
                COUNT(DISTINCT CASE WHEN sse.event_type='USED_REFERENCE' THEN sse.ticket_id END) tickets_with_reference
             FROM solution_suggestion_events sse
             LEFT JOIN tickets t ON t.id=sse.ticket_id
             WHERE ".implode(' AND ',$where)
        );
        $usage->execute($params);
        $usageRow=$usage->fetch()?:[];

        return[
            'total_articles'=>(int)($articles['total_articles']??0),
            'active_articles'=>(int)($articles['active_articles']??0),
            'archived_articles'=>(int)($articles['archived_articles']??0),
            'internal_published'=>(int)($articles['internal_published']??0),
            'public_available'=>(int)($articles['public_available']??0),
            'drafts'=>(int)($editorial['drafts']??0),
            'in_review'=>(int)($editorial['in_review']??0),
            'suggested'=>(int)($usageRow['suggested']??0),
            'opened'=>(int)($usageRow['opened']??0),
            'used_reference'=>(int)($usageRow['used_reference']??0),
            'tickets_with_reference'=>(int)($usageRow['tickets_with_reference']??0),
        ];
    }

    private function record(
        string $eventType,
        ?int $ticketId,
        ?int $actorUserId,
        string $context,
        array $reference,
        ?int $rank,
        ?int $score
    ): void {
        $eventType=strtoupper(trim($eventType));
        $context=strtoupper(trim($context));
        $type=strtoupper(trim((string)($reference['type']??'')));

        if(!self::isAllowedEvent($eventType))throw new RuntimeException('Evento de métrica no permitido.');
        if(!in_array($context,self::CONTEXTS,true))throw new RuntimeException('Contexto de métrica no permitido.');
        if(!in_array($type,self::TYPES,true))throw new RuntimeException('Tipo de referencia no permitido.');

        $articleId=(int)($reference['article_id']??0)?:null;
        $revisionId=(int)($reference['revision_id']??0)?:null;
        $problemId=(int)($reference['problem_id']??0)?:null;
        $sourceTicketId=(int)($reference['source_ticket_id']??0)?:null;

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "INSERT INTO solution_suggestion_events(
                ticket_id,actor_user_id,context,event_type,reference_type,
                knowledge_article_id,knowledge_revision_id,problem_id,source_ticket_id,
                rank_position,score,metadata_json,created_at
             ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())"
        );
        $q->execute([
            $ticketId,$actorUserId,$context,$eventType,$type,
            $articleId,$revisionId,$problemId,$sourceTicketId,
            $rank,$score,
            json_encode($reference,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
    }
}
