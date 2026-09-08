<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Logger;

final class MailService
{
    public function sendOtp(string $to,string $code,int $minutes): void
    {
        $safe = htmlspecialchars($code,ENT_QUOTES,'UTF-8');
        $subject = 'Código de acceso | '.APP_NAME;
        $html = '<h2>'.htmlspecialchars(APP_NAME,ENT_QUOTES,'UTF-8').'</h2>'
              . '<p>Tu código de acceso es:</p>'
              . '<p style="font-size:36px;font-weight:800;letter-spacing:8px">'.$safe.'</p>'
              . '<p>Vence en '.$minutes.' minutos.</p>'
              . '<p>No compartas este código.</p>';
        $text = "Tu código de acceso es {$code}. Vence en {$minutes} minutos.";
        $this->send($to,$subject,$html,$text,'OTP');
    }

    public function sendTicketNotification(string $to,string $subject,string $title,string $message,?string $ticketUrl=null): void
    {
        $safeTitle = htmlspecialchars($title,ENT_QUOTES,'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8'));
        $html = '<h2>'.htmlspecialchars(APP_NAME,ENT_QUOTES,'UTF-8').'</h2>'
              . '<h3>'.$safeTitle.'</h3>'
              . '<p>'.$safeMessage.'</p>';
        $text = $title.PHP_EOL.PHP_EOL.$message;
        if ($ticketUrl) {
            $safeUrl = htmlspecialchars($ticketUrl,ENT_QUOTES,'UTF-8');
            $html .= '<p><a href="'.$safeUrl.'" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px">Ver solicitud</a></p>';
            $html .= '<p style="font-size:12px;color:#666">'.$safeUrl.'</p>';
            $text .= PHP_EOL.PHP_EOL.'Ver solicitud: '.$ticketUrl;
        }
        $html .= '<p style="font-size:12px;color:#666">Mensaje automático de '.htmlspecialchars(APP_NAME,ENT_QUOTES,'UTF-8').'.</p>';
        $this->send($to,$subject,$html,$text,'TICKET');
    }

    private function send(string $to,string $subject,string $html,string $text,string $kind): void
    {
        if (MAIL_MODE==='log') {
            $line='['.date('Y-m-d H:i:s')."] {$kind} {$to} | {$subject} | ".preg_replace('/\s+/',' ',trim($text)).PHP_EOL;
            file_put_contents(STORAGE_PATH.'/logs/mail.log',$line,FILE_APPEND|LOCK_EX);
            return;
        }

        require_once APP_ROOT.'/vendor/phpmailer/phpmailer/src/Exception.php';
        require_once APP_ROOT.'/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once APP_ROOT.'/vendor/phpmailer/phpmailer/src/PHPMailer.php';

        $m=new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $m->isSMTP();
            $m->Host=SMTP_HOST;
            $m->Port=SMTP_PORT;
            $m->SMTPAuth=true;
            $m->Username=SMTP_USERNAME;
            $m->Password=SMTP_PASSWORD;
            $m->SMTPSecure=SMTP_SECURE;
            $m->CharSet='UTF-8';
            $m->setFrom(MAIL_FROM,MAIL_FROM_NAME);
            $m->addAddress($to);
            $m->isHTML(true);
            $m->Subject=$subject;
            $m->Body=$html;
            $m->AltBody=$text;
            $m->send();
        } catch (\Throwable $e) {
            Logger::error($e);
            throw new \RuntimeException('No fue posible enviar la notificación por correo.');
        }
    }
}
