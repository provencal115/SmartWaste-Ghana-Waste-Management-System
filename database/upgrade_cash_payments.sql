-- Cash payment evidence & invoice workflow (extends existing payments table)
USE smart_waste_db;

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS collector_id INT NULL AFTER verified_by,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER collector_id,
    ADD COLUMN IF NOT EXISTS amount_due DECIMAL(10,2) NULL AFTER amount,
    ADD COLUMN IF NOT EXISTS amount_received DECIMAL(10,2) NULL AFTER amount_due,
    ADD COLUMN IF NOT EXISTS evidence_url VARCHAR(500) NULL AFTER notes,
    ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) NULL AFTER receipt_number,
    ADD COLUMN IF NOT EXISTS verification_status ENUM('none','pending','approved','rejected','review') NOT NULL DEFAULT 'none' AFTER status,
    ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER verification_status,
    ADD COLUMN IF NOT EXISTS verification_notes TEXT NULL AFTER verified_at;

-- MySQL 8.0.12+ supports IF NOT EXISTS on ADD COLUMN; fallback script handles older versions.

ALTER TABLE payments
    ADD UNIQUE INDEX idx_payments_invoice (invoice_number);

ALTER TABLE payments
    ADD INDEX idx_payments_verification (verification_status, payment_method),
    ADD INDEX idx_payments_collector (collector_id),
    ADD INDEX idx_payments_schedule (schedule_id);

-- Optional foreign keys (added separately to avoid failures on re-run)
-- ALTER TABLE payments ADD CONSTRAINT fk_payments_collector FOREIGN KEY (collector_id) REFERENCES collectors(id) ON DELETE SET NULL;
-- ALTER TABLE payments ADD CONSTRAINT fk_payments_schedule FOREIGN KEY (schedule_id) REFERENCES collection_schedules(id) ON DELETE SET NULL;
