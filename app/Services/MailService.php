<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Logger;

final class MailService
{
    public const RESULT_SENT='SENT';
    public const RESULT_LOGGED='LOGGED';
    private const LOGO_CID='carrousel-logo';

    public static function mode(): string
    {
        return MAIL_MODE==='smtp'?'smtp':'log';
    }

    public static function configurationHealth(): array
    {
        $issues=[];$warnings=[];
        if(!in_array(MAIL_MODE,['log','smtp'],true))$issues[]='El modo de correo no es válido.';
        if(MAIL_MODE==='smtp'){
            if(SMTP_HOST==='')$issues[]='Falta el servidor SMTP.';
            if(SMTP_PORT<1||SMTP_PORT>65535)$issues[]='El puerto SMTP no es válido.';
            if(!in_array(SMTP_SECURE,['','none','tls','starttls','ssl','smtps'],true))$issues[]='El tipo de seguridad SMTP no es válido.';
            if(!filter_var(MAIL_FROM,FILTER_VALIDATE_EMAIL))$issues[]='El correo remitente no es válido.';
            if(SMTP_USERNAME!==''&&SMTP_PASSWORD==='')$issues[]='La cuenta SMTP tiene usuario pero no contraseña configurada.';
        }
        if(!APP_CANONICAL_CONFIGURED)$warnings[]='La URL estable del Helpdesk no está configurada; los botones del correo usarán la dirección desde la que se abrió la aplicación.';
        if(!is_file(self::logoPath()))$warnings[]='No encontramos el logo local para incrustarlo en los correos; se usará la URL pública como respaldo.';
        return [
            'mode'=>self::mode(),
            'ready'=>$issues===[],
            'issues'=>$issues,
            'warnings'=>$warnings,
            'canonical_url'=>APP_CANONICAL_URL,
            'from'=>MAIL_FROM,
            'from_name'=>MAIL_FROM_NAME,
            'support_delivery'=>'Integrantes activos de Equipo de soporte',
        ];
    }

    public function sendOtp(string $to,string $code,int $minutes): string
    {
        $subject='Código de acceso | '.APP_NAME;
        $safeCode=htmlspecialchars($code,ENT_QUOTES,'UTF-8');
        $content=''
            .'<p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65">Usa este código temporal para ingresar. No necesitas contraseña.</p>'
            .'<div style="margin:22px 0;padding:20px 16px;border:1px solid #d0d5dd;border-radius:14px;background:#f8fafc;text-align:center">'
            .'<div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#667085;margin-bottom:8px">Tu código</div>'
            .'<div style="font-size:38px;line-height:1;font-weight:850;letter-spacing:9px;color:#173b8f">'.$safeCode.'</div>'
            .'</div>'
            .'<p style="margin:0;color:#667085;font-size:13px;line-height:1.55">Vence en <strong style="color:#101828">'.$minutes.' minutos</strong>. Si no solicitaste este acceso, puedes ignorar este mensaje.</p>';
        $html=$this->layout('Acceso','Tu código de acceso',$content,null,null,'Código temporal para ingresar al Helpdesk.');
        $text="Tu código de acceso es {$code}. Vence en {$minutes} minutos. No compartas este código.";
        return $this->send($to,$subject,$html,$text,'OTP');
    }

    public function sendTicketNotification(
        string $to,
        string $subject,
        string $title,
        string $message,
        ?string $ticketUrl=null,
        string $buttonLabel='Ver solicitud'
    ): string {
        $safeMessage=nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8'));
        $content='<div style="color:#344054;font-size:15px;line-height:1.65">'.$safeMessage.'</div>';
        $html=$this->layout('Actualización',$title,$content,$ticketUrl,$buttonLabel,$message);
        $text=$title.PHP_EOL.PHP_EOL.$message;
        if($ticketUrl)$text.=PHP_EOL.PHP_EOL.'Abre el Helpdesk para consultar el detalle.';
        return $this->send($to,$subject,$html,$text,'TICKET');
    }

    private function layout(string $badge,string $title,string $content,?string $actionUrl,?string $actionLabel,string $preheader=''): string
    {
        $logoSource=is_file(self::logoPath())?'cid:'.self::LOGO_CID:APP_CANONICAL_URL.'/assets/images/logo.png';
        $logo=htmlspecialchars($logoSource,ENT_QUOTES,'UTF-8');
        $safeTitle=htmlspecialchars($title,ENT_QUOTES,'UTF-8');
        $safeBadge=htmlspecialchars($badge,ENT_QUOTES,'UTF-8');
        $safePreheader=htmlspecialchars(mb_strimwidth(trim($preheader),0,120,'…'),ENT_QUOTES,'UTF-8');
        $button='';
        if($actionUrl){
            $safeUrl=htmlspecialchars($actionUrl,ENT_QUOTES,'UTF-8');
            $safeLabel=htmlspecialchars($actionLabel?:'Ver solicitud',ENT_QUOTES,'UTF-8');
            $button='<div style="margin-top:24px"><a href="'.$safeUrl.'" style="display:inline-block;background:#1677ff;color:#ffffff;text-decoration:none;font-size:14px;font-weight:800;padding:13px 20px;border-radius:10px">'.$safeLabel.'</a></div>';
        }
        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            .'<body style="margin:0;padding:0;background:#eef3f9;font-family:Arial,Helvetica,sans-serif;color:#101828">'
            .'<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">'.$safePreheader.'</div>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#eef3f9;padding:28px 12px"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #d7deea;border-radius:18px;overflow:hidden">'
            .'<tr><td style="height:4px;background:#2748a0;background:linear-gradient(90deg,#2748a0 0%,#773db8 35%,#eb2f7d 67%,#f6a800 100%)"></td></tr>'
            .'<tr><td style="padding:24px 30px 10px">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td><img src="'.$logo.'" alt="Carrousel" width="108" style="display:block;max-width:108px;height:auto"></td><td align="right"><span style="display:inline-block;padding:6px 9px;border-radius:999px;background:#eef4ff;color:#214697;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em">'.$safeBadge.'</span></td></tr></table>'
            .'</td></tr>'
            .'<tr><td style="padding:10px 30px 30px">'
            .'<h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;color:#14213d">'.$safeTitle.'</h1>'
            .$content.$button
            .'<div style="margin-top:26px;padding-top:16px;border-top:1px solid #e4e7ec;color:#667085;font-size:11px;line-height:1.55">Mensaje automático del Helpdesk. Puedes continuar el seguimiento desde el botón de este mensaje.</div>'
            .'</td></tr></table>'
            .'<div style="max-width:620px;padding:13px 8px 0;color:#98a2b3;font-size:10px;line-height:1.5;text-align:center">Corporación Carrousel</div>'
            .'</td></tr></table></body></html>';
    }

    private function send(string $to,string $subject,string $html,string $text,string $kind): string
    {
        $to=strtolower(trim($to));
        $subject=preg_replace('/[\r\n]+/',' ',trim($subject))?:APP_NAME;
        if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('El destinatario del correo no es válido.');

        if(MAIL_MODE==='log'){
            $dir=STORAGE_PATH.'/logs';
            if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new \RuntimeException('No fue posible registrar el correo de prueba.');
            $line='['.date('Y-m-d H:i:s')."] {$kind} {$to} | {$subject} | ".preg_replace('/\s+/',' ',trim($text)).PHP_EOL;
            if(file_put_contents($dir.'/mail.log',$line,FILE_APPEND|LOCK_EX)===false)throw new \RuntimeException('No fue posible registrar el correo de prueba.');
            return self::RESULT_LOGGED;
        }

        if(MAIL_MODE!=='smtp')throw new \RuntimeException('El modo de correo configurado no es válido.');
        $health=self::configurationHealth();
        if(!$health['ready'])throw new \RuntimeException('La configuración de correo no está completa.');

        $autoload=APP_ROOT.'/vendor/autoload.php';
        if(!is_file($autoload))throw new \RuntimeException('Faltan dependencias de correo. Ejecuta composer install en la carpeta del proyecto.');
        require_once $autoload;

        $m=new \PHPMailer\PHPMailer\PHPMailer(true);
        try{
            $m->isSMTP();
            $m->Host=SMTP_HOST;
            $m->Port=SMTP_PORT;
            $m->SMTPAuth=SMTP_USERNAME!=='';
            if($m->SMTPAuth){$m->Username=SMTP_USERNAME;$m->Password=SMTP_PASSWORD;}
            $secure=SMTP_SECURE;
            if(in_array($secure,['tls','starttls'],true))$m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            elseif(in_array($secure,['ssl','smtps'],true))$m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            else{$m->SMTPSecure='';$m->SMTPAutoTLS=false;}
            $m->Timeout=15;
            $m->Timelimit=20;
            $m->SMTPKeepAlive=false;
            $m->CharSet='UTF-8';
            $m->Encoding='base64';
            $m->setFrom(MAIL_FROM,MAIL_FROM_NAME);
            $m->addAddress($to);
            $logoPath=self::logoPath();
            if(is_file($logoPath))$m->addEmbeddedImage($logoPath,self::LOGO_CID,'carrousel-logo.png','base64','image/png');
            $m->isHTML(true);
            $m->Subject=$subject;
            $m->Body=$html;
            $m->AltBody=$text;
            $m->send();
            return self::RESULT_SENT;
        }catch(\Throwable $e){
            Logger::error($e);
            throw new \RuntimeException('No fue posible enviar la notificación por correo.');
        }
    }

    private static function logoPath(): string
    {
        return APP_ROOT.'/public/assets/images/logo.png';
    }
}
