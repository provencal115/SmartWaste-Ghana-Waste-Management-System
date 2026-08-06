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
        requireRole(['finance_manager']);
        
        $stats = [
            'daily_revenue' => $db->query("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE status = 'completed' AND DATE(paid_at) = CURDATE()")->fetch()['total'],
            'weekly_revenue' => $db->query("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch()['total'],
            'monthly_revenue' => $db->query("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch()['total'],
            'outstanding' => $db->query('SELECT COALESCE(SUM(outstanding_balance),0) as total FROM residents')->fetch()['total'],
            'completed_payments' => $db->query("SELECT COUNT(*) as c FROM payments WHERE status = 'completed'")->fetch()['c'],
            'failed_payments' => $db->query("SELECT COUNT(*) as c FROM payments WHERE status = 'failed'")->fetch()['c'],
            'pending_cash' => $db->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending' AND payment_method = 'cash'")->fetch()['c'],
        ];
        
        $revenueByMethod = $db->query("SELECT payment_method, SUM(amount) as total, COUNT(*) as count FROM payments WHERE status = 'completed' GROUP BY payment_method")->fetchAll();
        $monthlyTrend = $db->query("SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as revenue FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetchAll();
        
        jsonResponse(['success' => true, 'data' => compact('stats', 'revenueByMethod', 'monthlyTrend')]);
        break;

    case 'payments':
        if ($method === 'GET') {
            requireRole(['finance_manager', 'administrator']);
            $status = $_GET['status'] ?? null;
            $sql = 'SELECT p.*, u.first_name, u.last_name, u.email FROM payments p JOIN residents r ON p.resident_id = r.id JOIN users u ON r.user_id = u.id';
            if ($status) { $sql .= ' WHERE p.status = ?'; $stmt = $db->prepare($sql . ' ORDER BY p.created_at DESC'); $stmt->execute([$status]); }
            else { $stmt = $db->query($sql . ' ORDER BY p.created_at DESC'); }
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    case 'verify-cash':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['finance_manager']);
        validateCsrf();
        $data = getJsonInput();
        
        $stmt = $db->prepare("UPDATE payments SET status = 'completed', verified_by = ?, paid_at = NOW() WHERE id = ? AND payment_method = 'cash' AND status = 'pending'");
        $stmt->execute([$user['id'], (int)$data['payment_id']]);
        
        $payStmt = $db->prepare('SELECT resident_id, amount FROM payments WHERE id = ?');
        $payStmt->execute([(int)$data['payment_id']]);
        $payment = $payStmt->fetch();
        if ($payment) {
            $update = $db->prepare('UPDATE residents SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?');
            $update->execute([$payment['amount'], $payment['resident_id']]);
            
            $resUser = $db->prepare('SELECT user_id FROM residents WHERE id = ?');
            $resUser->execute([$payment['resident_id']]);
            $res = $resUser->fetch();
            if ($res) sendNotification($db, $res['user_id'], 'Payment Verified', 'Your cash payment has been verified and confirmed.', 'payment_confirmation');
        }
        
        jsonResponse(['success' => true, 'message' => 'Cash payment verified']);
        break;

    case 'refund':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['finance_manager']);
        validateCsrf();
        $data = getJsonInput();
        
        $stmt = $db->prepare("UPDATE payments SET status = 'refunded', notes = ? WHERE id = ?");
        $stmt->execute([sanitize($data['reason'] ?? 'Refund processed'), (int)$data['payment_id']]);
        
        jsonResponse(['success' => true, 'message' => 'Refund processed']);
        break;

    case 'pricing':
        if ($method === 'GET') {
            requireRole(['finance_manager']);
            $stmt = $db->query('SELECT p.*, pp.name as plan_name, pp.frequency, cz.name as zone_name FROM pricing_policies p JOIN payment_plans pp ON p.payment_plan_id = pp.id LEFT JOIN collection_zones cz ON p.zone_id = cz.id WHERE p.is_active = 1');
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        if ($method === 'POST') {
            $user = requireRole(['finance_manager']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('INSERT INTO pricing_policies (bin_size, payment_plan_id, zone_id, customer_category, price) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$data['bin_size'], (int)$data['payment_plan_id'], $data['zone_id'] ?? null, $data['customer_category'] ?? 'standard', (float)$data['price']]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        }
        if ($method === 'PUT') {
            $user = requireRole(['finance_manager']);
            validateCsrf();
            $data = getJsonInput();
            $stmt = $db->prepare('UPDATE pricing_policies SET price = ?, is_active = ? WHERE id = ?');
            $stmt->execute([(float)$data['price'], (int)($data['is_active'] ?? 1), (int)$data['id']]);
            jsonResponse(['success' => true, 'message' => 'Pricing updated']);
        }
        break;

    case 'payment-history':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        requireRole(['finance_manager']);
        $residentId = $_GET['resident_id'] ?? null;
        if (!$residentId) jsonError('Resident ID required');
        $stmt = $db->prepare('SELECT * FROM payments WHERE resident_id = ? ORDER BY created_at DESC');
        $stmt->execute([(int)$residentId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'reports':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        requireRole(['finance_manager', 'administrator']);
        $type = $_GET['type'] ?? 'revenue';
        
        $data = match($type) {
            'revenue' => $db->query("SELECT DATE(paid_at) as date, payment_method, SUM(amount) as total, COUNT(*) as count FROM payments WHERE status = 'completed' GROUP BY DATE(paid_at), payment_method ORDER BY date DESC LIMIT 90")->fetchAll(),
            'outstanding' => $db->query("SELECT u.first_name, u.last_name, u.email, r.outstanding_balance, r.service_fee FROM residents r JOIN users u ON r.user_id = u.id WHERE r.outstanding_balance > 0 ORDER BY r.outstanding_balance DESC")->fetchAll(),
            default => [],
        };
        
        jsonResponse(['success' => true, 'data' => $data]);
        break;

    default:
        jsonError('Invalid action', 404);
}
