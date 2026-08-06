<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

setupCORS();
$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'collections';
$user = requireAuth();

$allowedRoles = ['administrator', 'finance_manager', 'inventory_manager'];
if (!in_array($user['role'], $allowedRoles) && $user['role'] !== 'resident') {
    jsonError('Forbidden', 403);
}

$data = match($type) {
    'collections' => $db->query("SELECT cs.*, u.first_name, u.last_name, r.address FROM collection_schedules cs JOIN residents r ON cs.resident_id = r.id JOIN users u ON r.user_id = u.id ORDER BY cs.preferred_date DESC LIMIT 500")->fetchAll(),
    'payments' => $db->query("SELECT p.*, u.first_name, u.last_name FROM payments p JOIN residents r ON p.resident_id = r.id JOIN users u ON r.user_id = u.id ORDER BY p.created_at DESC LIMIT 500")->fetchAll(),
    'inventory' => $db->query("SELECT size, color, status, warehouse_location, COUNT(*) as quantity FROM dustbins GROUP BY size, color, status, warehouse_location")->fetchAll(),
    'residents' => $db->query("SELECT r.*, u.first_name, u.last_name, u.email, u.phone, cz.name as zone_name FROM residents r JOIN users u ON r.user_id = u.id LEFT JOIN collection_zones cz ON r.zone_id = cz.id")->fetchAll(),
    'trucks' => $db->query("SELECT t.*, cz.name as zone_name FROM trucks t LEFT JOIN collection_zones cz ON t.zone_id = cz.id")->fetchAll(),
    'complaints' => $db->query("SELECT c.*, u.first_name, u.last_name FROM complaints c JOIN residents r ON c.resident_id = r.id JOIN users u ON r.user_id = u.id ORDER BY c.created_at DESC")->fetchAll(),
    'revenue' => $db->query("SELECT DATE(paid_at) as date, payment_method, SUM(amount) as total FROM payments WHERE status = 'completed' GROUP BY DATE(paid_at), payment_method ORDER BY date DESC")->fetchAll(),
    'staff' => $db->query("SELECT c.employee_id, u.first_name, u.last_name, COUNT(cs.id) as collections_completed FROM collectors c JOIN users u ON c.user_id = u.id LEFT JOIN collection_schedules cs ON cs.collector_id = c.id AND cs.status = 'completed' GROUP BY c.id")->fetchAll(),
    default => [],
};

jsonResponse(['success' => true, 'type' => $type, 'data' => $data, 'generated_at' => date('Y-m-d H:i:s')]);
