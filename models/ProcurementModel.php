<?php
require_once __DIR__ . '/../includes/Model.php';

class ProcurementModel extends Model
{
    private static ?bool $tableReady = null;

    public static function isAvailable(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            $row = self::fetchOne(
                "SELECT 1 AS ok FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'procurement_requests' LIMIT 1"
            );
            self::$tableReady = !empty($row);
        } catch (Throwable $e) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    /** @return list<array<string, mixed>> */
    public static function all(?string $status = null): array
    {
        if (!self::isAvailable()) {
            return [];
        }

        $sql = 'SELECT pr.*, u.first_name, u.last_name
                FROM procurement_requests pr
                JOIN users u ON u.id = pr.requested_by
                WHERE 1=1';
        $params = [];
        if ($status) {
            $sql .= ' AND pr.status = ?';
            $params[] = $status;
        }
        return self::fetchAll($sql . ' ORDER BY pr.created_at DESC', $params);
    }

    public static function create(array $data): int
    {
        if (!self::isAvailable()) {
            throw new RuntimeException('Procurement is not available. Run scripts/run_inventory_forecast_migration.php.');
        }

        self::query(
            'INSERT INTO procurement_requests (bin_size, quantity, recommended_quantity, reason, status, requested_by, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['bin_size'],
                (int)$data['quantity'],
                (int)($data['recommended_quantity'] ?? $data['quantity']),
                $data['reason'] ?? null,
                $data['status'] ?? 'pending',
                (int)$data['requested_by'],
                $data['notes'] ?? null,
            ]
        );
        return (int)self::lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        if (!self::isAvailable()) {
            throw new RuntimeException('Procurement is not available.');
        }

        self::query(
            'UPDATE procurement_requests SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    /** @return array<string, int> */
    public static function emptyStats(): array
    {
        return ['pending' => 0, 'approved' => 0, 'ordered' => 0, 'received' => 0, 'cancelled' => 0, 'total' => 0];
    }

    /** @return array<string, int> */
    public static function stats(): array
    {
        if (!self::isAvailable()) {
            return self::emptyStats();
        }

        $rows = self::fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM procurement_requests GROUP BY status"
        );
        $stats = self::emptyStats();
        foreach ($rows as $row) {
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = (int)$row['cnt'];
            }
            $stats['total'] += (int)$row['cnt'];
        }
        return $stats;
    }

    public static function ensureTable(): bool
    {
        if (self::isAvailable()) {
            return true;
        }

        try {
            self::query(
                "CREATE TABLE IF NOT EXISTS procurement_requests (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    bin_size ENUM('small', 'medium', 'large') NOT NULL,
                    quantity INT NOT NULL,
                    recommended_quantity INT NULL,
                    reason TEXT,
                    status ENUM('pending', 'approved', 'ordered', 'received', 'cancelled') NOT NULL DEFAULT 'pending',
                    requested_by INT NOT NULL,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_size (bin_size),
                    FOREIGN KEY (requested_by) REFERENCES users(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::$tableReady = true;
            return true;
        } catch (Throwable $e) {
            self::$tableReady = false;
            return false;
        }
    }
}
