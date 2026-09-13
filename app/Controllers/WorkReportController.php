<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http,Logger};
use App\Services\NotificationService;
use PDO;

final class WorkReportController
{
    private const TEMPLATES=[
        'GENERAL_SUPPORT'=>'Soporte general',
        'SOFTWARE_SUPPORT'=>'Soporte de software',
        'SOFTWARE_DEVELOPMENT'=>'Desarrollo de software',
        'AUDIT_ADVISORY'=>'Auditoría / asesoría',
    ];
    private const WORK_STATUSES=[
        'ANALYSIS'=>'En análisis / diagnóstico',
        'WAITING_CARROUSEL'=>'Esperando información de Carrousel',
        'WAITING_THIRD_PARTY'=>'Esperando tercero / fabricante',
        'IN_PROGRESS'=>'En atención / trabajando',
        'VALIDATING'=>'En validación',
        'READY_FOR_REVIEW'=>'Listo para revisión de Carrousel',
    ];
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
        if((($user['access_type']??'INTERNAL')!=='EXTERNAL')){
            $this->fail($ticketId,'Este informe está habilitado para el proveedor asignado al caso.','info');
        }

        $pdo=Database::pdo();
        $q=$pdo->prepare("SELECT t.id,t.ticket_number,t.subject,t.status,t.case_type,t.visibility_mode,
                eta.can_comment,eta.can_upload,
                COALESCE(NULLIF(eta.report_template,''),NULLIF(ep.report_template,''),'GENERAL_SUPPORT') report_template
            FROM external_ticket_access eta
            JOIN tickets t ON t.id=eta.ticket_id
            LEFT JOIN external_profiles ep ON ep.user_id=eta.user_id
            WHERE eta.ticket_id=? AND eta.user_id=? AND eta.revoked_at IS NULL AND t.deleted_at IS NULL
            LIMIT 1");
        $q->execute([$ticketId,(int)Auth::id()]);
        $ticket=$q->fetch();
        if(!$ticket||$ticket['case_type']!=='SPECIAL'||$ticket['visibility_mode']!=='EXTERNAL_ALLOWED'){
            $this->fail($ticketId,'Este caso no está habilitado para tu cuenta.','info');
        }
        if(!(bool)$ticket['can_comment'])$this->fail($ticketId,'Tu acceso a este caso es solo de consulta.','info');
        if(in_array((string)$ticket['status'],['CLOSED','CANCELLED'],true))$this->fail($ticketId,'Este caso ya está finalizado.','info');

        $reportTemplate=$this->normalizeTemplate((string)$ticket['report_template']);
        $workStatus=strtoupper(trim(Http::post('work_status')));
        if(!isset(self::WORK_STATUSES[$workStatus]))$this->fail($ticketId,'Selecciona el estado actual del trabajo.');

        $fields=[
            'diagnosis','root_cause','actions_performed','configuration_changes','tests_performed','result_summary','pending_items',
            'preventive_recommendation','provider_reference','system_module','environment','error_symptom','reproduction_steps',
            'procedure_steps','tools_access_used','rollback_steps','escalation_criteria','code_changes','database_changes',
            'release_version','deployment_notes','review_scope','finding','evidence_summary','business_impact','recommendation',
            'suggested_owner','follow_up','conclusion',
        ];
        $data=[];
        foreach($fields as $name){
            $value=trim(Http::post($name));
            if(mb_strlen($value)>8000)$this->fail($ticketId,'Uno de los campos del informe es demasiado extenso.');
            $data[$name]=$value;
        }
        $riskLevel=$this->optionalLevel(Http::post('risk_level'));
        $recommendationPriority=$this->optionalLevel(Http::post('recommendation_priority'));
        $timeSpent=max(0,(int)Http::post('time_spent_minutes'));
        $commitmentAt=$this->optionalDate(Http::post('commitment_at'),$ticketId);

        $this->validateRequiredFields($ticketId,$reportTemplate,$workStatus,$data,$riskLevel);

        // Compatibilidad histórica: el porcentaje se calcula automáticamente y nunca se solicita al proveedor.
        $legacyProgress=match($workStatus){
            'ANALYSIS'=>15,'WAITING_CARROUSEL'=>25,'WAITING_THIRD_PARTY'=>35,'IN_PROGRESS'=>60,'VALIDATING'=>80,'READY_FOR_REVIEW'=>100,default=>0,
        };
        $legacyType=match($workStatus){
            'WAITING_CARROUSEL','WAITING_THIRD_PARTY'=>'INFO_REQUEST',
            'READY_FOR_REVIEW'=>'WORK_COMPLETED',
            default=>'PROGRESS',
        };
        $readyForReview=$workStatus==='READY_FOR_REVIEW';

        $hasFile=isset($_FILES['attachment'])&&(int)($_FILES['attachment']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        if($hasFile&&!(bool)$ticket['can_upload'])$this->fail($ticketId,'Tu acceso no permite adjuntar evidencia en este caso.');

        $commentId=null;$attachmentId=null;$reportId=null;
        $summary=$this->commentSummary($reportTemplate,$workStatus,$data,$timeSpent,$commitmentAt);

        try{
            Database::transaction(function(PDO $pdo)use(
                $ticketId,$user,$reportTemplate,$workStatus,$legacyType,$legacyProgress,$data,$riskLevel,$recommendationPriority,
                $timeSpent,$commitmentAt,$readyForReview,$summary,$hasFile,&$commentId,&$attachmentId,&$reportId
            ):void{
                $q=$pdo->prepare("INSERT INTO ticket_comments(ticket_id,author_user_id,author_name,author_email,visibility,body,created_at)
                    VALUES(?,?,?,?, 'EXTERNAL', ?,NOW())");
                $q->execute([$ticketId,Auth::id(),$user['full_name']??null,$user['email']??null,$summary]);
                $commentId=(int)$pdo->lastInsertId();

                $report=[
                    'ticket_id'=>$ticketId,
                    'author_user_id'=>Auth::id(),
                    'author_access_type'=>'EXTERNAL',
                    'report_template'=>$reportTemplate,
                    'work_status'=>$workStatus,
                    'report_type'=>$legacyType,
                    'progress_percent'=>$legacyProgress,
                    'diagnosis'=>$data['diagnosis'],
                    'root_cause'=>$data['root_cause'],
                    'actions_performed'=>$data['actions_performed'],
                    'parts_materials'=>'No aplica / no requerido por la plantilla',
                    'configuration_changes'=>$data['configuration_changes'],
                    'tests_performed'=>$data['tests_performed'],
                    'result_summary'=>$data['result_summary'],
                    'pending_items'=>$data['pending_items'],
                    'preventive_recommendation'=>$data['preventive_recommendation'],
                    'provider_reference'=>$data['provider_reference'],
                    'time_spent_minutes'=>$timeSpent,
                    'commitment_at'=>$commitmentAt,
                    'system_module'=>$data['system_module']?:null,
                    'environment'=>$data['environment']?:null,
                    'error_symptom'=>$data['error_symptom']?:null,
                    'reproduction_steps'=>$data['reproduction_steps']?:null,
                    'procedure_steps'=>$data['procedure_steps']?:null,
                    'tools_access_used'=>$data['tools_access_used']?:null,
                    'rollback_steps'=>$data['rollback_steps']?:null,
                    'escalation_criteria'=>$data['escalation_criteria']?:null,
                    'code_changes'=>$data['code_changes']?:null,
                    'database_changes'=>$data['database_changes']?:null,
                    'release_version'=>$data['release_version']?:null,
                    'deployment_notes'=>$data['deployment_notes']?:null,
                    'review_scope'=>$data['review_scope']?:null,
                    'finding'=>$data['finding']?:null,
                    'evidence_summary'=>$data['evidence_summary']?:null,
                    'risk_level'=>$riskLevel,
                    'business_impact'=>$data['business_impact']?:null,
                    'recommendation'=>$data['recommendation']?:null,
                    'recommendation_priority'=>$recommendationPriority,
                    'suggested_owner'=>$data['suggested_owner']?:null,
                    'follow_up'=>$data['follow_up']?:null,
                    'conclusion'=>$data['conclusion']?:null,
                    'ready_for_review'=>$readyForReview?1:0,
                    'comment_id'=>$commentId,
                ];
                $columns=array_keys($report);
                $sql='INSERT INTO ticket_work_reports('.implode(',',$columns).',created_at,updated_at) VALUES('.implode(',',array_fill(0,count($columns),'?')).',NOW(),NOW())';
                $q=$pdo->prepare($sql);
                $q->execute(array_values($report));
                $reportId=(int)$pdo->lastInsertId();

                if($hasFile)$attachmentId=$this->storeUpload($pdo,$ticketId,$commentId,$_FILES['attachment']);

                $pdo->prepare("INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,new_value,metadata_json,created_at)
                    VALUES(?,'WORK_REPORT_ADDED',?,'USER',?,?,NOW())")
                    ->execute([
                        $ticketId,
                        Auth::id(),
                        json_encode(['report_template'=>$reportTemplate,'work_status'=>$workStatus,'ready_for_review'=>$readyForReview],JSON_UNESCAPED_UNICODE),
                        json_encode(['work_report_id'=>$reportId,'comment_id'=>$commentId,'attachment_id'=>$attachmentId],JSON_UNESCAPED_UNICODE),
                    ]);
                $pdo->prepare('UPDATE tickets SET updated_at=NOW() WHERE id=?')->execute([$ticketId]);
            });
        }catch(\Throwable $e){
            Logger::error($e);
            $this->fail($ticketId,'No pudimos guardar el informe técnico. Intenta nuevamente.');
        }

        Audit::log('WORK_REPORT_ADDED','ticket',$ticketId,null,[
            'work_report_id'=>$reportId,
            'report_template'=>$reportTemplate,
            'work_status'=>$workStatus,
            'ready_for_review'=>$readyForReview,
            'comment_id'=>$commentId,
            'attachment_id'=>$attachmentId,
            'author_access_type'=>'EXTERNAL',
        ]);

        try{
            $notifications=new NotificationService();
            $notifications->publishTicket(
                $ticketId,'WORK_REPORT_ADDED','Documentación del proveedor · '.$ticket['ticket_number'],
                ($user['full_name']??'Proveedor').' actualizó la documentación del caso: '.self::WORK_STATUSES[$workStatus].'.'.($readyForReview?' La atención quedó lista para revisión de Carrousel.':''),
                ['assignee','admins'],APP_BASE_URL.'/tickets/view?id='.$ticketId.'#conversacion',
                ['work_report_id'=>$reportId,'report_template'=>$reportTemplate,'work_status'=>$workStatus,'ready_for_review'=>$readyForReview,'has_attachment'=>$hasFile]
            );
        }catch(\Throwable $e){Logger::error($e);}

        Flash::set($readyForReview?'Documentación guardada y enviada a revisión de Carrousel.':'Documentación guardada correctamente.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#informe-tecnico');
        exit;
    }

    private function validateRequiredFields(int $ticketId,string $template,string $status,array $data,?string $riskLevel): void
    {
        $required=[];
        if(in_array($status,['ANALYSIS','IN_PROGRESS','VALIDATING','READY_FOR_REVIEW'],true))$required[]='diagnosis';
        if(in_array($status,['IN_PROGRESS','VALIDATING','READY_FOR_REVIEW'],true))$required[]='actions_performed';
        if(in_array($status,['WAITING_CARROUSEL','WAITING_THIRD_PARTY','READY_FOR_REVIEW'],true))$required[]='pending_items';
        if(in_array($status,['VALIDATING','READY_FOR_REVIEW'],true)){
            $required[]='tests_performed';$required[]='result_summary';
        }

        if($template==='SOFTWARE_SUPPORT'){
            $required[]='system_module';$required[]='error_symptom';
            if(in_array($status,['ANALYSIS','IN_PROGRESS'],true))$required[]='reproduction_steps';
            if($status==='READY_FOR_REVIEW')$required[]='procedure_steps';
        }elseif($template==='SOFTWARE_DEVELOPMENT'){
            $required[]='system_module';
            if(in_array($status,['IN_PROGRESS','VALIDATING','READY_FOR_REVIEW'],true))$required[]='code_changes';
            if($status==='READY_FOR_REVIEW')$required[]='release_version';
        }elseif($template==='AUDIT_ADVISORY'){
            $required=array_merge($required,['review_scope','finding','business_impact']);
            if(!$riskLevel)$this->fail($ticketId,'Indica el nivel de riesgo del hallazgo.');
            if(in_array($status,['VALIDATING','READY_FOR_REVIEW'],true))$required[]='recommendation';
            if($status==='READY_FOR_REVIEW')$required[]='conclusion';
        }

        $required=array_values(array_unique($required));
        $labels=[
            'diagnosis'=>'Diagnóstico / situación encontrada','actions_performed'=>'Acciones realizadas','pending_items'=>'Pendientes / información requerida',
            'tests_performed'=>'Pruebas realizadas','result_summary'=>'Resultado obtenido','system_module'=>'Sistema / módulo','error_symptom'=>'Error o síntoma',
            'reproduction_steps'=>'Cómo reproducir el problema','procedure_steps'=>'Procedimiento realizado paso a paso','code_changes'=>'Cambios de código / módulos',
            'release_version'=>'Versión / build liberada','review_scope'=>'Alcance de revisión','finding'=>'Hallazgo','business_impact'=>'Impacto para Carrousel',
            'recommendation'=>'Recomendación','conclusion'=>'Conclusión',
        ];
        foreach($required as $field){
            if(mb_strlen(trim((string)($data[$field]??'')))<2){
                $this->fail($ticketId,($labels[$field]??$field).' es obligatorio para el estado y tipo de servicio seleccionados.');
            }
        }
    }

    private function normalizeTemplate(string $value): string
    {
        $value=strtoupper(trim($value));
        return isset(self::TEMPLATES[$value])?$value:'GENERAL_SUPPORT';
    }

    private function optionalLevel(string $value): ?string
    {
        $value=strtoupper(trim($value));
        return in_array($value,['LOW','MEDIUM','HIGH','CRITICAL'],true)?$value:null;
    }

    private function optionalDate(string $value,int $ticketId): ?string
    {
        $value=trim($value);
        if($value==='')return null;
        $ts=strtotime($value);
        if($ts===false)$this->fail($ticketId,'La fecha de compromiso no es válida.');
        return date('Y-m-d H:i:s',$ts);
    }

    private function commentSummary(string $template,string $status,array $data,int $timeSpent,?string $commitmentAt): string
    {
        $lines=[
            'Documentación técnica del proveedor',
            'Tipo de servicio: '.(self::TEMPLATES[$template]??$template),
            'Estado: '.(self::WORK_STATUSES[$status]??$status),
        ];
        if($data['diagnosis']!=='')$lines[]='Diagnóstico / situación: '.$data['diagnosis'];
        if($data['actions_performed']!=='')$lines[]='Acciones realizadas: '.$data['actions_performed'];
        if($data['result_summary']!=='')$lines[]='Resultado: '.$data['result_summary'];
        if($data['pending_items']!=='')$lines[]='Pendientes: '.$data['pending_items'];
        if($data['provider_reference']!=='')$lines[]='Referencia proveedor: '.$data['provider_reference'];
        if($timeSpent>0)$lines[]='Tiempo invertido: '.$timeSpent.' min';
        if($commitmentAt)$lines[]='Próxima fecha compromiso: '.date('d/m/Y H:i',strtotime($commitmentAt));
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
