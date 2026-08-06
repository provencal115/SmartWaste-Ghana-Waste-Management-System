<?php

class ChatbotModel extends Model
{
    public static function allKnowledge(bool $enabledOnly = false): array
    {
        $sql = 'SELECT * FROM chatbot_knowledge';
        if ($enabledOnly) {
            $sql .= ' WHERE is_enabled = 1';
        }
        $sql .= ' ORDER BY priority DESC, title ASC';
        return self::fetchAll($sql);
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT * FROM chatbot_knowledge WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['category'],
                $data['title'],
                $data['keywords'],
                $data['response'],
                (int)($data['is_enabled'] ?? 1),
                (int)($data['is_suggestion'] ?? 0),
                (int)($data['priority'] ?? 0),
            ]
        );
        return (int)self::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::query(
            'UPDATE chatbot_knowledge SET category = ?, title = ?, keywords = ?, response = ?,
             is_enabled = ?, is_suggestion = ?, priority = ? WHERE id = ?',
            [
                $data['category'],
                $data['title'],
                $data['keywords'],
                $data['response'],
                (int)($data['is_enabled'] ?? 1),
                (int)($data['is_suggestion'] ?? 0),
                (int)($data['priority'] ?? 0),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        self::query('DELETE FROM chatbot_knowledge WHERE id = ?', [$id]);
    }

    public static function toggle(int $id, bool $enabled): void
    {
        self::query('UPDATE chatbot_knowledge SET is_enabled = ? WHERE id = ?', [(int)$enabled, $id]);
    }

    public static function incrementUse(int $id): void
    {
        self::query('UPDATE chatbot_knowledge SET use_count = use_count + 1 WHERE id = ?', [$id]);
    }

    public static function suggestions(): array
    {
        return self::fetchAll(
            'SELECT id, title, keywords FROM chatbot_knowledge
             WHERE is_enabled = 1 AND is_suggestion = 1
             ORDER BY priority DESC, title ASC'
        );
    }

    public static function logMessage(
        string $sessionId,
        ?int $userId,
        string $userMessage,
        string $botResponse,
        ?int $knowledgeId,
        ?string $category
    ): int {
        self::query(
            'INSERT INTO chatbot_messages (session_id, user_id, user_message, bot_response, knowledge_id, matched_category)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$sessionId, $userId, $userMessage, $botResponse, $knowledgeId, $category]
        );
        return (int)self::lastInsertId();
    }

    public static function sessionHistory(string $sessionId, int $limit = 50): array
    {
        return self::fetchAll(
            'SELECT user_message, bot_response, created_at FROM chatbot_messages
             WHERE session_id = ? ORDER BY id ASC LIMIT ?',
            [$sessionId, $limit]
        );
    }

    public static function trackFaq(string $userQuestion, ?int $knowledgeId): void
    {
        $normalized = self::normalizeQuestion($userQuestion);
        if ($normalized === '') {
            return;
        }

        $existing = self::fetchOne(
            'SELECT id, hit_count FROM chatbot_faq WHERE user_question = ? LIMIT 1',
            [$normalized]
        );

        if ($existing) {
            self::query(
                'UPDATE chatbot_faq SET hit_count = hit_count + 1, knowledge_id = COALESCE(?, knowledge_id), last_asked_at = NOW() WHERE id = ?',
                [$knowledgeId, $existing['id']]
            );
            return;
        }

        self::query(
            'INSERT INTO chatbot_faq (user_question, knowledge_id, hit_count) VALUES (?, ?, 1)',
            [$normalized, $knowledgeId]
        );
    }

    public static function topFaqs(int $limit = 15): array
    {
        return self::fetchAll(
            'SELECT f.*, k.title AS knowledge_title, k.category
             FROM chatbot_faq f
             LEFT JOIN chatbot_knowledge k ON f.knowledge_id = k.id
             ORDER BY f.hit_count DESC, f.last_asked_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function stats(): array
    {
        $total = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM chatbot_messages')['c'] ?? 0);
        $today = (int)(self::fetchOne(
            'SELECT COUNT(*) AS c FROM chatbot_messages WHERE DATE(created_at) = CURDATE()'
        )['c'] ?? 0);
        $sessions = (int)(self::fetchOne(
            'SELECT COUNT(DISTINCT session_id) AS c FROM chatbot_messages'
        )['c'] ?? 0);
        $enabled = (int)(self::fetchOne(
            'SELECT COUNT(*) AS c FROM chatbot_knowledge WHERE is_enabled = 1'
        )['c'] ?? 0);
        $knowledgeTotal = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM chatbot_knowledge')['c'] ?? 0);

        return [
            'total_messages' => $total,
            'messages_today' => $today,
            'unique_sessions' => $sessions,
            'enabled_responses' => $enabled,
            'knowledge_total' => $knowledgeTotal,
        ];
    }

    public static function recentMessages(int $limit = 20): array
    {
        return self::fetchAll(
            'SELECT m.*, u.first_name, u.last_name, u.email
             FROM chatbot_messages m
             LEFT JOIN users u ON m.user_id = u.id
             ORDER BY m.created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public static function normalizeQuestion(string $question): string
    {
        $q = strtolower(trim(preg_replace('/\s+/', ' ', $question) ?? ''));
        return mb_substr($q, 0, 500);
    }
}
