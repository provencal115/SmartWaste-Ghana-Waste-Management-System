<?php
/**
 * Seed realistic subscription payments linked to residents, plans, and bin pricing.
 * Usage: php scripts/seed_demo_payments.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../models/PricingModel.php';
require __DIR__ . '/../models/CollectionModel.php';

echo "SmartWaste demo payment seed\n\n";

$existingDemo = (int) (Model::fetchOne(
    "SELECT COUNT(*) AS c FROM payments WHERE transaction_ref LIKE 'DEMO-%'"
)['c'] ?? 0);

if ($existingDemo >= 400) {
    $total = PaymentModel::totalRevenue();
    if ($total >= 100000) {
        echo "Demo payments already seeded ({$existingDemo} records).\n";
        echo 'Total revenue (completed): ' . formatCurrency($total) . "\n";
        exit(0);
    }
    echo "Re-seeding payments (current revenue too low: " . formatCurrency($total) . ")\n";
    Model::query("DELETE FROM payments WHERE transaction_ref LIKE 'DEMO-%'");
}

// Distribute subscription plans across residents for realistic billing mix
Model::query(
    'UPDATE residents SET payment_plan_id = ((id - 1) % 3) + 1, service_fee = CASE
        WHEN ((id - 1) % 3) + 1 = 1 THEN CASE selected_bin_size WHEN \'small\' THEN 15 WHEN \'large\' THEN 40 ELSE 25 END
        WHEN ((id - 1) % 3) + 1 = 2 THEN CASE selected_bin_size WHEN \'small\' THEN 28 WHEN \'large\' THEN 75 ELSE 48 END
        ELSE CASE selected_bin_size WHEN \'small\' THEN 50 WHEN \'large\' THEN 140 ELSE 90 END
     END
     WHERE registration_confirmed = 1'
);

$residents = Model::fetchAll(
    'SELECT r.id, r.service_fee, r.payment_plan_id, r.selected_bin_size, r.zone_id, pp.frequency
     FROM residents r
     JOIN payment_plans pp ON pp.id = r.payment_plan_id
     WHERE r.registration_confirmed = 1
     ORDER BY r.id'
);

if ($residents === []) {
    echo "No confirmed residents found. Run seed_demo_operations.php first.\n";
    exit(1);
}

$methods = ['mobile_money', 'bank_card', 'cash'];
$methodWeights = [0.58, 0.27, 0.15];
$inserted = 0;
$receiptSeq = 1;

foreach ($residents as $idx => $resident) {
    $residentId = (int) $resident['id'];
    $planId = (int) $resident['payment_plan_id'];
    $binSize = $resident['selected_bin_size'] ?? 'medium';
    $amount = PricingModel::getPrice($planId, $binSize, $resident['zone_id'] ? (int) $resident['zone_id'] : null);
    if ($amount === null) {
        $amount = (float) ($resident['service_fee'] ?? 65);
    }

    $frequency = $resident['frequency'] ?? 'monthly';
    $paymentCount = match ($frequency) {
        'weekly'   => 14,
        'biweekly' => 9,
        default    => 7,
    };

    for ($p = 0; $p < $paymentCount; $p++) {
        $daysAgo = match ($frequency) {
            'weekly'   => ($p * 7) + ($idx % 4),
            'biweekly' => ($p * 14) + ($idx % 6),
            default    => ($p * 30) + ($idx % 9),
        };

        if ($daysAgo > 175) {
            continue;
        }

        $paidAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days +" . (8 + ($p % 6)) . ' hours'));
        $method = weightedMethod($methods, $methodWeights, $idx + $p);
        $receipt = sprintf('RCP-%s-%04d', date('Ymd', strtotime($paidAt)), $receiptSeq++);

        Model::query(
            'INSERT INTO payments (resident_id, amount, payment_method, payment_plan_id, status, transaction_ref, receipt_number, paid_at, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $residentId,
                $amount,
                $method,
                $planId,
                'completed',
                sprintf('DEMO-%d-%03d', $residentId, $p + 1),
                $receipt,
                $paidAt,
                date('Y-m-d', strtotime($paidAt . ' -3 days')),
            ]
        );
        $inserted++;
    }

    // Small outstanding balance on ~12% of accounts
    if ($idx % 8 === 0) {
        Model::query(
            'UPDATE residents SET outstanding_balance = ? WHERE id = ?',
            [round($amount * 1.5, 2), $residentId]
        );
    }
}

// Non-completed payments for realism (excluded from revenue)
$sampleResidents = array_slice($residents, 0, min(25, count($residents)));
foreach ($sampleResidents as $i => $resident) {
    $amount = (float) ($resident['service_fee'] ?? 65);
    $status = match ($i % 4) {
        0 => 'pending',
        1 => 'failed',
        2 => 'overdue',
        default => 'pending',
    };
    Model::query(
        'INSERT INTO payments (resident_id, amount, payment_method, payment_plan_id, status, transaction_ref, receipt_number, due_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            (int) $resident['id'],
            $amount,
            $methods[$i % 3],
            (int) $resident['payment_plan_id'],
            $status,
            'DEMO-PEND-' . $resident['id'] . '-' . $i,
            sprintf('RCP-PND-%04d', $receiptSeq++),
            date('Y-m-d', strtotime('+5 days')),
        ]
    );
}

$total = PaymentModel::totalRevenue();
$completed = (int) (Model::fetchOne("SELECT COUNT(*) AS c FROM payments WHERE status = 'completed'")['c'] ?? 0);

echo "Inserted {$inserted} completed demo payments.\n";
echo "Completed payments total: {$completed}\n";
echo 'Total revenue: ' . formatCurrency($total) . "\n";
echo "Done.\n";

function weightedMethod(array $methods, array $weights, int $seed): string
{
    $roll = ($seed * 17) % 100 / 100;
    $cumulative = 0.0;
    foreach ($methods as $i => $method) {
        $cumulative += $weights[$i];
        if ($roll <= $cumulative) {
            return $method;
        }
    }

    return $methods[0];
}
