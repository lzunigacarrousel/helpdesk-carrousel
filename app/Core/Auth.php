<?php
declare(strict_types=1);
namespace App\Core;

use App\Services\SessionService;

final class Auth
{
    private static ?array $user = null;
    private static array $permissions = [];
    private static ?bool $supportOperator = null;

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

    public static function isSupportOperator(): bool
    {
        if (!self::check() || (self::$user['access_type'] ?? 'INTERNAL') === 'EXTERNAL') return false;
        if (self::role() === 'ADMIN') return true;
        if (!in_array(self::role(), ['SEMIADMIN', 'TECHNICIAN'], true)) return false;
        if (self::$supportOperator !== null) return self::$supportOperator;

        try {
            $q = Database::pdo()->prepare(
                "SELECT COUNT(*)
                 FROM support_team_members stm
                 JOIN support_teams st ON st.id=stm.team_id AND st.is_active=1
                 WHERE stm.user_id=? AND stm.is_active=1 AND stm.ended_at IS NULL"
            );
            $q->execute([(int)self::id()]);
            self::$supportOperator = (int)$q->fetchColumn() > 0;
        } catch (\Throwable) {
            // Compatibilidad con instalaciones anteriores a la membresía explícita.
            self::$supportOperator = in_array(self::role(), ['SEMIADMIN', 'TECHNICIAN'], true);
        }
        return self::$supportOperator;
    }

    public static function isManagementViewer(): bool
    {
        return self::check()
            && (self::$user['access_type'] ?? 'INTERNAL') !== 'EXTERNAL'
            && in_array(self::role(), ['MANAGEMENT', 'SUPERVISOR'], true)
            && self::can('management.view');
    }

    public static function profileLabel(): string
    {
        if (!self::check()) return 'Usuario';
        if ((self::$user['access_type'] ?? 'INTERNAL') === 'EXTERNAL') return 'Colaborador';
        return match (self::role()) {
            'ADMIN' => 'Administrador',
            'SEMIADMIN' => 'Semiadministrador',
            'TECHNICIAN' => 'Técnico',
            'MANAGEMENT' => 'Gerencia',
            'SUPERVISOR' => 'Supervisor',
            default => 'Usuario',
        };
    }

    public static function login(array $user, int $sessionId): void
    {
        session_regenerate_id(true);
        self::$user = $user;
        self::$supportOperator = null;
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_session_id'] = $sessionId;
        Csrf::rotate();
        self::loadPermissions();
    }

    public static function logoutRuntime(): void
    {
        self::$user = null;
        self::$permissions = [];
        self::$supportOperator = null;
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
        if (self::can($permission)) return;

        Flash::set('Esa sección es solo para el equipo autorizado. Te llevamos a tu inicio.', 'info');
        header('Location: '.APP_BASE_URL.'/dashboard');
        exit;
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
        self::$supportOperator = null;
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
        self::$supportOperator = null;
        unset($_SESSION['auth_user_id'], $_SESSION['auth_session_id']);
    }
}
