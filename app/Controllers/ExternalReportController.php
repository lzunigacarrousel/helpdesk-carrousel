<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database,View};
use App\Services\XlsxExportService;
use PDO;

final class ExternalReportController
{
    public function index(): void
    {
        $this->requireAccess();$pdo=Database::pdo();$rows=$this->history($pdo);
        View::render('management/external_report',['user'=>Auth::user(),'rows'=>$rows,'summary'=>$this->summary($rows)]);
    }

    public function export(): void
    {
        $this->requireAccess();$pdo=Database::pdo();$rows=$this->history($pdo);$summary=$this->summary($rows);$data=[];
        foreach($rows as $r){$data[]=[
            $r['organization'],$r['contact'],$r['email'],$r['ticket_number'],$r['subject'],$r['granted_at'],$r['granted_by'],$r['revoked_at']?:'Activo',$r['revoked_by']?:'',
            $r['duration_minutes'],$r['can_comment']?'Sí':'No',$r['can_upload']?'Sí':'No',(int)$r['responses'],(int)$r['attachments'],$r['ticket_status']
        ];}
        Audit::log('EXTERNAL_REPORT_EXPORTED_XLSX','report',null,null,null,['rows'=>count($data)]);
        XlsxExportService::download('helpdesk_proveedores_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Proveedores','subtitle'=>'Historial de participación externa','headers'=>['Indicador','Valor'],'rows'=>[
                ['Participaciones',count($rows)],['Activas',(int)$summary['active']],['Finalizadas',(int)$summary['closed']],['Proveedores distintos',(int)$summary['providers']],['Respuestas externas',(int)$summary['responses']],['Archivos externos',(int)$summary['attachments']],['Generado',date('d/m/Y H:i:s')]
            ]],
            ['name'=>'Participaciones','title'=>'Helpdesk Carrousel · Historial de proveedores','subtitle'=>'Cada asignación y revocación se conserva como un ciclo independiente','headers'=>['Proveedor','Contacto','Correo','Ticket','Asunto','Asignado','Asignado por','Revocado','Revocado por','Duración (min)','Puede responder','Puede adjuntar','Respuestas','Archivos','Estado ticket'],'rows'=>$data]
        ]);
    }

    private function history(PDO $pdo): array
    {
        $users=[];
        foreach($pdo->query("SELECT u.id,u.full_name,u.email,COALESCE(ep.organization_name,u.full_name) organization_name FROM users u LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE u.access_type='EXTERNAL' AND u.deleted_at IS NULL")->fetchAll() as $u)$users[(int)$u['id']]=$u;
        $events=$pdo->query("SELECT te.id,te.ticket_id,te.event_type,te.actor_user_id,te.old_value,te.new_value,te.metadata_json,te.created_at,t.ticket_number,t.subject,t.status ticket_status,actor.full_name actor_name FROM ticket_events te JOIN tickets t ON t.id=te.ticket_id LEFT JOIN users actor ON actor.id=te.actor_user_id WHERE te.event_type IN('EXTERNAL_GRANTED','EXTERNAL_REVOKED') AND t.deleted_at IS NULL ORDER BY te.created_at,te.id")->fetchAll();
        $rows=[];$open=[];
        foreach($events as $e){
            $payload=$this->json((string)($e['event_type']==='EXTERNAL_GRANTED'?$e['new_value']:$e['old_value']));$uid=(int)($payload['external_user_id']??0);if($uid<=0||!isset($users[$uid]))continue;$key=(int)$e['ticket_id'].':'.$uid;
            if($e['event_type']==='EXTERNAL_GRANTED'){
                $meta=$this->json((string)$e['metadata_json'];); // placeholder
            }
        }
        return $rows;
    }

    private function summary(array $rows): array
    {
        $providers=[];$s=['active'=>0,'closed'=>0,'providers'=>0,'responses'=>0,'attachments'=>0];
        foreach($rows as $r){$providers[$r['email']]=true;$r['revoked_at']?$s['closed']++:$s['active']++;$s['responses']+=(int)$r['responses'];$s['attachments']+=(int)$r['attachments'];}
        $s['providers']=count($providers);return $s;
    }

    private function json(string $value): array{$x=json_decode($value,true);return is_array($x)?$x:[];}

    private function requireAccess(): void
    {
        Auth::requireLogin();if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('external.manage')&&!Auth::can('reports.view')){header('Location: '.APP_BASE_URL.'/dashboard');exit;}
    }
}
