-- Fix: collector seed used wrong user_id (5 instead of actual collector user id)
-- Run this if you already imported schema.sql and the collectors insert failed

USE smart_waste_db;

INSERT INTO collectors (user_id, employee_id, zone_id)
SELECT u.id, 'COL-001', 1
FROM users u
WHERE u.email = 'collector@smartwaste.gh'
  AND NOT EXISTS (
    SELECT 1 FROM collectors c WHERE c.user_id = u.id
  )
LIMIT 1;
