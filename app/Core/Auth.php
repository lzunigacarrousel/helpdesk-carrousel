<?php
declare(strict_types=1);
namespace App\Core;

use App\Services\SessionService;

final class Auth
{
    private static ?array $user = null;
    private static array $permissions = [];

    public static function bootstrap(): void
    {
        try {
            $uid = (int)($_SESSION['auth_user_id'] ?? 0);
            $sid = (int)($_SESSION['auth_session_id'] ?? 0);
            $sessions = new SessionService();

            if ($uid && $sid && $sessions->isActive($sid, $uid)) {
                $user = self::fetch($uid);
                if ($user && in_array($user['status'], ['ACTIVE', 'PENDING'], true)) {
                    self::$user = $user;
                    self::loadPermissions();
                    return;
                }
            }

            self::clear();
            $restored = $sessions->restoreFromCookie();
            if ($restored) {
                self::$user = $restored['user'];
                $_SESSION['auth_user_id'] = (int)$restored['user']['id'];
                $_SESSION['auth_session_id'] = (int)$restored['session_id'];
                self::loadPermissions();
                Audit::log('SESSION_RESTORED', 'user_session', (int)$restored['session_id']);
            }
        } catch (\Throwable $e) {
            Logger::error($e);
            self::clear();
        }
    }

    public static function check(): bool { return self::$user !== null; }
    public static function user(): ?array { return self::$user; }
    public static function id(): ?int { return self::$user ? (int)self::$user['id'] : null; }
    public static function role(): ?string { return self::$user['role_code'] ?? null; }
    public static function sessionId(): ?int
    {
        $id = (int)($_SESSION['auth_session_id'] ?? 0);
        return $id ?: null;
    }

    public static function can(string $permission): bool
    {
        return self::check() && (self::role() === 'ADMIN' || (self::$permissions[$permission] ?? false));
    }

    public static function login(array $user, int $sessionId): void
    {
        session_regenerate_id(true);
        self::$user = $user;
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_session_id'] = $sessionId;
        Csrf::rotate();
        self::loadPermissions();
    }

    public static function logoutRuntime(): void
    {
        self::$user = null;
        self::$permissions = [];
        self::clear();
        session_regenerate_id(true);
        Csrf::rotate();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['return_after_login'] = (string)($_SERVER['REQUEST_URI'] ?? '');
            header('Location: '.APP_BASE_URL.'/login');
            exit;
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::can($permission)) {
            http_response_code(403);
            echo '403 - Sin permiso';
            exit;
        }
    }

    private static function fetch(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*,r.code role_code,r.name role_name
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id=? AND u.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private static function loadPermissions(): void
    {
        self::$permissions = [];
        if (!self::$user) return;
        $stmt = Database::pdo()->prepare(
            "SELECT p.code,
                    CASE WHEN uo.effect='DENY' THEN 0
                         WHEN rp.permission_id IS NOT NULL THEN 1
                         ELSE 0 END allowed
             FROM permissions p
             LEFT JOIN role_permissions rp ON rp.permission_id=p.id AND rp.role_id=?
             LEFT JOIN user_permission_overrides uo ON uo.permission_id=p.id AND uo.user_id=?"
        );
        $stmt->execute([(int)self::$user['role_id'], (int)self::$user['id']]);
        foreach ($stmt->fetchAll() as $row) self::$permissions[$row['code']] = (bool)$row['allowed'];
    }

    private static function clear(): void
    {
        unset($_SESSION['auth_user_id'], $_SESSION['auth_session_id']);
    }
}
