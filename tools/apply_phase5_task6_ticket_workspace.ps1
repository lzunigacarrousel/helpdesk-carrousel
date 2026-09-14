$ErrorActionPreference = 'Stop'

$repo = Split-Path -Parent $PSScriptRoot
$path = Join-Path $repo 'app\Controllers\TicketController.php'

if (-not (Test-Path $path)) {
    throw "No existe $path"
}

$content = [System.IO.File]::ReadAllText($path)

$oldImport = "use App\Services\{NotificationService,ScopeService,SlaPresentationService,TicketClassificationService,RequesterLocationPolicyService};"
$newImport = "use App\Services\{NotificationService,ScopeService,SlaPresentationService,TicketClassificationService,RequesterLocationPolicyService,TicketActivityService};"

if (-not $content.Contains($oldImport) -and -not $content.Contains($newImport)) {
    throw 'No se encontro el import esperado de Services.'
}
if ($content.Contains($oldImport)) {
    $content = $content.Replace($oldImport, $newImport)
}

$oldWorkspace = @'
        $isSupport=Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.reassign')||Auth::can('tickets.view_all');$supportUsers=[];if(Auth::can('tickets.reassign'))$supportUsers=$pdo->query("SELECT u.id,u.full_name,u.email,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll();
        View::render('tickets/show',['user'=>Auth::user(),'ticket'=>$ticket,'events'=>$e->fetchAll(),'flash'=>Flash::pull(),'isSupport'=>$isSupport,'supportUsers'=>$supportUsers,'canClaim'=>Auth::can('tickets.claim')&&empty($ticket['assigned_to'])&&in_array($ticket['status'],['NEW','AVAILABLE','REOPENED'],true),'canReassign'=>Auth::can('tickets.reassign'),'canRelease'=>!empty($ticket['assigned_to'])&&((int)$ticket['assigned_to']===(int)Auth::id()||Auth::can('tickets.reassign'))&&!in_array($ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),'canChangeStatus'=>Auth::can('tickets.change_status')&&((int)($ticket['assigned_to']??0)===(int)Auth::id()||Auth::can('tickets.reassign')),'canClassify'=>Auth::can('tickets.classify')&&!in_array((string)$ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),'statusLabels'=>self::STATUS_LABELS,'priorityLabels'=>self::PRIORITY_LABELS,'canEditLocation'=>$canEditLocation,'locationParks'=>$locationParks,'locationAreas'=>$locationAreas]);
'@

$newWorkspace = @'
        $isSupport=Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.reassign')||Auth::can('tickets.view_all');$supportUsers=[];if(Auth::can('tickets.reassign'))$supportUsers=$pdo->query("SELECT u.id,u.full_name,u.email,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.access_type='INTERNAL' AND u.status='ACTIVE' AND u.deleted_at IS NULL AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN') ORDER BY u.full_name")->fetchAll();

        $activityService=new TicketActivityService();
        $activities=$isSupport?$activityService->listForTicket($id):[];
        $requesterActivities=!$isSupport?$activityService->requesterVisibleForTicket($id):[];
        $canCreateActivities=$isSupport&&Auth::can('activities.create');
        $canManageActivities=$isSupport&&Auth::can('activities.manage');
        $canCancelActivities=$isSupport&&Auth::can('activities.cancel');
        $activityTypes=$isSupport?TicketActivityService::types():[];
        $activityResults=$isSupport?TicketActivityService::results():[];
        $activityResponsibleUsers=$canCreateActivities?$activityService->responsibleOptionsForTicket($id):[];
        $activityProviderUsers=$canCreateActivities?$activityService->providerOptionsForTicket($id):[];
        $activityParks=$isSupport?$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll():[];

        View::render('tickets/show',[
            'user'=>Auth::user(),
            'ticket'=>$ticket,
            'events'=>$e->fetchAll(),
            'flash'=>Flash::pull(),
            'isSupport'=>$isSupport,
            'supportUsers'=>$supportUsers,
            'canClaim'=>Auth::can('tickets.claim')&&empty($ticket['assigned_to'])&&in_array($ticket['status'],['NEW','AVAILABLE','REOPENED'],true),
            'canReassign'=>Auth::can('tickets.reassign'),
            'canRelease'=>!empty($ticket['assigned_to'])&&((int)$ticket['assigned_to']===(int)Auth::id()||Auth::can('tickets.reassign'))&&!in_array($ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),
            'canChangeStatus'=>Auth::can('tickets.change_status')&&((int)($ticket['assigned_to']??0)===(int)Auth::id()||Auth::can('tickets.reassign')),
            'canClassify'=>Auth::can('tickets.classify')&&!in_array((string)$ticket['status'],['RESOLVED','CLOSED','CANCELLED'],true),
            'statusLabels'=>self::STATUS_LABELS,
            'priorityLabels'=>self::PRIORITY_LABELS,
            'canEditLocation'=>$canEditLocation,
            'locationParks'=>$locationParks,
            'locationAreas'=>$locationAreas,
            'activities'=>$activities,
            'requesterActivities'=>$requesterActivities,
            'activityTypes'=>$activityTypes,
            'activityResults'=>$activityResults,
            'activityResponsibleUsers'=>$activityResponsibleUsers,
            'activityProviderUsers'=>$activityProviderUsers,
            'activityParks'=>$activityParks,
            'canCreateActivities'=>$canCreateActivities,
            'canManageActivities'=>$canManageActivities,
            'canCancelActivities'=>$canCancelActivities,
        ]);
'@

if ($content.Contains($oldWorkspace)) {
    $content = $content.Replace($oldWorkspace, $newWorkspace)
} elseif (-not $content.Contains("'requesterActivities'=>`$requesterActivities")) {
    throw 'No se encontro el bloque esperado de TicketController::show().'
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $utf8NoBom)

Write-Host '[OK] TicketController::show() actualizado para Task 6.'
Write-Host '[OK] No se modifico la base de datos.'
