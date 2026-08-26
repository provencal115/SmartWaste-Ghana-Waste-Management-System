<?php
require_once __DIR__ . '/../includes/Model.php';

class CollectionModel extends Model
{
    public static function schedule(array $data): int
    {
        $collectorId = self::resolveCollectorForResident((int)$data['resident_id']);
        $routeId = self::resolveRouteForResident((int)$data['resident_id'], $collectorId);

        self::query(
            'INSERT INTO collection_schedules (resident_id, route_id, collector_id, schedule_type, preferred_date, preferred_time, recurrence_pattern, collection_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['resident_id'],
                $routeId,
                $collectorId,
                $data['schedule_type'],
                $data['preferred_date'],
                $data['preferred_time'] ?? null,
                $data['recurrence_pattern'] ?? null,
                $data['collection_notes'] ?? '',
            ]
        );
        return (int) self::lastInsertId();
    }

    public static function resolveCollectorForResident(int $residentId): ?int
    {
        $resident = self::fetchOne('SELECT zone_id FROM residents WHERE id = ?', [$residentId]);
        if (!$resident || !$resident['zone_id']) {
            return null;
        }
        $route = self::fetchOne(
            'SELECT collector_id FROM collection_routes WHERE zone_id = ? AND collector_id IS NOT NULL AND is_active = 1 LIMIT 1',
            [$resident['zone_id']]
        );
        if ($route && $route['collector_id']) {
            return (int) $route['collector_id'];
        }
        $collector = self::fetchOne(
            'SELECT id FROM collectors WHERE zone_id = ? AND is_available = 1 ORDER BY id LIMIT 1',
            [$resident['zone_id']]
        );
        return $collector ? (int) $collector['id'] : null;
    }

    public static function resolveRouteForResident(int $residentId, ?int $collectorId): ?int
    {
        $resident = self::fetchOne('SELECT zone_id FROM residents WHERE id = ?', [$residentId]);
        if (!$resident || !$resident['zone_id']) {
            return null;
        }
        if ($collectorId) {
            $route = self::fetchOne(
                'SELECT id FROM collection_routes WHERE zone_id = ? AND collector_id = ? AND is_active = 1 LIMIT 1',
                [$resident['zone_id'], $collectorId]
            );
            if ($route) {
                return (int) $route['id'];
            }
        }
        $route = self::fetchOne(
            'SELECT id FROM collection_routes WHERE zone_id = ? AND is_active = 1 LIMIT 1',
            [$resident['zone_id']]
        );
        return $route ? (int) $route['id'] : null;
    }

    public static function upcoming(int $residentId, int $limit = 5): array
    {
        return self::fetchAll(
            "SELECT * FROM collection_schedules WHERE resident_id = ? AND status IN ('scheduled','in_progress') AND preferred_date >= CURDATE() ORDER BY preferred_date LIMIT ?",
            [$residentId, $limit]
        );
    }

    public static function history(int $residentId, int $limit = 10): array
    {
        return self::fetchAll(
            "SELECT * FROM collection_schedules WHERE resident_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT ?",
            [$residentId, $limit]
        );
    }

    public static function todayForCollector(int $collectorId): array
    {
        return self::forCollector($collectorId, 'today');
    }

    /**
     * Pickups for collector: assigned directly or in collector's zone.
     */
    public static function forCollector(int $collectorId, string $filter = 'all', ?int $zoneId = null): array
    {
        $sql = "SELECT cs.*, r.address, r.city, r.zone_id, r.gps_lat, r.gps_lng,
                       r.selected_bin_size, r.outstanding_balance,
                       u.first_name, u.last_name, u.phone, u.email,
                       d.bin_code, d.size AS assigned_bin_size, d.color,
                       cz.name AS zone_name, cs.stop_order,
                       (SELECT p.status FROM payments p WHERE p.resident_id = r.id ORDER BY p.created_at DESC LIMIT 1) AS last_payment_status
                FROM collection_schedules cs
                JOIN residents r ON cs.resident_id = r.id
                JOIN users u ON r.user_id = u.id
                LEFT JOIN bin_assignments ba ON ba.resident_id = r.id AND ba.is_active = 1
                LEFT JOIN dustbins d ON ba.dustbin_id = d.id
                LEFT JOIN collection_zones cz ON r.zone_id = cz.id
                WHERE (cs.collector_id = ? OR (cs.collector_id IS NULL AND r.zone_id = ?))";

        $params = [$collectorId, $zoneId ?? 0];

        switch ($filter) {
            case 'today':
                $sql .= " AND cs.preferred_date = CURDATE()";
                break;
            case 'upcoming':
                $sql .= " AND cs.preferred_date > CURDATE() AND cs.status IN ('scheduled','in_progress','rescheduled')";
                break;
            case 'completed':
                $sql .= " AND cs.status = 'completed'";
                break;
            case 'missed':
                $sql .= " AND cs.status = 'missed'";
                break;
            case 'cancelled':
                $sql .= " AND cs.status = 'cancelled'";
                break;
        }

        $sql .= ' ORDER BY cs.preferred_date DESC, COALESCE(cs.stop_order, 9999), cs.preferred_time';

        return self::fetchAll($sql, $params);
    }

    /** Scheduled pickups eligible for route optimisation on a given date/zone. */
    public static function scheduledForOptimisation(string $date, int $zoneId, ?int $collectorId = null): array
    {
        $sql = "SELECT cs.*, r.address, r.city, r.zone_id, r.gps_lat, r.gps_lng,
                       r.selected_bin_size, u.first_name, u.last_name
                FROM collection_schedules cs
                JOIN residents r ON cs.resident_id = r.id
                JOIN users u ON r.user_id = u.id
                WHERE r.zone_id = ?
                  AND cs.preferred_date = ?
                  AND cs.status IN ('scheduled', 'in_progress', 'delayed', 'rescheduled')";

        $params = [$zoneId, $date];

        if ($collectorId) {
            $sql .= ' AND (cs.collector_id = ? OR cs.collector_id IS NULL)';
            $params[] = $collectorId;
        }

        $sql .= ' ORDER BY cs.preferred_time, cs.id';

        return self::fetchAll($sql, $params);
    }

    /** Today's pickups ordered by optimised route when available. */
    public static function todayRouteForCollector(int $collectorId, ?int $zoneId = null): array
    {
        $optimized = OptimizedRouteModel::todayForCollector($collectorId);
        $pickups = self::forCollector($collectorId, 'today', $zoneId);

        if (!$optimized || empty($optimized['route_data_decoded']['stops'])) {
            return $pickups;
        }

        $orderMap = [];
        foreach ($optimized['route_data_decoded']['stops'] as $stop) {
            $orderMap[(int)$stop['schedule_id']] = (int)$stop['order'];
        }

        usort($pickups, function ($a, $b) use ($orderMap) {
            $oa = $orderMap[(int)$a['id']] ?? 9999;
            $ob = $orderMap[(int)$b['id']] ?? 9999;
            return $oa <=> $ob;
        });

        return $pickups;
    }

    public static function findForCollector(int $scheduleId, int $collectorId, ?int $zoneId = null): ?array
    {
        $rows = self::fetchAll(
            "SELECT cs.*, r.address, r.city, r.zone_id, r.selected_bin_size, r.outstanding_balance, r.owns_existing_bin,
                    u.first_name, u.last_name, u.phone, u.email,
                    d.bin_code, d.size AS assigned_bin_size, d.color, d.capacity_liters,
                    cz.name AS zone_name,
                    (SELECT p.status FROM payments p WHERE p.resident_id = r.id ORDER BY p.created_at DESC LIMIT 1) AS last_payment_status
             FROM collection_schedules cs
             JOIN residents r ON cs.resident_id = r.id
             JOIN users u ON r.user_id = u.id
             LEFT JOIN bin_assignments ba ON ba.resident_id = r.id AND ba.is_active = 1
             LEFT JOIN dustbins d ON ba.dustbin_id = d.id
             LEFT JOIN collection_zones cz ON r.zone_id = cz.id
             WHERE cs.id = ? AND (cs.collector_id = ? OR (cs.collector_id IS NULL AND r.zone_id = ?))
             LIMIT 1",
            [$scheduleId, $collectorId, $zoneId ?? 0]
        );
        return $rows[0] ?? null;
    }

    public static function collectorStats(int $collectorId, ?int $zoneId = null): array
    {
        $base = self::fetchOne(
            "SELECT
                SUM(CASE WHEN cs.preferred_date = CURDATE() THEN 1 ELSE 0 END) AS today_total,
                SUM(CASE WHEN cs.preferred_date = CURDATE() AND cs.status = 'completed' THEN 1 ELSE 0 END) AS today_completed,
                SUM(CASE WHEN cs.preferred_date = CURDATE() AND cs.pickup_status = 'pending' THEN 1 ELSE 0 END) AS today_pending,
                SUM(CASE WHEN cs.preferred_date = CURDATE() AND cs.status = 'in_progress' THEN 1 ELSE 0 END) AS today_in_progress
             FROM collection_schedules cs
             JOIN residents r ON cs.resident_id = r.id
             WHERE (cs.collector_id = ? OR (cs.collector_id IS NULL AND r.zone_id = ?))",
            [$collectorId, $zoneId ?? 0]
        );

        return [
            'today_total' => (int)($base['today_total'] ?? 0),
            'today_completed' => (int)($base['today_completed'] ?? 0),
            'today_pending' => (int)($base['today_pending'] ?? 0),
            'today_in_progress' => (int)($base['today_in_progress'] ?? 0),
        ];
    }

    public static function updatePickup(int $scheduleId, string $status, ?int $collectorId = null, ?string $proof = null, ?string $notes = null): void
    {
        $scheduleStatus = match ($status) {
            'completed' => 'completed',
            'delayed' => 'delayed',
            'missed' => 'missed',
            'in_progress' => 'in_progress',
            'pending' => 'scheduled',
            default => 'scheduled',
        };
        self::query(
            'UPDATE collection_schedules SET pickup_status = ?, status = ?, proof_photo = COALESCE(?, proof_photo), collector_notes = COALESCE(?, collector_notes), completed_at = ?, collector_id = COALESCE(?, collector_id) WHERE id = ?',
            [
                $status,
                $scheduleStatus,
                $proof,
                $notes,
                $status === 'completed' ? date('Y-m-d H:i:s') : null,
                $collectorId,
                $scheduleId,
            ]
        );

        try {
            OptimizedRouteModel::syncScheduleStatus($scheduleId, $status);
        } catch (Throwable) {
        }
    }

    public static function stats(): array
    {
        $ops = operationalStats();

        return [
            'today' => self::fetchOne("SELECT COUNT(*) AS c FROM collection_schedules WHERE preferred_date = CURDATE()")['c'] ?? 0,
            'completed_today' => self::fetchOne("SELECT COUNT(*) AS c FROM collection_schedules WHERE status = 'completed' AND DATE(completed_at) = CURDATE()")['c'] ?? 0,
            'missed_week' => self::fetchOne("SELECT COUNT(*) AS c FROM collection_schedules WHERE status = 'missed' AND preferred_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['c'] ?? 0,
            'completed_total' => $ops['collections_completed'],
            'scheduled_total' => $ops['collections_scheduled'],
            'pending_total' => $ops['collections_pending'],
            'missed_total' => $ops['collections_missed'],
            'daily' => self::fetchAll("SELECT DATE(completed_at) AS date, COUNT(*) AS count FROM collection_schedules WHERE status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(completed_at) ORDER BY date"),
        ];
    }
}

class PaymentModel extends Model
{
    public static function hasCashColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $has = self::fetchOne("SHOW COLUMNS FROM payments LIKE 'verification_status'") !== null;
        } catch (Throwable) {
            $has = false;
        }
        return $has;
    }

    public static function create(array $data): int
    {
        if (self::hasCashColumns()) {
            self::query(
                'INSERT INTO payments (resident_id, amount, amount_due, amount_received, payment_method, payment_plan_id, status, verification_status, transaction_ref, receipt_number, invoice_number, paid_at, due_date, collector_id, schedule_id, evidence_url, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['resident_id'],
                    $data['amount'],
                    $data['amount_due'] ?? $data['amount'],
                    $data['amount_received'] ?? null,
                    $data['payment_method'],
                    $data['payment_plan_id'] ?? null,
                    $data['status'],
                    $data['verification_status'] ?? 'none',
                    $data['transaction_ref'],
                    $data['receipt_number'],
                    $data['invoice_number'] ?? null,
                    $data['paid_at'] ?? null,
                    $data['due_date'] ?? null,
                    $data['collector_id'] ?? null,
                    $data['schedule_id'] ?? null,
                    $data['evidence_url'] ?? null,
                    $data['notes'] ?? null,
                ]
            );
        } else {
            self::query(
                'INSERT INTO payments (resident_id, amount, payment_method, payment_plan_id, status, transaction_ref, receipt_number, paid_at, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$data['resident_id'], $data['amount'], $data['payment_method'], $data['payment_plan_id'] ?? null, $data['status'], $data['transaction_ref'], $data['receipt_number'], $data['paid_at'] ?? null, $data['due_date'] ?? null]
            );
        }
        return (int) self::lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        return self::fetchOne('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public static function findDetailed(int $id): ?array
    {
        return self::fetchOne(
            'SELECT p.*, u.first_name, u.last_name, u.email, u.phone,
                    r.address, r.city, r.gps_lat, r.gps_lng, r.id AS resident_pk,
                    r.selected_bin_size, r.outstanding_balance,
                    cu.first_name AS collector_first, cu.last_name AS collector_last,
                    col.employee_id AS collector_employee_id,
                    cs.preferred_date AS collection_date, cs.preferred_time AS collection_time,
                    vu.first_name AS verifier_first, vu.last_name AS verifier_last
             FROM payments p
             JOIN residents r ON p.resident_id = r.id
             JOIN users u ON r.user_id = u.id
             LEFT JOIN collectors col ON p.collector_id = col.id
             LEFT JOIN users cu ON col.user_id = cu.id
             LEFT JOIN collection_schedules cs ON p.schedule_id = cs.id
             LEFT JOIN users vu ON p.verified_by = vu.id
             WHERE p.id = ?',
            [$id]
        );
    }

    public static function findDetailedByReference(string $reference): ?array
    {
        return self::fetchOne(
            'SELECT p.*, u.first_name, u.last_name, u.email, u.phone,
                    r.address, r.city, r.gps_lat, r.gps_lng, r.id AS resident_pk,
                    r.selected_bin_size, r.outstanding_balance,
                    cu.first_name AS collector_first, cu.last_name AS collector_last,
                    col.employee_id AS collector_employee_id,
                    cs.preferred_date AS collection_date, cs.preferred_time AS collection_time,
                    vu.first_name AS verifier_first, vu.last_name AS verifier_last
             FROM payments p
             JOIN residents r ON p.resident_id = r.id
             JOIN users u ON r.user_id = u.id
             LEFT JOIN collectors col ON p.collector_id = col.id
             LEFT JOIN users cu ON col.user_id = cu.id
             LEFT JOIN collection_schedules cs ON p.schedule_id = cs.id
             LEFT JOIN users vu ON p.verified_by = vu.id
             WHERE p.receipt_number = ? OR p.invoice_number = ?
             LIMIT 1',
            [$reference, $reference]
        );
    }

    public static function forResident(int $residentId): array
    {
        return self::fetchAll('SELECT * FROM payments WHERE resident_id = ? ORDER BY created_at DESC', [$residentId]);
    }

    public static function forCollector(int $collectorId): array
    {
        if (!self::hasCashColumns()) {
            return [];
        }
        return self::fetchAll(
            'SELECT p.*, u.first_name, u.last_name, cs.preferred_date
             FROM payments p
             JOIN residents r ON p.resident_id = r.id
             JOIN users u ON r.user_id = u.id
             LEFT JOIN collection_schedules cs ON p.schedule_id = cs.id
             WHERE p.collector_id = ?
             ORDER BY p.created_at DESC',
            [$collectorId]
        );
    }

    public static function pendingCashVerification(?array $filters = null): array
    {
        if (!self::hasCashColumns()) {
            return self::fetchAll(
                "SELECT p.*, u.first_name, u.last_name, u.email FROM payments p
                 JOIN residents r ON p.resident_id = r.id JOIN users u ON r.user_id = u.id
                 WHERE p.payment_method = 'cash' AND p.status = 'pending' ORDER BY p.created_at DESC"
            );
        }

        $sql = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone,
                       cu.first_name AS collector_first, cu.last_name AS collector_last,
                       col.employee_id
                FROM payments p
                JOIN residents r ON p.resident_id = r.id
                JOIN users u ON r.user_id = u.id
                LEFT JOIN collectors col ON p.collector_id = col.id
                LEFT JOIN users cu ON col.user_id = cu.id
                WHERE p.payment_method = 'cash' AND p.verification_status = 'pending'";

        $params = [];
        if (!empty($filters['status']) && $filters['status'] !== 'pending') {
            $sql = str_replace("p.verification_status = 'pending'", 'p.verification_status = ?', $sql);
            $params[] = $filters['status'];
        }
        if (!empty($filters['collector_id'])) {
            $sql .= ' AND p.collector_id = ?';
            $params[] = (int) $filters['collector_id'];
        }
        if (!empty($filters['resident_id'])) {
            $sql .= ' AND p.resident_id = ?';
            $params[] = (int) $filters['resident_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY p.created_at DESC';
        return self::fetchAll($sql, $params);
    }

    public static function cashPayments(?array $filters = null): array
    {
        if (!self::hasCashColumns()) {
            return self::all();
        }

        $sql = "SELECT p.*, u.first_name, u.last_name, u.email,
                       cu.first_name AS collector_first, cu.last_name AS collector_last
                FROM payments p
                JOIN residents r ON p.resident_id = r.id
                JOIN users u ON r.user_id = u.id
                LEFT JOIN collectors col ON p.collector_id = col.id
                LEFT JOIN users cu ON col.user_id = cu.id
                WHERE p.payment_method = 'cash'";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND p.verification_status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['collector_id'])) {
            $sql .= ' AND p.collector_id = ?';
            $params[] = (int) $filters['collector_id'];
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT 500';
        return self::fetchAll($sql, $params);
    }

    /** @return array<string, mixed> */
    public static function cashStats(?array $filters = null): array
    {
        if (!self::hasCashColumns()) {
            return [
                'total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0,
                'review' => 0, 'revenue' => 0.0,
            ];
        }

        $where = "payment_method = 'cash'";
        $params = [];
        if (!empty($filters['date_from'])) {
            $where .= ' AND DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $rows = self::fetchAll(
            "SELECT verification_status, status, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
             FROM payments WHERE {$where} GROUP BY verification_status, status",
            $params
        );

        $stats = [
            'total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0,
            'review' => 0, 'revenue' => 0.0,
        ];
        foreach ($rows as $row) {
            $stats['total'] += (int) $row['cnt'];
            $vs = $row['verification_status'];
            if ($vs === 'pending') {
                $stats['pending'] += (int) $row['cnt'];
            } elseif ($vs === 'approved' || ($row['status'] === 'completed' && $vs === 'none')) {
                $stats['approved'] += (int) $row['cnt'];
                $stats['revenue'] += (float) $row['total'];
            } elseif ($vs === 'rejected') {
                $stats['rejected'] += (int) $row['cnt'];
            } elseif ($vs === 'review') {
                $stats['review'] += (int) $row['cnt'];
            }
        }

        return $stats;
    }

    public static function hasPendingCashForSchedule(int $scheduleId): bool
    {
        if (!self::hasCashColumns()) {
            return false;
        }
        $row = self::fetchOne(
            "SELECT id FROM payments WHERE schedule_id = ? AND payment_method = 'cash'
             AND verification_status IN ('pending','review') LIMIT 1",
            [$scheduleId]
        );
        return $row !== null;
    }

    public static function submitCollectorCash(array $data): int
    {
        $paymentId = self::create($data);
        return $paymentId;
    }

    public static function updateEvidence(int $paymentId, string $path): void
    {
        if (!self::hasCashColumns()) {
            return;
        }
        self::query('UPDATE payments SET evidence_url = ? WHERE id = ?', [$path, $paymentId]);
    }

    public static function all(?string $status = null): array
    {
        $sql = 'SELECT p.*, u.first_name, u.last_name, u.email FROM payments p JOIN residents r ON p.resident_id = r.id JOIN users u ON r.user_id = u.id';
        if ($status) {
            return self::fetchAll($sql . ' WHERE p.status = ? ORDER BY p.created_at DESC', [$status]);
        }
        return self::fetchAll($sql . ' ORDER BY p.created_at DESC');
    }

    public static function verifyCash(int $paymentId, int $verifiedBy): void
    {
        self::processVerification($paymentId, 'approve', $verifiedBy, null);
    }

    public static function processVerification(int $paymentId, string $action, int $verifiedBy, ?string $notes = null): bool
    {
        $payment = self::findById($paymentId);
        if (!$payment || ($payment['payment_method'] ?? '') !== 'cash') {
            return false;
        }

        $vStatus = self::hasCashColumns() ? ($payment['verification_status'] ?? 'none') : 'none';
        $canProcess = self::hasCashColumns()
            ? in_array($vStatus, ['pending', 'review'], true) || ($payment['status'] === 'pending' && $vStatus === 'none')
            : ($payment['status'] === 'pending');

        if (!$canProcess) {
            return false;
        }

        if ($action === 'approve') {
            $amount = (float) ($payment['amount_received'] ?? $payment['amount']);
            if (self::hasCashColumns()) {
                self::query(
                    "UPDATE payments SET status = 'completed', verification_status = 'approved',
                     verified_by = ?, verified_at = NOW(), paid_at = NOW(), verification_notes = ?
                     WHERE id = ? AND payment_method = 'cash'",
                    [$verifiedBy, $notes, $paymentId]
                );
            } else {
                self::query(
                    "UPDATE payments SET status = 'completed', verified_by = ?, paid_at = NOW() WHERE id = ? AND payment_method = 'cash'",
                    [$verifiedBy, $paymentId]
                );
            }
            self::query(
                'UPDATE residents SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?',
                [$amount, $payment['resident_id']]
            );
            return true;
        }

        if (!self::hasCashColumns()) {
            return false;
        }

        if ($action === 'reject') {
            self::query(
                "UPDATE payments SET status = 'failed', verification_status = 'rejected',
                 verified_by = ?, verified_at = NOW(), verification_notes = ?
                 WHERE id = ? AND payment_method = 'cash'",
                [$verifiedBy, $notes, $paymentId]
            );
            return true;
        }

        if ($action === 'review') {
            self::query(
                "UPDATE payments SET verification_status = 'review', verification_notes = ?
                 WHERE id = ? AND payment_method = 'cash'",
                [$notes, $paymentId]
            );
            return true;
        }

        return false;
    }

    /** Sum of all completed (successful) payments — excludes pending, failed, refunded. */
    public static function totalRevenue(): float
    {
        return (float) (self::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS t FROM payments WHERE status = 'completed'"
        )['t'] ?? 0);
    }

    /** @return array<int, array{date: string, revenue: float}> */
    public static function revenueTrendDaily(int $days): array
    {
        $rows = self::fetchAll(
            "SELECT DATE(paid_at) AS date, SUM(amount) AS revenue
             FROM payments
             WHERE status = 'completed'
               AND paid_at IS NOT NULL
               AND paid_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(paid_at)
             ORDER BY date",
            [$days]
        );

        return array_map(static fn ($r) => [
            'date'    => $r['date'],
            'revenue' => (float) $r['revenue'],
        ], $rows);
    }

    /** @return array<int, array{month: string, revenue: float, label: string}> */
    public static function revenueTrendMonthly(int $months): array
    {
        $rows = self::fetchAll(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, SUM(amount) AS revenue
             FROM payments
             WHERE status = 'completed'
               AND paid_at IS NOT NULL
               AND paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
             ORDER BY month",
            [$months]
        );

        return array_map(static function ($r) {
            $ts = strtotime($r['month'] . '-01');

            return [
                'month'   => $r['month'],
                'label'   => $ts ? date('M Y', $ts) : $r['month'],
                'revenue' => (float) $r['revenue'],
            ];
        }, $rows);
    }

    /** @return array<string, mixed> */
    public static function revenueAnalytics(): array
    {
        return [
            'total'        => self::totalRevenue(),
            'daily'        => (float) (self::fetchOne(
                "SELECT COALESCE(SUM(amount), 0) AS t FROM payments WHERE status = 'completed' AND DATE(paid_at) = CURDATE()"
            )['t'] ?? 0),
            'weekly'       => (float) (self::fetchOne(
                "SELECT COALESCE(SUM(amount), 0) AS t FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            )['t'] ?? 0),
            'monthly'      => (float) (self::fetchOne(
                "SELECT COALESCE(SUM(amount), 0) AS t FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            )['t'] ?? 0),
            'trend_7d'     => self::revenueTrendDaily(7),
            'trend_30d'    => self::revenueTrendDaily(30),
            'trend_6mo'    => self::revenueTrendMonthly(6),
            'by_method'    => self::fetchAll(
                "SELECT payment_method, SUM(amount) AS total, COUNT(*) AS count
                 FROM payments WHERE status = 'completed' GROUP BY payment_method"
            ),
            'monthly_trend'=> self::revenueTrendMonthly(12),
        ];
    }

    public static function financeStats(): array
    {
        $analytics = self::revenueAnalytics();

        return [
            'total_revenue' => $analytics['total'],
            'daily'         => $analytics['daily'],
            'weekly'        => $analytics['weekly'],
            'monthly'       => $analytics['monthly'],
            'outstanding'   => (float) (self::fetchOne('SELECT COALESCE(SUM(outstanding_balance), 0) AS t FROM residents')['t'] ?? 0),
            'by_method'     => $analytics['by_method'],
            'monthly_trend' => $analytics['monthly_trend'],
            'trend_7d'      => $analytics['trend_7d'],
            'trend_30d'     => $analytics['trend_30d'],
            'trend_6mo'     => $analytics['trend_6mo'],
        ];
    }
}

class ComplaintModel extends Model
{
    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO complaints (resident_id, subject, description, category, rating, image_url) VALUES (?, ?, ?, ?, ?, ?)',
            [$data['resident_id'], $data['subject'], $data['description'], $data['category'], $data['rating'] ?? null, $data['image_url'] ?? null]
        );
        return (int) self::lastInsertId();
    }

    public static function forResident(int $residentId): array
    {
        return self::fetchAll('SELECT * FROM complaints WHERE resident_id = ? ORDER BY created_at DESC', [$residentId]);
    }

    public static function all(): array
    {
        return self::fetchAll(
            'SELECT c.*, u.first_name, u.last_name, u.email FROM complaints c JOIN residents r ON c.resident_id = r.id JOIN users u ON r.user_id = u.id ORDER BY c.created_at DESC'
        );
    }

    public static function updateStatus(int $id, string $status, string $notes = ''): void
    {
        self::query(
            'UPDATE complaints SET status = ?, resolution_notes = ?, resolved_at = ? WHERE id = ?',
            [$status, $notes, in_array($status, ['resolved', 'closed']) ? date('Y-m-d H:i:s') : null, $id]
        );
    }
}
