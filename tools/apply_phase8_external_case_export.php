<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$controllerPath=$root.'/app/Controllers/ExternalCaseHistoryController.php';
$routerPath=$root.'/public/index.php';
$viewPath=$root.'/app/Views/tickets/index.php';

foreach([$routerPath,$viewPath] as $path){
    if(!is_file($path)){
        fwrite(STDERR,'[ERROR] No existe '.$path.PHP_EOL);
        exit(1);
    }
}
if(is_file($controllerPath)){
    fwrite(STDERR,'[ERROR] Ya existe ExternalCaseHistoryController.php'.PHP_EOL);
    exit(1);
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

$controller=<<<'PHP'
<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database};
use App\Services\XlsxExportService;

final class ExternalCaseHistoryController
{
    public function export(): void
    {
        Auth::requireLogin();
        $user=Auth::user();
        if((($user['access_type']??'INTERNAL')!=='EXTERNAL')){
            header('Location: '.APP_BASE_URL.'/mis-tickets');
            exit;
        }

        $pdo=Database::pdo();
        $s=$pdo->prepare("SELECT t.ticket_number,t.subject,COALESCE(c.name,'') category_name,COALESCE(p.name,'') park_name,eta.granted_at external_granted_at,eta.revoked_at external_revoked_at FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id WHERE eta.user_id=? AND t.case_type='SPECIAL' AND t.deleted_at IS NULL AND (eta.revoked_at IS NOT NULL OR t.visibility_mode='EXTERNAL_ALLOWED') ORDER BY COALESCE(eta.revoked_at,eta.granted_at) DESC,t.created_at DESC");
        $s->execute([Auth::id()]);

        $data=[];
        foreach($s->fetchAll() as $row){
            $isClosed=!empty($row['external_revoked_at']);
            $data[]=[
                (string)$row['ticket_number'],
                (string)$row['subject'],
                (string)$row['category_name'],
                (string)$row['park_name'],
                $isClosed?'Finalizada':'Activa',
                $this->displayDate((string)$row['external_granted_at']),
                $isClosed?$this->displayDate((string)$row['external_revoked_at']):'',
            ];
        }

        XlsxExportService::download('helpdesk_mis_casos_'.date('Ymd_His').'.xlsx',[[
            'name'=>'Mis casos',
            'title'=>'Helpdesk Carrousel · Mis casos',
            'subtitle'=>'Historial de participaciones compartidas contigo',
            'headers'=>['Ticket','Asunto','Categoría','Ubicación','Participación','Asignado','Finalizado'],
            'rows'=>$data,
        ]]);
    }

    private function displayDate(string $value): string
    {
        $timestamp=$value!==''?strtotime($value):false;
        return $timestamp?date('d/m/Y H:i',$timestamp):'';
    }
}
PHP;

$router=$normalize((string)file_get_contents($routerPath));
$view=$normalize((string)file_get_contents($viewPath));

$importOld='ExternalReportController,TicketClassificationController,TicketLocationController,TicketActivityController,ProviderRatingController};';
$importNew='ExternalReportController,TicketClassificationController,TicketLocationController,TicketActivityController,ProviderRatingController,ExternalCaseHistoryController};';
$router=$replaceOnce($router,$importOld,$importNew,'Import ExternalCaseHistoryController');

$routeOld="    ['GET','/mis-tickets',[TicketController::class,'index']],";
$routeNew="    ['GET','/mis-tickets',[TicketController::class,'index']],\n    ['GET','/mis-tickets/exportar',[ExternalCaseHistoryController::class,'export']],";
$router=$replaceOnce($router,$routeOld,$routeNew,'Ruta Excel externo');

$headingOld=<<<'PHP'
<div class="page-heading tickets-heading"><div><h1 class="page-title"><?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isExternal?'Consulta tus casos activos y el historial de participaciones finalizadas.':'Revisa el estado de tus casos y abre solo el que necesites continuar.' ?></p></div><?php if(!$isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
PHP;
$headingNew=<<<'PHP'
<div class="page-heading tickets-heading"><div><h1 class="page-title"><?= htmlspecialchars($isExternal?'Mis casos':'Mis solicitudes') ?></h1><p class="page-subtitle"><?= $isExternal?'Consulta tus casos activos y el historial de participaciones finalizadas.':'Revisa el estado de tus casos y abre solo el que necesites continuar.' ?></p></div><?php if($isExternal): ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/mis-tickets/exportar">Descargar Excel</a></div><?php else: ?><div class="tickets-main-actions"><a class="btn btn-primary" href="<?= APP_BASE_URL ?>/crear-ticket">+ Nueva solicitud</a></div><?php endif; ?></div>
PHP;
$view=$replaceOnce($view,$headingOld,$headingNew,'Boton Descargar Excel externo');

if(file_put_contents($controllerPath,$normalize($controller))===false||file_put_contents($routerPath,$router)===false||file_put_contents($viewPath,$view)===false){
    fwrite(STDERR,'[ERROR] No fue posible escribir los archivos.'.PHP_EOL);
    exit(1);
}

foreach([$controllerPath,$routerPath,$viewPath] as $path){
    passthru('"'.PHP_BINARY.'" -l "'.$path.'"',$code);
    if($code!==0)exit($code);
}

echo '[OK] Excel seguro para proveedor externo aplicado. No se modifico la BD.'.PHP_EOL;
