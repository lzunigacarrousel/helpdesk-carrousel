<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=0;

function ok(bool $condition,string $message):void
{
    global $errors;
    echo ($condition?'[OK] ':'[FALLO] ').$message.PHP_EOL;
    if(!$condition)$errors++;
}

$auth=(string)file_get_contents($root.'/app/Core/Auth.php');
$scope=(string)file_get_contents($root.'/app/Services/ScopeService.php');
$ticket=(string)file_get_contents($root.'/app/Controllers/TicketController.php');
$conversation=(string)file_get_contents($root.'/app/Controllers/ConversationController.php');
$ticketView=(string)file_get_contents($root.'/app/Controllers/TicketViewController.php');
$agenda=(string)file_get_contents($root.'/app/Controllers/AgendaController.php');
$management=(string)file_get_contents($root.'/app/Controllers/ManagementController.php');
$shell=(string)file_get_contents($root.'/app/Views/shared/app_start.php');
$externalView=(string)file_get_contents($root.'/app/Views/tickets/show_external.php');
$install=(string)file_get_contents($root.'/database/INSTALAR.sql');
$sql=(string)file_get_contents($root.'/database/VERIFICAR_FASE12_SEGURIDAD_20260919.sql');

ok(str_contains($auth,"in_array(self::role(), ['SEMIADMIN', 'TECHNICIAN'], true)"),'Solo Semiadmin/Técnico pueden ser operadores de soporte no Admin');
ok(str_contains($auth,"in_array(self::role(), ['MANAGEMENT', 'SUPERVISOR'], true)"),'Gerencia/Supervisor se reconocen como perfiles de consulta');
ok(str_contains($auth,"(self::\$user['access_type'] ?? 'INTERNAL') !== 'EXTERNAL'"),'Colaborador externo nunca es management viewer');
ok(str_contains($auth,"CASE WHEN uo.effect='DENY' THEN 0"),'Override individual solo puede retirar capacidad');

ok(str_contains($scope,"if((\$user['access_type']??'INTERNAL')==='EXTERNAL')return['0=1',[]]"),'Scope interno bloquea externos');
ok(str_contains($scope,"in_array(\$role,['ADMIN','SEMIADMIN','MANAGEMENT'],true)"),'Admin/Semiadmin/Gerencia usan alcance global');
ok(str_contains($scope,"if(\$role==='SUPERVISOR')"),'Supervisor tiene rama de alcance propia');
ok(str_contains($scope,"park_id IN (SELECT id FROM parks WHERE region_id=?)"),'Supervisor puede quedar limitado por región');
ok(str_contains($scope,"{$alias}.park_id=?")||str_contains($scope,'{$alias}.park_id=?'),'Supervisor puede quedar limitado por parque');
ok(str_contains($scope,"{$alias}.area_id=?")||str_contains($scope,'{$alias}.area_id=?'),'Supervisor puede quedar limitado por área');
ok(str_contains($scope,"if(\$role==='TECHNICIAN')"),'Técnico usa scopes de soporte');
ok(str_contains($scope,"scope_type']??'')==='GLOBAL'"),'Técnico reconoce scope global');
ok(str_contains($scope,"requester_user_id=? OR LOWER({\$alias}.requester_email)=?")||str_contains($scope,'requester_user_id=? OR LOWER({$alias}.requester_email)=?'),'Solicitante queda limitado a información propia');

ok(str_contains($ticket,"Auth::requirePermission('tickets.view_queue')"),'Cola exige permiso explícito');
ok(str_contains($ticket,"Auth::requirePermission('tickets.claim')"),'Tomar caso exige permiso');
ok(str_contains($ticket,"Auth::requirePermission('tickets.reassign')"),'Reasignar exige permiso');
ok(str_contains($ticket,"Auth::can('tickets.change_status')"),'Cambio de estado depende de capacidad');
ok(str_contains($ticket,"(new ScopeService())->ticketConstraint('t')"),'Cola y visibilidad usan ScopeService');
ok(str_contains($ticket,"Ese caso no está disponible dentro de tu alcance."),'Claim bloquea casos fuera de scope');
ok(str_contains($ticket,"r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')"),'Solo perfiles de soporte pueden ser responsables');
ok(str_contains($ticket,"if(!\$isSupport)"),'Vista no soporte filtra eventos internos');
ok(str_contains($ticket,"requesterEventTypes=['CREATED','STATUS_CHANGED','RESOLVED','CLOSED','REOPENED']"),'Solicitante/consulta solo recibe eventos públicos de ciclo');
ok(str_contains($ticket,"'canCreateActivities'=>\$canCreateActivities"),'Acciones de actividades dependen de capacidad');

ok(str_contains($conversation,"if(\$isExternal)\$visibility='EXTERNAL'"),'Proveedor no puede escribir comentario público/interno');
ok(str_contains($conversation,"elseif(\$isSupport&&\$requestedVisibility==='INTERNAL')\$visibility='INTERNAL'"),'Solo soporte puede seleccionar conversación interna');
ok(str_contains($conversation,"if(\$visibility==='INTERNAL'&&!\$context['is_support'])"),'Adjunto interno se bloquea fuera de soporte');
ok(str_contains($conversation,"if(\$visibility==='EXTERNAL'&&!\$context['is_support']&&!\$context['is_external'])"),'Adjunto de proveedor no se expone al solicitante');
ok(str_contains($conversation,"['email'=>false,'in_app'=>true]"),'Nota interna nunca dispara correo');
ok(str_contains($conversation,"external_ticket_access WHERE ticket_id=? AND user_id=? AND revoked_at IS NULL"),'Proveedor necesita acceso explícito vigente');

ok(str_contains($ticketView,"eta.revoked_at IS NULL"),'Vista externa exige acceso vigente');
ok(str_contains($ticketView,"t.case_type='SPECIAL'"),'Vista externa exige caso especial');
ok(str_contains($ticketView,"t.visibility_mode='EXTERNAL_ALLOWED'"),'Vista externa exige colaboración habilitada');

ok(str_contains($agenda,"\$allowed=['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR']"),'Agenda excluye solicitante y externo');
ok(str_contains($agenda,"\$canProgram=in_array(\$role,['ADMIN','SEMIADMIN','TECHNICIAN'],true)"),'Gerencia/Supervisor no programan actividades');

ok(str_contains($management,'$this->requireManagement();'),'Dashboard de gestión aplica guard');
ok(str_contains($management,'$this->requireReports();'),'Informes aplican guard');
ok(str_contains($management,"new TicketReportFilterService()"),'Gestión usa filtros con ScopeService');

ok(str_contains($shell,"<?php if(\$isSupport): ?>"),'Menú operativo solo aparece a soporte');
ok(str_contains($shell,"<?php elseif(\$isExternal): ?>"),'Colaborador recibe navegación separada');
ok(str_contains($shell,"<?php elseif(Auth::isManagementViewer()): ?>"),'Gerencia/Supervisor no reciben menú de solicitudes operativo');
ok(str_contains($shell,'<?php if(!$isExternal): ?><a class="btn btn-outline-secondary btn-sm portal-btn"'),'Proveedor no ve enlace al Portal');

ok(!str_contains($externalView,'Nota interna'),'Vista externa no rotula ni expone notas internas');
ok(!str_contains($externalView,'Portal de Sistemas'),'Vista externa no expone Portal de Sistemas');

ok(str_contains($install,"WHERE r.code='MANAGEMENT' AND p.code IN("),'Instalación define matriz de Gerencia');
ok(str_contains($install,"WHERE r.code='SUPERVISOR' AND p.code IN("),'Instalación define matriz de Supervisor');
ok(str_contains($install,"WHERE r.code='REQUESTER' AND p.code IN('tickets.view_own','tickets.comment_public','knowledge.view')"),'Solicitante tiene matriz mínima');
ok(str_contains($install,"WHERE r.code='EXTERNAL' AND p.code IN('tickets.comment_public')"),'Externo tiene permiso mínimo');

ok(str_contains($sql,'MANAGEMENT_OPERACION_PROHIBIDA'),'SQL valida que Gerencia no opere');
ok(str_contains($sql,'SUPERVISOR_OPERACION_PROHIBIDA'),'SQL valida que Supervisor no opere');
ok(str_contains($sql,'REQUESTER_PERMISOS_NO_CANONICOS'),'SQL valida Solicitante');
ok(str_contains($sql,'EXTERNAL_PERMISOS_NO_CANONICOS'),'SQL valida Externo');
ok(str_contains($sql,'TECHNICIAN_ADMIN_PROHIBIDO'),'SQL valida Técnico');
ok(str_contains($sql,'SUPERVISORES_SCOPE_VACIO'),'SQL valida scope de Supervisor');
ok(str_contains($sql,"THEN 'PASS'"),'SQL emite PASS de seguridad');

if($errors){
    fwrite(STDERR,"[ERROR] {$errors} validación(es) fallaron.".PHP_EOL);
    exit(1);
}
echo '[OK] Seguridad, perfiles, permisos y scopes Fase 12 preparados.'.PHP_EOL;
