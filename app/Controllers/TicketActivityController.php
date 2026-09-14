<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth,Csrf,Flash,Http};
use App\Services\{NotificationService,TicketActivityService};
use RuntimeException;

final class TicketActivityController
{
    public function create(): void
    {
        $this->guard('activities.create');

        $ticketId=(int)Http::post('ticket_id');
        $participants=$_POST['participant_user_ids']??[];
        if(!is_array($participants))$participants=[];

        $service=new TicketActivityService();
        $activityId=$service->create([
            'ticket_id'=>$ticketId,
            'activity_type'=>Http::post('activity_type'),
            'responsible_user_id'=>(int)Http::post('responsible_user_id'),
            'provider_user_id'=>(int)Http::post('provider_user_id')?:null,
            'park_id'=>(int)Http::post('park_id')?:null,
            'is_remote'=>$this->postedBool('is_remote'),
            'objective'=>Http::post('objective'),
            'internal_preparation_notes'=>Http::post('internal_preparation_notes'),
            'scheduled_start_at'=>Http::post('scheduled_start_at'),
            'scheduled_end_at'=>Http::post('scheduled_end_at'),
            'requester_visible'=>$this->postedBool('requester_visible'),
            'requester_summary'=>Http::post('requester_summary'),
            'participant_user_ids'=>$participants,
        ]);

        $activity=$this->activity($service,$ticketId,$activityId);
        $this->notifyActivity($activity,'CREATED');
        Flash::set('Actividad programada.','success');
        $this->redirect($ticketId);
    }

    public function reschedule(): void
    {
        $this->guard('activities.manage');
        $activityId=(int)Http::post('activity_id');
        $service=new TicketActivityService();
        $result=$service->reschedule($activityId,[
            'scheduled_start_at'=>Http::post('scheduled_start_at'),
            'scheduled_end_at'=>Http::post('scheduled_end_at'),
            'reason'=>Http::post('reason'),
        ]);

        $activity=$this->activity($service,(int)$result['ticket_id'],$activityId);
        $this->notifyActivity($activity,'RESCHEDULED');
        Flash::set('Actividad reprogramada.','success');
        $this->redirect((int)$result['ticket_id']);
    }

    public function start(): void
    {
        $this->guard('activities.manage');
        $result=(new TicketActivityService())->start((int)Http::post('activity_id'));
        Flash::set('Actividad iniciada.','success');
        $this->redirect((int)$result['ticket_id']);
    }

    public function complete(): void
    {
        $this->guard('activities.manage');
        $result=(new TicketActivityService())->complete((int)Http::post('activity_id'),[
            'result_code'=>Http::post('result_code'),
            'work_performed'=>Http::post('work_performed'),
            'result_summary'=>Http::post('result_summary'),
            'pending_items'=>Http::post('pending_items'),
        ]);

        Flash::set(
            ($result['result_code']??'')==='REQUIERE_SEGUIMIENTO'
                ? 'Actividad finalizada. El resultado requiere seguimiento.'
                : 'Actividad finalizada.',
            'success'
        );
        $this->redirect((int)$result['ticket_id']);
    }

    public function cancel(): void
    {
        $this->guard('activities.cancel');
        $activityId=(int)Http::post('activity_id');
        $service=new TicketActivityService();
        $result=$service->cancel($activityId,Http::post('cancel_reason'));

        $activity=$this->activity($service,(int)$result['ticket_id'],$activityId);
        $this->notifyActivity($activity,'CANCELLED');
        Flash::set('Actividad cancelada.','success');
        $this->redirect((int)$result['ticket_id']);
    }

    public function addParticipant(): void
    {
        $this->guard('activities.manage');
        $result=(new TicketActivityService())->addParticipant(
            (int)Http::post('activity_id'),
            (int)Http::post('user_id')
        );
        Flash::set('Participante agregado.','success');
        $this->redirect((int)$result['ticket_id']);
    }

    public function removeParticipant(): void
    {
        $this->guard('activities.manage');
        $result=(new TicketActivityService())->removeParticipant(
            (int)Http::post('activity_id'),
            (int)Http::post('user_id')
        );
        Flash::set('Participante retirado.','success');
        $this->redirect((int)$result['ticket_id']);
    }

    private function guard(string $permission): void
    {
        Auth::requirePermission($permission);
        Csrf::verify($_POST['_csrf']??null);
    }

    private function postedBool(string $key): bool
    {
        if(!array_key_exists($key,$_POST))return false;
        return in_array(strtolower(trim((string)$_POST[$key])),['1','true','on','yes','si','sí'],true);
    }

    private function activity(TicketActivityService $service,int $ticketId,int $activityId): array
    {
        foreach($service->listForTicket($ticketId) as $activity){
            if((int)$activity['id']===$activityId)return $activity;
        }
        throw new RuntimeException('No fue posible releer la actividad actualizada.');
    }

    private function notifyActivity(array $activity,string $event): void
    {
        $ticketId=(int)($activity['ticket_id']??0);
        if($ticketId<=0)return;

        $notification=new NotificationService();
        $url=APP_BASE_URL.'/tickets/view?id='.$ticketId.'#actividades';
        $actorId=(int)(Auth::id()??0);
        $responsibleId=(int)($activity['responsible_user_id']??0);

        if($responsibleId>0&&$responsibleId!==$actorId){
            [$title,$message]=match($event){
                'RESCHEDULED'=>['Actividad reprogramada','Cambió la fecha u hora de una actividad asignada a ti.'],
                'CANCELLED'=>['Actividad cancelada','Se canceló una actividad asignada a ti.'],
                default=>['Nueva actividad asignada','Tienes una nueva actividad programada en Helpdesk.'],
            };
            $notification->notifyUser(
                $responsibleId,
                (string)($activity['responsible_email']??''),
                $ticketId,
                'ACTIVITY_'.$event.'_RESPONSIBLE',
                $title,
                $message,
                $url,
                true,
                true
            );
        }

        if(!empty($activity['requester_visible'])){
            $summary=trim((string)($activity['requester_summary']??''));
            if($summary!==''){
                $when=trim((string)($activity['scheduled_start_at']??''));
                $park=trim((string)($activity['park_name']??''));
                $safeMessage=$summary;
                if($when!=='')$safeMessage.=' Fecha programada: '.$when.'.';
                if($park!=='')$safeMessage.=' Ubicación: '.$park.'.';
                if($event==='CANCELLED')$safeMessage.=' La atención programada fue cancelada.';

                $notification->publishTicket(
                    $ticketId,
                    'ACTIVITY_'.$event.'_REQUESTER',
                    $event==='RESCHEDULED'?'Atención reprogramada':($event==='CANCELLED'?'Atención cancelada':'Atención programada'),
                    $safeMessage,
                    ['requester'],
                    $url,
                    ['activity_id'=>(int)$activity['id']],
                    ['email'=>true,'in_app'=>true]
                );
            }
        }

        $providerId=(int)($activity['provider_user_id']??0);
        if((string)($activity['activity_type']??'')==='INTERVENCION_PROVEEDOR'&&$providerId>0&&$providerId!==$actorId){
            $providerMessage=match($event){
                'RESCHEDULED'=>'La intervención asignada cambió de fecha u hora.',
                'CANCELLED'=>'La intervención asignada fue cancelada.',
                default=>'Tienes una intervención asignada en Helpdesk.',
            };
            $notification->notifyUser(
                $providerId,
                (string)($activity['provider_email']??''),
                $ticketId,
                'ACTIVITY_'.$event.'_PROVIDER',
                'Actualización de intervención',
                $providerMessage,
                $url,
                true,
                true
            );
        }
    }

    private function redirect(int $ticketId): never
    {
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#actividades');
        exit;
    }
}
