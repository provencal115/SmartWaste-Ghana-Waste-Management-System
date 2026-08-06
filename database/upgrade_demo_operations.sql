-- SmartWaste upgrade — fleet status + demo operations support
-- Run once: mysql -u root smart_waste_db < database/upgrade_demo_operations.sql

USE smart_waste_db;

-- Allow realistic fleet operational statuses
ALTER TABLE trucks
    MODIFY COLUMN status ENUM('active', 'on_route', 'maintenance', 'breakdown', 'retired') NOT NULL DEFAULT 'active';

-- Additional Accra service zones used by the fleet
INSERT IGNORE INTO collection_zones (name, description, region, is_active) VALUES
('Madina', 'Madina residential and market area', 'Greater Accra', 1),
('Tema', 'Tema industrial and residential zone', 'Greater Accra', 1);

-- Replace legacy sample trucks with the operational SmartWaste fleet (5 vehicles)
DELETE FROM trucks WHERE plate_number IN ('GR-1234-20', 'GR-5678-20', 'GR-9012-20');

INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance)
SELECT 'SW-001', 'Garbage Collection Truck', 8000, 'active', id, DATE_SUB(CURDATE(), INTERVAL 45 DAY)
FROM collection_zones WHERE name = 'Accra Central' LIMIT 1
ON DUPLICATE KEY UPDATE
    model = VALUES(model),
    capacity_kg = VALUES(capacity_kg),
    status = VALUES(status),
    zone_id = VALUES(zone_id),
    last_maintenance = VALUES(last_maintenance);

INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance)
SELECT 'SW-002', 'Garbage Collection Truck', 8000, 'on_route', id, DATE_SUB(CURDATE(), INTERVAL 30 DAY)
FROM collection_zones WHERE name = 'East Legon' LIMIT 1
ON DUPLICATE KEY UPDATE
    model = VALUES(model),
    capacity_kg = VALUES(capacity_kg),
    status = VALUES(status),
    zone_id = VALUES(zone_id),
    last_maintenance = VALUES(last_maintenance);

INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance)
SELECT 'SW-003', 'Garbage Collection Truck', 5000, 'active', id, DATE_SUB(CURDATE(), INTERVAL 60 DAY)
FROM collection_zones WHERE name = 'Madina' LIMIT 1
ON DUPLICATE KEY UPDATE
    model = VALUES(model),
    capacity_kg = VALUES(capacity_kg),
    status = VALUES(status),
    zone_id = VALUES(zone_id),
    last_maintenance = VALUES(last_maintenance);

INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance)
SELECT 'SW-004', 'Garbage Collection Truck', 8000, 'on_route', id, DATE_SUB(CURDATE(), INTERVAL 20 DAY)
FROM collection_zones WHERE name = 'Tema' LIMIT 1
ON DUPLICATE KEY UPDATE
    model = VALUES(model),
    capacity_kg = VALUES(capacity_kg),
    status = VALUES(status),
    zone_id = VALUES(zone_id),
    last_maintenance = VALUES(last_maintenance);

INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance)
SELECT 'SW-005', 'Garbage Collection Truck', 5000, 'maintenance', id, CURDATE()
FROM collection_zones WHERE name = 'Accra Central' LIMIT 1
ON DUPLICATE KEY UPDATE
    model = VALUES(model),
    capacity_kg = VALUES(capacity_kg),
    status = VALUES(status),
    zone_id = VALUES(zone_id),
    last_maintenance = VALUES(last_maintenance);
