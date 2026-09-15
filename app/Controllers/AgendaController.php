<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth,View};
use App\Services\{AgendaService,ScopeService};
use DateTimeImmutable;

final class AgendaController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user=Auth::user();
        $role=(string)Auth::role();
        $allowed=['ADMIN','SEMIADMIN','TECHNICIAN','MANAGEMENT','SUPERVISOR'];
        if(($user['access_type']??'INTERNAL')==='EXTERNAL'||!in_array($role,$allowed,true)){
            http_response_code(403);
            View::render('errors/friendly',[
                'user'=>$user,
                'title'=>'Acceso no autorizado',
                'message'=>'No tienes acceso a la agenda.',
            ]);
            return;
        }

        [$filters,$notice]=$this->filters($role);
        $service=new AgendaService();
        $activities=$service->activities($filters);
        $canProgram=in_array($role,['ADMIN','SEMIADMIN','TECHNICIAN'],true);
        $ticketMatches=[];
        if($canProgram&&$filters['program']&&$filters['ticket_q']!==''){
            $ticketMatches=$service->searchTickets($filters['ticket_q']);
        }

        View::render('agenda/index',[
            'user'=>$user,
            'filters'=>$filters,
            'activities'=>$activities,
            'overdue'=>$service->overdueBefore($filters['from'],$filters),
            'options'=>$service->filterOptions($filters),
            'ticketMatches'=>$ticketMatches,
            'hourWindow'=>AgendaService::hourWindow($activities),
            'canProgram'=>$canProgram,
            'scopeLabel'=>(new ScopeService())->scopeLabel(),
            'notice'=>$notice,
        ]);
    }

    private function filters(string $role): array
    {
        $view=in_array($_GET['view']??'', ['calendar','list'],true)?$_GET['view']:'calendar';
        $today=new DateTimeImmutable('today');
        $defaultFrom=$view==='calendar'
            ?$today->modify('monday this week')
            :$today;
        $defaultTo=$view==='calendar'
            ?$defaultFrom->modify('+6 days')
            :$today->modify('+6 days');
        $from=$defaultFrom;
        $to=$defaultTo;
        $notice=null;
        $hasFrom=array_key_exists('from',$_GET);
        $hasTo=array_key_exists('to',$_GET);

        if($hasFrom||$hasTo){
            $customFrom=$this->date($_GET['from']??null);
            $customTo=$this->date($_GET['to']??null);
            if($customFrom!==null&&$customTo!==null&&$customFrom<=$customTo&&$customFrom->diff($customTo)->days<=90){
                $from=$customFrom;
                $to=$customTo;
            }else{
                $notice='El rango solicitado no es valido. Se mostro el rango predeterminado.';
            }
        }

        $history=($_GET['history']??'')==='1';
        $status=(string)($_GET['status']??'active');
        $allowedStatuses=['active','all','PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'];
        if(!in_array($status,$allowedStatuses,true)||(!$history&&in_array($status,['all','FINALIZADA','CANCELADA'],true))){
            $status='active';
        }

        $scopeMode=$role==='TECHNICIAN'&&($_GET['scope_mode']??'')==='all'?'all':'mine';
        if($role!=='TECHNICIAN')$scopeMode='all';
        $activityType=(string)($_GET['activity_type']??'');
        if(!in_array($activityType,AgendaService::TYPES,true))$activityType='';

        return [[
            'view'=>$view,
            'from'=>$from->format('Y-m-d'),
            'to'=>$to->format('Y-m-d'),
            'responsible_user_id'=>$this->positiveId($_GET['responsible_user_id']??null),
            'park_id'=>$this->positiveId($_GET['park_id']??null),
            'activity_type'=>$activityType,
            'status'=>$status,
            'scope_mode'=>$scopeMode,
            'history'=>$history,
            'program'=>($_GET['program']??'')==='1'&&in_array($role,['ADMIN','SEMIADMIN','TECHNICIAN'],true),
            'ticket_q'=>mb_substr(trim((string)($_GET['ticket_q']??'')),0,100),
        ],$notice];
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        if(!is_string($value))return null;
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        return $date!==false&&$date->format('Y-m-d')===$value?$date:null;
    }

    private function positiveId(mixed $value): int
    {
        return is_string($value)&&ctype_digit($value)&&(int)$value>0?(int)$value:0;
    }
}
