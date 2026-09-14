<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Audit,Auth,Database};
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final class TicketActivityService
{
    private const TYPES=[
        'VISITA_EN_SITIO',
        'SOPORTE_REMOTO',
        'SEGUIMIENTO',
        'INTERVENCION_PROVEEDOR',
        'OTRA',
    ];

    private const STATUSES=[
        'PROGRAMADA',
        'EN_CURSO',
        'FINALIZADA',
        'CANCELADA',
    ];

    private const RESULTS=[
        'RESUELTA',
        'PARCIAL',
        'SIN_RESOLVER',
        'REQUIERE_SEGUIMIENTO',
    ];

    public static function types(): array
    {
        return self::TYPES;
    }

    public static function results(): array
    {
        return self::RESULTS;
    }

    public function listForTicket(int $ticketId): array
    {
        if($ticketId<=0)return[];

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT a.*,
                    responsible.full_name responsible_name,
                    responsible.email responsible_email,
                    provider.full_name provider_name,
                    provider.email provider_email,
                    ep.organization_name provider_organization,
                    p.name park_name
             FROM ticket_activities a
             JOIN users responsible ON responsible.id=a.responsible_user_id
             LEFT JOIN users provider ON provider.id=a.provider_user_id
             LEFT JOIN external_profiles ep ON ep.user_id=a.provider_user_id
             LEFT JOIN parks p ON p.id=a.park_id
             WHERE a.ticket_id=?
             ORDER BY FIELD(a.status,'EN_CURSO','PROGRAMADA','FINALIZADA','CANCELADA'),
                      a.scheduled_start_at,a.id"
        );
        $q->execute([$ticketId]);
        $rows=$q->fetchAll();
        if(!$rows)return[];

        $ids=array_map(static fn(array $row):int=>(int)$row['id'],$rows);
        $participants=$this->participantsByActivity($pdo,$ids);
        foreach($rows as &$row){
            $row['participants']=$participants[(int)$row['id']]??[];
        }
        unset($row);
        return $rows;
    }

    public function requesterVisibleForTicket(int $ticketId): array
    {
        if($ticketId<=0)return[];

        $q=Database::pdo()->prepare(
            "SELECT a.id,a.activity_type,a.status,a.park_id,
                    a.scheduled_start_at,a.scheduled_end_at,a.requester_summary,
                    p.name park_name
             FROM ticket_activities a
             LEFT JOIN parks p ON p.id=a.park_id
             WHERE a.ticket_id=? AND a.requester_visible=1
             ORDER BY FIELD(a.status,'EN_CURSO','PROGRAMADA','FINALIZADA','CANCELADA'),
                      a.scheduled_start_at,a.id"
        );
        $q->execute([$ticketId]);
        return $q->fetchAll();
    }

    public function responsibleOptionsForTicket(int $ticketId): array
    {
        if($ticketId<=0)return[];

        $q=Database::pdo()->query(
            "SELECT u.id,u.full_name,u.email,r.code role_code
             FROM users u
             JOIN roles r ON r.id=u.role_id
             WHERE u.access_type='INTERNAL'
               AND u.status='ACTIVE'
               AND u.deleted_at IS NULL
               AND r.code IN('ADMIN','SEMIADMIN','TECHNICIAN')
             ORDER BY u.full_name,u.id"
        );
        $scope=new ScopeService();
        $rows=[];
        foreach($q->fetchAll() as $user){
            $userId=(int)$user['id'];
            if($scope->userCanAccessTicket($userId,$ticketId))$rows[]=$user;
        }
        return $rows;
    }

    public function providerOptionsForTicket(int $ticketId): array
    {
        if($ticketId<=0)return[];

        $q=Database::pdo()->prepare(
            "SELECT u.id,u.full_name,u.email,ep.organization_name,ep.external_type
             FROM external_ticket_access eta
             JOIN users u ON u.id=eta.user_id
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE eta.ticket_id=?
               AND eta.revoked_at IS NULL
               AND u.access_type='EXTERNAL'
               AND u.status='ACTIVE'
               AND u.deleted_at IS NULL
             ORDER BY ep.organization_name,u.full_name,u.id"
        );
        $q->execute([$ticketId]);
        return $q->fetchAll();
    }

    public function create(array $input): int
    {
        $ticketId=(int)($input['ticket_id']??0);
        $type=strtoupper(trim((string)($input['activity_type']??'')));
        $responsibleUserId=(int)($input['responsible_user_id']??0);
        $providerUserId=(int)($input['provider_user_id']??0)?:null;
        $parkId=(int)($input['park_id']??0)?:null;
        $isRemote=(bool)($input['is_remote']??false);
        $objective=trim((string)($input['objective']??''));
        $notes=trim((string)($input['internal_preparation_notes']??''));
        $visible=(bool)($input['requester_visible']??false);
        $summary=$this->normalizeVisibleSummary($visible,(string)($input['requester_summary']??''));
        [$scheduledStart,$scheduledEnd]=$this->requireScheduledWindow(
            (string)($input['scheduled_start_at']??''),
            (string)($input['scheduled_end_at']??'')
        );

        if($ticketId<=0)throw new InvalidArgumentException('ticket_id es obligatorio.');
        if($objective==='')throw new InvalidArgumentException('El objetivo de la actividad es obligatorio.');
        $this->requireTicket($ticketId);
        $this->requireOperationalUser($responsibleUserId,$ticketId);
        $this->validateTypeSpecific($type,$ticketId,$providerUserId,$parkId,$isRemote);

        $actorId=(int)Auth::id();
        if($actorId<=0)throw new RuntimeException('Se requiere un usuario autenticado para crear la actividad.');

        $participantIds=[];
        foreach((array)($input['participant_user_ids']??[]) as $participantId){
            $participantId=(int)$participantId;
            if($participantId<=0||$participantId===$responsibleUserId)continue;
            $this->requireInternalActiveUser($participantId);
            $participantIds[$participantId]=$participantId;
        }

        $activityId=Database::transaction(function(PDO $pdo) use(
            $ticketId,$type,$responsibleUserId,$providerUserId,$parkId,$isRemote,$objective,$notes,
            $scheduledStart,$scheduledEnd,$visible,$summary,$actorId,$participantIds
        ): int {
            $q=$pdo->prepare(
                "INSERT INTO ticket_activities(
                    ticket_id,activity_type,status,responsible_user_id,provider_user_id,park_id,is_remote,
                    objective,internal_preparation_notes,scheduled_start_at,scheduled_end_at,
                    requester_visible,requester_summary,created_by,created_at,updated_at
                 ) VALUES(?,?,'PROGRAMADA',?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())"
            );
            $q->execute([
                $ticketId,$type,$responsibleUserId,$providerUserId,$parkId,$isRemote?1:0,
                $objective,$notes!==''?$notes:null,$scheduledStart,$scheduledEnd,$visible?1:0,$summary,$actorId,
            ]);
            $activityId=(int)$pdo->lastInsertId();

            if($participantIds){
                $insert=$pdo->prepare(
                    'INSERT IGNORE INTO ticket_activity_participants(activity_id,user_id,created_by,created_at) VALUES(?,?,?,NOW())'
                );
                foreach($participantIds as $participantId){
                    $insert->execute([$activityId,$participantId,$actorId]);
                }
            }

            $this->insertEvent($pdo,$ticketId,'ACTIVITY_CREATED',[
                'activity_id'=>$activityId,
                'activity_type'=>$type,
                'status'=>'PROGRAMADA',
                'responsible_user_id'=>$responsibleUserId,
                'provider_user_id'=>$providerUserId,
                'park_id'=>$parkId,
                'is_remote'=>$isRemote,
                'scheduled_start_at'=>$scheduledStart,
                'scheduled_end_at'=>$scheduledEnd,
                'requester_visible'=>$visible,
            ],[
                'participant_user_ids'=>array_values($participantIds),
            ]);

            return $activityId;
        });

        Audit::log('ACTIVITY_CREATED','ticket_activity',$activityId,null,[
            'ticket_id'=>$ticketId,
            'activity_type'=>$type,
            'status'=>'PROGRAMADA',
            'responsible_user_id'=>$responsibleUserId,
        ]);

        return $activityId;
    }

    public function reschedule(int $activityId,array $input): array
    {
        $reason=trim((string)($input['reason']??''));
        if(mb_strlen($reason)<5){
            throw new InvalidArgumentException('El motivo de reprogramación es obligatorio.');
        }
        [$scheduledStart,$scheduledEnd]=$this->requireScheduledWindow(
            (string)($input['scheduled_start_at']??''),
            (string)($input['scheduled_end_at']??'')
        );

        $actorId=(int)Auth::id();
        if($actorId<=0)throw new RuntimeException('Se requiere un usuario autenticado para reprogramar.');

        $result=Database::transaction(function(PDO $pdo) use($activityId,$scheduledStart,$scheduledEnd,$reason): array {
            $activity=$this->requireActivity($activityId,true);
            if((string)$activity['status']!=='PROGRAMADA'){
                throw new InvalidArgumentException('Solo una actividad PROGRAMADA puede reprogramarse.');
            }

            $q=$pdo->prepare(
                'UPDATE ticket_activities
                 SET scheduled_start_at=?,scheduled_end_at=?,updated_at=NOW()
                 WHERE id=?'
            );
            $q->execute([$scheduledStart,$scheduledEnd,$activityId]);

            $this->insertEvent($pdo,(int)$activity['ticket_id'],'ACTIVITY_RESCHEDULED',[
                'activity_id'=>$activityId,
                'status'=>'PROGRAMADA',
                'scheduled_start_at'=>$scheduledStart,
                'scheduled_end_at'=>$scheduledEnd,
            ],[
                'reason'=>$reason,
                'old_start'=>(string)$activity['scheduled_start_at'],
                'old_end'=>(string)$activity['scheduled_end_at'],
                'new_start'=>$scheduledStart,
                'new_end'=>$scheduledEnd,
            ]);

            return[
                'id'=>$activityId,
                'ticket_id'=>(int)$activity['ticket_id'],
                'status'=>'PROGRAMADA',
                'scheduled_start_at'=>$scheduledStart,
                'scheduled_end_at'=>$scheduledEnd,
                'reason'=>$reason,
            ];
        });

        Audit::log('ACTIVITY_RESCHEDULED','ticket_activity',$activityId,[
            'scheduled_start_at'=>$result['scheduled_start_at']??null,
            'scheduled_end_at'=>$result['scheduled_end_at']??null,
        ],$result,['reason'=>$reason]);

        return $result;
    }

    public function start(int $activityId): array
    {
        $actorId=(int)Auth::id();
        if($actorId<=0)throw new RuntimeException('Se requiere un usuario autenticado para iniciar la actividad.');

        $result=Database::transaction(function(PDO $pdo) use($activityId): array {
            $activity=$this->requireActivity($activityId,true);
            if((string)$activity['status']!=='PROGRAMADA'){
                throw new InvalidArgumentException('Solo una actividad PROGRAMADA puede iniciarse.');
            }

            $q=$pdo->prepare(
                "UPDATE ticket_activities
                 SET status='EN_CURSO',started_at=NOW(),updated_at=NOW()
                 WHERE id=?"
            );
            $q->execute([$activityId]);

            $refresh=$pdo->prepare('SELECT id,ticket_id,status,started_at FROM ticket_activities WHERE id=? LIMIT 1');
            $refresh->execute([$activityId]);
            $current=$refresh->fetch();
            if(!$current)throw new RuntimeException('No se pudo releer la actividad iniciada.');

            $this->insertEvent($pdo,(int)$activity['ticket_id'],'ACTIVITY_STARTED',[
                'activity_id'=>$activityId,
                'status'=>'EN_CURSO',
                'started_at'=>(string)$current['started_at'],
            ]);

            return[
                'id'=>$activityId,
                'ticket_id'=>(int)$activity['ticket_id'],
                'status'=>'EN_CURSO',
                'started_at'=>(string)$current['started_at'],
            ];
        });

        Audit::log('ACTIVITY_STARTED','ticket_activity',$activityId,null,$result);
        return $result;
    }

    private function requireScheduledWindow(string $start,string $end): array
    {
        $start=trim($start);
        $end=trim($end);
        if($start===''||$end===''){
            throw new InvalidArgumentException('Inicio y fin estimado son obligatorios.');
        }

        try{
            $startAt=new DateTimeImmutable($start);
            $endAt=new DateTimeImmutable($end);
        }catch(\Throwable){
            throw new InvalidArgumentException('La fecha u hora programada no es válida.');
        }

        if($startAt >= $endAt){
            throw new InvalidArgumentException('scheduled_start_at debe ser menor que scheduled_end_at.');
        }

        return[$startAt->format('Y-m-d H:i:s'),$endAt->format('Y-m-d H:i:s')];
    }

    private function normalizeVisibleSummary(bool $visible,string $summary): ?string
    {
        $summary=trim($summary);
        if(!$visible)return null;
        if($summary===''){
            throw new InvalidArgumentException('requester_visible requiere requester_summary.');
        }
        if(mb_strlen($summary)>500){
            throw new InvalidArgumentException('El resumen visible no puede superar 500 caracteres.');
        }
        return $summary;
    }

    private function requireOperationalUser(int $userId,int $ticketId): array
    {
        if($userId<=0||$ticketId<=0){
            throw new InvalidArgumentException('Responsable o ticket inválido.');
        }

        $q=Database::pdo()->prepare(
            "SELECT u.id,u.full_name,u.email,u.access_type,u.status,r.code role_code
             FROM users u
             JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL LIMIT 1"
        );
        $q->execute([$userId]);
        $user=$q->fetch();
        if(!$user||$user['access_type']!=='INTERNAL'||$user['status']!=='ACTIVE'){
            throw new InvalidArgumentException('El responsable debe ser un usuario interno activo.');
        }
        if(!in_array((string)$user['role_code'],['ADMIN','SEMIADMIN','TECHNICIAN'],true)){
            throw new InvalidArgumentException('El usuario seleccionado no tiene un rol operativo válido.');
        }
        if(!(new ScopeService())->userCanAccessTicket($userId,$ticketId)){
            throw new InvalidArgumentException('El responsable no tiene alcance operativo sobre este ticket.');
        }
        return $user;
    }

    private function requireProvider(int $userId,int $ticketId): array
    {
        if($userId<=0||$ticketId<=0){
            throw new InvalidArgumentException('INTERVENCION_PROVEEDOR requiere provider_user_id válido.');
        }

        $q=Database::pdo()->prepare(
            "SELECT u.id,u.full_name,u.email,u.access_type,u.status,
                    ep.organization_name,eta.ticket_id
             FROM users u
             JOIN external_ticket_access eta
               ON eta.user_id=u.id AND eta.ticket_id=? AND eta.revoked_at IS NULL
             LEFT JOIN external_profiles ep ON ep.user_id=u.id
             WHERE u.id=? AND u.deleted_at IS NULL LIMIT 1"
        );
        $q->execute([$ticketId,$userId]);
        $provider=$q->fetch();
        if(!$provider||$provider['access_type']!=='EXTERNAL'||$provider['status']!=='ACTIVE'){
            throw new InvalidArgumentException('El proveedor debe ser externo, activo y tener acceso vigente al ticket.');
        }
        return $provider;
    }

    private function requireActivity(int $activityId,bool $forUpdate=false): array
    {
        if($activityId<=0)throw new InvalidArgumentException('Actividad inválida.');
        $sql='SELECT * FROM ticket_activities WHERE id=? LIMIT 1'.($forUpdate?' FOR UPDATE':'');
        $q=Database::pdo()->prepare($sql);
        $q->execute([$activityId]);
        $activity=$q->fetch();
        if(!$activity)throw new RuntimeException('La actividad no existe.');
        return $activity;
    }

    private function requireTicket(int $ticketId): array
    {
        $q=Database::pdo()->prepare('SELECT id,park_id,area_id,status FROM tickets WHERE id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$ticketId]);
        $ticket=$q->fetch();
        if(!$ticket)throw new RuntimeException('El ticket no existe.');
        return $ticket;
    }

    private function requireInternalActiveUser(int $userId): array
    {
        $q=Database::pdo()->prepare(
            "SELECT id,full_name,email FROM users
             WHERE id=? AND access_type='INTERNAL' AND status='ACTIVE' AND deleted_at IS NULL LIMIT 1"
        );
        $q->execute([$userId]);
        $user=$q->fetch();
        if(!$user)throw new InvalidArgumentException('El participante debe ser un usuario interno activo.');
        return $user;
    }

    private function validateTypeSpecific(
        string $type,
        int $ticketId,
        ?int $providerUserId,
        ?int $parkId,
        bool $isRemote
    ): void {
        if(!in_array($type,self::TYPES,true)){
            throw new InvalidArgumentException('Tipo de actividad inválido.');
        }
        if($type==='VISITA_EN_SITIO'&&($parkId??0)<=0){
            throw new InvalidArgumentException('VISITA_EN_SITIO requiere park_id.');
        }
        if($type==='SOPORTE_REMOTO'&&!$isRemote){
            throw new InvalidArgumentException('SOPORTE_REMOTO requiere is_remote=1.');
        }
        if($type==='INTERVENCION_PROVEEDOR'){
            $this->requireProvider((int)$providerUserId,$ticketId);
        }
    }

    private function insertEvent(PDO $pdo,int $ticketId,string $eventType,array $newValue,array $metadata=[]): void
    {
        $q=$pdo->prepare(
            'INSERT INTO ticket_events(ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at)
             VALUES(?,?,?,\'USER\',NULL,?,?,NOW())'
        );
        $q->execute([
            $ticketId,
            $eventType,
            Auth::id(),
            json_encode($newValue,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $metadata?json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
        ]);
    }

    private function participantsByActivity(PDO $pdo,array $activityIds): array
    {
        if(!$activityIds)return[];
        $placeholders=implode(',',array_fill(0,count($activityIds),'?'));
        $q=$pdo->prepare(
            "SELECT ap.activity_id,u.id,u.full_name,u.email
             FROM ticket_activity_participants ap
             JOIN users u ON u.id=ap.user_id
             WHERE ap.activity_id IN ({$placeholders})
             ORDER BY u.full_name,u.id"
        );
        $q->execute($activityIds);
        $grouped=[];
        foreach($q->fetchAll() as $row){
            $grouped[(int)$row['activity_id']][]=$row;
        }
        return $grouped;
    }
}
