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
        requireRole(['administrator']);
        
        $stats = [
            'active_users' => $db->query('SELECT COUNT(*) as c FROM users WHERE is_active = 1')->fetch()['c'],
            'active_collections' => $db->query("SELECT COUNT(*) as c FROM collection_schedules WHERE status IN ('scheduled','in_progress') AND preferred_date = CURDATE()")->fetch()['c'],
            'today_pickups' => $db->query("SELECT COUNT(*) as c FROM collection_schedules WHERE preferred_date = CURDATE()")->fetch()['c'],
            'missed_collections' => $db->query("SELECT COUNT(*) as c FROM collection_schedules WHERE status = 'missed' AND preferred_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch()['c'],
            'total_revenue' => $db->query("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE status = 'completed'")->fetch()['total'],
            'outstanding' => $db->query('SELECT COALESCE(SUM(outstanding_balance),0) as total FROM residents')->fetch()['total'],
        ];
        
        $dailyCollections = $db->query("
            SELECT DATE(completed_at) as date, COUNT(*) as count
            FROM collection_schedules WHERE status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(completed_at) ORDER BY date
        ")->fetchAll();
        
        $revenueTrends = $db->query("
            SELECT DATE(paid_at) as date, SUM(amount) as revenue
            FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(paid_at) ORDER BY date
        ")->fetchAll();
        
        $binAllocation = $db->query("SELECT status, COUNT(*) as count FROM dustbins GROUP BY status")->fetchAll();
        $paymentStats = $db->query("SELECT status, COUNT(*) as count, SUM(amount) as total FROM payments GROUP BY status")->fetchAll();
        $customerGrowth = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM residents WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetchAll();
        
        jsonResponse(['success' => true, 'data' => compact('stats', 'dailyCollections', 'revenueTrends', 'binAllocation', 'paymentStats', 'customerGrowth')]);
        break;

    case 'users':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $role = $_GET['role'] ?? null;
            $sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.last_login, u.created_at, r.name as role FROM users u JOIN roles r ON u.role_id = r.id';
            if ($role) { $sql .= ' WHERE r.name = ?'; $stmt = $db->prepare($sql); $stmt->execute([$role]); }
            else { $stmt = $db->query($sql); }
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'PUT') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $update = $db->prepare('UPDATE users SET is_active = ?, role_id = (SELECT id FROM roles WHERE name = ?) WHERE id = ?');
            $update->execute([(int)($data['is_active'] ?? 1), $data['role'] ?? 'resident', (int)$data['user_id']]);
            logActivity($db, $user['id'], 'update_user', 'admin', $data);
            jsonResponse(['success' => true, 'message' => 'User updated']);
        }
        break;

    case 'zones':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $stmt = $db->query('SELECT * FROM collection_zones ORDER BY name');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'POST') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('INSERT INTO collection_zones (name, description, region) VALUES (?, ?, ?)');
            $stmt->execute([sanitize($data['name']), sanitize($data['description'] ?? ''), sanitize($data['region'] ?? '')]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        }
        break;

    case 'routes':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $stmt = $db->query('SELECT cr.*, cz.name as zone_name, u.first_name, u.last_name, t.plate_number FROM collection_routes cr LEFT JOIN collection_zones cz ON cr.zone_id = cz.id LEFT JOIN collectors c ON cr.collector_id = c.id LEFT JOIN users u ON c.user_id = u.id LEFT JOIN trucks t ON cr.truck_id = t.id');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'POST') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('INSERT INTO collection_routes (name, zone_id, collector_id, truck_id, route_data, is_optimized) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([sanitize($data['name']), (int)$data['zone_id'], $data['collector_id'] ?? null, $data['truck_id'] ?? null, json_encode($data['route_data'] ?? []), (int)($data['is_optimized'] ?? 0)]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        }
        break;

    case 'trucks':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $stmt = $db->query('SELECT t.*, cz.name as zone_name FROM trucks t LEFT JOIN collection_zones cz ON t.zone_id = cz.id');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'POST') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('INSERT INTO trucks (plate_number, model, capacity_kg, zone_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([sanitize($data['plate_number']), sanitize($data['model'] ?? ''), (int)($data['capacity_kg'] ?? 5000), $data['zone_id'] ?? null]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        }
        break;

    case 'reschedule':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['administrator']);
        validateCsrf();
        $data = getJsonInput();
        
        $stmt = $db->prepare("UPDATE collection_schedules SET preferred_date = ?, preferred_time = ?, status = 'rescheduled', collector_id = ? WHERE id = ?");
        $stmt->execute([$data['preferred_date'], $data['preferred_time'] ?? null, $data['collector_id'] ?? null, (int)$data['schedule_id']]);
        
        $schedStmt = $db->prepare('SELECT r.user_id FROM collection_schedules cs JOIN residents r ON cs.resident_id = r.id WHERE cs.id = ?');
        $schedStmt->execute([(int)$data['schedule_id']]);
        $sched = $schedStmt->fetch();
        if ($sched) {
            sendNotification($db, $sched['user_id'], 'Collection Rescheduled', "Your collection has been rescheduled to {$data['preferred_date']}", 'rescheduled');
        }
        
        jsonResponse(['success' => true, 'message' => 'Collection rescheduled']);
        break;

    case 'complaints':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $stmt = $db->query('SELECT c.*, u.first_name, u.last_name, u.email FROM complaints c JOIN residents r ON c.resident_id = r.id JOIN users u ON r.user_id = u.id ORDER BY c.created_at DESC');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'PUT') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('UPDATE complaints SET status = ?, resolution_notes = ?, resolved_at = ? WHERE id = ?');
            $resolvedAt = in_array($data['status'], ['resolved', 'closed']) ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$data['status'], sanitize($data['resolution_notes'] ?? ''), $resolvedAt, (int)$data['complaint_id']]);
            jsonResponse(['success' => true, 'message' => 'Complaint updated']);
        }
        break;

    case 'logs':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        requireRole(['administrator']);
        $stmt = $db->query('SELECT sl.*, u.first_name, u.last_name, u.email FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.created_at DESC LIMIT 100');
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'smart-settings':
        if ($method === 'GET') {
            requireRole(['administrator']);
            $stmt = $db->query('SELECT * FROM smart_settings');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'PUT') {
            $user = requireRole(['administrator']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('UPDATE smart_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?');
            $stmt->execute([json_encode($data['value']), $user['id'], $data['key']]);
            jsonResponse(['success' => true, 'message' => 'Settings updated']);
        }
        break;

    case 'pricing':
        if ($method === 'GET') {
            requireRole(['administrator', 'finance_manager']);
            $stmt = $db->query('SELECT p.*, pp.name as plan_name, cz.name as zone_name FROM pricing_policies p JOIN payment_plans pp ON p.payment_plan_id = pp.id LEFT JOIN collection_zones cz ON p.zone_id = cz.id');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'PUT') {
            $user = requireRole(['administrator', 'finance_manager']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('UPDATE pricing_policies SET price = ? WHERE id = ?');
            $stmt->execute([(float)$data['price'], (int)$data['id']]);
            jsonResponse(['success' => true, 'message' => 'Pricing updated']);
        }
        break;

    default:
        jsonError('Invalid action', 404);
}
