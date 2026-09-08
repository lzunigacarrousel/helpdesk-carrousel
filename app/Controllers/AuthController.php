<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Csrf,Database,Flash,Http,View};
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

    public function requestOtp(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $email = strtolower(Http::post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Correo inválido.');
        $_SESSION['login_email'] = $email;
        $svc = new AuthService();
        $user = $svc->findUserByEmail($email);
        if (!$user) {
            $_SESSION['register_email'] = $email;
            header('Location: '.APP_BASE_URL.'/register');
            exit;
        }
        $svc->sendOtp($email);
        header('Location: '.APP_BASE_URL.'/otp');
        exit;
    }

    public function register(): void
    {
        if (Auth::check()) { header('Location: '.APP_BASE_URL.'/dashboard'); exit; }
        $email = (string)($_SESSION['register_email'] ?? $_SESSION['login_email'] ?? '');
        if ($email === '') { header('Location: '.APP_BASE_URL.'/login'); exit; }

        $pdo=Database::pdo();
        $parks=$pdo->query("SELECT id,name FROM parks WHERE is_active=1 ORDER BY name")->fetchAll();
        $areas=$pdo->query("SELECT id,name FROM areas WHERE is_active=1 ORDER BY name")->fetchAll();
        $positions=$pdo->query("SELECT id,code,name FROM positions WHERE is_active=1 ORDER BY sort_order,name")->fetchAll();

        View::render('auth/register', [
            'email'=>$email,
            'parks'=>$parks,
            'areas'=>$areas,
            'positions'=>$positions,
            'flash'=>Flash::pull(),
        ]);
    }

    public function createUser(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $expected = strtolower((string)($_SESSION['register_email'] ?? $_SESSION['login_email'] ?? ''));
        $email = strtolower(Http::post('email'));
        if ($expected === '' || !hash_equals($expected, $email)) throw new \RuntimeException('El correo del registro no coincide con la solicitud de acceso.');
        $name = trim(Http::post('name'));
        $phone = trim(Http::post('phone'));
        if (mb_strlen($name) < 3) throw new \RuntimeException('Ingresa tu nombre completo.');
        if ($phone === '') throw new \RuntimeException('Ingresa tu teléfono.');

        $organization=[
            'assignment_type'=>Http::post('assignment_type'),
            'park_id'=>Http::post('park_id'),
            'area_id'=>Http::post('area_id'),
            'position_id'=>Http::post('position_id'),
        ];

        $svc = new AuthService();
        $svc->register($email, $name, $phone, $organization);
        $svc->sendOtp($email);
        unset($_SESSION['register_email']);
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
        (new SessionService())->revokeCurrent();
        Auth::logoutRuntime();
        header('Location: '.APP_BASE_URL.'/');
        exit;
    }
}
