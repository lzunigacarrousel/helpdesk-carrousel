<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth,Database,Http};

final class SessionService
{
    private const COOKIE = 'helpdesk_carrousel_auth';

    public function create(int $userId, ?string $deviceName = null): int
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = (new \DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

        $stmt = Database::pdo()->prepare(
            'INSERT INTO user_sessions(user_id,token_hash,device_name,ip_address,user_agent,last_seen_at,expires_at,created_at)
             VALUES(?,?,?,?,?,NOW(),?,NOW())'
        );
        $stmt->execute([
            $userId,
            $hash,
            $deviceName ?: Http::device(),
            Http::ip(),
            Http::userAgent(),
            $expires,
        ]);

        $id = (int)Database::pdo()->lastInsertId();
        $this->setCookie(self::COOKIE, $token, strtotime($expires));
        return $id;
    }

    public function restoreFromCookie(): ?array
    {
        $token = (string)($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare(
            "SELECT s.id session_id,u.*,r.code role_code,r.name role_name
             FROM user_sessions s
             JOIN users u ON u.id=s.user_id
             JOIN roles r ON r.id=u.role_id
             WHERE s.token_hash=? AND s.revoked_at IS NULL AND s.expires_at>NOW()
               AND u.deleted_at IS NULL AND u.status IN ('ACTIVE','PENDING')
             LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->clearCookie();
            return null;
        }

        $newToken = bin2hex(random_bytes(32));
        Database::pdo()->prepare(
            'UPDATE user_sessions SET token_hash=?,last_seen_at=NOW(),ip_address=?,user_agent=? WHERE id=?'
        )->execute([hash('sha256', $newToken), Http::ip(), Http::userAgent(), (int)$row['session_id']]);
        $this->setCookie(self::COOKIE, $newToken, time() + 86400 * 30);

        return ['session_id' => (int)$row['session_id'], 'user' => $row];
    }

    public function isActive(int $sessionId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM user_sessions WHERE id=? AND user_id=? AND revoked_at IS NULL AND expires_at>NOW()'
        );
        $stmt->execute([$sessionId, $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function revokeCurrent(): void
    {
        $id = Auth::sessionId();
        if ($id) {
            Database::pdo()->prepare('UPDATE user_sessions SET revoked_at=NOW() WHERE id=? AND revoked_at IS NULL')->execute([$id]);
        }
        $this->clearCookie();
    }

    private function setCookie(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => rtrim(APP_PUBLIC_PATH, '/').'/',
            'secure' => APP_CAN_USE_SECURE_FEATURES,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearCookie(): void
    {
        $this->setCookie(self::COOKIE, '', time() - 3600);
        unset($_COOKIE[self::COOKIE]);
    }
}
