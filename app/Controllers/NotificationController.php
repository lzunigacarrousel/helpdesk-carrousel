<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Csrf,Http};
use App\Services\NotificationService;

final class NotificationController
{
    public function read(): void
    {
        Auth::requireLogin();
        Csrf::verify($_POST['_csrf']??null);
        $id=(int)Http::post('delivery_id');
        if($id>0)(new NotificationService())->markRead($id,(int)Auth::id());
        http_response_code(204);exit;
    }

    public function readAll(): void
    {
        Auth::requireLogin();
        Csrf::verify($_POST['_csrf']??null);
        (new NotificationService())->markAllRead((int)Auth::id());
        http_response_code(204);exit;
    }
}
