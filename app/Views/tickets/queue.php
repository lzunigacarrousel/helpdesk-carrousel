<?php
use App\Core\{Csrf,Auth};
$priorityLabels=$priorityLabels??['LOW'=>'Baja','MEDIUM'=>'Media','HIGH'=>'Alta','CRITICAL'=>'Crítica'];
$statusLabels=$statusLabels??[];$myTickets=$myTickets??[];$tickets=$tickets??[];
$pageTitle='Centro de soporte';$pageSection='Centro de soporte';$activeNav='support';$helpContext='support_center';

$allRows=array_values(array_merge($myTickets,$tickets));
$allowedViews=['attention','mine','available','in_progress','pending','critical','overdue','near_due','reopened'];
$view=strtolower(trim((string)($_GET['view']??'attention')));if(!in_array($view,$allowedViews,true))$view='attention';
$search=trim((string)($_GET['q']??''));$parkFilter=trim((string)($_GET['park']??''));$categoryFilter=trim((string)($_GET['category']??''));$priorityFilter=strtoupper(trim((string)($_GET['priority']??'')));$statusFilter=strtoupper(trim((string)($_GET['status']??'')));$responsibleFilter=trim((string)($_GET['responsible']??''));
$now=time();$nearLimit=$now+7200;$uid=(int)Auth::id();
$isOpen=static fn(array $t):bool=>!in_array((string)$t['status'],['RESOLVED','CLOSED','CANCELLED'],true);
$isOverdue=static fn(array $t):bool=>!empty($t['resolution_due_at'])&&strtotime((string)$t['resolution_due_at'])<$now&&!in_array((string)$t['status'],['RESOLVED','CLOSED','CANCELLED'],true);
$isNearDue=static fn(array $t):bool=>!empty($t['resolution_due_at'])&&(($ts=strtotime((string)$t['resolution_due_at']))!==false)&&$ts>=$now&&$ts<=$nearLimit&&!in_array((string)$t['status'],['RESOLVED','CLOSED','CANCELLED'],true);

$quickMatch=static function(array $t)use($view,$uid,$isOpen,$isOverdue,$isNearDue):bool{
    return match($view){
        'mine'=>(int)($t['assigned_to']??0)===$uid&&$isOpen($t),
        'available'=>empty($t['assigned_to'])&&in_array((string)$t['status'],['NEW','AVAILABLE','REOPENED'],true),
        'in_progress'=>(string)$t['status']==='IN_PROGRESS',
        'pending'=>(string)$t['status']==='PENDING',
        'critical'=>(string)$t['priority']==='CRITICAL'&&$isOpen($t),
        'overdue'=>$isOverdue($t),
        'near_due'=>$isNearDue($t),
        'reopened'=>(string)$t['status']==='REOPENED',
        default=>$isOpen($t),
    };
};
$filtered=array_values(array_filter($allRows,static function(array $t)use($quickMatch,$search,$parkFilter,$categoryFilter,$priorityFilter,$statusFilter,$responsibleFilter,$uid):bool{
    if(!$quickMatch($t))return false;
    if($search!==''){
        $haystack=mb_strtolower(implode(' ',[(string)($t['ticket_number']??''),(string)($t['subject']??''),(string)($t['description']??''),(string)($t['requester_name']??''),(string)($t['requester_email']??''),(string)($t['park_name']??''),(string)($t['category_name']??'')]));
        if(!str_contains($haystack,mb_strtolower($search)))return false;
    }
    if($parkFilter!==''&&(string)($t['park_name']??'')!==$parkFilter)return false;
    if($categoryFilter!==''&&(string)($t['category_name']??'')!==$categoryFilter)return false;
    if($priorityFilter!==''&&(string)($t['priority']??'')!==$priorityFilter)return false;
    if($statusFilter!==''&&(string)($t['status']??'')!==$statusFilter)return false;
    if($responsibleFilter==='mine'&&(int)($t['assigned_to']??0)!==$uid)return false;
    if($responsibleFilter==='unassigned'&&!empty($t['assigned_to']))return false;
    return true;
}));
$priorityOrder=['CRITICAL'=>0,'HIGH'=>1,'MEDIUM'=>2,'LOW'=>3];
usort($filtered,static function(array $a,array $b)use($priorityOrder):int{
    $pa=$priorityOrder[$a['priority']??'']??9;$pb=$priorityOrder[$b['priority']??'']??9;if($pa!==$pb)return $pa<=>$pb;
    $da=!empty($a['resolution_due_at'])?strtotime((string)$a['resolution_due_at']):PHP_INT_MAX;$db=!empty($b['resolution_due_at'])?strtotime((string)$b['resolution_due_at']):PHP_INT_MAX;if($da!==$db)return $da<=>$db;
    return strcmp((string)($b['updated_at']??''),(string)($a['updated_at']??''));
});

$counts=['attention'=>0,'mine'=>0,'available'=>0,'in_progress'=>0,'pending'=>0,'critical'=>0,'overdue'=>0,'near_due'=>0,'reopened'=>0];
foreach($allRows as $t){if($isOpen($t))$counts['attention']++;if((int)($t['assigned_to']??0)===$uid&&$isOpen($t))$counts['mine']++;if(empty($t['assigned_to'])&&in_array((string)$t['status'],['NEW','AVAILABLE','REOPENED'],true))$counts['available']++;if(($t['status']??'')==='IN_PROGRESS')$counts['in_progress']++;if(($t['status']??'')==='PENDING')$counts['pending']++;if(($t['priority']??'')==='CRITICAL'&&$isOpen($t))$counts['critical']++;if($isOverdue($t))$counts['overdue']++;if($isNearDue($t))$counts['near_due']++;if(($t['status']??'')==='REOPENED')$counts['reopened']++;}
$parks=[];$categories=[];foreach($allRows as $t){if(!empty($t['park_name']))$parks[(string)$t['park_name']]=true;if(!empty($t['category_name']))$categories[(string)$t['category_name']]=true;}ksort($parks,SORT_NATURAL|SORT_FLAG_CASE);ksort($categories,SORT_NATURAL|SORT_FLAG_CASE);
$advancedActive=$search!==''||$parkFilter!==''||$categoryFilter!==''||$priorityFilter!==''||$statusFilter!==''||$responsibleFilter!=='';
$viewLabels=['attention'=>'Todos','mine'=>'Míos','available'=>'Disponibles','in_progress'=>'En proceso','pending'=>'En espera','critical'=>'Críticos','overdue'=>'Vencidos','near_due'=>'Por vencer','reopened'=>'Reabiertos'];
$formatSla=static function(array $t)use($now):array{
    if(in_array((string)$t['status'],['RESOLVED','CLOSED','CANCELLED'],true))return['Finalizado','neutral'];
    if(empty($t['resolution_due_at']))return['Sin SLA','neutral'];
    $due=strtotime((string)$t['resolution_due_at']);if($due===false)return['Sin SLA','neutral'];$diff=$due-$now;$abs=abs($diff);$h=intdiv($abs,3600);$m=intdiv($abs%3600,60);$txt=$h>0?$h.'h '.$m.'m':max(1,$m).'m';
    if($diff<0)return['Vencido hace '.$txt,'danger'];if($diff<=7200)return[$txt.' restantes','warning'];return[$txt.' restantes','ok'];
};
require APP_ROOT.'/app/Views/shared/app_start.php';
?>
<div class="support-page queue-operational-page">
  <div class="support-hero queue-hero">
    <div><div class="ticket-kicker">Equipo de soporte</div><h1 class="page-title">Centro de soporte</h1><p class="page-subtitle">Encuentra rápido qué requiere atención, entiende el problema y actúa sin perder contexto.</p></div>
    <div class="queue-summary"><strong><?= count($filtered) ?></strong><span><?= htmlspecialchars($viewLabels[$view]??'Casos') ?></span></div>
  </div>
  <?php if(!empty($flash)): ?><div class="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

  <nav class="queue-quick-filters" aria-label="Filtros rápidos">
    <?php foreach($viewLabels as $code=>$label): ?><a class="queue-filter-chip <?= $view===$code?'active':'' ?> <?= in_array($code,['critical','overdue'],true)&&($counts[$code]??0)>0?'attention':'' ?>" href="<?= APP_BASE_URL ?>/tickets/queue?view=<?= urlencode($code) ?>"><span><?= htmlspecialchars($label) ?></span><b><?= (int)($counts[$code]??0) ?></b></a><?php endforeach; ?>
  </nav>

  <details class="queue-filters-panel" <?= $advancedActive?'open':'' ?>>
    <summary><span>Filtros</span><small><?= $advancedActive?'Hay filtros avanzados aplicados':'Buscar por ticket, parque, categoría, prioridad o estado' ?></small></summary>
    <form method="get" action="<?= APP_BASE_URL ?>/tickets/queue" class="queue-filter-form">
      <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
      <label class="queue-filter-search">Buscar<input class="form-control" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ticket, asunto, problema, solicitante…"></label>
      <label>Parque<select class="form-control" name="park"><option value="">Todos</option><?php foreach(array_keys($parks) as $p): ?><option <?= $parkFilter===$p?'selected':'' ?>><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select></label>
      <label>Categoría<select class="form-control" name="category"><option value="">Todas</option><?php foreach(array_keys($categories) as $c): ?><option <?= $categoryFilter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select></label>
      <label>Prioridad<select class="form-control" name="priority"><option value="">Todas</option><?php foreach($priorityLabels as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>" <?= $priorityFilter===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
      <label>Estado<select class="form-control" name="status"><option value="">Todos</option><?php foreach($statusLabels as $code=>$label): ?><option value="<?= htmlspecialchars($code) ?>" <?= $statusFilter===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
      <label>Responsable<select class="form-control" name="responsible"><option value="">Todos visibles</option><option value="mine" <?= $responsibleFilter==='mine'?'selected':'' ?>>Míos</option><option value="unassigned" <?= $responsibleFilter==='unassigned'?'selected':'' ?>>Sin asignar</option></select></label>
      <div class="queue-filter-actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="<?= APP_BASE_URL ?>/tickets/queue?view=<?= urlencode($view) ?>">Limpiar</a></div>
    </form>
  </details>

  <section class="card queue-table-card">
    <div class="queue-table-head"><div><span class="ticket-kicker"><?= htmlspecialchars($viewLabels[$view]??'Casos') ?></span><h2><?= count($filtered) ?> caso<?= count($filtered)===1?'':'s' ?></h2></div><small>Ordenados por prioridad y vencimiento.</small></div>
    <?php if($filtered): ?>
    <div class="table-responsive queue-table-wrap"><table class="queue-table responsive"><thead><tr><th>Ticket / asunto</th><th>Solicitante</th><th>Parque</th><th>Prioridad</th><th>Estado</th><th>SLA</th><th>Responsable</th><th>Actualizado</th><th>Acción</th></tr></thead><tbody>
      <?php foreach($filtered as $t): [$slaText,$slaClass]=$formatSla($t);$assigned=(int)($t['assigned_to']??0); ?>
      <tr class="queue-row priority-row-<?= strtolower((string)$t['priority']) ?>">
        <td data-label="Ticket"><div class="queue-ticket-main"><span><?= htmlspecialchars($t['ticket_number']) ?></span><strong><?= htmlspecialchars($t['subject']) ?></strong><p><?= htmlspecialchars(mb_strimwidth(trim((string)($t['description']??'')),0,150,'…')) ?></p></div></td>
        <td data-label="Solicitante"><strong><?= htmlspecialchars($t['requester_name']??'Sin nombre') ?></strong><small><?= htmlspecialchars($t['requester_email']??'') ?></small></td>
        <td data-label="Parque"><?= htmlspecialchars($t['park_name']??'No especificado') ?></td>
        <td data-label="Prioridad"><span class="priority-chip priority-<?= strtolower((string)$t['priority']) ?>"><?= htmlspecialchars($priorityLabels[$t['priority']]??$t['priority']) ?></span></td>
        <td data-label="Estado"><span class="ticket-status-pill status-<?= strtolower((string)$t['status']) ?>"><?= htmlspecialchars($statusLabels[$t['status']]??$t['status']) ?></span></td>
        <td data-label="SLA"><span class="queue-sla <?= $slaClass ?>"><?= htmlspecialchars($slaText) ?></span></td>
        <td data-label="Responsable"><?= $assigned===$uid?'Yo':($assigned===0?'Sin asignar':'Asignado') ?></td>
        <td data-label="Actualizado"><?= htmlspecialchars(date('d/m H:i',strtotime((string)($t['updated_at']??$t['created_at'])))) ?></td>
        <td data-label="Acción" class="queue-action-cell"><?php if($assigned===0): ?><form method="post" action="<?= APP_BASE_URL ?>/tickets/claim" data-single-submit><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>"><button class="btn btn-primary btn-sm" type="submit">Tomar</button></form><?php else: ?><a class="btn btn-primary btn-sm" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Continuar</a><?php endif; ?><a class="btn btn-outline-secondary btn-sm" href="<?= APP_BASE_URL ?>/tickets/view?id=<?= (int)$t['id'] ?>">Ver</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="empty-state queue-empty"><strong><?= $view==='available'?'La cola está al día':'No hay casos en esta vista' ?></strong><span><?= $advancedActive?'Prueba limpiar los filtros o cambiar el segmento.':'No tienes elementos que requieran atención aquí.' ?></span></div><?php endif; ?>
  </section>
</div>
<?php require APP_ROOT.'/app/Views/shared/app_end.php'; ?>