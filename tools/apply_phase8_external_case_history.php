<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controllerPath=$root.'/app/Controllers/TicketController.php';
$viewPath=$root.'/app/Views/tickets/index.php';

foreach([$controllerPath,$viewPath] as $path){
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe '.$path.PHP_EOL);
        exit(1);
    }
}

$normalize=static function(string $value):string{
    return str_replace(["\r\n","\r"],"\n",$value);
};

$replaceOnce=static function(string $source,string $old,string $new,string $label)use($normalize):string{
    $source=$normalize($source);
    $old=$normalize($old);
    $new=$normalize($new);
    $count=substr_count($source,$old);
    if($count!==1){
        fwrite(STDERR,'[ERROR] '.$label.': esperaba 1 coincidencia y encontro '.$count.'.'.PHP_EOL);
        exit(1);
    }
    return str_replace($old,$new,$source);
};

$controller=$normalize((string)file_get_contents($controllerPath));
$view=$normalize((string)file_get_contents($viewPath));

$controllerOld=<<<'PHP'
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE eta.user_id=? AND eta.revoked_at IS NULL AND t.case_type='SPECIAL' AND t.visibility_mode='EXTERNAL_ALLOWED' AND t.deleted_at IS NULL ORDER BY t.created_at DESC");$s->execute([$uid]);
PHP;
$controllerNew=<<<'PHP'
            $s=$pdo->prepare("SELECT t.*,c.name category_name,p.name park_name,a.name area_name,u.full_name assigned_name,eta.granted_at external_granted_at,eta.revoked_at external_revoked_at FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id LEFT JOIN areas a ON a.id=t.area_id LEFT JOIN users u ON u.id=t.assigned_to WHERE eta.user_id=? AND t.case_type='SPECIAL' AND t.deleted_at IS NULL AND (eta.revoked_at IS NOT NULL OR t.visibility_mode='EXTERNAL_ALLOWED') ORDER BY COALESCE(eta.revoked_at,eta.granted_at) DESC,t.created_at DESC");$s->execute([$uid]);
PHP;
$controller=$replaceOnce($controller,$controllerOld,$controllerNew,'Query historial externo');

$counterOld=<<<'PHP'
$openCount=0;$waitingCount=0;$doneCount=0;$reviewCount=0;
foreach($tickets as $t){
  $st=(string)$t['status'];
  if(in_array($st,['NEW','AVAILABLE','IN_PROGRESS','REOPENED'],true))$openCount++;
  elseif($st==='PENDING')$waitingCount++;
  elseif($st==='RESOLVED'){$doneCount++;$reviewCount++;}
  elseif($st==='CLOSED')$doneCount++;
}
PHP;
$counterNew=<<<'PHP'
$openCount=0;$waitingCount=0;$doneCount=0;$reviewCount=0;$totalCount=count($tickets);
foreach($tickets as $t){
  $st=(string)$t['status'];
  if($isExternal){
    if(!empty($t['external_revoked_at'])){$doneCount++;continue;}
    if($st==='PENDING')$waitingCount++;
    else $openCount++;
    continue;
  }
  if(in_array($st,['NEW','AVAILABLE','IN_PROGRESS','REOPENED'],true))$openCount++;
  elseif($st==='PENDING')$waitingCount++;
  elseif($st==='RESOLVED'){$doneCount++;$reviewCount++;}
  elseif($st==='CLOSED')$doneCount++;
}
PHP;
$view=$replaceOnce($view,$counterOld,$counterNew,'Contadores Mis casos');

$headingOld=<<<'PHP'
<div class="page-heading tickets-heading"><div><h1 class="page-title"><?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isExternal?'Abre solo el caso que necesites revisar o actualizar.':'Revisa el estado de tus casos y abre solo el que necesites continuar.' ?></p></div><?php if(!$isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
PHP;
$headingNew=<<<'PHP'
<div class="page-heading tickets-heading"><div><h1 class="page-title"><?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isExternal?'Consulta tus casos activos y el historial de participaciones finalizadas.':'Revisa el estado de tus casos y abre solo el que necesites continuar.' ?></p></div><?php if(!$isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
PHP;
$view=$replaceOnce($view,$headingOld,$headingNew,'Encabezado Mis casos');

$statsOld=<<<'PHP'
  <div class="external-case-stats"><div><span>Activos</span><strong><?= $openCount ?></strong></div><div><span>En espera</span><strong><?= $waitingCount ?></strong></div><div><span>Finalizados</span><strong><?= $doneCount ?></strong></div></div>
PHP;
$statsNew=<<<'PHP'
  <div class="external-case-stats"><div><span>Activos</span><strong><?= $openCount ?></strong></div><div><span>En espera</span><strong><?= $waitingCount ?></strong></div><div><span>Finalizados</span><strong><?= $doneCount ?></strong></div><div><span>Total</span><strong><?= $totalCount ?></strong></div></div>
PHP;
$view=$replaceOnce($view,$statsOld,$statsNew,'Resumen externo');

$loopOld=<<<'PHP'
<?php if($tickets): ?><section class="ticket-list <?= $isExternal?'external-ticket-list':'' ?>" aria-label="<?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?>"><?php foreach($tickets as $t):
  $needsReview=!$isExternal&&(string)$t['status']==='RESOLVED';
  $target=$needsReview?APP_BASE_URL.'/tickets/feedback?id='.(int)$t['id']:APP_BASE_URL.'/tickets/view?id='.(int)$t['id'];
  $openText=$isExternal?'Ver caso →':($needsReview?'Revisar y cerrar →':'Ver detalle →');
PHP;
$loopNew=<<<'PHP'
<?php if($tickets): ?><section class="ticket-list <?= $isExternal?'external-ticket-list':'' ?>" aria-label="<?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?>"><?php foreach($tickets as $t):
  $needsReview=!$isExternal&&(string)$t['status']==='RESOLVED';
  $isExternalHistory=$isExternal&&!empty($t['external_revoked_at']);
  $target=$needsReview?APP_BASE_URL.'/tickets/feedback?id='.(int)$t['id']:APP_BASE_URL.'/tickets/view?id='.(int)$t['id'];
  $openText=$isExternal?($isExternalHistory?'Participación finalizada':'Ver caso →'):($needsReview?'Revisar y cerrar →':'Ver detalle →');
PHP;
$view=$replaceOnce($view,$loopOld,$loopNew,'Inicio loop tickets');

$cardOld=<<<'PHP'
<article class="ticket-list-card <?= $isExternal?'external-ticket-card ':'' ?><?= $needsReview?'ticket-awaiting-confirmation':'' ?>">
<a class="ticket-card-link" href="<?= $target ?>" aria-label="Abrir <?= htmlspecialchars($t['ticket_number']) ?>"></a>
<div class="ticket-list-top"><div><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($subject) ?></h2></div><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></div>
<?php if($showDescription): ?><p class="ticket-list-problem"><?= htmlspecialchars(mb_strimwidth($description,0,220,'…')) ?></p><?php endif; ?>
<div class="ticket-list-meta"><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><?php if(!$isExternal): ?><span><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span><?php endif; ?><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span></div>
<div class="ticket-list-bottom"><span><?= $isExternal?(!empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Sin asignar'):'Seguimiento por equipo de soporte' ?></span><span class="ticket-open-text"><?= $openText ?></span></div>
PHP;
$cardNew=<<<'PHP'
<article class="ticket-list-card <?= $isExternal?'external-ticket-card ':'' ?><?= $needsReview?'ticket-awaiting-confirmation ':'' ?><?= $isExternalHistory?'external-ticket-history':'' ?>">
<?php if(!$isExternalHistory): ?><a class="ticket-card-link" href="<?= $target ?>" aria-label="Abrir <?= htmlspecialchars($t['ticket_number']) ?>"></a><?php endif; ?>
<div class="ticket-list-top"><div><span class="ticket-number-small"><?= htmlspecialchars($t['ticket_number']) ?></span><h2><?= htmlspecialchars($subject) ?></h2></div><span class="ticket-status-pill status-<?= $isExternalHistory?'closed':strtolower((string)$t['status']) ?>"><?= htmlspecialchars($isExternalHistory?'Participación finalizada':($statusLabels[$t['status']]??$t['status'])) ?></span></div>
<?php if($showDescription): ?><p class="ticket-list-problem"><?= htmlspecialchars(mb_strimwidth($description,0,220,'…')) ?></p><?php endif; ?>
<div class="ticket-list-meta"><span><?= htmlspecialchars($t['category_name']??'Sin categoría') ?></span><span><?= htmlspecialchars($t['park_name']??'Ubicación no especificada') ?></span><?php if(!$isExternal): ?><span><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span><?php endif; ?><?php if($isExternal&&!empty($t['external_granted_at'])): ?><span>Asignado <?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['external_granted_at']))) ?></span><?php else: ?><span><?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['created_at']))) ?></span><?php endif; ?><?php if($isExternalHistory&&!empty($t['external_revoked_at'])): ?><span>Finalizó <?= htmlspecialchars(date('d/m/Y H:i',strtotime($t['external_revoked_at']))) ?></span><?php endif; ?></div>
<div class="ticket-list-bottom"><span><?= $isExternal?($isExternalHistory?'Historial de participación':(!empty($t['assigned_name'])?'Responsable: '.htmlspecialchars($t['assigned_name']):'Sin asignar')):'Seguimiento por equipo de soporte' ?></span><span class="ticket-open-text"><?= $openText ?></span></div>
PHP;
$view=$replaceOnce($view,$cardOld,$cardNew,'Tarjeta ticket');

$emptyOld=<<<'PHP'
</article><?php endforeach; ?></section><?php else: ?><section class="card"><div class="empty-state"><h2><?= $isExternal?'No tienes casos asignados':'Aún no tienes solicitudes' ?></h2><p><?= $isExternal?'Cuando un caso requiera tu participación aparecerá aquí.':'Cuando necesites ayuda, usa Nueva solicitud en la parte superior.' ?></p></div></section><?php endif; ?>
PHP;
$emptyNew=<<<'PHP'
</article><?php endforeach; ?></section><?php else: ?><section class="card"><div class="empty-state"><h2><?= $isExternal?'Aún no tienes casos compartidos':'Aún no tienes solicitudes' ?></h2><p><?= $isExternal?'Cuando Carrousel requiera tu participación en un caso, aparecerá aquí y permanecerá en tu historial al finalizar.':'Cuando necesites ayuda, usa Nueva solicitud en la parte superior.' ?></p></div></section><?php endif; ?>
PHP;
$view=$replaceOnce($view,$emptyOld,$emptyNew,'Estado vacio externo');

if(file_put_contents($controllerPath,$controller)===false||file_put_contents($viewPath,$view)===false){
    fwrite(STDERR,'[ERROR] No fue posible escribir los archivos.'.PHP_EOL);
    exit(1);
}

$php=PHP_BINARY;
foreach([$controllerPath,$viewPath] as $path){
    passthru('"'.$php.'" -l "'.$path.'"',$code);
    if($code!==0)exit($code);
}

echo '[OK] Historial seguro para proveedor externo aplicado. Casos revocados quedan visibles sin reabrir el detalle. No se modifico la BD.'.PHP_EOL;
