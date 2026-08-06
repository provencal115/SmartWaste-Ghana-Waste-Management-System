<?php
/**
 * Seed realistic demo residents and collection schedules.
 * Usage: php scripts/seed_demo_operations.php
 *
 * Safe to re-run — skips if demo dataset already present.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/stats.php';

$targetResidents = 250;
$demoEmailPattern = 'demo%@smartwaste.gh';

echo "SmartWaste demo operations seed\n\n";

// ── Fleet upgrade (enum + 5 trucks) ───────────────────────────────────────
try {
    Model::query(
        "ALTER TABLE trucks MODIFY COLUMN status ENUM('active','on_route','maintenance','breakdown','retired') NOT NULL DEFAULT 'active'"
    );
} catch (Throwable $e) {
    echo "Note: truck status enum — {$e->getMessage()}\n";
}

$zonesToEnsure = [
    ['Madina', 'Madina residential and market area'],
    ['Tema', 'Tema industrial and residential zone'],
];
foreach ($zonesToEnsure as [$name, $desc]) {
    try {
        Model::query(
            'INSERT IGNORE INTO collection_zones (name, description, region, is_active) VALUES (?, ?, ?, 1)',
            [$name, $desc, 'Greater Accra']
        );
    } catch (Throwable) {
    }
}

$zoneId = static function (string $name): ?int {
    $row = Model::fetchOne('SELECT id FROM collection_zones WHERE name = ? LIMIT 1', [$name]);
    return $row ? (int) $row['id'] : null;
};

$fleet = [
    ['SW-001', 'Garbage Collection Truck', 8000, 'active', 'Accra Central'],
    ['SW-002', 'Garbage Collection Truck', 8000, 'on_route', 'East Legon'],
    ['SW-003', 'Garbage Collection Truck', 5000, 'active', 'Madina'],
    ['SW-004', 'Garbage Collection Truck', 8000, 'on_route', 'Tema'],
    ['SW-005', 'Garbage Collection Truck', 5000, 'maintenance', 'Accra Central'],
];

Model::query("DELETE FROM trucks WHERE plate_number IN ('GR-1234-20','GR-5678-20','GR-9012-20')");

foreach ($fleet as [$plate, $model, $capacity, $status, $zoneName]) {
    $zid = $zoneId($zoneName);
    $existing = Model::fetchOne('SELECT id FROM trucks WHERE plate_number = ?', [$plate]);
    if ($existing) {
        Model::query(
            'UPDATE trucks SET model = ?, capacity_kg = ?, status = ?, zone_id = ? WHERE id = ?',
            [$model, $capacity, $status, $zid, $existing['id']]
        );
    } else {
        Model::query(
            'INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance) VALUES (?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 30 DAY))',
            [$plate, $model, $capacity, $status, $zid]
        );
    }
}
echo "Fleet: " . count($fleet) . " vehicles configured.\n";

// ── Residents ─────────────────────────────────────────────────────────────
$confirmedCount = (int) (Model::fetchOne(
    'SELECT COUNT(*) AS c FROM residents WHERE registration_confirmed = 1'
)['c'] ?? 0);

$demoCount = (int) (Model::fetchOne(
    "SELECT COUNT(*) AS c FROM users WHERE email LIKE 'demo%@smartwaste.gh'"
)['c'] ?? 0);

$toCreate = max(0, $targetResidents - $confirmedCount);

if ($toCreate === 0 && $demoCount > 0) {
    echo "Residents: already at {$confirmedCount} confirmed (target {$targetResidents}). Skipping resident seed.\n";
} else {
    $role = Model::fetchOne("SELECT id FROM roles WHERE name = 'resident'");
    $plan = Model::fetchOne('SELECT id FROM payment_plans ORDER BY id LIMIT 1');
    $roleId = (int) $role['id'];
    $planId = (int) ($plan['id'] ?? 1);
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    $zoneIds = array_column(Model::fetchAll('SELECT id FROM collection_zones WHERE is_active = 1'), 'id');
    if ($zoneIds === []) {
        $zoneIds = [1];
    }

    $sizes = ['small', 'medium', 'large'];
    $colors = ['green', 'blue', 'black'];
    $firstNames = ['Kwame', 'Ama', 'Kofi', 'Abena', 'Yaw', 'Akua', 'Kwesi', 'Efua', 'Kojo', 'Adwoa'];
    $lastNames = ['Mensah', 'Osei', 'Asante', 'Boateng', 'Owusu', 'Agyeman', 'Darko', 'Sarpong', 'Appiah', 'Tetteh'];
    $areas = ['Accra', 'East Legon', 'Madina', 'Tema', 'Osu', 'Dansoman', 'Spintex', 'Labadi'];

    $startIndex = $demoCount + 1;
    $created = 0;

    for ($i = $startIndex; $i < $startIndex + $toCreate; $i++) {
        $email = sprintf('demo%03d@smartwaste.gh', $i);
        if (Model::fetchOne('SELECT id FROM users WHERE email = ?', [$email])) {
            continue;
        }

        $fn = $firstNames[$i % count($firstNames)];
        $ln = $lastNames[($i * 3) % count($lastNames)];
        $zone = $zoneIds[$i % count($zoneIds)];
        $size = $sizes[$i % count($sizes)];
        $color = $colors[$i % count($colors)];
        $area = $areas[$i % count($areas)];

        Model::query(
            'INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_active, email_verified)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1)',
            [$roleId, $email, $hash, $fn, $ln, '+23320' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT)]
        );
        $userId = (int) Model::lastInsertId();

        Model::query(
            'INSERT INTO residents (user_id, zone_id, address, city, selected_bin_size, selected_bin_color, payment_plan_id, service_fee, registration_confirmed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $userId,
                $zone,
                ($i % 120 + 1) . ' ' . $area . ' Street',
                $area === 'Tema' ? 'Tema' : 'Accra',
                $size,
                $color,
                $planId,
                match ($size) {
                    'small' => 45.00,
                    'medium' => 65.00,
                    default => 85.00,
                },
            ]
        );
        $created++;
    }

    echo "Residents: created {$created} demo customers.\n";
}

// ── Collection schedules ──────────────────────────────────────────────────
$totalSchedules = (int) (Model::fetchOne('SELECT COUNT(*) AS c FROM collection_schedules')['c'] ?? 0);
$targetSchedules = 320;

if ($totalSchedules >= $targetSchedules) {
    echo "Collections: {$totalSchedules} records exist (target {$targetSchedules}). Skipping collection seed.\n";
} else {
    $collector = Model::fetchOne('SELECT id FROM collectors LIMIT 1');
    $collectorId = $collector ? (int) $collector['id'] : null;

    $residents = Model::fetchAll(
        'SELECT id FROM residents WHERE registration_confirmed = 1 ORDER BY id ASC'
    );

    if ($residents === []) {
        echo "Collections: no residents to schedule.\n";
    } else {
        $needed = $targetSchedules - $totalSchedules;
        $completedTarget = min(168, (int) round($needed * 0.52));
        $scheduledTarget = min(98, (int) round($needed * 0.31));
        $missedTarget = min(32, (int) round($needed * 0.10));
        $inProgressTarget = max(0, $needed - $completedTarget - $scheduledTarget - $missedTarget);

        $buckets = array_merge(
            array_fill(0, $completedTarget, 'completed'),
            array_fill(0, $scheduledTarget, 'scheduled'),
            array_fill(0, $missedTarget, 'missed'),
            array_fill(0, $inProgressTarget, 'in_progress')
        );

        $inserted = 0;
        $residentCount = count($residents);

        foreach ($buckets as $idx => $status) {
            $resident = $residents[$idx % $residentCount];
            $residentId = (int) $resident['id'];

            if ($status === 'completed') {
                $daysAgo = ($idx % 90) + 1;
                $date = date('Y-m-d', strtotime("-{$daysAgo} days"));
                $completedAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days +9 hours"));
                Model::query(
                    "INSERT INTO collection_schedules (resident_id, collector_id, schedule_type, preferred_date, preferred_time, status, pickup_status, completed_at)
                     VALUES (?, ?, 'one_time', ?, '09:00:00', 'completed', 'completed', ?)",
                    [$residentId, $collectorId, $date, $completedAt]
                );
            } elseif ($status === 'scheduled') {
                $daysAhead = ($idx % 21) + 1;
                $date = date('Y-m-d', strtotime("+{$daysAhead} days"));
                Model::query(
                    "INSERT INTO collection_schedules (resident_id, collector_id, schedule_type, preferred_date, preferred_time, status, pickup_status)
                     VALUES (?, ?, 'one_time', ?, '10:00:00', 'scheduled', 'pending')",
                    [$residentId, $collectorId, $date]
                );
            } elseif ($status === 'missed') {
                $daysAgo = ($idx % 14) + 2;
                $date = date('Y-m-d', strtotime("-{$daysAgo} days"));
                Model::query(
                    "INSERT INTO collection_schedules (resident_id, collector_id, schedule_type, preferred_date, preferred_time, status, pickup_status)
                     VALUES (?, ?, 'one_time', ?, '08:00:00', 'missed', 'missed')",
                    [$residentId, $collectorId, $date]
                );
            } else {
                $date = date('Y-m-d');
                Model::query(
                    "INSERT INTO collection_schedules (resident_id, collector_id, schedule_type, preferred_date, preferred_time, status, pickup_status)
                     VALUES (?, ?, 'one_time', ?, '11:00:00', 'in_progress', 'in_progress')",
                    [$residentId, $collectorId, $date]
                );
            }
            $inserted++;
        }

        echo "Collections: inserted {$inserted} schedules.\n";
    }
}

// ── Summary ───────────────────────────────────────────────────────────────
$ops = operationalStats();
echo "\nOperational summary:\n";
foreach ($ops as $key => $val) {
    echo "  {$key}: {$val}\n";
}

echo "\nDone.\n";
