<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$path=$root.'/app/Services/ProviderParticipationService.php';
if(!is_file($path)){
    fwrite(STDERR,"[ERROR] No existe ProviderParticipationService.php".PHP_EOL);
    exit(1);
}

$body=str_replace("\r\n","\n",(string)file_get_contents($path));
$original=$body;

function replaceOnce(string &$body,string $old,string $new,string $label): void
{
    $count=substr_count($body,$old);
    if($count!==1){
        fwrite(STDERR,"[ERROR] {$label}: esperaba 1 coincidencia y encontro {$count}.".PHP_EOL);
        exit(1);
    }
    $body=str_replace($old,$new,$body);
}

if(!str_contains($body,'function rowsForTicket(')){
    $anchor="    public static function buildCycles(array \$users,array \$events,array \$comments,array \$attachments,array \$reports,?int \$nowTs = null): array\n";
    $method="    public function rowsForTicket(int \$ticketId, ?int \$nowTs = null): array\n    {\n        if(\$ticketId<=0)return [];\n        return array_values(array_filter(\n            \$this->rows(\$nowTs),\n            static fn(array \$row):bool=>(int)(\$row['ticket_id']??0)===\$ticketId\n        ));\n    }\n\n";
    replaceOnce($body,$anchor,$method.$anchor,'rowsForTicket');
    echo "[OK] Agregado rowsForTicket().".PHP_EOL;
}else{
    echo "[OK] rowsForTicket() ya existe.".PHP_EOL;
}

if(!str_contains($body,"'grant_event_id'=>")){
    $old="                    'ticket_id'=>\$ticketId,'user_id'=>\$uid,\n";
    $new="                    'ticket_id'=>\$ticketId,'user_id'=>\$uid,\n                    'grant_event_id'=>(int)(\$event['id']??0),'revoke_event_id'=>null,\n";
    replaceOnce($body,$old,$new,'grant_event_id');
    echo "[OK] Agregada identidad del grant al ciclo.".PHP_EOL;
}else{
    echo "[OK] grant_event_id ya existe.".PHP_EOL;
}

if(!str_contains($body,"['revoke_event_id']=(int)(\$event['id']??0)")){
    $old="            }elseif(isset(\$open[\$key])){\n                \$i=\$open[\$key];\n                \$rows[\$i]['revoked_at']=(string)(\$event['created_at']??'');\n                \$rows[\$i]['revoked_by']=(string)((\$event['actor_name']??'')?:'Sistema');\n                unset(\$open[\$key]);\n            }\n";
    $new="            }elseif(isset(\$open[\$key])){\n                \$i=\$open[\$key];\n                \$rows[\$i]['revoked_at']=(string)(\$event['created_at']??'');\n                \$rows[\$i]['revoked_by']=(string)((\$event['actor_name']??'')?:'Sistema');\n                \$rows[\$i]['revoke_event_id']=(int)(\$event['id']??0);\n                unset(\$open[\$key]);\n            }\n";
    replaceOnce($body,$old,$new,'revoke_event_id');
    echo "[OK] Agregada identidad del revoke explicito.".PHP_EOL;
}else{
    echo "[OK] revoke_event_id ya existe.".PHP_EOL;
}

if($body===$original){
    echo "[OK] Sin cambios necesarios.".PHP_EOL;
    exit(0);
}

file_put_contents($path,$body);
echo "[OK] Identidad exacta de ciclos aplicada. No se modifico la BD.".PHP_EOL;
