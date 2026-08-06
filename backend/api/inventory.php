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
        requireRole(['inventory_manager']);
        
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_bins,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'damaged' THEN 1 ELSE 0 END) as damaged,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as under_maintenance
            FROM dustbins
        ")->fetch();
        
        $bySize = $db->query("SELECT size, color, status, COUNT(*) as count FROM dustbins GROUP BY size, color, status")->fetchAll();
        
        $alerts = $db->query("
            SELECT it.*, COALESCE(d.count, 0) as current_stock
            FROM inventory_thresholds it
            LEFT JOIN (
                SELECT size, color, COUNT(*) as count FROM dustbins WHERE status = 'available' GROUP BY size, color
            ) d ON it.bin_size = d.size AND it.bin_color = d.color
            WHERE COALESCE(d.count, 0) < it.minimum_quantity
        ")->fetchAll();
        
        $recentMovements = $db->query("
            SELECT im.*, d.bin_code, u.first_name, u.last_name
            FROM inventory_movements im
            JOIN dustbins d ON im.dustbin_id = d.id
            JOIN users u ON im.performed_by = u.id
            ORDER BY im.created_at DESC LIMIT 20
        ")->fetchAll();
        
        jsonResponse(['success' => true, 'data' => [
            'stats' => $stats,
            'by_size_color' => $bySize,
            'low_stock_alerts' => $alerts,
            'recent_movements' => $recentMovements,
        ]]);
        break;

    case 'bins':
        if ($method === 'GET') {
            requireRole(['inventory_manager', 'administrator']);
            $status = $_GET['status'] ?? null;
            $size = $_GET['size'] ?? null;
            
            $sql = 'SELECT * FROM dustbins WHERE 1=1';
            $params = [];
            if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
            if ($size) { $sql .= ' AND size = ?'; $params[] = $size; }
            $sql .= ' ORDER BY created_at DESC';
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        
        if ($method === 'POST') {
            $user = requireRole(['inventory_manager']);
            validateCsrf();
            $data = getJsonInput();
            
            $binCode = generateBinCode($data['size'], $data['color']);
            $capacity = getBinCapacity($data['size']);
            
            $stmt = $db->prepare('INSERT INTO dustbins (bin_code, qr_code, size, color, brand, capacity_liters, status, warehouse_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $binCode, $binCode, $data['size'], $data['color'],
                sanitize($data['brand'] ?? 'EcoBin'), $capacity,
                'available', sanitize($data['warehouse_location'] ?? 'Warehouse A')
            ]);
            $binId = (int)$db->lastInsertId();
            
            $mov = $db->prepare('INSERT INTO inventory_movements (dustbin_id, movement_type, to_location, performed_by, notes) VALUES (?, ?, ?, ?, ?)');
            $mov->execute([$binId, 'delivery', sanitize($data['warehouse_location'] ?? 'Warehouse A'), $user['id'], 'New bin delivery']);
            
            logActivity($db, $user['id'], 'add_bin', 'inventory', ['bin_code' => $binCode]);
            jsonResponse(['success' => true, 'bin_code' => $binCode, 'id' => $binId], 201);
        }
        break;

    case 'assign-bin':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['inventory_manager']);
        validateCsrf();
        $data = getJsonInput();
        
        $db->beginTransaction();
        try {
            $oldAssign = $db->prepare('UPDATE bin_assignments SET is_active = 0, returned_at = NOW() WHERE resident_id = ? AND is_active = 1');
            $oldAssign->execute([(int)$data['resident_id']]);
            
            $assign = $db->prepare('INSERT INTO bin_assignments (resident_id, dustbin_id, assigned_by) VALUES (?, ?, ?)');
            $assign->execute([(int)$data['resident_id'], (int)$data['dustbin_id'], $user['id']]);
            
            $updateBin = $db->prepare("UPDATE dustbins SET status = 'assigned' WHERE id = ?");
            $updateBin->execute([(int)$data['dustbin_id']]);
            
            $mov = $db->prepare('INSERT INTO inventory_movements (dustbin_id, movement_type, performed_by, notes) VALUES (?, ?, ?, ?)');
            $mov->execute([(int)$data['dustbin_id'], 'assignment', $user['id'], 'Assigned to resident #' . $data['resident_id']]);
            
            $db->commit();
            jsonResponse(['success' => true, 'message' => 'Bin assigned successfully']);
        } catch (Exception $e) {
            $db->rollBack();
            jsonError('Assignment failed', 500);
        }
        break;

    case 'record-repair':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireRole(['inventory_manager']);
        validateCsrf();
        $data = getJsonInput();
        
        $update = $db->prepare("UPDATE dustbins SET status = ? WHERE id = ?");
        $update->execute([$data['status'] ?? 'maintenance', (int)$data['dustbin_id']]);
        
        $mov = $db->prepare('INSERT INTO inventory_movements (dustbin_id, movement_type, performed_by, notes) VALUES (?, ?, ?, ?)');
        $mov->execute([(int)$data['dustbin_id'], 'repair', $user['id'], sanitize($data['notes'] ?? '')]);
        
        jsonResponse(['success' => true, 'message' => 'Repair recorded']);
        break;

    case 'report':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        requireRole(['inventory_manager', 'administrator']);
        
        $report = $db->query("
            SELECT size, color, status, warehouse_location, COUNT(*) as quantity
            FROM dustbins GROUP BY size, color, status, warehouse_location
            ORDER BY size, color
        ")->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $report]);
        break;

    default:
        jsonError('Invalid action', 404);
}
