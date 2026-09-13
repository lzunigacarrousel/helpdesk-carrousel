<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\NotificationService;
use PDO;

final class WorkReportController
{
    private const REPORT_TYPES=['PROGRESS','INFO_REQUEST','WORK_COMPLETED'];
    private const MAX_FILE_SIZE=10485760;
    private const ALLOWED_MIME=[
        'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','text/plain'=>'txt','text/csv'=>'csv',
        'application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
        'application/vnd.ms-excel'=>'xls','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',
    ];

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        if($ticketId<=0)$this->fail(0,'No encontramos el caso.');

        $user=Auth::user();
        $isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
        if(!$isExternal)$this->fail($ticketId,'Este informe está habilitado para el proveedor asignado al caso.','info');

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.status,t.case_type,t.visibility_mode,eta.can_comment,eta.can_upload
            FROM external_ticket_access eta
            JOIN tickets t ON t.id=eta.ticket_id
            WHERE eta.ticket_id=? AND eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL
            LIMIT 1");
        $q->execute([$ticketId,(int)Auth::id()]);
        $ticket=$q->fetch();
        if(!$ticket||$ticket['case_type']!=='SPECIAL'||$ticket['visibility_mode']!=='EXTERNAL_ALLOWED')$this->fail($ticketId,'Este caso no está habilitado para tu cuenta.','info');
        if(!(bool)$ticket['can_comment'])$this->fail($ticketId,'Tu acceso a este caso es solo de consulta.','info');
        if(in_array((string)$ticket['status'],['CLOSED','CANCELLED'],true))$this->fail($ticketId,'Este caso ya está finalizado.','info');

        $reportType=strtoupper(trim(Http::post('report_type')));
        if(!in_array($reportType,self::REPORT_TYPES,true))$this->fail($ticketId,'Selecciona el estado actual del trabajo.');

        $progress=(int)Http::post('progress_percent');
        if($progress<0||$progress>100)$this->fail($ticketId,'El porcentaje de avance debe estar entre 0 y 100.');
        if($reportType==='WORK_COMPLETED'&&$progress!==100)$this->fail($ticketId,'Una atención terminada debe reportarse con 100% de avance.');

        $fields=[
            'diagnosis'=>'Diagnóstico técnico',
            'root_cause'=>'Causa raíz',
            'actions_performed'=>'Acciones realizadas',
            'parts_materials'=>'Repuestos / materiales',
            'configuration_changes'=>'Cambios de configuración',
            'tests_performed'=>'Pruebas realizadas',
            'result_summary'=>'Resultado obtenido',
            'pending_items'=>'Pendientes',
            'preventive_recommendation'=>'Recomendación preventiva',
            'provider_reference'=>'Referencia del proveedor',
        ];
        $data=[];
        foreach($fields as $name=>$label){
            $value=trim(Http::post($name));
            if(mb_strlen($value)<2)$this->fail($ticketId,$label.' es obligatorio. Si no aplica, escribe “No aplica” y explica brevemente por qué.');
            if(mb_strlen($value)>8000)$this->fail($ticketId,$label.' es demasiado extenso.');
            $data[$name]=$value;
        }

        $timeSpent=(int)Http::post('time_spent_minutes');
        if($timeSpent<=0||$timeSpent>525600)$this->fail($ticketId,'Indica el tiempo invertido en minutos.');

        $commitmentRaw=trim(Http::post('commitment_at'));
        $commitmentAt=null;
        if($commitmentRaw!==''){
            $ts=strtotime($commitmentRaw);
            if($ts===false)$this->fail($ticketId,'La fecha compromiso no es válida.');
            $commitmentAt=date('Y-m-d H:i:s',$ts);
        }elseif($reportType!=='WORK_COMPLETED'){
            $this->fail($ticketId,'Indica la fecha compromiso mientras la atención siga abierta.');
        }

        $readyForReview=isset($_POST['ready_for_review'])&&$_POST['ready_for_review']==='1';
        if($reportType==='WORK_COMPLETED'&&!$readyForReview)$this->fail($ticketId,'Confirma que la atención está terminada y lista para revisión de Carrousel.');
        if($reportType!=='WORK_COMPLETED')$readyForReview=false;

        $hasFile=isset($_FILES['attachment'])&&(int)($_FILES['attachment']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        if($hasFile&&!(bool)$ticket['can_upload'])$this->fail($ticketId,'Tu acceso no permite adjuntar evidencia en este caso.');

        $commentId=null;$attachmentId=null;$reportId=null;
        $summary=$this->commentSummary($reportType,$progress,$data,$timeSpent,$commitmentAt,$readyForReview);

        try{
            Database::transaction(function(PDO $pdo)use($ticketId,$user,$reportType,$progress,$data,$timeSpent,$commitmentAt,$readyForReview,$summary,$hasFile,&$commentId,&$attachmentId,&$reportId):void{
                $q=$pdo->prepare("INSERT INTO ticket_comments(ticket_id,author_user_id,author_name,author_email,visibility,body,created_at) VALUES(?,?,?,?, 'EXTERNAL', ?,NOW())");
                $q->execute([$ticketId,Auth::id(),$user['full_name']??null,$user['email']??null,$summary]);
                $commentId=(int)$pdo->lastInsertId();

                $q=$pdo->prepare("INSERT INTO ticket_work_reports(ticket_id,author_user_id,author_access_type,report_type,progress_percent,diagnosis,root_cause,actions_performed,parts_materials,configuration_changes,tests_performed,result_summary,pending_items,preventive_recommendation,provider_reference,time_spent_minutes,commitment_at,ready_for_review,comment_id,created_at,updated_at)
                    VALUES(?,?,'EXTERNAL',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
                $q->execute([
                    $ticketId,Auth::id(),$reportType,$progress,$data['diagnosis'],$data['root_cause'],$data['actions_performed'],$data['parts_materials'],
                    $data['configuration_changes'],$data['tests_performed'],$data['result_summary'],$data['pending_items'],$data['preventive_recommendation'],
                    $data['provider_reference'],$timeSpent,$commitmentAt,$readyForReview?1:0,$commentId,
                ]);
                $reportId=(int)$pdo->lastInsertId();

                if($hasFile)$attachmentId=$this->storeUpload($pdo,$ticketId,$commentId,$_FILES['attachment']);

                $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at)
                    VALUES(?,'WORK_REPORT_ADDED',?,'USER',?,?,NOW())")
                    ->execute([
                        $ticketId,Auth::id(),json_encode(['report_type'=>$reportType,'progress_percent'=>$progress,'ready_for_review'=>$readyForReview],JSON_UNESCAPED_UNICODE),
                        json_encode(['work_report_id'=>$reportId,'comment_id'=>$commentId,'attachment_id'=>$attachmentId],JSON_UNESCAPED_UNICODE),
                    ]);
                $pdo->prepare('UPDATE tickets SET updated_at=NOW() WHERE id=?')->execute([$ticketId]);
            });
        }catch(\Throwable $e){
            Logger::error($e);
            $this->fail($ticketId,'No pudimos guardar el informe técnico. Intenta nuevamente.');
        }

        Audit::log('WORK_REPORT_ADDED','ticket',$ticketId,null,[
            'work_report_id'=>$reportId,'report_type'=>$reportType,'progress_percent'=>$progress,'ready_for_review'=>$readyForReview,
            'comment_id'=>$commentId,'attachment_id'=>$attachmentId,'author_access_type'=>'EXTERNAL',
        ]);

        try{
            $notifications=new NotificationService();
            $audiences=['assignee','admins'];
            $notifications->publishTicket(
                $ticketId,'WORK_REPORT_ADDED','Informe técnico del proveedor · '.$ticket['ticket_number'],
                ($user['full_name']??'Proveedor').' documentó el trabajo del caso al '.$progress.'%.'.($readyForReview?' La atención quedó lista para revisión de Carrousel.':''),
                $audiences,APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion',
                ['work_report_id'=>$reportId,'report_type'=>$reportType,'progress_percent'=>$progress,'ready_for_review'=>$readyForReview,'has_attachment'=>$hasFile]
            );
        }catch(\Throwable $e){Logger::error($e);}

        Flash::set($readyForReview?'Informe técnico guardado y enviado a revisión de Carrousel.':'Informe técnico guardado correctamente.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#informe-tecnico');
        exit;
    }

    private function commentSummary(string $reportType,int $progress,array $data,int $timeSpent,?string $commitmentAt,bool $readyForReview): string
    {
        $label=match($reportType){'PROGRESS'=>'En atención','INFO_REQUEST'=>'Esperando información','WORK_COMPLETED'=>'Atención terminada',default=>$reportType};
        $lines=[
            'Informe técnico del proveedor',
            'Estado: '.$label.' · Avance: '.$progress.'%',
            'Diagnóstico: '.$data['diagnosis'],
            'Causa raíz: '.$data['root_cause'],
            'Acciones realizadas: '.$data['actions_performed'],
            'Resultado: '.$data['result_summary'],
            'Pendientes: '.$data['pending_items'],
            'Referencia: '.$data['provider_reference'].' · Tiempo invertido: '.$timeSpent.' min',
        ];
        if($commitmentAt)$lines[]='Fecha compromiso: '.date('d/m/Y H:i',strtotime($commitmentAt));
        if($readyForReview)$lines[]='Estado de entrega: listo para revisión de Carrousel.';
        return implode("\n",$lines);
    }

    private function storeUpload(PDO $pdo,int $ticketId,int $commentId,array $file): int
    {
        $error=(int)($file['error']??UPLOAD_ERR_NO_FILE);
        if($error!==UPLOAD_ERR_OK)throw new \RuntimeException('No pudimos recibir el archivo.');
        $size=(int)($file['size']??0);
        if($size<=0||$size>self::MAX_FILE_SIZE)throw new \RuntimeException('El archivo debe pesar máximo 10 MB.');
        $tmp=(string)($file['tmp_name']??'');
        if($tmp===''||!is_uploaded_file($tmp))throw new \RuntimeException('No pudimos validar el archivo.');
        $finfo=new \finfo(FILEINFO_MIME_TYPE);$mime=(string)$finfo->file($tmp);
        if(!isset(self::ALLOWED_MIME[$mime]))throw new \RuntimeException('Ese tipo de archivo no está permitido.');
        $ext=self::ALLOWED_MIME[$mime];$dir='storage/ticket_uploads/'.date('Y').'/'.date('m');$absolute=APP_ROOT.'/'.$dir;
        if(!is_dir($absolute)&&!mkdir($absolute,0775,true)&&!is_dir($absolute))throw new \RuntimeException('No pudimos preparar el almacenamiento del archivo.');
        $stored=bin2hex(random_bytes(18)).'.'.$ext;$target=$absolute.'/'.$stored;
        if(!move_uploaded_file($tmp,$target))throw new \RuntimeException('No pudimos guardar el archivo.');
        $sha=hash_file('sha256',$target)?:null;$original=trim((string)($file['name']??'archivo.'.$ext));
        $q=$pdo->prepare("INSERT INTO ticket_attachments(ticket_id,comment_id,uploaded_by_user_id,visibility,original_name,stored_name,storage_path,mime_type,size_bytes,sha256,created_at)
            VALUES(?,?,?,'EXTERNAL',?,?,?,?,?,?,NOW())");
        $q->execute([$ticketId,$commentId,Auth::id(),$original,$stored,$dir.'/'.$stored,$mime,$size,$sha]);
        return (int)$pdo->lastInsertId();
    }

    private function fail(int $ticketId,string $message,string $type='warning'): never
    {
        Flash::set($message,$type);
        header('Location: '.APP_BASE_URL.($ticketId>0?'/tickets/view?id='.$ticketId:'/mis-tickets'));
        exit;
    }
}
