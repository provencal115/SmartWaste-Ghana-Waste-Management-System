-- Verify and repair demo accounts and collector profile
-- Run in phpMyAdmin or: mysql -u root smart_waste_db < database/verify_accounts.sql

USE smart_waste_db;

-- Ensure all roles exist
INSERT IGNORE INTO roles (name, description) VALUES
('resident', 'Residential waste service customer'),
('collector', 'Garbage collection field worker'),
('inventory_manager', 'Warehouse and bin inventory manager'),
('administrator', 'System administrator with full access'),
('finance_manager', 'Financial operations manager');

-- Repair collector user role (must be role_id = collector role)
UPDATE users u
JOIN roles r ON r.name = 'collector'
SET u.role_id = r.id, u.is_active = 1, u.email_verified = 1
WHERE u.email = 'collector@smartwaste.gh';

-- Ensure collector profile row exists
INSERT INTO collectors (user_id, employee_id, zone_id)
SELECT u.id, 'COL-001', (SELECT id FROM collection_zones ORDER BY id LIMIT 1)
FROM users u
WHERE u.email = 'collector@smartwaste.gh'
  AND NOT EXISTS (SELECT 1 FROM collectors c WHERE c.user_id = u.id)
LIMIT 1;

-- Verify all demo accounts are active
UPDATE users SET is_active = 1, email_verified = 1
WHERE email IN (
    'admin@smartwaste.gh',
    'finance@smartwaste.gh',
    'inventory@smartwaste.gh',
    'collector@smartwaste.gh'
);
