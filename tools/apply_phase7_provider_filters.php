<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/app/Services/ProviderParticipationService.php';
$body=(string)file_get_contents($path);

if(str_contains($body,'function applyFilters(')){
    echo "[OK] Filtros de proveedores ya aplicados.".PHP_EOL;
    exit(0);
}

$anchor='    private static function rowIndexFor(';
if(substr_count($body,$anchor)!==1){
    fwrite(STDERR,"[ERROR] No se encontro exactamente una vez el ancla rowIndexFor.".PHP_EOL);
    exit(1);
}

$methods=<<<'PHP'
    public static function applyFilters(array $rows,array $filters): array
    {
        $q=mb_strtolower(trim((string)($filters['q']??'')));
        $provider=(int)($filters['provider']??0);
        $state=(string)($filters['state']??'');
        $activity=(string)($filters['activity']??'');
        $from=(string)($filters['from']??'');
        $to=(string)($filters['to']??'');

        return array_values(array_filter($rows,static function(array $row)use($q,$provider,$state,$activity,$from,$to):bool{
            if($provider>0&&(int)($row['user_id']??0)!==$provider)return false;
            if($state==='active'&&($row['revoked_at']??null)!==null)return false;
            if($state==='closed'&&($row['revoked_at']??null)===null)return false;
            if($activity==='NONE'&&($row['work_status']??null)!==null)return false;
            if($activity!==''&&$activity!=='NONE'&&(string)($row['work_status']??'')!==$activity)return false;

            $day=substr((string)($row['granted_at']??''),0,10);
            if($from!==''&&$day<$from)return false;
            if($to!==''&&$day>$to)return false;

            if($q!==''){
                $haystack=mb_strtolower(implode(' ',[
                    (string)($row['organization']??''),
                    (string)($row['contact']??''),
                    (string)($row['email']??''),
                    (string)($row['ticket_number']??''),
                    (string)($row['subject']??''),
                ]));
                if(!str_contains($haystack,$q))return false;
            }
            return true;
        }));
    }

    public static function summary(array $rows): array
    {
        $summary=[
            'participations'=>count($rows),
            'active'=>0,
            'no_response'=>0,
            'avg_first_response_minutes'=>null,
            'returns'=>0,
        ];
        $responseMinutes=[];
        foreach($rows as $row){
            if(($row['revoked_at']??null)===null)$summary['active']++;
            if(($row['first_response_minutes']??null)===null){
                $summary['no_response']++;
            }else{
                $responseMinutes[]=(int)$row['first_response_minutes'];
            }
            $summary['returns']+=(int)($row['returns']??0);
        }
        if($responseMinutes!==[]){
            $summary['avg_first_response_minutes']=(int)round(array_sum($responseMinutes)/count($responseMinutes));
        }
        return $summary;
    }

    public static function providers(array $rows): array
    {
        $providers=[];
        foreach($rows as $row){
            $userId=(int)($row['user_id']??0);
            if($userId<=0)continue;
            $providers[$userId]=(string)($row['organization']??$row['contact']??'');
        }
        asort($providers,SORT_NATURAL|SORT_FLAG_CASE);
        return $providers;
    }

    public static function activityOptions(): array
    {
        return ['NONE'=>'Sin actualización']+self::WORK_STATUS_LABELS;
    }

PHP;

$body=str_replace($anchor,$methods.$anchor,$body,$count);
if($count!==1){
    fwrite(STDERR,"[ERROR] No se pudo insertar el bloque de filtros.".PHP_EOL);
    exit(1);
}

file_put_contents($path,$body);
passthru('"'.PHP_BINARY.'" -l "'.$path.'"',$lintCode);
if($lintCode!==0){
    fwrite(STDERR,"[ERROR] PHP lint fallo despues del ajuste.".PHP_EOL);
    exit(1);
}

echo "[OK] Filtros y resumen de proveedores aplicados.".PHP_EOL;
