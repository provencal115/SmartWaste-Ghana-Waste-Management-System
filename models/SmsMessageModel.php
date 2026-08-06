<?php

class SmsMessageModel extends Model
{
    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO sms_messages (user_id, phone, message, message_type, provider, status, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'] ?? null,
                $data['phone'],
                $data['message'],
                $data['message_type'] ?? 'general',
                $data['provider'] ?? 'simulate',
                $data['status'] ?? 'pending',
                !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            ]
        );

        return (int) self::lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?string $providerMessageId = null, ?string $error = null): void
    {
        self::query(
            'UPDATE sms_messages SET status = ?, provider_message_id = COALESCE(?, provider_message_id),
             error_message = ?, sent_at = IF(? IN (\'sent\', \'simulated\'), NOW(), sent_at) WHERE id = ?',
            [$status, $providerMessageId, $error, $status, $id]
        );
    }

    public static function find(int $id): ?array
    {
        $row = self::fetchOne(
            'SELECT s.*, u.first_name, u.last_name, u.email
             FROM sms_messages s
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?',
            [$id]
        );
        if ($row && !empty($row['metadata'])) {
            $row['metadata'] = json_decode($row['metadata'], true);
        }
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(array $filters = []): array
    {
        $sql = 'SELECT s.*, u.first_name, u.last_name, u.email
                FROM sms_messages s
                LEFT JOIN users u ON u.id = s.user_id WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'sent', 'failed', 'simulated'], true)) {
            $sql .= ' AND s.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $sql .= ' AND s.message_type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (s.phone LIKE ? OR s.message LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(s.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(s.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY s.created_at DESC';

        return self::fetchAll($sql, $params);
    }

    /** @return array{total: int, sent: int, failed: int, today: int} */
    public static function stats(): array
    {
        try {
            return [
                'total'  => (int) (self::fetchOne('SELECT COUNT(*) AS c FROM sms_messages')['c'] ?? 0),
                'sent'   => (int) (self::fetchOne("SELECT COUNT(*) AS c FROM sms_messages WHERE status IN ('sent','simulated')")['c'] ?? 0),
                'failed' => (int) (self::fetchOne("SELECT COUNT(*) AS c FROM sms_messages WHERE status = 'failed'")['c'] ?? 0),
                'today'  => (int) (self::fetchOne('SELECT COUNT(*) AS c FROM sms_messages WHERE DATE(created_at) = CURDATE()')['c'] ?? 0),
            ];
        } catch (Throwable) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'today' => 0];
        }
    }

    public static function tableExists(): bool
    {
        try {
            self::fetchOne('SELECT 1 FROM sms_messages LIMIT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
