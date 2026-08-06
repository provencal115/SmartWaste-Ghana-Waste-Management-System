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
    case 'dashboard':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $user = requireRole(['collector']);
        
        $colStmt = $db->prepare('SELECT c.* FROM collectors c WHERE c.user_id = ?');
        $colStmt->execute([$user['id']]);
        $collector = $colStmt->fetch();
        
        $todaySchedule = $db->prepare("
            SELECT cs.*, r.address, u.first_name, u.last_name, u.phone,
                   d.bin_code, d.size, d.color
            FROM collection_schedules cs
            JOIN residents r ON cs.resident_id = r.id
            JOIN users u ON r.user_id = u.id
            LEFT JOIN bin_assignments ba ON ba.resident_id = r.id AND ba.is_active = 1
            LEFT JOIN dustbins d ON ba.dustbin_id = d.id
            WHERE cs.collector_id = ? AND cs.preferred_date = CURDATE()
            ORDER BY cs.preferred_time ASC
        ");
        $todaySchedule->execute([$collector['id']]);
        
        $routes = $db->prepare('SELECT * FROM collection_routes WHERE collector_id = ? AND is_active = 1');
        $routes->execute([$collector['id']]);
        
        $reports = $db->prepare('SELECT * FROM collector_reports WHERE collector_id = ? ORDER BY created_at DESC LIMIT 10');
        $reports->execute([$collector['id']]);
        
        jsonResponse([
            'success' => true,
            'data' => [
                'collector' => $collector,
                'today_schedule' => $todaySchedule->fetchAll(),
                'routes' => $routes->fetchAll(),
                'recent_reports' => $reports->fetchAll(),
                'stats' => [
                    'completed_today' => count(array_filter($todaySchedule->fetchAll() ?: [], fn($s) => $s['pickup_status'] === 'completed')),
                ]
            ]
        ]);
        break;

    case 'update-pickup':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['collector']);
        validateCsrf();
        $data = getJsonInput();
        
        $colStmt = $db->prepare('SELECT id FROM collectors WHERE user_id = ?');
        $colStmt->execute([$user['id']]);
        $collectorId = $colStmt->fetch()['id'];
        
        $status = $data['pickup_status'] ?? 'completed';
        $scheduleStatus = match($status) {
            'completed' => 'completed',
            'delayed' => 'delayed',
            'missed' => 'missed',
            default => 'completed',
        };
        
        $stmt = $db->prepare('UPDATE collection_schedules SET pickup_status = ?, status = ?, proof_photo = ?, signature_data = ?, completed_at = ?, collector_id = ? WHERE id = ?');
        $stmt->execute([
            $status, $scheduleStatus,
            $data['proof_photo'] ?? null,
            $data['signature_data'] ?? null,
            $status === 'completed' ? date('Y-m-d H:i:s') : null,
            $collectorId,
            (int)$data['schedule_id']
        ]);
        
        $schedStmt = $db->prepare('SELECT cs.resident_id, r.user_id FROM collection_schedules cs JOIN residents r ON cs.resident_id = r.id WHERE cs.id = ?');
        $schedStmt->execute([(int)$data['schedule_id']]);
        $sched = $schedStmt->fetch();
        
        if ($sched) {
            $notifType = match($status) {
                'completed' => 'general',
                'delayed' => 'service_delay',
                'missed' => 'missed_collection',
                default => 'general',
            };
            sendNotification($db, $sched['user_id'], 'Pickup ' . ucfirst($status), "Your collection has been marked as {$status}", $notifType);
        }
        
        logActivity($db, $user['id'], 'update_pickup', 'collector', $data);
        jsonResponse(['success' => true, 'message' => 'Pickup status updated']);
        break;

    case 'scan-bin':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['collector']);
        $data = getJsonInput();
        
        $stmt = $db->prepare('SELECT d.*, ba.resident_id, u.first_name, u.last_name FROM dustbins d LEFT JOIN bin_assignments ba ON ba.dustbin_id = d.id AND ba.is_active = 1 LEFT JOIN residents r ON ba.resident_id = r.id LEFT JOIN users u ON r.user_id = u.id WHERE d.bin_code = ? OR d.qr_code = ?');
        $stmt->execute([$data['code'] ?? '', $data['code'] ?? '']);
        $bin = $stmt->fetch();
        
        if (!$bin) jsonError('Bin not found', 404);
        jsonResponse(['success' => true, 'data' => $bin]);
        break;

    case 'submit-report':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['collector']);
        validateCsrf();
        $data = getJsonInput();
        
        $colStmt = $db->prepare('SELECT id FROM collectors WHERE user_id = ?');
        $colStmt->execute([$user['id']]);
        $collectorId = $colStmt->fetch()['id'];
        
        $stmt = $db->prepare('INSERT INTO collector_reports (collector_id, schedule_id, report_type, description, photo_url, gps_lat, gps_lng) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $collectorId, $data['schedule_id'] ?? null,
            $data['report_type'], sanitize($data['description']),
            $data['photo_url'] ?? null, $data['gps_lat'] ?? null, $data['gps_lng'] ?? null
        ]);
        
        if ($data['report_type'] === 'truck_breakdown') {
            $admins = $db->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'administrator')");
            foreach ($admins->fetchAll() as $admin) {
                sendNotification($db, $admin['id'], 'Truck Breakdown', sanitize($data['description']), 'truck_breakdown');
            }
        }
        
        jsonResponse(['success' => true, 'message' => 'Report submitted', 'id' => $db->lastInsertId()]);
        break;

    case 'sync-offline':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['collector']);
        $data = getJsonInput();
        
        $synced = [];
        foreach ($data['actions'] ?? [] as $action) {
            $stmt = $db->prepare('INSERT INTO offline_sync_queue (user_id, action_type, payload, synced, synced_at) VALUES (?, ?, ?, 1, NOW())');
            $stmt->execute([$user['id'], $action['type'], json_encode($action['payload'])]);
            $synced[] = $db->lastInsertId();
        }
        
        jsonResponse(['success' => true, 'synced_count' => count($synced)]);
        break;

    default:
        jsonError('Invalid action', 404);
}
