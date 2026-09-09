<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Logger;

final class MailService
{
    public function sendOtp(string $to,string $code,int $minutes): void
    {
        $subject='Código de acceso | '.APP_NAME;
        $safeCode=htmlspecialchars($code,ENT_QUOTES,'UTF-8');
        $content=''
            .'<p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65">Usa este código temporal para ingresar de forma segura. No necesitas contraseña.</p>'
            .'<div style="margin:22px 0;padding:20px 16px;border:1px solid #d0d5dd;border-radius:14px;background:#f8fafc;text-align:center">'
            .'<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#667085;margin-bottom:8px">Tu código</div>'
            .'<div style="font-size:38px;line-height:1;font-weight:850;letter-spacing:9px;color:#173b8f">'.$safeCode.'</div>'
            .'</div>'
            .'<p style="margin:0;color:#667085;font-size:13px;line-height:1.55">Vence en <strong style="color:#101828">'.$minutes.' minutos</strong>. Si tú no solicitaste este acceso, puedes ignorar este mensaje.</p>';
        $html=$this->layout('Acceso seguro','Tu código de acceso',$content,null,null,'Seguridad');
        $text="Tu código de acceso es {$code}. Vence en {$minutes} minutos. No compartas este código.";
        $this->send($to,$subject,$html,$text,'OTP');
    }

    public function sendTicketNotification(
        string $to,
        string $subject,
        string $title,
        string $message,
        ?string $ticketUrl=null,
        string $buttonLabel='Abrir en Helpdesk'
    ): void {
        $safeMessage=nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8'));
        $content='<div style="color:#344054;font-size:15px;line-height:1.65">'.$safeMessage.'</div>';
        $html=$this->layout('Helpdesk Carrousel',$title,$content,$ticketUrl,$buttonLabel,'Actualización');
        $text=$title.PHP_EOL.PHP_EOL.$message;
        if($ticketUrl)$text.=PHP_EOL.PHP_EOL.'Abrir en Helpdesk: '.$ticketUrl;
        $this->send($to,$subject,$html,$text,'TICKET');
    }

    private function layout(string $eyebrow,string $title,string $content,?string $actionUrl,?string $actionLabel,string $badge): string
    {
        $logo=htmlspecialchars(APP_BASE_URL.'/assets/images/logo.png',ENT_QUOTES,'UTF-8');
        $safeEyebrow=htmlspecialchars($eyebrow,ENT_QUOTES,'UTF-8');
        $safeTitle=htmlspecialchars($title,ENT_QUOTES,'UTF-8');
        $safeBadge=htmlspecialchars($badge,ENT_QUOTES,'UTF-8');
        $button='';
        if($actionUrl){
            $safeUrl=htmlspecialchars($actionUrl,ENT_QUOTES,'UTF-8');
            $safeLabel=htmlspecialchars($actionLabel?:'Abrir en Helpdesk',ENT_QUOTES,'UTF-8');
            $button='<div style="margin-top:26px"><a href="'.$safeUrl.'" style="display:inline-block;background:#1677ff;color:#ffffff;text-decoration:none;font-size:14px;font-weight:800;padding:13px 20px;border-radius:10px">'.$safeLabel.'</a></div>';
        }
        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            .'<body style="margin:0;padding:0;background:#eef3f9;font-family:Arial,Helvetica,sans-serif;color:#101828">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#eef3f9;padding:28px 12px"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #d7deea;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(20,45,90,.10)">'
            .'<tr><td style="height:4px;background:linear-gradient(90deg,#2748a0 0%,#773db8 35%,#eb2f7d 67%,#f6a800 100%)"></td></tr>'
            .'<tr><td style="padding:24px 30px 12px">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td><img src="'.$logo.'" alt="Carrousel" width="112" style="display:block;max-width:112px;height:auto"></td><td align="right"><span style="display:inline-block;padding:6px 9px;border-radius:999px;background:#eef4ff;color:#214697;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em">'.$safeBadge.'</span></td></tr></table>'
            .'</td></tr>'
            .'<tr><td style="padding:8px 30px 30px">'
            .'<div style="font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#2748a0;margin-bottom:8px">'.$safeEyebrow.'</div>'
            .'<h1 style="margin:0 0 16px;font-size:25px;line-height:1.25;color:#14213d">'.$safeTitle.'</h1>'
            .$content.$button
            .'<div style="margin-top:28px;padding-top:18px;border-top:1px solid #e4e7ec;color:#667085;font-size:11px;line-height:1.55">Mensaje automático de <strong style="color:#344054">'.htmlspecialchars(APP_NAME,ENT_QUOTES,'UTF-8').'</strong>. No respondas a este correo; utiliza el seguimiento dentro del Helpdesk.</div>'
            .'</td></tr></table>'
            .'<div style="max-width:640px;padding:14px 8px 0;color:#98a2b3;font-size:10px;line-height:1.5;text-align:center">Corporación Carrousel · Soporte y trazabilidad interna</div>'
            .'</td></tr></table></body></html>';
    }

    private function send(string $to,string $subject,string $html,string $text,string $kind): void
    {
        if(MAIL_MODE==='log'){
            $line='['.date('Y-m-d H:i:s')."] {$kind} {$to} | {$subject} | ".preg_replace('/\s+/',' ',trim($text)).PHP_EOL;
            file_put_contents(STORAGE_PATH.'/logs/mail.log',$line,FILE_APPEND|LOCK_EX);
            return;
        }

        $autoload=APP_ROOT.'/vendor/autoload.php';
        if(!is_file($autoload))throw new \RuntimeException('Faltan dependencias PHP. Ejecuta composer install en la carpeta del proyecto.');
        require_once $autoload;

        $m=new \PHPMailer\PHPMailer\PHPMailer(true);
        try{
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
        }catch(\Throwable $e){
            Logger::error($e);
            throw new \RuntimeException('No fue posible enviar la notificación por correo.');
        }
    }
}
