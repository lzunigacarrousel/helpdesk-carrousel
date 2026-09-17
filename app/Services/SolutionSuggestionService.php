<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

final class SolutionSuggestionService
{
    public function forTicket(array $ticket,int $limit=5): array
    {
        $pdo=Database::pdo();
        $terms=$this->terms((string)($ticket['subject']??'').' '.(string)($ticket['description']??''));
        $category=(int)($ticket['category_id']??0);$park=(int)($ticket['park_id']??0);$items=[];

        $q=$pdo->query("SELECT ka.id,ka.article_number,kr.id revision_id,kr.title,kr.summary,kr.content,kr.category_id,kr.updated_at
            FROM knowledge_articles ka
            JOIN knowledge_revisions kr ON kr.id=ka.current_internal_revision_id
            WHERE ka.lifecycle_status='ACTIVE'
            ORDER BY kr.updated_at DESC LIMIT 80");
        foreach($q->fetchAll() as $r){
            $score=$this->score($r['title'].' '.($r['summary']??'').' '.strip_tags((string)$r['content']),$terms,$category,(int)($r['category_id']??0),$park,0);
            if($score<18)continue;
            $items[]=[
                'type'=>'ARTICLE',
                'id'=>(int)$r['id'],
                'revision_id'=>(int)$r['revision_id'],
                'number'=>$r['article_number'],
                'title'=>$r['title'],
                'summary'=>$r['summary']?:mb_strimwidth(strip_tags((string)$r['content']),0,180,'…'),
                'score'=>$score,
                'url'=>APP_BASE_URL.'/knowledge/view?id='.(int)$r['id'],
            ];
        }

        $q=$pdo->query("SELECT kp.id,kp.problem_number,kp.title,kp.description,kp.root_cause,kp.workaround,kp.permanent_solution,kp.category_id,kp.park_id,kp.occurrence_count,kp.updated_at,
                GROUP_CONCAT(pt.name SEPARATOR ' ') tag_text
            FROM known_problems kp
            LEFT JOIN known_problem_tags kpt ON kpt.problem_id=kp.id
            LEFT JOIN problem_tags pt ON pt.id=kpt.tag_id
            WHERE kp.status<>'CLOSED'
            GROUP BY kp.id
            ORDER BY kp.occurrence_count DESC,kp.updated_at DESC LIMIT 80");
        foreach($q->fetchAll() as $r){
            $text=$r['title'].' '.$r['description'].' '.($r['root_cause']??'').' '.($r['workaround']??'').' '.($r['permanent_solution']??'').' '.($r['tag_text']??'');
            $score=$this->score($text,$terms,$category,(int)($r['category_id']??0),$park,(int)($r['park_id']??0))+min(12,(int)$r['occurrence_count']*2);
            if($score<18)continue;
            $summary=$r['workaround']?:($r['permanent_solution']?:$r['description']);
            $items[]=['type'=>'PROBLEM','id'=>(int)$r['id'],'number'=>$r['problem_number'],'title'=>$r['title'],'summary'=>mb_strimwidth((string)$summary,0,180,'…'),'score'=>$score,'url'=>APP_BASE_URL.'/problems/view?id='.(int)$r['id']];
        }

        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.description,t.category_id,t.park_id,t.resolved_at,tr.solution_applied
            FROM tickets t JOIN ticket_resolutions tr ON tr.ticket_id=t.id
            WHERE t.id<>? AND t.deleted_at IS NULL AND t.status IN('RESOLVED','CLOSED') AND tr.is_reusable=1
            ORDER BY t.resolved_at DESC LIMIT 80");
        $q->execute([(int)($ticket['id']??0)]);
        foreach($q->fetchAll() as $r){
            $score=$this->score($r['subject'].' '.$r['description'].' '.$r['solution_applied'],$terms,$category,(int)($r['category_id']??0),$park,(int)($r['park_id']??0));
            if($score<18)continue;
            $items[]=['type'=>'TICKET','id'=>(int)$r['id'],'number'=>$r['ticket_number'],'title'=>$r['subject'],'summary'=>mb_strimwidth((string)$r['solution_applied'],0,180,'…'),'score'=>$score,'url'=>APP_BASE_URL.'/tickets/view?id='.(int)$r['id']];
        }

        usort($items,fn($a,$b)=>$b['score']<=>$a['score']);
        $dedup=[];$out=[];
        foreach($items as $item){$key=$item['type'].'-'.$item['id'];if(isset($dedup[$key]))continue;$dedup[$key]=1;$item['score']=min(99,max(1,(int)$item['score']));$out[]=$item;if(count($out)>=$limit)break;}
        return $out;
    }

    public function forRequesterDraft(array $context,int $limit=3): array
    {
        $pdo=Database::pdo();
        $limit=max(1,min(3,$limit));
        $terms=$this->terms(
            (string)($context['subject']??'').' '.(string)($context['description']??'')
        );
        $category=(int)($context['category_id']??0);
        $hasContext=$terms!==[]||$category>0;
        if(!$hasContext)return[];

        $q=$pdo->query(
            "SELECT ka.id,ka.article_number,kr.id revision_id,kr.title,kr.summary,kr.content,kr.category_id,kr.updated_at
             FROM knowledge_articles ka
             JOIN knowledge_revisions kr ON kr.id=ka.current_public_revision_id
             WHERE ka.lifecycle_status='ACTIVE'
             ORDER BY kr.updated_at DESC
             LIMIT 80"
        );

        $items=[];
        foreach($q->fetchAll() as $row){
            $score=$this->score(
                $row['title'].' '.($row['summary']??'').' '.strip_tags((string)$row['content']),
                $terms,$category,(int)($row['category_id']??0),0,0
            );
            if($score<7)continue;
            $items[]=[
                'type'=>'ARTICLE',
                'id'=>(int)$row['id'],
                'revision_id'=>(int)$row['revision_id'],
                'number'=>$row['article_number'],
                'title'=>$row['title'],
                'summary'=>$row['summary']?:mb_strimwidth(strip_tags((string)$row['content']),0,180,'…'),
                'content'=>strip_tags((string)$row['content']),
                'score'=>$score,
            ];
        }

        usort($items,static fn(array $a,array $b):int=>$b['score']<=>$a['score']);
        return array_slice($items,0,$limit);
    }

    private function score(string $text,array $terms,int $ticketCategory,int $candidateCategory,int $ticketPark,int $candidatePark): int
    {
        $score=0;
        if($ticketCategory>0&&$candidateCategory===$ticketCategory)$score+=34;
        if($ticketPark>0&&$candidatePark===$ticketPark)$score+=18;
        $haystack=mb_strtolower($text);
        foreach($terms as $term){if(mb_strpos($haystack,$term)!==false)$score+=7;}
        return min(100,$score);
    }

    private function terms(string $text): array
    {
        $text=mb_strtolower($text);
        $parts=preg_split('/[^\p{L}\p{N}]+/u',$text,-1,PREG_SPLIT_NO_EMPTY)?:[];
        $stop=['para','como','esta','este','esto','desde','cuando','donde','porque','pero','con','sin','del','las','los','una','uno','que','por','se','no','si','al','de','la','el','en','un','y'];
        $out=[];
        foreach($parts as $p){if(mb_strlen($p)<4||in_array($p,$stop,true))continue;$out[$p]=$p;if(count($out)>=12)break;}
        return array_values($out);
    }
}
