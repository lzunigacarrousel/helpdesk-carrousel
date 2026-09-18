<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,View};

final class HelpController
{
    public function manual(): void
    {
        Auth::requireLogin();
        $user=Auth::user();
        $isExternal=(($user['access_type']??'INTERNAL')==='EXTERNAL');
        $isSupport=!$isExternal&&(Auth::can('tickets.view_queue')||Auth::can('tickets.change_status')||Auth::can('tickets.view_all'));
        $role=(string)Auth::role();

        if($isExternal){
            $manualProfile=['key'=>'collaborator','label'=>'Colaborador','summary'=>'Casos compartidos, conversación, evidencia y seguimiento de la solución.'];
        }elseif(in_array($role,['ADMIN','SEMIADMIN'],true)){
            $manualProfile=['key'=>'admin','label'=>$role==='ADMIN'?'Administrador':'Semiadministrador','summary'=>'Operación de soporte, conocimiento, informes y administración según tus permisos.'];
        }elseif($role==='TECHNICIAN'||$isSupport){
            $manualProfile=['key'=>'technician','label'=>'Técnico','summary'=>'Atención de casos, conversación, actividades, recurrencias y conocimiento.'];
        }elseif($role==='SUPERVISOR'){
            $manualProfile=['key'=>'supervisor','label'=>'Supervisor','summary'=>'Consulta de tickets, agenda e informes dentro del alcance asignado, sin operar soporte.'];
        }elseif($role==='MANAGEMENT'){
            $manualProfile=['key'=>'management','label'=>'Gerencia','summary'=>'Consulta ejecutiva de operación, tendencias, problemas y conocimiento, sin atender tickets.'];
        }else{
            $manualProfile=['key'=>'requester','label'=>'Solicitante','summary'=>'Crear solicitudes, dar seguimiento, responder y consultar soluciones disponibles.'];
        }

        $profileKey=(string)$manualProfile['key'];
        $manualGuide=match($profileKey){
            'collaborator'=>[
                'start'=>'Abre Mis casos y revisa qué apoyo necesita el equipo.',
                'workspace_label'=>'Mis casos',
                'workspace_href'=>APP_BASE_URL.'/mis-tickets',
                'workspace'=>'Responde, adjunta evidencia y da seguimiento solo a los casos compartidos contigo.',
                'after'=>'Cuando termines tu intervención, el equipo interno valida el trabajo y continúa el cierre.',
            ],
            'technician'=>[
                'start'=>'Revisa el Centro de soporte y prioriza por SLA, estado y responsabilidad.',
                'workspace_label'=>'Centro de soporte',
                'workspace_href'=>APP_BASE_URL.'/tickets/queue',
                'workspace'=>'Atiende, conversa, programa actividades y documenta la solución dentro del ticket.',
                'after'=>'Una buena resolución puede alimentar problemas conocidos y conocimiento reutilizable.',
            ],
            'supervisor'=>[
                'start'=>'Empieza por Dashboard e Informes para revisar lo que ocurre dentro de tu alcance.',
                'workspace_label'=>'Informes',
                'workspace_href'=>APP_BASE_URL.'/gestion/informes',
                'workspace'=>'Consulta tickets, agenda, carga y tendencias; tu perfil no opera la atención de soporte.',
                'after'=>'Usa el detalle para seguimiento y coordinación, sin tomar, reasignar ni resolver tickets.',
            ],
            'management'=>[
                'start'=>'Empieza por el Dashboard interno para entender volumen, tiempos y tendencias.',
                'workspace_label'=>'Dashboard interno',
                'workspace_href'=>APP_BASE_URL.'/gestion',
                'workspace'=>'Consulta indicadores, informes, problemas y conocimiento sin entrar a operar tickets.',
                'after'=>'Profundiza en Informes cuando necesites explicar un indicador o revisar su detalle.',
            ],
            'admin'=>[
                'start'=>'Usa el Centro de soporte para operación diaria y Gestión para control transversal.',
                'workspace_label'=>'Centro de soporte',
                'workspace_href'=>APP_BASE_URL.'/tickets/queue',
                'workspace'=>'Además de atender casos, administra capacidades habilitadas como usuarios, proveedores, correo y auditoría.',
                'after'=>'Las acciones administrativas deben conservar permisos, alcance y trazabilidad.',
            ],
            default=>[
                'start'=>'Usa Solicitar ayuda para registrar lo que ocurre con tus propias palabras.',
                'workspace_label'=>'Solicitar ayuda',
                'workspace_href'=>APP_BASE_URL.'/crear-ticket',
                'workspace'=>'Después consulta Mis solicitudes para ver respuestas, archivos, estado y solución.',
                'after'=>'Si soporte programa una visita o seguimiento, la información publicada aparecerá dentro de tu solicitud.',
            ],
        };

        $canManagement=!$isExternal&&(in_array($role,['ADMIN','SEMIADMIN'],true)||Auth::can('management.view'));
        $canReports=!$isExternal&&(in_array($role,['ADMIN','SEMIADMIN'],true)||Auth::can('reports.view')||Auth::can('management.view'));
        $canUsersManage=!$isExternal&&($role==='ADMIN'||Auth::can('users.manage'));
        $canExternalManage=!$isExternal&&(in_array($role,['ADMIN','SEMIADMIN'],true)||Auth::can('external.manage'));
        $canAudit=!$isExternal&&Auth::can('audit.view');
        $canMailAdmin=$canAudit;
        $canProviderReport=!$isExternal&&(in_array($role,['ADMIN','SEMIADMIN'],true)||Auth::can('external.manage')||Auth::can('reports.view'));
        $canAdmin=$canUsersManage||$canExternalManage||$canAudit||$canMailAdmin;

        View::render('help/manual',[
            'user'=>$user,
            'manualProfile'=>$manualProfile,
            'manualGuide'=>$manualGuide,
            'isRequesterProfile'=>$profileKey==='requester',
            'isTechnicianProfile'=>$profileKey==='technician',
            'isSupervisorProfile'=>$profileKey==='supervisor',
            'isManagementProfile'=>$profileKey==='management',
            'isAdminProfile'=>$profileKey==='admin',
            'isCollaboratorProfile'=>$profileKey==='collaborator',
            'isExternal'=>$isExternal,
            'isSupport'=>$isSupport,
            'canProblems'=>!$isExternal&&Auth::can('problems.view'),
            'canKnowledge'=>!$isExternal&&Auth::can('knowledge.view'),
            'canKnowledgeDraft'=>!$isExternal&&Auth::can('knowledge.draft_manage'),
            'canKnowledgeReview'=>!$isExternal&&Auth::can('knowledge.review'),
            'canKnowledgePublishInternal'=>!$isExternal&&Auth::can('knowledge.publish_internal'),
            'canKnowledgePublishPublic'=>!$isExternal&&Auth::can('knowledge.publish_public'),
            'canKnowledgeHistory'=>!$isExternal&&Auth::can('knowledge.history'),
            'canKnowledgeRestore'=>!$isExternal&&Auth::can('knowledge.restore'),
            'canManagement'=>$canManagement,
            'canReports'=>$canReports,
            'canProviderReport'=>$canProviderReport,
            'canUsersManage'=>$canUsersManage,
            'canExternalManage'=>$canExternalManage,
            'canAudit'=>$canAudit,
            'canMailAdmin'=>$canMailAdmin,
            'canAdmin'=>$canAdmin,
        ]);
    }
}
