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
        $user = requireRole(['resident']);
        
        $stmt = $db->prepare('
            SELECT r.*, pp.name as payment_plan_name, pp.frequency,
                   d.bin_code, d.color as bin_color, d.size as bin_size, d.capacity_liters,
                   cz.name as zone_name
            FROM residents r
            LEFT JOIN payment_plans pp ON r.payment_plan_id = pp.id
            LEFT JOIN bin_assignments ba ON ba.resident_id = r.id AND ba.is_active = 1
            LEFT JOIN dustbins d ON ba.dustbin_id = d.id
            LEFT JOIN collection_zones cz ON r.zone_id = cz.id
            WHERE r.user_id = ?
        ');
        $stmt->execute([$user['id']]);
        $resident = $stmt->fetch();
        
        $upcoming = $db->prepare("SELECT * FROM collection_schedules WHERE resident_id = ? AND status IN ('scheduled', 'in_progress') AND preferred_date >= CURDATE() ORDER BY preferred_date ASC LIMIT 5");
        $upcoming->execute([$resident['id']]);
        
        $history = $db->prepare("SELECT * FROM collection_schedules WHERE resident_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 10");
        $history->execute([$resident['id']]);
        
        $notifs = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 10');
        $notifs->execute([$user['id']]);
        
        $payments = $db->prepare('SELECT * FROM payments WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5');
        $payments->execute([$resident['id']]);
        
        jsonResponse([
            'success' => true,
            'data' => [
                'resident' => $resident,
                'upcoming_pickups' => $upcoming->fetchAll(),
                'service_history' => $history->fetchAll(),
                'notifications' => $notifs->fetchAll(),
                'recent_payments' => $payments->fetchAll(),
            ]
        ]);
        break;

    case 'schedule-pickup':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['resident']);
        validateCsrf();
        $data = getJsonInput();
        
        $resStmt = $db->prepare('SELECT id FROM residents WHERE user_id = ?');
        $resStmt->execute([$user['id']]);
        $residentId = $resStmt->fetch()['id'];
        
        $stmt = $db->prepare('INSERT INTO collection_schedules (resident_id, schedule_type, preferred_date, preferred_time, recurrence_pattern, collection_notes) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $residentId,
            $data['schedule_type'] ?? 'one_time',
            $data['preferred_date'],
            $data['preferred_time'] ?? null,
            $data['recurrence_pattern'] ?? null,
            sanitize($data['collection_notes'] ?? '')
        ]);
        
        sendNotification($db, $user['id'], 'Pickup Scheduled', "Your pickup has been scheduled for {$data['preferred_date']}", 'pickup_reminder');
        logActivity($db, $user['id'], 'schedule_pickup', 'resident', $data);
        
        jsonResponse(['success' => true, 'message' => 'Pickup scheduled successfully', 'id' => $db->lastInsertId()]);
        break;

    case 'make-payment':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['resident']);
        validateCsrf();
        $data = getJsonInput();
        
        $resStmt = $db->prepare('SELECT id, service_fee FROM residents WHERE user_id = ?');
        $resStmt->execute([$user['id']]);
        $resident = $resStmt->fetch();
        
        $amount = $data['amount'] ?? $resident['service_fee'];
        $receiptNumber = generateReceiptNumber();
        $status = ($data['payment_method'] ?? '') === 'cash' ? 'pending' : 'completed';
        
        $stmt = $db->prepare('INSERT INTO payments (resident_id, amount, payment_method, payment_plan_id, status, transaction_ref, receipt_number, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $resident['id'], $amount, $data['payment_method'],
            $data['payment_plan_id'] ?? null, $status,
            'TXN-' . strtoupper(uniqid()), $receiptNumber,
            $status === 'completed' ? date('Y-m-d H:i:s') : null
        ]);
        
        if ($status === 'completed') {
            $update = $db->prepare('UPDATE residents SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?');
            $update->execute([$amount, $resident['id']]);
        }
        
        sendNotification($db, $user['id'], 'Payment ' . ucfirst($status), "Payment of GHS {$amount} via {$data['payment_method']}", 'payment_confirmation');
        
        jsonResponse(['success' => true, 'receipt_number' => $receiptNumber, 'status' => $status]);
        break;

    case 'submit-complaint':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['resident']);
        validateCsrf();
        $data = getJsonInput();
        
        $resStmt = $db->prepare('SELECT id FROM residents WHERE user_id = ?');
        $resStmt->execute([$user['id']]);
        $residentId = $resStmt->fetch()['id'];
        
        $stmt = $db->prepare('INSERT INTO complaints (resident_id, subject, description, category, rating, image_url) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $residentId, sanitize($data['subject']), sanitize($data['description']),
            $data['category'] ?? 'other', $data['rating'] ?? null, $data['image_url'] ?? null
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Complaint submitted', 'id' => $db->lastInsertId()]);
        break;

    case 'complaints':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $user = requireRole(['resident']);
        
        $resStmt = $db->prepare('SELECT id FROM residents WHERE user_id = ?');
        $resStmt->execute([$user['id']]);
        $residentId = $resStmt->fetch()['id'];
        
        $stmt = $db->prepare('SELECT * FROM complaints WHERE resident_id = ? ORDER BY created_at DESC');
        $stmt->execute([$residentId]);
        
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'pricing':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $stmt = $db->query('
            SELECT pp.*, p.bin_size, p.price, p.zone_id, p.customer_category
            FROM pricing_policies p
            JOIN payment_plans pp ON p.payment_plan_id = pp.id
            WHERE p.is_active = 1
            ORDER BY p.bin_size, pp.id
        ');
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'zones':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $stmt = $db->query('SELECT * FROM collection_zones WHERE is_active = 1');
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        jsonError('Invalid action', 404);
}
