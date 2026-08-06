-- SmartWaste upgrade v2 — run once: mysql -u root smartwaste_db < database/upgrade_v2.sql

ALTER TABLE residents ADD COLUMN owns_existing_bin TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_confirmed;
ALTER TABLE collection_schedules ADD COLUMN collector_notes TEXT NULL AFTER collection_notes;
ALTER TABLE collection_schedules MODIFY COLUMN pickup_status ENUM('pending', 'in_progress', 'completed', 'delayed', 'missed') DEFAULT 'pending';
