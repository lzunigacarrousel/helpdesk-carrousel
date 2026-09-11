<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$ok=true;

function check(bool $cond,string $msg):void{
    global $ok;
    echo ($cond?'[OK] ':'[FALLO] ').$msg.PHP_EOL;
    $ok=$ok&&$cond;
}
function loadFile(string $root,string $rel):string{
    $p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);
    return is_file($p)?(string)file_get_contents($p):'';
}

$router=loadFile($root,'public/index.php');
$admin=loadFile($root,'app/Controllers/AdminController.php');
$support=loadFile($root,'app/Controllers/SupportTeamController.php');
$supportView=loadFile($root,'app/Views/management/support_team.php');
$topics=loadFile($root,'app/Services/RequesterTopicService.php');
$css=loadFile($root,'public/assets/css/layout-density-v25.css');
$install=loadFile($root,'database/INSTALAR.sql');

check(str_contains($router,"['GET','/knowledge/create',[KnowledgeController::class,'form']]"),'GET /knowledge/create abre el formulario');
check(str_contains($router,"['GET','/knowledge/gestion',[KnowledgeController::class,'index']]"),'GET /knowledge/gestion lleva a Knowledge');
check(str_contains($router,"['GET','/problems/create',[ProblemController::class,'form']]"),'GET /problems/create abre el formulario');

check(str_contains($admin,"syncSupportMembership(\$pdo,\$uid,\$data['role_code'],\$data['status'])"),'Usuarios sincroniza equipo con perfil y estado');
check(str_contains($admin,"\$status==='ACTIVE'"),'Solo usuarios activos forman parte automática de soporte');
check(str_contains($support,'private function pendingSupport'),'Equipo identifica perfiles de soporte pendientes');
check(str_contains($supportView,'Pendientes de activar:'),'Equipo explica por qué un perfil pendiente no aparece');
check(str_contains($supportView,'Administrador, Semiadministrador y Técnico'),'Equipo explica perfiles operativos elegibles');

check(str_contains($install,"'NETWORK_OUTAGE','No tengo Internet'"),'Selector usa lenguaje humano desde el catálogo');
check(str_contains($topics,'No puedo ingresar / contraseña')||str_contains($install,"'ACCESS_PASSWORD','No puedo ingresar / contraseña'"),'Accesos usan lenguaje comprensible');
check(str_contains($topics,'Cuéntanos qué necesitas'),'Ayudas del selector conservan español natural');

check(str_contains($css,'AJUSTES FINALES 2026-09-11 · KPI dashboard'),'Existe ajuste final de KPI');
check(str_contains($css,'repeat(8,minmax(0,1fr))'),'Dashboard usa 8 KPI en escritorio amplio');

check(str_contains($install,"'NETWORK','Internet y conexión'"),'Instalación nueva usa Internet y conexión');
check(str_contains($install,"'ACCESS','Acceso a sistemas'"),'Instalación nueva usa Acceso a sistemas');
check(str_contains($install,"'POS','Facturación y POS'"),'Instalación nueva usa Facturación y POS');
check(str_contains($install,"'PAYOUT','Payout / Kiddies'"),'Instalación nueva usa Payout / Kiddies');
check(str_contains($install,"'OTHER','Otro'"),'Otro queda al final del catálogo');

exit($ok?0:1);
