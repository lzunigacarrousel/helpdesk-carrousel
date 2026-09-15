<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$servicePath=$root.'/app/Services/ProviderParticipationService.php';
$errors=0;
function ok(bool $condition,string $message):void{global $errors;echo($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;if(!$condition)$errors++;}
function j(array $value):string{return (string)json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}

$users=[10=>['id'=>10,'full_name'=>'Proveedor Uno','email'=>'proveedor1@example.com','organization_name'=>'Proveedor Uno, S.A.']];
$events=[
 ['id'=>1,'ticket_id'=>100,'event_type'=>'EXTERNAL_GRANTED','old_value'=>null,'new_value'=>j(['external_user_id'=>10]),'metadata_json'=>'{}','created_at'=>'2026-09-01 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
 ['id'=>2,'ticket_id'=>100,'event_type'=>'EXTERNAL_REVOKED','old_value'=>j(['external_user_id'=>10]),'new_value'=>null,'metadata_json'=>'{}','created_at'=>'2026-09-03 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
 ['id'=>3,'ticket_id'=>100,'event_type'=>'EXTERNAL_GRANTED','old_value'=>null,'new_value'=>j(['external_user_id'=>10]),'metadata_json'=>'{}','created_at'=>'2026-09-04 08:00:00','ticket_number'=>'HD-100','subject'=>'Caso A','ticket_status'=>'REOPENED','actor_name'=>'Luis'],
];
$comments=[
 ['id'=>500,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'INTERNAL','created_at'=>'2026-09-01 08:15:00'],
 ['id'=>502,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-01 09:00:00'],
 ['id'=>501,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-01 09:30:00'],
 ['id'=>503,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-04 09:00:00'],
 ['id'=>504,'ticket_id'=>100,'author_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-04 10:00:00'],
];
$attachments=[
 ['id'=>701,'ticket_id'=>100,'uploaded_by_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-01 10:00:00'],
 ['id'=>702,'ticket_id'=>100,'uploaded_by_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-03 12:00:00'],
 ['id'=>703,'ticket_id'=>100,'uploaded_by_user_id'=>10,'visibility'=>'EXTERNAL','created_at'=>'2026-09-04 10:30:00'],
];
$reports=[
 ['id'=>601,'ticket_id'=>100,'author_user_id'=>10,'comment_id'=>501,'work_status'=>'ANALYSIS','time_spent_minutes'=>30,'ready_for_review'=>0,'created_at'=>'2026-09-01 09:30:00'],
 ['id'=>602,'ticket_id'=>100,'author_user_id'=>10,'comment_id'=>503,'work_status'=>'READY_FOR_REVIEW','time_spent_minutes'=>45,'ready_for_review'=>1,'created_at'=>'2026-09-04 09:00:00'],
];

require_once $servicePath;
$rows=\App\Services\ProviderParticipationService::buildCycles($users,$events,$comments,$attachments,$reports,strtotime('2026-09-05 08:00:00'));
$first=$rows[0]??[];$second=$rows[1]??[];

ok(($first['first_response_at']??null)==='2026-09-01 09:00:00','Mensaje independiente puede ser primera respuesta');
ok(($first['first_response_origin']??null)==='Mensaje','Origen identifica primera respuesta por mensaje');
ok((int)($first['first_response_minutes']??-1)===60,'Tiempo primera respuesta se mide desde grant');
ok((int)($first['responses']??-1)===1,'Comentario de informe e interno no duplican respuestas');
ok((int)($first['attachments']??-1)===1,'Adjunto se cuenta dentro del primer ciclo');
ok((int)($first['reports']??-1)===1,'Cuenta informe técnico del primer ciclo');
ok((int)($first['declared_minutes']??-1)===30,'Suma tiempo declarado del primer ciclo');
ok(($first['work_status']??null)==='ANALYSIS','Actividad actual usa último informe del primer ciclo');
ok(($first['activity_label']??null)==='En análisis / diagnóstico','Actividad actual usa etiqueta operativa');
ok(($first['last_activity_at']??null)==='2026-09-01 09:30:00','Última actividad técnica conserva timestamp');
ok((int)($first['deliveries']??-1)===0,'Primer ciclo no tiene entrega lista para revisión');

ok(($second['first_response_at']??null)==='2026-09-04 09:00:00','Informe puede ser primera respuesta del ciclo');
ok(($second['first_response_origin']??null)==='Informe técnico','Comentario autogenerado no desplaza origen Informe técnico');
ok((int)($second['first_response_minutes']??-1)===60,'Informe mide tiempo de primera respuesta desde grant');
ok((int)($second['responses']??-1)===1,'Solo mensaje independiente cuenta como respuesta del segundo ciclo');
ok((int)($second['attachments']??-1)===1,'Adjunto fuera de ciclo no se mezcla con segundo ciclo');
ok((int)($second['reports']??-1)===1,'Cuenta informe técnico del segundo ciclo');
ok((int)($second['declared_minutes']??-1)===45,'Suma tiempo declarado del segundo ciclo');
ok(($second['work_status']??null)==='READY_FOR_REVIEW','Actividad actual usa READY_FOR_REVIEW');
ok(($second['activity_label']??null)==='Listo para revisión de Carrousel','READY_FOR_REVIEW usa etiqueta operativa');
ok(($second['last_activity_at']??null)==='2026-09-04 09:00:00','Última actividad del segundo ciclo proviene del informe');
ok((int)($second['deliveries']??-1)===1,'Cuenta entrega READY_FOR_REVIEW');

if($errors){fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);exit(1);}echo '[OK] Respuesta y actividad de proveedores.'.PHP_EOL;
