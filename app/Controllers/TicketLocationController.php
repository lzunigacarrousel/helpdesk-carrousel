<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http};
use PDO;

final class TicketLocationController
{
    public function update(): void
    {
        Auth::requirePermission('tickets.classify');
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        $parkId=(int)Http::post('park_id');
        $areaId=(int)Http::post('area_id');
        $reason=trim(Http::post('location_change_reason'));

        if($ticketId<=0)throw new \RuntimeException('Caso no válido.');
        if(mb_strlen($reason)<8)throw new \RuntimeException('Explica brevemente por qué estás corrigiendo la ubicación del caso.');

        $pdo=Database::pdo();
        $q=$pdo->prepare(
            "SELECT id,park_id,area_id
             FROM tickets
             WHERE id=? AND deleted_at IS NULL
             LIMIT 1"
        );
        $q->execute([$ticketId]);
        $before=$q->fetch();
        if(!$before)throw new \RuntimeException('No encontramos ese caso.');

        $parkId=$parkId>0?$parkId:0;
        $areaId=$areaId>0?$areaId:0;

        if($parkId>0){
            $v=$pdo->prepare('SELECT COUNT(*) FROM parks WHERE id=? AND is_active=1');
            $v->execute([$parkId]);
            if((int)$v->fetchColumn()!==1)throw new \RuntimeException('El parque seleccionado no está disponible.');
        }
        if($areaId>0){
            $v=$pdo->prepare('SELECT COUNT(*) FROM areas WHERE id=? AND is_active=1');
            $v->execute([$areaId]);
            if((int)$v->fetchColumn()!==1)throw new \RuntimeException('El área seleccionada no está disponible.');
        }

        $oldPark=(int)($before['park_id']??0);
        $oldArea=(int)($before['area_id']??0);
        if($oldPark===$parkId && $oldArea===$areaId){
            Flash::set('La ubicación del caso no cambió.','info');
            header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);
            exit;
        }

        Database::transaction(function(PDO $pdo)use($ticketId,$oldPark,$oldArea,$parkId,$areaId,$reason):void{
            $pdo->prepare('UPDATE tickets SET park_id=?,area_id=?,updated_at=NOW() WHERE id=?')
                ->execute([$parkId?:null,$areaId?:null,$ticketId]);

            $pdo->prepare(
                "INSERT INTO ticket_events(
                    ticket_id,event_type,actor_user_id,actor_type,old_value,new_value,metadata_json,created_at
                 ) VALUES(?,'LOCATION_CHANGED',?,'USER',?,?,?,NOW())"
            )->execute([
                $ticketId,
                (int)Auth::id(),
                json_encode(['park_id'=>$oldPark?:null,'area_id'=>$oldArea?:null],JSON_UNESCAPED_UNICODE),
                json_encode(['park_id'=>$parkId?:null,'area_id'=>$areaId?:null],JSON_UNESCAPED_UNICODE),
                json_encode(['reason'=>$reason],JSON_UNESCAPED_UNICODE),
            ]);
        });

        Audit::log(
            'TICKET_LOCATION_CHANGED',
            'ticket',
            $ticketId,
            ['park_id'=>$oldPark?:null,'area_id'=>$oldArea?:null],
            ['park_id'=>$parkId?:null,'area_id'=>$areaId?:null,'reason'=>$reason]
        );

        Flash::set('Ubicación histórica del caso actualizada. El cambio quedó registrado en auditoría.','success');
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId);
        exit;
    }
}
