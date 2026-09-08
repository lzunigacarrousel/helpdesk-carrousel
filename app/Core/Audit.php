<?php
declare(strict_types=1);
namespace App\Core;

final class Audit
{
    public static function log(
        string $event,
        ?string $entity = null,
        int|string|null $id = null,
        mixed $old = null,
        mixed $new = null,
        array $meta = [],
        ?string $source = null
    ): void {
        try {
            $user = Auth::user();
            $resolvedSource = $source ?: (Auth::check() ? 'AUTHENTICATED_WEB' : 'PUBLIC_WEB');
            $entityType = $entity ?: 'system';

            $stmt = Database::pdo()->prepare(
                'INSERT INTO audit_logs(
                    actor_user_id,actor_email,action,entity_type,entity_id,source,
                    ip_address,user_agent,old_values,new_values,metadata_json,created_at
                 ) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $stmt->execute([
                Auth::id(),
                $user['email'] ?? null,
                substr($event, 0, 100),
                substr($entityType, 0, 80),
                $id === null ? null : (string)$id,
                $resolvedSource,
                Http::ip(),
                Http::userAgent(),
                self::encode($old),
                self::encode($new),
                self::encode($meta),
            ]);
        } catch (\Throwable $e) {
            Logger::error($e);
        }
    }

    private static function encode(mixed $value): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
