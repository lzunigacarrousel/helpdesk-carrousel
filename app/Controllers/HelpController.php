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

        View::render('help/manual',[
            'user'=>$user,
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
