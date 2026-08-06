<?php

class ContactMessageModel extends Model
{
    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO contact_messages (full_name, email, phone, subject, message, status, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['full_name'],
                $data['email'],
                $data['phone'] ?: null,
                $data['subject'],
                $data['message'],
                'new',
                $data['ip_address'] ?? null,
            ]
        );

        return (int) self::lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(array $filters = []): array
    {
        $sql = 'SELECT cm.*,
                (SELECT COUNT(*) FROM contact_message_replies r WHERE r.message_id = cm.id) AS reply_count
                FROM contact_messages cm WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['new', 'read', 'replied'], true)) {
            $sql .= ' AND cm.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(cm.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(cm.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (cm.full_name LIKE ? OR cm.email LIKE ? OR cm.subject LIKE ? OR cm.message LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $sql .= ' ORDER BY cm.created_at DESC';

        return self::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT * FROM contact_messages WHERE id = ?', [$id]);
    }

    public static function findWithReplies(int $id): ?array
    {
        $message = self::find($id);
        if (!$message) {
            return null;
        }

        $message['replies'] = self::fetchAll(
            'SELECT r.*, CONCAT(u.first_name, " ", u.last_name) AS admin_name
             FROM contact_message_replies r
             LEFT JOIN users u ON u.id = r.admin_user_id
             WHERE r.message_id = ?
             ORDER BY r.sent_at ASC',
            [$id]
        );

        return $message;
    }

    public static function markAsRead(int $id): bool
    {
        $msg = self::find($id);
        if (!$msg) {
            return false;
        }
        if ($msg['status'] === 'new') {
            return self::execute(
                "UPDATE contact_messages SET status = 'read', read_at = NOW() WHERE id = ?",
                [$id]
            );
        }
        if (empty($msg['read_at'])) {
            return self::execute('UPDATE contact_messages SET read_at = NOW() WHERE id = ?', [$id]);
        }
        return true;
    }

    public static function markAsReplied(int $id): bool
    {
        return self::execute(
            "UPDATE contact_messages SET status = 'replied' WHERE id = ?",
            [$id]
        );
    }

    public static function addReply(int $messageId, ?int $adminUserId, string $body, bool $emailSent): int
    {
        self::query(
            'INSERT INTO contact_message_replies (message_id, admin_user_id, reply_body, email_sent) VALUES (?, ?, ?, ?)',
            [$messageId, $adminUserId, $body, $emailSent ? 1 : 0]
        );

        self::markAsReplied($messageId);

        return (int) self::lastInsertId();
    }

    public static function delete(int $id): bool
    {
        return self::execute('DELETE FROM contact_messages WHERE id = ?', [$id]);
    }

    /** @param int[] $ids */
    public static function deleteMany(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        self::query("DELETE FROM contact_messages WHERE id IN ($placeholders)", $ids);

        return count($ids);
    }

    public static function unreadCount(): int
    {
        try {
            return (int) (self::fetchOne("SELECT COUNT(*) AS c FROM contact_messages WHERE status = 'new'")['c'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /** @return array{total: int, unread: int, today: int} */
    public static function stats(): array
    {
        try {
            return [
                'total'  => (int) (self::fetchOne('SELECT COUNT(*) AS c FROM contact_messages')['c'] ?? 0),
                'unread' => self::unreadCount(),
                'today'  => (int) (self::fetchOne('SELECT COUNT(*) AS c FROM contact_messages WHERE DATE(created_at) = CURDATE()')['c'] ?? 0),
            ];
        } catch (Throwable) {
            return ['total' => 0, 'unread' => 0, 'today' => 0];
        }
    }

    public static function tableExists(): bool
    {
        try {
            self::fetchOne('SELECT 1 FROM contact_messages LIMIT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
