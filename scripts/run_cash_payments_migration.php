<?php
/**
 * Apply cash payment / invoice columns to payments table.
 * Usage: php scripts/run_cash_payments_migration.php
 */
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';

$columns = [
    'collector_id'        => 'INT NULL',
    'schedule_id'         => 'INT NULL',
    'amount_due'          => 'DECIMAL(10,2) NULL',
    'amount_received'       => 'DECIMAL(10,2) NULL',
    'evidence_url'        => 'VARCHAR(500) NULL',
    'invoice_number'      => 'VARCHAR(50) NULL',
    'verification_status' => "ENUM('none','pending','approved','rejected','review') NOT NULL DEFAULT 'none'",
    'verified_at'         => 'DATETIME NULL',
    'verification_notes'  => 'TEXT NULL',
];

foreach ($columns as $name => $definition) {
    $safe = preg_replace('/[^a-z_]/', '', $name);
    $exists = Model::fetchOne("SHOW COLUMNS FROM payments LIKE '{$safe}'");
    if ($exists) {
        echo "SKIP: {$name} already exists\n";
        continue;
    }
    Model::query("ALTER TABLE payments ADD COLUMN {$safe} {$definition}");
    echo "ADDED: {$name}\n";
}

$idx = Model::fetchOne("SHOW INDEX FROM payments WHERE Key_name = 'idx_payments_invoice'");
if (!$idx) {
    try {
        Model::query('ALTER TABLE payments ADD UNIQUE INDEX idx_payments_invoice (invoice_number)');
        echo "ADDED: idx_payments_invoice\n";
    } catch (Throwable $e) {
        echo "WARN: invoice index — {$e->getMessage()}\n";
    }
}

echo "Cash payments migration complete.\n";
