<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fails=0;
function forensicText(string $path):string{$v=@file_get_contents($path);return is_string($v)?$v:'';}
function forensicOk(bool $ok,string $msg):void{global $fails;echo ($ok?'[OK] ':'[FALLO] ').$msg.PHP_EOL;if(!$ok)$fails++;}

$users=forensicText($root.'/app/Views/admin/users.php');
$externals=forensicText($root.'/app/Views/admin/externals.php');
$catalogs=forensicText($root.'/app/Views/admin/catalogs.php');
$team=forensicText($root.'/app/Views/management/support_team.php');
$mail=forensicText($root.'/app/Views/admin/mail.php');
$audit=forensicText($root.'/app/Views/admin/audit.php');
$css=forensicText($root.'/public/assets/css/admin-dialog.css');
$js=forensicText($root.'/public/assets/js/admin-dialog.js');
$end=forensicText($root.'/app/Views/shared/app_end.php');

forensicOk(str_contains($end,'admin-dialog.css')&&str_contains($end,'admin-dialog.js'),'Shell carga componente administrativo compartido');
forensicOk(str_contains($css,'.admin-record-dialog')&&str_contains($css,'.admin-dialog-host'),'Existe geometría modal y host de tabla sin altura');
forensicOk(str_contains($css,'::backdrop')&&str_contains($css,'@media(max-width:760px)'),'Diálogo contempla backdrop y móvil');
forensicOk(str_contains($js,'showModal()')&&str_contains($js,'data-admin-dialog-open'),'JS abre diálogos de forma declarativa');
forensicOk(str_contains($js,"addEventListener('close'")&&str_contains($js,"aria-expanded"),'JS restaura estado accesible al cerrar');

foreach(['Usuarios'=>$users,'Proveedores'=>$externals] as $name=>$view){
    forensicOk(str_contains($view,'data-admin-dialog-open'),$name.' abre edición desde la fila');
    forensicOk(str_contains($view,'data-admin-dialog'),$name.' usa diálogo compartido');
    forensicOk(str_contains($view,'admin-dialog-host'),$name.' conserva el diálogo fuera de la geometría visible de la tabla');
}
forensicOk(str_contains($catalogs,'admin-record-dialog')&&str_contains($catalogs,'data-admin-dialog'),'Catálogos adopta el mismo componente compartido');
forensicOk(!str_contains($users,'data-user-edit-row')&&!str_contains($externals,'data-external-edit-row')&&!str_contains($catalogs,'<tr class="catalog-edit'),'No quedan editores inline conocidos en tablas administrativas');

forensicOk(str_contains($users,'/admin/users/assign')&&str_contains($users,'/admin/users/convertir-externo')&&str_contains($users,'/admin/users/delete'),'Usuarios conserva actualizar, convertir y retirar acceso');
forensicOk(str_contains($users,'/admin/users/backfill-park-tickets'),'Usuarios conserva corrección histórica de parque');
forensicOk(str_contains($externals,'/admin/externos/actualizar')&&str_contains($externals,'/admin/externos/plantilla'),'Proveedores conserva perfil y plantilla');
forensicOk(str_contains($externals,'/admin/externos/convertir-interno')&&str_contains($externals,'/admin/externos/desactivar'),'Proveedores conserva conversión y desactivación');
forensicOk(str_contains($externals,'/admin/externos/asignar')&&str_contains($externals,'/admin/externos/revocar'),'Proveedores conserva compartir y revocar casos');

forensicOk(!str_contains($team,'Editar'),'Equipo de soporte no fuerza diálogo: sus acciones son agregar/retirar');
forensicOk(!str_contains($mail,'Editar'),'Correo no fuerza diálogo: administra pruebas/reintentos');
forensicOk(!str_contains($audit,'Editar'),'Auditoría permanece de solo consulta');
forensicOk(substr_count($users,'name="_csrf"')>=4,'Usuarios mantiene CSRF en operaciones administrativas');
forensicOk(substr_count($externals,'name="_csrf"')>=5,'Proveedores mantiene CSRF en operaciones administrativas');
forensicOk(substr_count($catalogs,'name="_csrf"')>=4,'Catálogos mantiene CSRF en operaciones administrativas');

if($fails){fwrite(STDERR,PHP_EOL.'[ERROR] '.$fails.' hallazgo(s) forense(s) pendientes.'.PHP_EOL);exit(1);}
echo PHP_EOL.'[OK] Auditoría forense de edición administrativa completada.'.PHP_EOL;
