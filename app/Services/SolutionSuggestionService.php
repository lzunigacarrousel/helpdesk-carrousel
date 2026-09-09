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

        $q=$pdo->query("SELECT ka.id,ka.article_number,ka.title,ka.summary,ka.content,ka.category_id,ka.visibility,ka.updated_at
            FROM knowledge_articles ka WHERE ka.status='PUBLISHED' ORDER BY ka.updated_at DESC LIMIT 80");
        foreach($q->fetchAll() as $r){
            $score=$this->score($r['title'].' '.($r['summary']??'').' '.strip_tags((string)$r['content']),$terms,$category,(int)($r['category_id']??0),$park,0);
            if($score<18)continue;
            $items[]=['type'=>'ARTICLE','id'=>(int)$r['id'],'number'=>$r['article_number'],'title'=>$r['title'],'summary'=>$r['summary']?:mb_strimwidth(strip_tags((string)$r['content']),0,180,'…'),'score'=>$score,'url'=>APP_BASE_URL.'/knowledge/view?id='.(int)$r['id']];
        }

        $q=$pdo->query("SELECT kp.id,kp.problem_number,kp.title,kp.description,kp.root_cause,kp.workaround,kp.permanent_solution,kp.category_id,kp.park_id,kp.occurrence_count,kp.updated_at
            FROM known_problems kp WHERE kp.status<>'CLOSED' ORDER BY kp.occurrence_count DESC,kp.updated_at DESC LIMIT 80");
        foreach($q->fetchAll() as $r){
            $text=$r['title'].' '.$r['description'].' '.($r['root_cause']??'').' '.($r['workaround']??'').' '.($r['permanent_solution']??'');
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
