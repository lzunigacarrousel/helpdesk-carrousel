<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Database,View};
use App\Services\{ProviderParticipationService,ProviderRatingService,XlsxExportService};

final class ExternalReportController
{
    public function index(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $service=new ProviderParticipationService($pdo);
        $ratingService=new ProviderRatingService($pdo);
        $allRows=$service->scopedRows($ratingService->enrichRows($service->rows()));
        $filters=$this->filters();
        $rows=ProviderParticipationService::applyFilters($allRows,$filters);
        $providerRatingSummary=ProviderRatingService::providerSummary($rows);

        View::render('management/external_report',[
            'user'=>Auth::user(),
            'rows'=>$rows,
            'summary'=>ProviderParticipationService::summary($rows),
            'filters'=>$filters,
            'providers'=>$service->registeredProviders(),
            'activityOptions'=>ProviderParticipationService::activityOptions(),
            'ratingOptions'=>ProviderRatingService::ratingOptions(),
            'providerRatingSummary'=>$providerRatingSummary,
        ]);
    }

    public function export(): void
    {
        $this->requireAccess();
        $pdo=Database::pdo();
        $service=new ProviderParticipationService($pdo);
        $ratingService=new ProviderRatingService($pdo);
        $filters=$this->filters();
        $rows=ProviderParticipationService::applyFilters($service->scopedRows($ratingService->enrichRows($service->rows())),$filters);
        $summary=ProviderParticipationService::summary($rows);
        $providerRatingSummary=ProviderRatingService::providerSummary($rows);
        $data=[];

        foreach($rows as $r){
            $data[]=[
                (string)$r['organization'],
                (string)$r['contact'],
                (string)$r['email'],
                (string)$r['ticket_number'],
                (string)$r['subject'],
                $this->displayDate((string)$r['granted_at']),
                (string)$r['granted_by'],
                $r['revoked_at']?$this->displayDate((string)$r['revoked_at']):'Activo',
                (string)($r['revoked_by']?:''),
                (int)$r['duration_minutes'],
                $this->displayDate($r['first_response_at']??null),
                $r['first_response_minutes']===null?'':(int)$r['first_response_minutes'],
                (string)($r['first_response_origin']??''),
                (string)($r['activity_label']??'Sin actualización'),
                $this->displayDate($r['last_activity_at']??null),
                (int)($r['declared_minutes']??0),
                (int)($r['responses']??0),
                (int)($r['attachments']??0),
                (int)($r['reports']??0),
                (int)($r['deliveries']??0),
                (int)($r['returns']??0),
                $r['provider_rating_score']===null?'':(int)$r['provider_rating_score'],
                (string)($r['provider_rating_label']??'Sin evaluar'),
                (string)($r['provider_rating_comment']??''),
                (string)($r['provider_rating_actor']??''),
                $this->displayDate($r['provider_rating_at']??null),
                (int)($r['provider_rating_revisions']??0),
                $this->ticketStatusLabel((string)$r['ticket_status']),
                empty($r['revoked_at'])?'Activo':'Finalizado',
            ];
        }

        $qualityData=[];
        foreach($providerRatingSummary as $quality){
            $qualityData[]=[
                (string)($quality['organization']??''),
                $quality['average_score']===null?'':(float)$quality['average_score'],
                (int)($quality['rated_cycles']??0),
                (int)($quality['unrated_cycles']??0),
            ];
        }

        Audit::log('EXTERNAL_REPORT_EXPORTED_XLSX','report',null,null,null,[
            'rows'=>count($data),
            'filters'=>$filters,
        ]);

        XlsxExportService::download('helpdesk_proveedores_'.date('Ymd_His').'.xlsx',[
            [
                'name'=>'Resumen',
                'title'=>'Helpdesk Carrousel · Proveedores',
                'subtitle'=>'Participación operativa de proveedores externos',
                'headers'=>['Indicador','Valor'],
                'rows'=>[
                    ['Participaciones',(int)$summary['participations']],
                    ['Activas',(int)$summary['active']],
                    ['Sin respuesta',(int)$summary['no_response']],
                    ['Promedio primera respuesta (min)',$summary['avg_first_response_minutes']??''],
                    ['Devoluciones',(int)$summary['returns']],
                    ['Generado',date('d/m/Y H:i:s')],
                ],
            ],
            [
                'name'=>'Participaciones',
                'title'=>'Helpdesk Carrousel · Historial de proveedores',
                'subtitle'=>'La exportación respeta los filtros aplicados en pantalla',
                'headers'=>[
                    'Proveedor','Contacto','Correo','Ticket','Asunto','Asignado','Asignado por',
                    'Revocado','Revocado por','Duración (min)','Primera respuesta','T. primera respuesta (min)',
                    'Origen primera respuesta','Actividad actual','Última actualización','Trabajo declarado (min)',
                    'Respuestas','Archivos','Informes','Entregas listas','Devoluciones',
                    'Valoración proveedor','Etiqueta valoración','Comentario valoración','Registró valoración','Fecha valoración','Correcciones valoración',
                    'Estado ticket','Estado ciclo',
                ],
                'rows'=>$data,
            ],
            [
                'name'=>'Calidad proveedores',
                'title'=>'Helpdesk Carrousel · Calidad por proveedor',
                'subtitle'=>'Promedio vigente; los ciclos sin evaluar no equivalen a cero',
                'headers'=>['Proveedor','Promedio calidad','Ciclos evaluados','Ciclos sin evaluar'],
                'rows'=>$qualityData,
            ],
        ]);
    }

    private function filters(): array
    {
        $q=trim((string)($_GET['q']??''));
        $provider=max(0,(int)($_GET['provider']??0));
        $state=(string)($_GET['state']??'');
        if(!in_array($state,['','active','closed'],true))$state='';

        $activity=(string)($_GET['activity']??'');
        $validActivities=array_keys(ProviderParticipationService::activityOptions());
        if($activity!==''&&!in_array($activity,$validActivities,true))$activity='';

        $rating=(string)($_GET['rating']??'');
        $validRatings=array_keys(ProviderRatingService::ratingOptions());
        if($rating!==''&&!in_array($rating,$validRatings,true))$rating='';

        return [
            'q'=>$q,
            'provider'=>$provider,
            'state'=>$state,
            'activity'=>$activity,
            'rating'=>$rating,
            'from'=>$this->validDate((string)($_GET['from']??'')),
            'to'=>$this->validDate((string)($_GET['to']??'')),
        ];
    }

    private function ticketStatusLabel(string $status): string
    {
        return match($status){
            'NEW'=>'Nuevo',
            'AVAILABLE'=>'Disponible',
            'IN_PROGRESS'=>'En proceso',
            'PENDING'=>'En espera',
            'RESOLVED'=>'Resuelto',
            'CLOSED'=>'Cerrado',
            'REOPENED'=>'Reabierto',
            'CANCELLED'=>'Cancelado',
            default=>$status,
        };
    }

    private function validDate(string $value): string
    {
        if($value==='')return '';
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        return $date&&$date->format('Y-m-d')===$value?$value:'';
    }

    private function displayDate(?string $value): string
    {
        $timestamp=$value?strtotime($value):false;
        return $timestamp?date('d/m/Y H:i',$timestamp):'';
    }

    private function requireAccess(): void
    {
        Auth::requireLogin();
        if(!in_array(Auth::role(),['ADMIN','SEMIADMIN'],true)&&!Auth::can('external.manage')&&!Auth::can('reports.view')){
            header('Location: '.APP_BASE_URL.'/dashboard');
            exit;
        }
    }
}
