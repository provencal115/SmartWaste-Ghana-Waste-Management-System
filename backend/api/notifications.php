<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

setupCORS();
$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $user = requireAuth();
        
        $unread = $db->prepare('SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0');
        $unread->execute([$user['id']]);
        
        $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 50');
        $stmt->execute([$user['id']]);
        
        jsonResponse(['success' => true, 'unread_count' => $unread->fetch()['c'], 'data' => $stmt->fetchAll()]);
        break;

    case 'mark-read':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $data = getJsonInput();
        
        if (isset($data['id'])) {
            $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([(int)$data['id'], $user['id']]);
        } else {
            $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
            $stmt->execute([$user['id']]);
        }
        
        jsonResponse(['success' => true]);
        break;

    case 'send':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['administrator']);
        validateCsrf();
        $data = getJsonInput();
        
        $channels = $data['channels'] ?? ['in_app'];
        foreach ($channels as $channel) {
            if ($data['user_id'] ?? null) {
                sendNotification($db, (int)$data['user_id'], $data['title'], $data['message'], $data['type'] ?? 'general', $channel);
            } elseif ($data['role'] ?? null) {
                $users = $db->prepare('SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = ? AND u.is_active = 1');
                $users->execute([$data['role']]);
                foreach ($users->fetchAll() as $u) {
                    sendNotification($db, $u['id'], $data['title'], $data['message'], $data['type'] ?? 'emergency', $channel);
                }
            }
        }
        
        jsonResponse(['success' => true, 'message' => 'Notifications sent']);
        break;

    default:
        jsonError('Invalid action', 404);
}
