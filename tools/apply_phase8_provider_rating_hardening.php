<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderRatingService.php';
$viewPath=$root.'/app/Views/tickets/show.php';

function readLf(string $path): string
{
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe: '.$path.PHP_EOL);
        exit(1);
    }
    return str_replace(["\r\n","\r"],"\n",(string)file_get_contents($path));
}

function writeLf(string $path,string $body): void
{
    file_put_contents($path,$body);
}

function replaceOnce(string $body,string $old,string $new,string $label): string
{
    $count=substr_count($body,$old);
    if($count===0){
        if(str_contains($body,$new)){
            echo '[OK] '.$label.': ya aplicado.'.PHP_EOL;
            return $body;
        }
        fwrite(STDERR,'[ERROR] '.$label.': no se encontro el ancla.'.PHP_EOL);
        exit(1);
    }
    if($count!==1){
        fwrite(STDERR,'[ERROR] '.$label.': esperaba 1 coincidencia y encontro '.$count.'.'.PHP_EOL);
        exit(1);
    }
    echo '[OK] '.$label.'.'.PHP_EOL;
    return str_replace($old,$new,$body);
}

$service=readLf($servicePath);
$view=readLf($viewPath);

$anchor="    public static function buildCurrentRatings(array \$events): array\n";
$method="    public static function isCycleEvaluable(array \$cycle): bool\n    {\n        return (int)(\$cycle['grant_event_id']??0)>0\n            && (int)(\$cycle['revoke_event_id']??0)>0\n            && trim((string)(\$cycle['revoked_at']??''))!=='';\n    }\n\n";
if(!str_contains($service,'function isCycleEvaluable(')){
    $service=replaceOnce($service,$anchor,$method.$anchor,'Agregado criterio central isCycleEvaluable');
}else{
    echo '[OK] Agregado criterio central isCycleEvaluable: ya aplicado.'.PHP_EOL;
}

$old="            if(is_array(\$rating)\n                &&(int)(\$rating['external_user_id']??0)===(int)(\$cycle['user_id']??0)\n";
$new="            if(is_array(\$rating)\n                &&self::isCycleEvaluable(\$cycle)\n                &&(int)(\$rating['external_user_id']??0)===(int)(\$cycle['user_id']??0)\n";
$service=replaceOnce($service,$old,$new,'Lectura ignora ratings de ciclos no evaluables');

$old="            if((\$cycle['revoke_event_id']??null)===null){\n                throw new \\RuntimeException('Solo puedes evaluar una participación finalizada mediante revocación.');\n            }\n";
$new="            if(!self::isCycleEvaluable(\$cycle)){\n                throw new \\RuntimeException('Solo puedes evaluar una participación finalizada mediante revocación.');\n            }\n";
$service=replaceOnce($service,$old,$new,'Persistencia reutiliza criterio evaluable');

$old="use App\\Services\\{SolutionSuggestionService,TicketClassificationService};\n";
$new="use App\\Services\\{ProviderRatingService,SolutionSuggestionService,TicketClassificationService};\n";
$view=replaceOnce($view,$old,$new,'Vista importa ProviderRatingService');

$old="      \$closed=!empty(\$cycle['revoke_event_id']);\n\n      \$rated=(\$cycle['provider_rating_score']??null)!==null;\n";
$new="      \$closed=!empty(\$cycle['revoked_at']);\n\n      \$cycleEvaluable=ProviderRatingService::isCycleEvaluable(\$cycle);\n\n      \$rated=(\$cycle['provider_rating_score']??null)!==null;\n";
$view=replaceOnce($view,$old,$new,'Vista calcula criterio evaluable central');

$old="      <?php if(!\$closed): ?>\n\n        <div class=\"dashboard-scope-note\"><span>Podrás evaluar cuando finalice la participación.</span></div>\n\n      <?php elseif(!\$rated&&\$canRateProviders): ?>\n";
$new="      <?php if(!\$cycleEvaluable): ?>\n\n        <div class=\"dashboard-scope-note\"><span><?= \$closed?'Esta participación no puede evaluarse porque no finalizó mediante revocación explícita.':'Podrás evaluar cuando finalice la participación.' ?></span></div>\n\n      <?php elseif(!\$rated&&\$canRateProviders): ?>\n";
$view=replaceOnce($view,$old,$new,'UI usa criterio central para habilitar evaluación');

writeLf($servicePath,$service);
writeLf($viewPath,$view);

passthru('"'.PHP_BINARY.'" -l "'.$servicePath.'"',$serviceCode);
if($serviceCode!==0)exit($serviceCode);
passthru('"'.PHP_BINARY.'" -l "'.$viewPath.'"',$viewCode);
if($viewCode!==0)exit($viewCode);

echo '[OK] Endurecimiento Task 6 aplicado. No se modifico la BD.'.PHP_EOL;
