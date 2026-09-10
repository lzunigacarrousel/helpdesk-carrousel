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
        $this->requireAccess();
        $pdo=Database::pdo();
        $rows=$this->history($pdo);
        View::render('management/external_report',[
            'user'=>Auth::user(),
            'rows'=>$rows,
            'summary'=>$this->summary($rows),
        ]);
    }

    public function export(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();$rows=$this->history($pdo);$summary=$this->summary($rows);$data=[];
        foreach($rows as $r){
            $data[]=[
                $r['organization'],$r['contact'],$r['email'],$r['ticket_number'],$r['subject'],
                $this->displayDate($r['granted_at']),$r['granted_by'],
                $r['revoked_at']?$this->displayDate($r['revoked_at']):'Activo',$r['revoked_by']?:'',
                (int)$r['duration_minutes'],$r['can_comment']?'Sí':'No',$r['can_upload']?'Sí':'No',
                (int)$r['responses'],(int)$r['attachments'],$r['ticket_status']
            ];
        }
        Audit::log('EXTERNAL_REPORT_EXPORTED_XLSX','report',null,null,null,['rows'=>count($data)]);
        XlsxExportService::download('helpdesk_proveedores_'.date('Ymd_His').'.xlsx',[
            ['name'=>'Resumen','title'=>'Helpdesk Carrousel · Proveedores','subtitle'=>'Historial de participación externa','headers'=>['Indicador','Valor'],'rows'=>[
                ['Participaciones',count($rows)],
                ['Activas',(int)$summary['active']],
                ['Finalizadas',(int)$summary['closed']],
                ['Proveedores distintos',(int)$summary['providers']],
                ['Respuestas externas',(int)$summary['responses']],
                ['Archivos externos',(int)$summary['attachments']],
                ['Generado',date('d/m/Y H:i:s')]
            ]],
            ['name'=>'Participaciones','title'=>'Helpdesk Carrousel · Historial de proveedores','subtitle'=>'Cada asignación y revocación se conserva como un ciclo independiente','headers'=>[
                'Proveedor','Contacto','Correo','Ticket','Asunto','Asignado','Asignado por','Revocado','Revocado por','Duración (min)','Puede responder','Puede adjuntar','Respuestas','Archivos','Estado ticket'
            ],'rows'=>$data]
        ]);
    }

    private function history(PDO $pdo): array
    {
        $users=[];
        foreach($pdo->query("SELECT u.id,u.full_name,u.email,COALESCE(ep.organization_name,u.full_name) organization_name FROM users u LEFT JOIN external_profiles ep ON ep.user_id=u.id WHERE u.access_type='EXTERNAL'")->fetchAll() as $u){
            $users[(int)$u['id']]=$u;
        }

        $events=$pdo->query("SELECT te.id,te.ticket_id,te.event_type,te.old_value,te.new_value,te.metadata_json,te.created_at,
            t.ticket_number,t.subject,t.status ticket_status,actor.full_name actor_name
            FROM ticket_events te
            JOIN tickets t ON t.id=te.ticket_id
            LEFT JOIN users actor ON actor.id=te.actor_user_id
            WHERE te.event_type IN('EXTERNAL_GRANTED','EXTERNAL_REVOKED') AND t.deleted_at IS NULL
            ORDER BY te.created_at,te.id")->fetchAll();

        $rows=[];$open=[];
        foreach($events as $e){
            $payload=$this->json((string)($e['event_type']==='EXTERNAL_GRANTED'?$e['new_value']:$e['old_value']));
            $uid=(int)($payload['external_user_id']??0);
            if($uid<=0||!isset($users[$uid]))continue;
            $ticketId=(int)$e['ticket_id'];$key=$ticketId.':'.$uid;

            if($e['event_type']==='EXTERNAL_GRANTED'){
                if(isset($open[$key])){
                    $i=$open[$key];$rows[$i]['revoked_at']=(string)$e['created_at'];$rows[$i]['revoked_by']='Nueva asignación';unset($open[$key]);
                }
                $meta=$this->json((string)($e['metadata_json']??''));$u=$users[$uid];
                $rows[]=[
                    'ticket_id'=>$ticketId,'user_id'=>$uid,
                    'organization'=>(string)$u['organization_name'],'contact'=>(string)$u['full_name'],'email'=>(string)$u['email'],
                    'ticket_number'=>(string)$e['ticket_number'],'subject'=>(string)$e['subject'],'ticket_status'=>(string)$e['ticket_status'],
                    'granted_at'=>(string)$e['created_at'],'granted_by'=>(string)($e['actor_name']?:'Sistema'),
                    'revoked_at'=>null,'revoked_by'=>null,
                    'can_comment'=>(bool)($meta['can_comment']??true),'can_upload'=>(bool)($meta['can_upload']??true),
                    'duration_minutes'=>0,'responses'=>0,'attachments'=>0,
                ];
                $open[$key]=array_key_last($rows);
            }elseif(isset($open[$key])){
                $i=$open[$key];$rows[$i]['revoked_at']=(string)$e['created_at'];$rows[$i]['revoked_by']=(string)($e['actor_name']?:'Sistema');unset($open[$key]);
            }
        }

        foreach($rows as &$row){
            $start=strtotime((string)$row['granted_at'])?:time();$end=$row['revoked_at']?(strtotime((string)$row['revoked_at'])?:time()):time();
            $row['duration_minutes']=max(0,(int)round(($end-$start)/60));
        }
        unset($row);

        $comments=$pdo->query("SELECT tc.ticket_id,tc.author_user_id,tc.created_at FROM ticket_comments tc JOIN users u ON u.id=tc.author_user_id WHERE tc.deleted_at IS NULL AND tc.visibility='EXTERNAL' AND u.access_type='EXTERNAL'")->fetchAll();
        foreach($comments as $comment){$this->countWithinCycle($rows,(int)$comment['ticket_id'],(int)$comment['author_user_id'],(string)$comment['created_at'],'responses');}
        $attachments=$pdo->query("SELECT ta.ticket_id,ta.uploaded_by_user_id,ta.created_at FROM ticket_attachments ta JOIN users u ON u.id=ta.uploaded_by_user_id WHERE ta.visibility='EXTERNAL' AND u.access_type='EXTERNAL'")->fetchAll();
        foreach($attachments as $file){$this->countWithinCycle($rows,(int)$file['ticket_id'],(int)$file['uploaded_by_user_id'],(string)$file['created_at'],'attachments');}

        usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['granted_at'],(string)$a['granted_at']));
        return $rows;
    }

    private function countWithinCycle(array &$rows,int $ticketId,int $userId,string $createdAt,string $field): void
    {
        $at=strtotime($createdAt);if(!$at)return;
        foreach($rows as &$row){
            if((int)$row['ticket_id']!==$ticketId||(int)$row['user_id']!==$userId)continue;
            $from=strtotime((string)$row['granted_at'])?:0;$to=$row['revoked_at']?(strtotime((string)$row['revoked_at'])?:PHP_INT_MAX):PHP_INT_MAX;
            if($at>=$from&&$at<=$to){$row[$field]=(int)$row[$field]+1;break;}
        }
        unset($row);
    }

    private function summary(array $rows): array
    {
        $providers=[];$s=['active'=>0,'closed'=>0,'providers'=>0,'responses'=>0,'attachments'=>0];
        foreach($rows as $r){$providers[$r['email']]=true;$r['revoked_at']?$s['closed']++:$s['active']++;$s['responses']+=(int)$r['responses'];$s['attachments']+=(int)$r['attachments'];}
        $s['providers']=count($providers);return $s;
    }

    private function json(string $value): array{$x=json_decode($value,true);return is_array($x)?$x:[];}
    private function displayDate(?string $value): string{$ts=$value?strtotime($value):false;return $ts?date('d/m/Y H:i',$ts):'';}

    private function requireAccess(): void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('external.manage')&&!Auth::can('reports.view')){header('Location: '.APP_BASE_URL.'/dashboard');exit;}
    }
}
