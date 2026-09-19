<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/HelpdeskCarrousel/public/index.php';
$_SERVER['HTTP_HOST']='localhost';

require_once $root.'/config/config.php';
require_once $root.'/app/Core/Database.php';

use App\Core\Database;

$errors=0;
function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}
function scalar(\PDO $pdo,string $sql,array $params=[]):mixed
{
    $q=$pdo->prepare($sql);$q->execute($params);return $q->fetchColumn();
}

if(DB_NAME!=='carrousel_helpdesk'){
    fwrite(STDERR,'[ERROR] Este ensayo solo puede ejecutarse en carrousel_helpdesk.'.PHP_EOL);
    exit(1);
}

$pdo=Database::pdo();
$ticketNumber='HD-F12-E2E-'.date('YmdHis').'-'.random_int(100,999);

$support=$pdo->query(
    "SELECT u.id,u.full_name,u.email,r.code role_code
     FROM users u
     JOIN roles r ON r.id=u.role_id
     WHERE u.access_type='INTERNAL'
       AND u.status='ACTIVE'
       AND u.deleted_at IS NULL
       AND r.code IN('TECHNICIAN','SEMIADMIN','ADMIN')
     ORDER BY FIELD(r.code,'TECHNICIAN','SEMIADMIN','ADMIN'),u.id
     LIMIT 1"
)->fetch();
$requester=$pdo->query("SELECT u.id,u.full_name,u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code='REQUESTER' ORDER BY u.id LIMIT 1")->fetch();
$external=$pdo->query("SELECT u.id,u.full_name,u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='EXTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code='EXTERNAL' ORDER BY u.id LIMIT 1")->fetch();
$categoryId=(int)($pdo->query("SELECT id FROM ticket_categories WHERE is_active=1 ORDER BY id LIMIT 1")->fetchColumn()?:0);

ok(is_array($support),'Existe operador de soporte activo para ensayo');
ok(is_array($requester),'Existe Solicitante activo para ensayo');
ok(is_array($external),'Existe Colaborador activo para ensayo');
ok($categoryId>0,'Existe categoría activa para ensayo');

if($errors)exit(1);

$pdo->beginTransaction();
try{
    $articleNumber='KB-F12-E2E-'.date('YmdHis').'-'.random_int(100,999);
    $pdo->prepare(
        "INSERT INTO knowledge_articles(
            article_number,lifecycle_status,created_by_user_id,title,summary,content,
            status,visibility,category_id,author_user_id,published_at,created_at,updated_at
         ) VALUES(?,'ACTIVE',?,?,?,?,?,'PUBLISHED','INTERNAL',?,?,NOW(),NOW(),NOW())"
    )->execute([
        $articleNumber,
        (int)$support['id'],
        'Conocimiento temporal E2E',
        'Referencia temporal para validar trazabilidad.',
        "Causa raíz\nCausa temporal E2E\n\nSolución\nSolución temporal E2E\n\nPrevención\nPrevención temporal E2E",
        $categoryId,
        (int)$support['id'],
    ]);
    $articleId=(int)$pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO knowledge_revisions(
            article_id,revision_number,state,title,summary,content,category_id,
            created_by_user_id,internal_published_by_user_id,internal_published_at,created_at,updated_at
         ) VALUES(?,1,'PUBLISHED',?,?,?,?,?,?,NOW(),NOW(),NOW())"
    )->execute([
        $articleId,
        'Conocimiento temporal E2E',
        'Referencia temporal para validar trazabilidad.',
        "Causa raíz\nCausa temporal E2E\n\nSolución\nSolución temporal E2E\n\nPrevención\nPrevención temporal E2E",
        $categoryId,
        (int)$support['id'],
        (int)$support['id'],
    ]);
    $revisionId=(int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE knowledge_articles SET current_internal_revision_id=? WHERE id=?")
        ->execute([$revisionId,$articleId]);
    ok($articleId>0&&$revisionId>0,'0. Conocimiento temporal creado dentro de la transacción');

    $insert=$pdo->prepare("INSERT INTO tickets(
      ticket_number,origin,requester_user_id,requester_email,requester_name,category_id,
      subject,description,priority,status,created_at,updated_at
    ) VALUES(?,?,?,?,?,?,?,?,'MEDIUM','NEW',NOW(),NOW())");
    $insert->execute([
        $ticketNumber,'AUTHENTICATED_WEB',(int)$requester['id'],(string)$requester['email'],
        (string)$requester['full_name'],$categoryId,'Ensayo E2E Fase 12',
        'Caso temporal para validar el ciclo completo de Helpdesk Carrousel.'
    ]);
    $ticketId=(int)$pdo->lastInsertId();
    ok($ticketId>0,'1. Ticket temporal creado');

    $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'CREATED',?,'USER',?,NOW())")
        ->execute([$ticketId,(int)$requester['id'],json_encode(['status'=>'NEW'],JSON_UNESCAPED_UNICODE)]);

    $pdo->prepare("UPDATE tickets SET assigned_to=?,assigned_at=NOW(),status='IN_PROGRESS',first_response_at=NOW(),updated_at=NOW() WHERE id=?")
        ->execute([(int)$support['id'],$ticketId]);
    $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'CLAIMED',?,'USER',?,NOW())")
        ->execute([$ticketId,(int)$support['id'],json_encode(['status'=>'IN_PROGRESS'],JSON_UNESCAPED_UNICODE)]);
    ok((string)scalar($pdo,'SELECT status FROM tickets WHERE id=?',[$ticketId])==='IN_PROGRESS','2. Caso tomado y en proceso');

    $comment=$pdo->prepare("INSERT INTO ticket_comments(ticket_id,author_user_id,author_name,author_email,visibility,body,created_at) VALUES(?,?,?,?,?,?,NOW())");
    $comment->execute([$ticketId,(int)$support['id'],$support['full_name'],$support['email'],'PUBLIC','Respuesta pública E2E.']);
    $comment->execute([$ticketId,(int)$support['id'],$support['full_name'],$support['email'],'INTERNAL','Nota interna E2E.']);
    ok((int)scalar($pdo,"SELECT COUNT(*) FROM ticket_comments WHERE ticket_id=? AND visibility='PUBLIC'",[$ticketId])===1,'3A. Conversación pública');
    ok((int)scalar($pdo,"SELECT COUNT(*) FROM ticket_comments WHERE ticket_id=? AND visibility='INTERNAL'",[$ticketId])===1,'3B. Conversación interna');

    $pdo->prepare("UPDATE tickets SET case_type='SPECIAL',visibility_mode='EXTERNAL_ALLOWED',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    $pdo->prepare("INSERT INTO external_ticket_access(ticket_id,user_id,can_comment,can_upload,granted_by,granted_at) VALUES(?,?,1,1,?,NOW())")
        ->execute([$ticketId,(int)$external['id'],(int)$support['id']]);
    $comment->execute([$ticketId,(int)$external['id'],$external['full_name'],$external['email'],'EXTERNAL','Respuesta proveedor E2E.']);
    ok((int)scalar($pdo,"SELECT COUNT(*) FROM ticket_comments WHERE ticket_id=? AND visibility='EXTERNAL'",[$ticketId])===1,'4. Colaboración con proveedor');

    $pdo->prepare("UPDATE tickets SET status='PENDING',pending_reason_code='WAITING_PROVIDER',pending_note='Ensayo E2E',updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'STATUS_CHANGED',?,'USER',?,NOW())")
        ->execute([$ticketId,(int)$support['id'],json_encode(['status'=>'PENDING','pending_reason_code'=>'WAITING_PROVIDER'],JSON_UNESCAPED_UNICODE)]);
    ok((string)scalar($pdo,'SELECT pending_reason_code FROM tickets WHERE id=?',[$ticketId])==='WAITING_PROVIDER','5. Espera por proveedor');

    $pdo->prepare("UPDATE tickets SET status='IN_PROGRESS',pending_reason_code=NULL,pending_note=NULL,updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    ok((string)scalar($pdo,'SELECT status FROM tickets WHERE id=?',[$ticketId])==='IN_PROGRESS','6. Atención continuada');

    $pdo->prepare("INSERT INTO ticket_activities(
      ticket_id,activity_type,status,responsible_user_id,objective,
      scheduled_start_at,scheduled_end_at,created_by,created_at,updated_at
    ) VALUES(?,'SEGUIMIENTO','PROGRAMADA',?,'Validación E2E',NOW(),DATE_ADD(NOW(),INTERVAL 30 MINUTE),?,NOW(),NOW())")
        ->execute([$ticketId,(int)$support['id'],(int)$support['id']]);
    $activityId=(int)$pdo->lastInsertId();
    $pdo->prepare("UPDATE ticket_activities SET status='EN_CURSO',started_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$activityId]);
    $pdo->prepare("UPDATE ticket_activities SET status='FINALIZADA',result_code='RESUELTA',work_performed='Prueba E2E',result_summary='Actividad validada',finished_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$activityId]);
    ok((string)scalar($pdo,'SELECT status FROM ticket_activities WHERE id=?',[$activityId])==='FINALIZADA','7. Actividad programada/iniciada/finalizada');

    $pdo->prepare("INSERT INTO ticket_resolution_references(
      ticket_id,reference_type,knowledge_article_id,knowledge_revision_id,used_by_user_id,used_at,
      applied_root_cause,applied_solution,applied_prevention
    ) VALUES(?,'KNOWLEDGE',?,?,?,NOW(),0,1,0)")
        ->execute([$ticketId,$articleId,$revisionId,(int)$support['id']]);
    ok((int)scalar($pdo,'SELECT COUNT(*) FROM ticket_resolution_references WHERE ticket_id=?',[$ticketId])===1,'8. Referencia de conocimiento trazada');

    $pdo->prepare("INSERT INTO ticket_resolutions(
      ticket_id,resolution_type,root_cause,solution_applied,preventive_action,is_reusable,resolved_by,created_at,updated_at
    ) VALUES(?,'FIX','Causa E2E','Solución aplicada durante ensayo E2E.','Prevención E2E',1,?,NOW(),NOW())")
        ->execute([$ticketId,(int)$support['id']]);
    $pdo->prepare("UPDATE tickets SET status='RESOLVED',resolved_at=NOW(),closed_at=NULL,updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,created_at) VALUES(?,'RESOLUTION_RECORDED',?,'USER',?,NOW())")
        ->execute([$ticketId,(int)$support['id'],json_encode(['status'=>'RESOLVED'],JSON_UNESCAPED_UNICODE)]);
    ok((string)scalar($pdo,'SELECT status FROM tickets WHERE id=?',[$ticketId])==='RESOLVED','9. Resolución documentada');

    $pdo->prepare("UPDATE tickets SET status='REOPENED',resolved_at=NULL,closed_at=NULL,updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    $comment->execute([$ticketId,(int)$requester['id'],$requester['full_name'],$requester['email'],'PUBLIC','Devuelto por solicitante E2E.']);
    $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at) VALUES(?,'REOPENED',?,'USER',?,?,NOW())")
        ->execute([$ticketId,(int)$requester['id'],json_encode(['status'=>'REOPENED'],JSON_UNESCAPED_UNICODE),json_encode(['source'=>'REQUESTER_RETURN'],JSON_UNESCAPED_UNICODE)]);
    ok((string)scalar($pdo,'SELECT status FROM tickets WHERE id=?',[$ticketId])==='REOPENED','10. Reapertura por solicitante');

    $pdo->prepare("UPDATE ticket_resolutions SET solution_applied='Solución final E2E.',updated_at=NOW() WHERE ticket_id=?")->execute([$ticketId]);
    $pdo->prepare("UPDATE tickets SET status='RESOLVED',resolved_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    $pdo->prepare("INSERT INTO ticket_feedback(ticket_id,requester_user_id,nps_score,comment,created_at,updated_at) VALUES(?,?,10,'Feedback E2E',NOW(),NOW())")
        ->execute([$ticketId,(int)$requester['id']]);
    $pdo->prepare("UPDATE tickets SET status='CLOSED',closed_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$ticketId]);
    ok((string)scalar($pdo,'SELECT status FROM tickets WHERE id=?',[$ticketId])==='CLOSED','11. Confirmación final cierra el caso');
    ok((int)scalar($pdo,'SELECT nps_score FROM ticket_feedback WHERE ticket_id=?',[$ticketId])===10,'12. NPS persistible');

    ok((int)scalar($pdo,'SELECT COUNT(*) FROM ticket_events WHERE ticket_id=?',[$ticketId])>=4,'13. Historial de eventos presente');
    ok((int)scalar($pdo,'SELECT COUNT(*) FROM ticket_comments WHERE ticket_id=?',[$ticketId])===4,'14. Tres canales + devolución trazados');

    $pdo->rollBack();
    ok((int)scalar($pdo,'SELECT COUNT(*) FROM tickets WHERE ticket_number=?',[$ticketNumber])===0,'15. ROLLBACK elimina caso temporal');
}catch(\Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    fwrite(STDERR,'[ERROR] Ensayo E2E: '.$e->getMessage().PHP_EOL);
    exit(1);
}

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Ensayo transaccional E2E Fase 12 completado con ROLLBACK.'.PHP_EOL;
