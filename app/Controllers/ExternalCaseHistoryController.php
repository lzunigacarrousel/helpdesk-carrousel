<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Database};
use App\Services\XlsxExportService;

final class ExternalCaseHistoryController
{
    public function export(): void
    {
        Auth::requireLogin();
        $user=Auth::user();
        if((($user['access_type']??'INTERNAL')!=='EXTERNAL')){
            header('Location: '.APP_BASE_URL.'/mis-tickets');
            exit;
        }

        $pdo=Database::pdo();
        $s=$pdo->prepare("SELECT t.ticket_number,t.subject,COALESCE(c.name,'') category_name,COALESCE(p.name,'') park_name,eta.granted_at external_granted_at,eta.revoked_at external_revoked_at FROM external_ticket_access eta JOIN tickets t ON t.id=eta.ticket_id LEFT JOIN ticket_categories c ON c.id=t.category_id LEFT JOIN parks p ON p.id=t.park_id WHERE eta.user_id=? AND t.case_type='SPECIAL' AND t.deleted_at IS NULL AND (eta.revoked_at IS NOT NULL OR t.visibility_mode='EXTERNAL_ALLOWED') ORDER BY COALESCE(eta.revoked_at,eta.granted_at) DESC,t.created_at DESC");
        $s->execute([Auth::id()]);

        $data=[];
        foreach($s->fetchAll() as $row){
            $isClosed=!empty($row['external_revoked_at']);
            $data[]=[
                (string)$row['ticket_number'],
                (string)$row['subject'],
                (string)$row['category_name'],
                (string)$row['park_name'],
                $isClosed?'Finalizada':'Activa',
                $this->displayDate((string)$row['external_granted_at']),
                $isClosed?$this->displayDate((string)$row['external_revoked_at']):'',
            ];
        }

        XlsxExportService::download('helpdesk_mis_casos_'.date('Ymd_His').'.xlsx',[[
            'name'=>'Mis casos',
            'title'=>'Helpdesk Carrousel · Mis casos',
            'subtitle'=>'Historial de participaciones compartidas contigo',
            'headers'=>['Ticket','Asunto','Categoría','Ubicación','Participación','Asignado','Finalizado'],
            'rows'=>$data,
        ]]);
    }

    private function displayDate(string $value): string
    {
        $timestamp=$value!==''?strtotime($value):false;
        return $timestamp?date('d/m/Y H:i',$timestamp):'';
    }
}