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

        View::render('help/manual',[
            'user'=>$user,
            'manualProfile'=>$manualProfile,
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
            'canManagement'=>!$isExternal&&(Auth::role()==='ADMIN'||Auth::role()==='SEMIADMIN'||Auth::can('management.view')),
            'canAdmin'=>!$isExternal&&(Auth::role()==='ADMIN'||Auth::can('users.manage')||Auth::can('audit.view')),
        ]);
    }
}
