<?php
declare(strict_types=1);

namespace App\Services;

final class ProviderRatingService
{
    public const SCORE_LABELS=[
        1=>'Muy deficiente',
        2=>'Deficiente',
        3=>'Adecuado',
        4=>'Bueno',
        5=>'Excelente',
    ];

    public function __construct(private ?object $pdo=null){}

    public static function validateInput(int $score,string $comment,bool $correction): void
    {
        if($score<1||$score>5){
            throw new \InvalidArgumentException('Selecciona una valoración entre 1 y 5.');
        }

        $comment=trim($comment);
        if(($score<=2||$correction)&&$comment===''){
            throw new \InvalidArgumentException(
                $correction
                    ?'Explica el motivo de la corrección.'
                    :'Agrega un comentario para una valoración de 1 o 2 estrellas.'
            );
        }
    }

    public static function buildCurrentRatings(array $events): array
    {
        usort($events,static function(array $a,array $b):int{
            $cmp=strcmp((string)($a['created_at']??''),(string)($b['created_at']??''));
            return $cmp!==0?$cmp:((int)($a['id']??0)<=> (int)($b['id']??0));
        });

        $current=[];
        foreach($events as $event){
            $type=(string)($event['event_type']??'');
            if(!in_array($type,['PROVIDER_RATED','PROVIDER_RATING_CORRECTED'],true))continue;

            $meta=json_decode((string)($event['metadata_json']??''),true);
            if(!is_array($meta))continue;

            $externalUserId=(int)($meta['external_user_id']??0);
            $grantEventId=(int)($meta['grant_event_id']??0);
            $score=(int)($meta['score']??0);
            $comment=trim((string)($meta['comment']??''));
            if($externalUserId<=0||$grantEventId<=0||!isset(self::SCORE_LABELS[$score]))continue;

            if($type==='PROVIDER_RATED'){
                if(isset($current[$grantEventId]))continue;
                try{
                    self::validateInput($score,$comment,false);
                }catch(\InvalidArgumentException){
                    continue;
                }
                $revisionCount=0;
            }else{
                if(!isset($current[$grantEventId]))continue;
                $previous=$current[$grantEventId];
                $correctedEventId=(int)($meta['corrected_rating_event_id']??0);
                if($correctedEventId<=0||$correctedEventId!==(int)($previous['event_id']??0))continue;
                if($externalUserId!==(int)($previous['external_user_id']??0))continue;
                try{
                    self::validateInput($score,$comment,true);
                }catch(\InvalidArgumentException){
                    continue;
                }
                $revisionCount=(int)($previous['revision_count']??0)+1;
            }

            $current[$grantEventId]=[
                'event_id'=>(int)($event['id']??0),
                'ticket_id'=>(int)($event['ticket_id']??0),
                'score'=>$score,
                'label'=>self::SCORE_LABELS[$score],
                'comment'=>$comment,
                'rated_at'=>(string)($event['created_at']??''),
                'actor_user_id'=>(int)($event['actor_user_id']??0),
                'actor_name'=>(string)($event['actor_name']??''),
                'external_user_id'=>$externalUserId,
                'grant_event_id'=>$grantEventId,
                'revision_count'=>$revisionCount,
            ];
        }

        return $current;
    }

    public static function enrichCycles(array $cycles,array $ratingEvents): array
    {
        $current=self::buildCurrentRatings($ratingEvents);
        $result=[];

        foreach($cycles as $cycle){
            $grantEventId=(int)($cycle['grant_event_id']??0);
            $rating=$grantEventId>0?($current[$grantEventId]??null):null;

            if(is_array($rating)
                &&(int)($rating['external_user_id']??0)===(int)($cycle['user_id']??0)
                &&(int)($rating['ticket_id']??0)===(int)($cycle['ticket_id']??0)){
                $cycle['provider_rating_score']=(int)$rating['score'];
                $cycle['provider_rating_label']=(string)$rating['label'];
                $cycle['provider_rating_comment']=(string)$rating['comment'];
                $cycle['provider_rating_at']=(string)$rating['rated_at'];
                $cycle['provider_rating_actor']=(string)$rating['actor_name'];
                $cycle['provider_rating_event_id']=(int)$rating['event_id'];
                $cycle['provider_rating_revisions']=(int)$rating['revision_count'];
            }else{
                $cycle['provider_rating_score']=null;
                $cycle['provider_rating_label']='Sin evaluar';
                $cycle['provider_rating_comment']=null;
                $cycle['provider_rating_at']=null;
                $cycle['provider_rating_actor']=null;
                $cycle['provider_rating_event_id']=null;
                $cycle['provider_rating_revisions']=0;
            }

            $result[]=$cycle;
        }

        return $result;
    }

    public static function providerSummary(array $rows): array
    {
        $summary=[];
        $scores=[];

        foreach($rows as $row){
            $userId=(int)($row['user_id']??0);
            if($userId<=0)continue;

            if(!isset($summary[$userId])){
                $summary[$userId]=[
                    'user_id'=>$userId,
                    'organization'=>(string)($row['organization']??$row['contact']??''),
                    'rated_cycles'=>0,
                    'unrated_cycles'=>0,
                    'average_score'=>null,
                ];
                $scores[$userId]=[];
            }

            $score=$row['provider_rating_score']??null;
            if($score===null){
                $summary[$userId]['unrated_cycles']++;
                continue;
            }

            $score=(int)$score;
            if(!isset(self::SCORE_LABELS[$score])){
                $summary[$userId]['unrated_cycles']++;
                continue;
            }

            $summary[$userId]['rated_cycles']++;
            $scores[$userId][]=$score;
        }

        foreach($summary as $userId=>&$row){
            if(($scores[$userId]??[])!==[]){
                $row['average_score']=round(array_sum($scores[$userId])/count($scores[$userId]),2);
            }
        }
        unset($row);

        return $summary;
    }

    public static function ratingOptions(): array
    {
        return [
            'UNRATED'=>'Sin evaluar',
            '1'=>'1★ Muy deficiente',
            '2'=>'2★ Deficiente',
            '3'=>'3★ Adecuado',
            '4'=>'4★ Bueno',
            '5'=>'5★ Excelente',
        ];
    }
}
