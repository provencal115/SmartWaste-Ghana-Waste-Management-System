-- Smart Inventory Forecasting — procurement requests + forecast settings

CREATE TABLE IF NOT EXISTS procurement_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_size ENUM('small', 'medium', 'large') NOT NULL,
    quantity INT NOT NULL,
    recommended_quantity INT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'ordered', 'received', 'cancelled') NOT NULL DEFAULT 'pending',
    requested_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_size (bin_size),
    FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO smart_settings (setting_key, setting_value, description)
SELECT 'inventory_forecast',
       '{"enabled": true, "lookback_days": 90, "safety_stock_days": 30, "reorder_multiplier": 1.5, "minimum_by_size": {"small": 20, "medium": 20, "large": 20}}',
       'Smart inventory forecasting and procurement alert settings'
WHERE NOT EXISTS (SELECT 1 FROM smart_settings WHERE setting_key = 'inventory_forecast');
