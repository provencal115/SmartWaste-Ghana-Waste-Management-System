<?php
require_once __DIR__ . '/../includes/Model.php';

class UserModel extends Model
{
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function findByEmail(string $email): ?array
    {
        return self::fetchOne(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE LOWER(TRIM(u.email)) = ?',
            [self::normalizeEmail($email)]
        );
    }

    /**
     * True only when the email belongs to a completed account in `users`.
     * Contact message emails (contact_messages table) never count as registered.
     */
    public static function isRegisteredEmail(string $email): bool
    {
        $normalized = self::normalizeEmail($email);
        if ($normalized === '') {
            return false;
        }

        $row = self::fetchOne(
            'SELECT u.id
             FROM users u
             LEFT JOIN residents res ON res.user_id = u.id
             WHERE LOWER(TRIM(u.email)) = ?
               AND (
                    u.is_active = 1
                    OR COALESCE(res.registration_confirmed, 0) = 1
                    OR u.role_id != (SELECT id FROM roles WHERE name = \'resident\' LIMIT 1)
               )
             LIMIT 1',
            [$normalized]
        );

        return $row !== null;
    }

    /** Resident signup started but never confirmed on the review step. */
    public static function findPendingRegistration(string $email): ?array
    {
        return self::fetchOne(
            'SELECT u.*
             FROM users u
             JOIN roles r ON r.id = u.role_id AND r.name = \'resident\'
             LEFT JOIN residents res ON res.user_id = u.id
             WHERE LOWER(TRIM(u.email)) = ?
               AND u.is_active = 0
               AND COALESCE(res.registration_confirmed, 0) = 0
             LIMIT 1',
            [self::normalizeEmail($email)]
        );
    }

    public static function deletePendingRegistration(int $userId): void
    {
        self::query('DELETE FROM users WHERE id = ? AND is_active = 0', [$userId]);
    }

    public static function findById(int $id): ?array
    {
        return self::fetchOne(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, verification_token, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            [$data['role_id'], $data['email'], $data['password_hash'], $data['first_name'], $data['last_name'], $data['phone'] ?? null, $data['verification_token'] ?? null]
        );
        return (int) self::lastInsertId();
    }

    public static function activate(int $userId): void
    {
        self::query('UPDATE users SET is_active = 1, email_verified = 1 WHERE id = ?', [$userId]);
    }

    public static function all(?string $role = null): array
    {
        $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.last_login,
                u.welcome_email_status, u.welcome_email_sent_at, r.name AS role_name
                FROM users u JOIN roles r ON u.role_id = r.id';
        if ($role) {
            return self::fetchAll($sql . ' WHERE r.name = ? ORDER BY u.created_at DESC', [$role]);
        }
        return self::fetchAll($sql . ' ORDER BY u.created_at DESC');
    }

    public static function setWelcomeEmailStatus(int $userId, string $status): void
    {
        if (!in_array($status, ['pending', 'sent', 'failed'], true)) {
            return;
        }
        $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : null;
        try {
            self::query(
                'UPDATE users SET welcome_email_status = ?, welcome_email_sent_at = COALESCE(?, welcome_email_sent_at) WHERE id = ?',
                [$status, $sentAt, $userId]
            );
        } catch (Throwable) {
            // Column may not exist before migration
        }
    }

    public static function setResetToken(string $email, string $token): void
    {
        self::query('UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?', [$token, $email]);
    }

    public static function resetPassword(string $token, string $hash): bool
    {
        return self::execute('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ? AND reset_token_expires > NOW()', [$hash, $token]);
    }
}
