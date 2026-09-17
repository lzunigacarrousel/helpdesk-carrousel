<?php
declare(strict_types=1);

namespace App\Services;

final class KnowledgeCandidateService
{
    private const REUSE_SIGNALS=[
        'ROOT_CAUSE_DOCUMENTED',
        'KNOWN_PROBLEM',
        'RECURRENT',
        'REOPENED',
    ];

    public static function evaluate(array $ticket,array $resolution,array $signals=[]): array
    {
        $status=(string)($ticket['status']??'');
        $acceptedStatus=in_array($status,['RESOLVED','CLOSED'],true);

        $solution=(string)($resolution['solution_applied']??'');
        $rootCause=(string)($resolution['root_cause']??'');
        $prevention=(string)($resolution['preventive_action']??'');

        $normalizedSignals=[];
        foreach($signals as $signal){
            $signal=strtoupper(trim((string)$signal));
            if(in_array($signal,self::REUSE_SIGNALS,true))$normalizedSignals[$signal]=$signal;
        }
        if(self::meaningfulText($rootCause))$normalizedSignals['ROOT_CAUSE_DOCUMENTED']='ROOT_CAUSE_DOCUMENTED';

        $structuredContext=self::meaningfulText($rootCause)
            ||self::meaningfulText($prevention)
            ||isset($normalizedSignals['KNOWN_PROBLEM']);

        $documentationOk=self::meaningfulText($solution)&&$structuredContext;
        $reasons=array_values($normalizedSignals);

        return[
            'eligible'=>$acceptedStatus&&$documentationOk&&$reasons!==[],
            'reasons'=>$reasons,
            'documentation_ok'=>$documentationOk,
        ];
    }

    private static function meaningfulText(string $value): bool
    {
        $value=trim(preg_replace('/\s+/u',' ',$value)??'');
        if($value==='')return false;

        $normalized=mb_strtolower($value);
        if(in_array($normalized,[
            'ok','listo','resuelto','solucionado','hecho','n/a','na',
            'no aplica','ninguno','ninguna','pendiente'
        ],true))return false;

        $words=preg_split('/\s+/u',$value,-1,PREG_SPLIT_NO_EMPTY)?:[];
        return count($words)>=4;
    }
}
