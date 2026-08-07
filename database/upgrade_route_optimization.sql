-- Intelligent Collection Route Optimisation

CREATE TABLE IF NOT EXISTS optimized_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    collection_route_id INT NULL,
    zone_id INT NOT NULL,
    collector_id INT NOT NULL,
    truck_id INT NULL,
    collection_date DATE NOT NULL,
    route_name VARCHAR(150) NOT NULL,
    route_data JSON NOT NULL,
    estimated_distance_km DECIMAL(10,2) NOT NULL DEFAULT 0,
    estimated_duration_min INT NOT NULL DEFAULT 0,
    start_lat DECIMAL(10,8) NULL,
    start_lng DECIMAL(11,8) NULL,
    end_lat DECIMAL(10,8) NULL,
    end_lng DECIMAL(11,8) NULL,
    total_stops INT NOT NULL DEFAULT 0,
    completed_stops INT NOT NULL DEFAULT 0,
    status ENUM('optimised', 'active', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'optimised',
    algorithm VARCHAR(50) NOT NULL DEFAULT 'nearest_neighbor',
    optimized_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    created_by INT NULL,
    version INT NOT NULL DEFAULT 1,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date_zone (collection_date, zone_id),
    INDEX idx_collector_date (collector_id, collection_date),
    INDEX idx_current (is_current, collection_date),
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id),
    FOREIGN KEY (collector_id) REFERENCES collectors(id),
    FOREIGN KEY (truck_id) REFERENCES trucks(id) ON DELETE SET NULL,
    FOREIGN KEY (collection_route_id) REFERENCES collection_routes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link schedules to optimised runs (safe if columns already exist)
SET @db = DATABASE();
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'collection_schedules' AND COLUMN_NAME = 'optimized_route_id') = 0,
    'ALTER TABLE collection_schedules ADD COLUMN optimized_route_id INT NULL AFTER route_id, ADD COLUMN stop_order INT NULL AFTER optimized_route_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'collection_schedules' AND CONSTRAINT_NAME = 'fk_cs_optimized_route') = 0,
    'ALTER TABLE collection_schedules ADD CONSTRAINT fk_cs_optimized_route FOREIGN KEY (optimized_route_id) REFERENCES optimized_routes(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
