<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Audit,Auth,Csrf,Flash,Http,View};
use App\Services\{AuthService,SessionService};

final class AuthController
{
    public function home(): void
    {
        if (Auth::check()) {
            header('Location: '.APP_BASE_URL.'/dashboard');
            exit;
        }
        View::render('auth/login', [
            'email' => $_SESSION['login_email'] ?? '',
            'flash' => Flash::pull(),
        ]);
    }

    public function legacyRegister(): void
    {
        Flash::set('El registro de cuentas se gestiona desde Administración.','info');
        header('Location: '.APP_BASE_URL.'/login');
        exit;
    }

    public function requestOtp(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $email = strtolower(Http::post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Correo inválido.');
        $_SESSION['login_email'] = $email;
        $svc = new AuthService();
        $user = $svc->findUserByEmail($email);
        if (!$user) {
            Flash::set('Este correo todavía no tiene acceso al Helpdesk. Puedes enviar una solicitud sin iniciar sesión. Si necesitas acceso al sistema, comunícate con Sistemas.','info');
            header('Location: '.APP_BASE_URL.'/login');
            exit;
        }
        $svc->sendOtp($email);
        header('Location: '.APP_BASE_URL.'/otp');
        exit;
    }

    public function otp(): void
    {
        $email = (string)($_SESSION['otp_email'] ?? $_SESSION['login_email'] ?? '');
        if ($email === '') { header('Location: '.APP_BASE_URL.'/login'); exit; }
        View::render('auth/otp', ['email' => $email, 'flash' => Flash::pull()]);
    }

    public function verify(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $expected = strtolower((string)($_SESSION['otp_email'] ?? ''));
        $email = strtolower(Http::post('email'));
        if ($expected === '' || !hash_equals($expected, $email)) throw new \RuntimeException('La verificación no corresponde al correo solicitado.');
        $code = preg_replace('/\D/', '', Http::post('code'));
        (new AuthService())->verify($email, $code);
        unset($_SESSION['login_email']);
        $return = (string)($_SESSION['return_after_login'] ?? '');
        unset($_SESSION['return_after_login']);
        if ($return !== '' && str_starts_with($return, APP_PUBLIC_PATH.'/')) {
            header('Location: '.$return);
        } else {
            header('Location: '.APP_BASE_URL.'/dashboard');
        }
        exit;
    }

    public function resend(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $expected = strtolower((string)($_SESSION['otp_email'] ?? ''));
        $email = strtolower(Http::post('email'));
        if ($expected === '' || !hash_equals($expected, $email)) throw new \RuntimeException('La solicitud no corresponde al correo verificado.');
        (new AuthService())->sendOtp($email);
        header('Location: '.APP_BASE_URL.'/otp');
        exit;
    }

    public function logout(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        Audit::log('LOGOUT','user',Auth::id());
        (new SessionService())->revokeCurrent();
        Auth::logoutRuntime();
        header('Location: '.APP_BASE_URL.'/');
        exit;
    }
}
