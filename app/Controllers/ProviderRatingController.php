<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Database,Flash,Http};
use App\Services\{ProviderRatingService,ScopeService};

final class ProviderRatingController
{
    private const ALLOWED_ROLES=['ADMIN','SEMIADMIN','TECHNICIAN'];

    public function rate(): void
    {
        Auth::requireLogin();
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        $externalUserId=(int)Http::post('external_user_id');
        $grantEventId=(int)Http::post('grant_event_id');
        $score=(int)Http::post('score');
        $comment=trim(Http::post('comment'));

        $this->requireAccess($ticketId);
        $eventId=(new ProviderRatingService(Database::pdo()))->rateCycle(
            $ticketId,$externalUserId,$grantEventId,$score,$comment,(int)Auth::id()
        );

        Audit::log('PROVIDER_RATED','ticket',$ticketId,null,[
            'external_user_id'=>$externalUserId,
            'grant_event_id'=>$grantEventId,
            'rating_event_id'=>$eventId,
            'score'=>$score,
        ]);

        Flash::set('Valoración del proveedor registrada.','success');
        $this->redirect($ticketId);
    }

    public function correct(): void
    {
        Auth::requireLogin();
        Csrf::verify($_POST['_csrf']??null);

        $ticketId=(int)Http::post('ticket_id');
        $externalUserId=(int)Http::post('external_user_id');
        $grantEventId=(int)Http::post('grant_event_id');
        $correctedRatingEventId=(int)Http::post('corrected_rating_event_id');
        $score=(int)Http::post('score');
        $comment=trim(Http::post('comment'));

        $this->requireAccess($ticketId);
        $eventId=(new ProviderRatingService(Database::pdo()))->correctCycle(
            $ticketId,$externalUserId,$grantEventId,$score,$comment,$correctedRatingEventId,(int)Auth::id()
        );

        Audit::log('PROVIDER_RATING_CORRECTED','ticket',$ticketId,null,[
            'external_user_id'=>$externalUserId,
            'grant_event_id'=>$grantEventId,
            'rating_event_id'=>$eventId,
            'corrected_rating_event_id'=>$correctedRatingEventId,
            'score'=>$score,
        ]);

        Flash::set('Corrección de valoración registrada.','success');
        $this->redirect($ticketId);
    }

    private function requireAccess(int $ticketId): void
    {
        if($ticketId<=0)throw new \RuntimeException('Ticket no válido.');
        if(!in_array(Auth::role(),self::ALLOWED_ROLES,true)){
            http_response_code(403);
            throw new \RuntimeException('No tienes permiso para evaluar proveedores.');
        }
        if(!(new ScopeService())->userCanAccessTicket((int)Auth::id(),$ticketId)){
            http_response_code(403);
            throw new \RuntimeException('Ese caso está fuera de tu alcance.');
        }
    }

    private function redirect(int $ticketId): never
    {
        header('Location: '.APP_BASE_URL.'/tickets/view?id='.$ticketId.'#provider-quality');
        exit;
    }
}
