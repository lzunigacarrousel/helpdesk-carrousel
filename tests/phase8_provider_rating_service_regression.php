<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderRatingService.php';
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

function j(array $value):string
{
    return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function throwsInvalidArgument(callable $callback):bool
{
    try{
        $callback();
        return false;
    }catch(InvalidArgumentException){
        return true;
    }
}

$body=is_file($servicePath)?(string)file_get_contents($servicePath):'';
ok($body!=='','Existe ProviderRatingService');
ok(str_contains($body,'SCORE_LABELS'),'Expone escala de valoración');
ok(str_contains($body,'function validateInput('),'Expone validateInput');
ok(str_contains($body,'function buildCurrentRatings('),'Expone buildCurrentRatings');
ok(str_contains($body,'function enrichCycles('),'Expone enrichCycles');
ok(str_contains($body,'function providerSummary('),'Expone providerSummary');
ok(str_contains($body,'function ratingOptions('),'Expone ratingOptions');
ok(str_contains($body,'function isCycleEvaluable('),'Expone isCycleEvaluable');

if($body!==''){
    require_once $servicePath;
    if(class_exists('App\\Services\\ProviderRatingService')){
        $class='App\\Services\\ProviderRatingService';

        ok(($class::SCORE_LABELS[1]??null)==='Muy deficiente','1 estrella es Muy deficiente');
        ok(($class::SCORE_LABELS[2]??null)==='Deficiente','2 estrellas es Deficiente');
        ok(($class::SCORE_LABELS[3]??null)==='Adecuado','3 estrellas es Adecuado');
        ok(($class::SCORE_LABELS[4]??null)==='Bueno','4 estrellas es Bueno');
        ok(($class::SCORE_LABELS[5]??null)==='Excelente','5 estrellas es Excelente');

        $valid=true;
        try{
            $class::validateInput(5,'',false);
            $class::validateInput(3,'',false);
            $class::validateInput(1,'Problema grave documentado',false);
            $class::validateInput(4,'Corrección documentada',true);
        }catch(Throwable){$valid=false;}
        ok($valid,'Acepta entradas válidas');

        ok(throwsInvalidArgument(static fn()=>$class::validateInput(0,'',false)),'Rechaza score 0');
        ok(throwsInvalidArgument(static fn()=>$class::validateInput(6,'',false)),'Rechaza score 6');
        ok(throwsInvalidArgument(static fn()=>$class::validateInput(1,'',false)),'Score 1 exige comentario');
        ok(throwsInvalidArgument(static fn()=>$class::validateInput(2,'   ',false)),'Score 2 exige comentario');
        ok(throwsInvalidArgument(static fn()=>$class::validateInput(5,'',true)),'Corrección exige comentario');

        if(method_exists($class,'isCycleEvaluable')){
            $explicitClosed=['grant_event_id'=>101,'revoke_event_id'=>102,'revoked_at'=>'2026-09-03 08:00:00'];
            $implicitClosed=['grant_event_id'=>103,'revoke_event_id'=>null,'revoked_at'=>'2026-09-04 08:00:00'];
            $activeCycle=['grant_event_id'=>104,'revoke_event_id'=>null,'revoked_at'=>null];
            ok($class::isCycleEvaluable($explicitClosed),'Cierre explícito por EXTERNAL_REVOKED es evaluable');
            ok(!$class::isCycleEvaluable($implicitClosed),'Cierre implícito por nuevo grant no es evaluable');
            ok(!$class::isCycleEvaluable($activeCycle),'Ciclo activo no es evaluable');
        }else{
            ok(false,'Cierre explícito por EXTERNAL_REVOKED es evaluable');
            ok(false,'Cierre implícito por nuevo grant no es evaluable');
            ok(false,'Ciclo activo no es evaluable');
        }

        $ratingEvents=[
            [
                'id'=>201,'ticket_id'=>100,'event_type'=>'PROVIDER_RATED','actor_user_id'=>1,'actor_name'=>'Tecnico A',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>101,'score'=>2,'comment'=>'Respuesta incompleta']),
                'created_at'=>'2026-09-03 09:00:00',
            ],
            [
                'id'=>202,'ticket_id'=>100,'event_type'=>'PROVIDER_RATING_CORRECTED','actor_user_id'=>2,'actor_name'=>'Tecnico B',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>101,'score'=>4,'comment'=>'Se corrigió tras revisar evidencia','corrected_rating_event_id'=>201]),
                'created_at'=>'2026-09-03 10:00:00',
            ],
            [
                'id'=>203,'ticket_id'=>100,'event_type'=>'PROVIDER_RATING_CORRECTED','actor_user_id'=>3,'actor_name'=>'Tecnico C',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>101,'score'=>1,'comment'=>'Corrección corrupta','corrected_rating_event_id'=>999]),
                'created_at'=>'2026-09-03 11:00:00',
            ],
            [
                'id'=>204,'ticket_id'=>100,'event_type'=>'PROVIDER_RATED','actor_user_id'=>1,'actor_name'=>'Tecnico A',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>103,'score'=>5,'comment'=>'Excelente atención']),
                'created_at'=>'2026-09-06 08:00:00',
            ],
            [
                'id'=>205,'ticket_id'=>100,'event_type'=>'PROVIDER_RATED','actor_user_id'=>1,'actor_name'=>'Tecnico A',
                'metadata_json'=>'{json-invalido',
                'created_at'=>'2026-09-06 09:00:00',
            ],
            [
                'id'=>206,'ticket_id'=>100,'event_type'=>'PROVIDER_RATED','actor_user_id'=>1,'actor_name'=>'Tecnico A',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>0,'score'=>5,'comment'=>'Grant inválido']),
                'created_at'=>'2026-09-06 10:00:00',
            ],
            [
                'id'=>207,'ticket_id'=>100,'event_type'=>'COMMENTED','actor_user_id'=>1,'actor_name'=>'Tecnico A',
                'metadata_json'=>j(['external_user_id'=>10,'grant_event_id'=>103,'score'=>1,'comment'=>'No es rating']),
                'created_at'=>'2026-09-06 11:00:00',
            ],
        ];

        $current=$class::buildCurrentRatings($ratingEvents);
        ok(count($current)===2,'Solo conserva grants con valoración válida');
        ok((int)($current[101]['score']??0)===4,'Última corrección válida es vigente');
        ok((int)($current[101]['score']??0)===4&&((int)($current[101]['event_id']??0)===202),'Corrección obsoleta no desplaza valoración vigente');
        ok((int)($current[101]['event_id']??0)===202,'Conserva id de evento vigente');
        ok((int)($current[101]['revision_count']??-1)===1,'Cuenta una corrección válida');
        ok(($current[101]['label']??null)==='Bueno','Valoración vigente usa etiqueta correcta');
        ok(($current[101]['comment']??null)==='Se corrigió tras revisar evidencia','Conserva comentario vigente');
        ok(($current[101]['actor_name']??null)==='Tecnico B','Conserva actor de valoración vigente');
        ok(($current[101]['rated_at']??null)==='2026-09-03 10:00:00','Conserva fecha de valoración vigente');
        ok((int)($current[103]['score']??0)===5,'Segundo ciclo conserva valoración independiente');

        $cycles=[
            [
                'ticket_id'=>100,'user_id'=>10,'organization'=>'Proveedor Uno, S.A.','grant_event_id'=>101,'revoke_event_id'=>102,
                'granted_at'=>'2026-09-01 08:00:00','revoked_at'=>'2026-09-03 08:00:00',
            ],
            [
                'ticket_id'=>100,'user_id'=>10,'organization'=>'Proveedor Uno, S.A.','grant_event_id'=>103,'revoke_event_id'=>104,
                'granted_at'=>'2026-09-04 08:00:00','revoked_at'=>'2026-09-05 08:00:00',
            ],
            [
                'ticket_id'=>101,'user_id'=>10,'organization'=>'Proveedor Uno, S.A.','grant_event_id'=>105,'revoke_event_id'=>106,
                'granted_at'=>'2026-09-07 08:00:00','revoked_at'=>'2026-09-08 08:00:00',
            ],
            [
                'ticket_id'=>200,'user_id'=>11,'organization'=>'Proveedor Dos, S.A.','grant_event_id'=>2010,'revoke_event_id'=>2011,
                'granted_at'=>'2026-09-01 09:00:00','revoked_at'=>'2026-09-02 09:00:00',
            ],
        ];

        $enriched=$class::enrichCycles($cycles,$ratingEvents);
        ok((int)($enriched[0]['provider_rating_score']??0)===4,'Enriquece primer ciclo con score vigente');
        ok(($enriched[0]['provider_rating_label']??null)==='Bueno','Enriquece primer ciclo con etiqueta');
        ok((int)($enriched[0]['provider_rating_event_id']??0)===202,'Enriquece con evento vigente');
        ok((int)($enriched[0]['provider_rating_revisions']??-1)===1,'Enriquece con cantidad de correcciones');
        ok(($enriched[2]['provider_rating_score']??null)===null,'Ciclo sin evaluación conserva score null');
        ok(($enriched[2]['provider_rating_label']??null)==='Sin evaluar','Ciclo sin evaluación usa etiqueta Sin evaluar');
        ok(($enriched[2]['provider_rating_comment']??null)===null,'Ciclo sin evaluación no inventa comentario');

        $mismatched=$class::enrichCycles([
            ['ticket_id'=>100,'user_id'=>99,'organization'=>'Proveedor distinto','grant_event_id'=>101,'revoke_event_id'=>102,'granted_at'=>'2026-09-01 08:00:00','revoked_at'=>'2026-09-03 08:00:00'],
        ],$ratingEvents);
        ok(($mismatched[0]['provider_rating_score']??null)===null,'Rating de otro proveedor no se aplica al ciclo');

        $summary=$class::providerSummary($enriched);
        ok((int)($summary[10]['rated_cycles']??0)===2,'Resumen cuenta ciclos evaluados');
        ok((int)($summary[10]['unrated_cycles']??0)===1,'Resumen cuenta ciclos sin evaluar');
        ok((float)($summary[10]['average_score']??0)===4.5,'Promedio ignora Sin evaluar y usa vigentes');
        ok((int)($summary[11]['rated_cycles']??-1)===0,'Proveedor sin ratings tiene cero evaluados');
        ok((int)($summary[11]['unrated_cycles']??0)===1,'Proveedor sin ratings cuenta ciclo sin evaluar');
        ok(array_key_exists('average_score',$summary[11])&&$summary[11]['average_score']===null,'Proveedor sin ratings no recibe promedio cero');

        $options=$class::ratingOptions();
        ok(($options['UNRATED']??null)==='Sin evaluar','Opciones incluyen Sin evaluar');
        ok(($options['1']??null)==='1★ Muy deficiente','Opciones incluyen 1 estrella');
        ok(($options['5']??null)==='5★ Excelente','Opciones incluyen 5 estrellas');
    }else{
        ok(false,'Clase ProviderRatingService disponible');
    }
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}

echo '[OK] Dominio inmutable de valoración de proveedores.'.PHP_EOL;
