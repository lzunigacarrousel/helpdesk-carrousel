<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Audit,Auth,Database,Http};
use PDO;

final class AuthService
{
    public function findUserByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*,r.code role_code,r.name role_name
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.email=? AND u.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function register(string $email, string $name, ?string $phone, array $organization): array
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $phone = trim((string)$phone);
        $assignmentType = strtoupper(trim((string)($organization['assignment_type'] ?? '')));
        $parkId = (int)($organization['park_id'] ?? 0) ?: null;
        $areaId = (int)($organization['area_id'] ?? 0) ?: null;
        $positionId = (int)($organization['position_id'] ?? 0) ?: null;

        if (!in_array($assignmentType, ['PARK','CORPORATE','OTHER'], true)) {
            throw new \RuntimeException('Selecciona dónde trabajas.');
        }
        if ($assignmentType === 'PARK' && !$parkId) {
            throw new \RuntimeException('Selecciona tu parque o ubicación.');
        }
        if ($assignmentType === 'CORPORATE' && !$areaId) {
            throw new \RuntimeException('Selecciona tu área.');
        }
        if (!$positionId) {
            throw new \RuntimeException('Selecciona tu puesto o función.');
        }

        return Database::transaction(function (PDO $pdo) use ($email, $name, $phone, $assignmentType, $parkId, $areaId, $positionId): array {
            $existing = $this->findUserByEmail($email);
            if ($existing) return $existing;

            $roleId = (int)$pdo->query("SELECT id FROM roles WHERE code='REQUESTER' LIMIT 1")->fetchColumn();
            if ($roleId <= 0) throw new \RuntimeException('No existe el rol base de solicitante.');

            if ($parkId) {
                $q=$pdo->prepare('SELECT COUNT(*) FROM parks WHERE id=? AND is_active=1');
                $q->execute([$parkId]);
                if ((int)$q->fetchColumn() !== 1) throw new \RuntimeException('La ubicación seleccionada no está disponible.');
            }
            if ($areaId) {
                $q=$pdo->prepare('SELECT COUNT(*) FROM areas WHERE id=? AND is_active=1');
                $q->execute([$areaId]);
                if ((int)$q->fetchColumn() !== 1) throw new \RuntimeException('El área seleccionada no está disponible.');
            }
            $q=$pdo->prepare('SELECT COUNT(*) FROM positions WHERE id=? AND is_active=1');
            $q->execute([$positionId]);
            if ((int)$q->fetchColumn() !== 1) throw new \RuntimeException('El puesto seleccionado no está disponible.');

            $stmt = $pdo->prepare(
                "INSERT INTO users(role_id,access_type,email,full_name,phone,status,created_at,updated_at)
                 VALUES(?,'INTERNAL',?,?,?,'PENDING',NOW(),NOW())"
            );
            $stmt->execute([$roleId, $email, $name, $phone ?: null]);
            $id = (int)$pdo->lastInsertId();

            $regionId = null;
            if ($parkId) {
                $q=$pdo->prepare('SELECT region_id FROM parks WHERE id=? LIMIT 1');
                $q->execute([$parkId]);
                $regionId=(int)$q->fetchColumn() ?: null;
            }

            $managerId = $this->resolveManager($pdo, $parkId, $areaId, $regionId);
            $reason = $managerId
                ? 'Autorregistro Helpdesk · responsable asignado automaticamente'
                : 'Autorregistro Helpdesk · responsable pendiente de validar';

            $assign = $pdo->prepare(
                "INSERT INTO user_assignments(user_id,region_id,park_id,area_id,position_id,assignment_type,manager_user_id,status,starts_at,reason,created_at)
                 VALUES(?,?,?,?,?,?,?,'ACTIVE',NOW(),?,NOW())"
            );
            $assign->execute([$id,$regionId,$parkId,$areaId,$positionId,$assignmentType,$managerId,$reason]);

            $pdo->prepare(
                'UPDATE tickets SET requester_user_id=? WHERE requester_user_id IS NULL AND LOWER(requester_email)=?'
            )->execute([$id, $email]);

            Audit::log('USER_REGISTERED', 'user', $id, null, [
                'email'=>$email,
                'status'=>'PENDING',
                'assignment_type'=>$assignmentType,
                'park_id'=>$parkId,
                'area_id'=>$areaId,
                'position_id'=>$positionId,
                'manager_user_id'=>$managerId,
            ]);

            return $this->findUserByEmail($email) ?? throw new \RuntimeException('No fue posible crear el usuario.');
        });
    }

    private function resolveManager(PDO $pdo, ?int $parkId, ?int $areaId, ?int $regionId): ?int
    {
        if ($parkId) {
            $q=$pdo->prepare(
                "SELECT manager_user_id
                 FROM user_assignments
                 WHERE park_id=? AND manager_user_id IS NOT NULL
                   AND status='ACTIVE' AND ends_at IS NULL
                 ORDER BY id DESC LIMIT 1"
            );
            $q->execute([$parkId]);
            $id=(int)$q->fetchColumn();
            if ($id>0) return $id;
        }

        if ($areaId) {
            $q=$pdo->prepare(
                "SELECT manager_user_id
                 FROM user_assignments
                 WHERE area_id=? AND manager_user_id IS NOT NULL
                   AND status='ACTIVE' AND ends_at IS NULL
                 ORDER BY park_id IS NULL DESC,id DESC LIMIT 1"
            );
            $q->execute([$areaId]);
            $id=(int)$q->fetchColumn();
            if ($id>0) return $id;
        }

        if ($regionId) {
            $q=$pdo->prepare(
                "SELECT ua.user_id
                 FROM user_assignments ua
                 JOIN positions p ON p.id=ua.position_id AND p.code='REGIONAL_SUPERVISOR'
                 WHERE ua.region_id=? AND ua.status='ACTIVE' AND ua.ends_at IS NULL
                 ORDER BY ua.id DESC LIMIT 1"
            );
            $q->execute([$regionId]);
            $id=(int)$q->fetchColumn();
            if ($id>0) return $id;
        }

        return null;
    }

    public function sendOtp(string $email): void
    {
        $email = strtolower(trim($email));
        $this->rateLimit($email);
        $user = $this->findUserByEmail($email);
        if (!$user || in_array($user['status'], ['BLOCKED', 'DISABLED'], true)) {
            throw new \RuntimeException('La cuenta no está disponible.');
        }

        $pdo = Database::pdo();
        $pdo->prepare('UPDATE otp_codes SET consumed_at=NOW() WHERE email=? AND consumed_at IS NULL')->execute([$email]);

        $code = (string)random_int(100000, 999999);
        $expiresMinutes = 10;
        $expiresAt = (new \DateTimeImmutable('+10 minutes'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            "INSERT INTO otp_codes(user_id,email,purpose,code_hash,expires_at,max_attempts,request_ip,created_at)
             VALUES(?,?,'LOGIN',?,?,5,?,NOW())"
        );
        $stmt->execute([(int)$user['id'], $email, password_hash($code, PASSWORD_DEFAULT), $expiresAt, Http::ip()]);

        Audit::log('OTP_REQUESTED', 'user', (int)$user['id'], null, null, ['email' => $email]);
        try {
            (new MailService())->sendOtp($email, $code, $expiresMinutes);
        } catch (\Throwable $e) {
            $pdo->prepare('UPDATE otp_codes SET consumed_at=NOW() WHERE id=?')->execute([(int)$pdo->lastInsertId()]);
            throw $e;
        }
        $_SESSION['otp_email'] = $email;
    }

    public function verify(string $email, string $code): array
    {
        $email = strtolower(trim($email));
        $result = Database::transaction(function (PDO $pdo) use ($email, $code): array {
            $stmt = $pdo->prepare(
                "SELECT * FROM otp_codes
                 WHERE email=? AND purpose='LOGIN' AND consumed_at IS NULL
                 ORDER BY id DESC LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$email]);
            $otp = $stmt->fetch();
            if (!$otp) return ['error' => 'Solicita un código nuevo.'];
            if (strtotime((string)$otp['expires_at']) < time()) {
                $pdo->prepare('UPDATE otp_codes SET consumed_at=NOW() WHERE id=?')->execute([(int)$otp['id']]);
                return ['error' => 'El código venció.'];
            }
            if (!password_verify($code, (string)$otp['code_hash'])) {
                $attempts = (int)$otp['attempts'] + 1;
                $consume = $attempts >= (int)$otp['max_attempts'];
                $pdo->prepare('UPDATE otp_codes SET attempts=?,consumed_at=? WHERE id=?')
                    ->execute([$attempts, $consume ? date('Y-m-d H:i:s') : null, (int)$otp['id']]);
                return ['error' => $consume ? 'Demasiados intentos. Solicita un código nuevo.' : 'Código incorrecto.'];
            }

            $pdo->prepare('UPDATE otp_codes SET consumed_at=NOW() WHERE id=?')->execute([(int)$otp['id']]);
            $pdo->prepare(
                'UPDATE users SET email_verified_at=COALESCE(email_verified_at,NOW()),last_login_at=NOW(),updated_at=NOW() WHERE id=?'
            )->execute([(int)$otp['user_id']]);

            $user = $this->findUserByEmail($email);
            if (!$user) return ['error' => 'Usuario no disponible.'];
            $sessionId = (new SessionService())->create((int)$user['id']);
            return ['user' => $user, 'session_id' => $sessionId];
        });

        if (isset($result['error'])) {
            Audit::log('OTP_FAILED', null, null, null, null, ['email' => $email]);
            throw new \RuntimeException($result['error']);
        }

        Auth::login($result['user'], (int)$result['session_id']);
        Audit::log('LOGIN_SUCCESS', 'user', (int)$result['user']['id']);
        unset($_SESSION['otp_email']);
        return $result['user'];
    }

    private function rateLimit(string $email): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE email=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
        $stmt->execute([$email]);
        $ipStmt = $pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE request_ip=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
        $ipStmt->execute([Http::ip()]);
        if ((int)$stmt->fetchColumn() >= 5 || (int)$ipStmt->fetchColumn() >= 20) {
            throw new \RuntimeException('Límite de códigos alcanzado. Espera 15 minutos.');
        }
    }
}
