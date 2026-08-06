-- Smart Garbage Collection & Inventory Management System
-- Database Schema

CREATE DATABASE IF NOT EXISTS smart_waste_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_waste_db;

-- Roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (name, description) VALUES
('resident', 'Residential waste service customer'),
('collector', 'Garbage collection field worker'),
('inventory_manager', 'Warehouse and bin inventory manager'),
('administrator', 'System administrator with full access'),
('finance_manager', 'Financial operations manager');

-- Permissions
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    description TEXT
);

-- Role Permissions
CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(500),
    is_active TINYINT(1) DEFAULT 0,
    email_verified TINYINT(1) DEFAULT 0,
    welcome_email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    welcome_email_sent_at DATETIME NULL,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Collection Zones
CREATE TABLE collection_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    region VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Plans
CREATE TABLE payment_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    frequency VARCHAR(20) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1
);

INSERT INTO payment_plans (name, frequency, description) VALUES
('Weekly', 'weekly', 'Payment every week'),
('Bi-weekly', 'biweekly', 'Payment every two weeks'),
('Monthly', 'monthly', 'Payment every month');

-- Pricing Policies
CREATE TABLE pricing_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_size ENUM('small', 'medium', 'large') NOT NULL,
    payment_plan_id INT NOT NULL,
    zone_id INT,
    customer_category VARCHAR(50) DEFAULT 'standard',
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'GHS',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_plan_id) REFERENCES payment_plans(id),
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id)
);

-- Default pricing
INSERT INTO pricing_policies (bin_size, payment_plan_id, price) VALUES
('small', 1, 15.00), ('small', 2, 28.00), ('small', 3, 50.00),
('medium', 1, 25.00), ('medium', 2, 48.00), ('medium', 3, 90.00),
('large', 1, 40.00), ('large', 2, 75.00), ('large', 3, 140.00);

-- Dustbins
CREATE TABLE dustbins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_code VARCHAR(50) NOT NULL UNIQUE,
    qr_code VARCHAR(255),
    size ENUM('small', 'medium', 'large') NOT NULL,
    color ENUM('green', 'blue', 'black', 'yellow', 'red') NOT NULL,
    brand VARCHAR(100) DEFAULT 'EcoBin',
    capacity_liters INT NOT NULL,
    status ENUM('available', 'assigned', 'damaged', 'maintenance', 'lost', 'retired') DEFAULT 'available',
    warehouse_location VARCHAR(100),
    purchase_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Residents
CREATE TABLE residents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    zone_id INT,
    address TEXT NOT NULL,
    city VARCHAR(100) DEFAULT 'Accra',
    gps_lat DECIMAL(10,8),
    gps_lng DECIMAL(11,8),
    payment_plan_id INT,
    selected_bin_size ENUM('small', 'medium', 'large'),
    selected_bin_color ENUM('green', 'blue', 'black', 'yellow', 'red'),
    service_fee DECIMAL(10,2),
    outstanding_balance DECIMAL(10,2) DEFAULT 0,
    registration_confirmed TINYINT(1) DEFAULT 0,
    owns_existing_bin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id),
    FOREIGN KEY (payment_plan_id) REFERENCES payment_plans(id)
);

-- Bin Assignments
CREATE TABLE bin_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    dustbin_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (resident_id) REFERENCES residents(id),
    FOREIGN KEY (dustbin_id) REFERENCES dustbins(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Collectors
CREATE TABLE collectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    employee_id VARCHAR(50) UNIQUE,
    license_number VARCHAR(50),
    zone_id INT,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id)
);

-- Trucks
CREATE TABLE trucks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(100),
    capacity_kg INT,
    status ENUM('active', 'on_route', 'maintenance', 'breakdown', 'retired') DEFAULT 'active',
    zone_id INT,
    last_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id)
);

-- Collection Routes
CREATE TABLE collection_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    zone_id INT NOT NULL,
    collector_id INT,
    truck_id INT,
    route_data JSON,
    is_optimized TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES collection_zones(id),
    FOREIGN KEY (collector_id) REFERENCES collectors(id),
    FOREIGN KEY (truck_id) REFERENCES trucks(id)
);

-- Collection Schedules
CREATE TABLE collection_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    route_id INT,
    collector_id INT,
    schedule_type ENUM('one_time', 'recurring') DEFAULT 'one_time',
    preferred_date DATE NOT NULL,
    preferred_time TIME,
    recurrence_pattern VARCHAR(50),
    collection_notes TEXT,
    collector_notes TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'delayed', 'missed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    pickup_status ENUM('pending', 'in_progress', 'completed', 'delayed', 'missed') DEFAULT 'pending',
    proof_photo VARCHAR(500),
    signature_data TEXT,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id),
    FOREIGN KEY (route_id) REFERENCES collection_routes(id),
    FOREIGN KEY (collector_id) REFERENCES collectors(id)
);

-- Payments
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('mobile_money', 'bank_card', 'cash') NOT NULL,
    payment_plan_id INT,
    status ENUM('pending', 'completed', 'failed', 'overdue', 'refunded') DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    receipt_number VARCHAR(50) UNIQUE,
    verified_by INT,
    notes TEXT,
    paid_at DATETIME,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id),
    FOREIGN KEY (payment_plan_id) REFERENCES payment_plans(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('pickup_reminder', 'payment_reminder', 'payment_confirmation', 'missed_collection', 'service_delay', 'truck_breakdown', 'rescheduled', 'bin_full', 'complaint_update', 'emergency', 'general') DEFAULT 'general',
    channel ENUM('in_app', 'email', 'sms') DEFAULT 'in_app',
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Complaints
CREATE TABLE complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('service', 'billing', 'bin_damage', 'missed_pickup', 'other') DEFAULT 'other',
    status ENUM('open', 'in_progress', 'resolved', 'escalated', 'closed') DEFAULT 'open',
    rating INT CHECK (rating >= 1 AND rating <= 5),
    image_url VARCHAR(500),
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Inventory Movements
CREATE TABLE inventory_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dustbin_id INT NOT NULL,
    movement_type ENUM('delivery', 'assignment', 'return', 'repair', 'maintenance', 'retirement', 'loss') NOT NULL,
    from_location VARCHAR(100),
    to_location VARCHAR(100),
    performed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dustbin_id) REFERENCES dustbins(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- Inventory Thresholds
CREATE TABLE inventory_thresholds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bin_size ENUM('small', 'medium', 'large') NOT NULL,
    bin_color ENUM('green', 'blue', 'black', 'yellow', 'red') NOT NULL,
    minimum_quantity INT NOT NULL DEFAULT 10,
    UNIQUE KEY (bin_size, bin_color)
);

INSERT INTO inventory_thresholds (bin_size, bin_color, minimum_quantity) VALUES
('small', 'green', 10), ('small', 'blue', 10), ('small', 'black', 10),
('medium', 'green', 8), ('medium', 'blue', 8), ('medium', 'black', 8),
('large', 'green', 5), ('large', 'blue', 5), ('large', 'black', 5);

-- Collector Reports
CREATE TABLE collector_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    collector_id INT NOT NULL,
    schedule_id INT,
    report_type ENUM('overflow', 'damaged_bin', 'blocked_road', 'missed_pickup', 'truck_breakdown', 'emergency', 'other') NOT NULL,
    description TEXT NOT NULL,
    photo_url VARCHAR(500),
    gps_lat DECIMAL(10,8),
    gps_lng DECIMAL(11,8),
    status ENUM('pending', 'acknowledged', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collector_id) REFERENCES collectors(id),
    FOREIGN KEY (schedule_id) REFERENCES collection_schedules(id)
);

-- Offline Sync Queue
CREATE TABLE offline_sync_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    synced TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Contact Messages (public Contact Us form)
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') NOT NULL DEFAULT 'new',
    ip_address VARCHAR(45) NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_status (status),
    INDEX idx_contact_created (created_at),
    INDEX idx_contact_email (email)
);

CREATE TABLE contact_message_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    admin_user_id INT NULL,
    reply_body TEXT NOT NULL,
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES contact_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reply_message (message_id)
);

-- AI Virtual Assistant (offline chatbot)
CREATE TABLE chatbot_knowledge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    keywords TEXT NOT NULL,
    response TEXT NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_suggestion TINYINT(1) NOT NULL DEFAULT 0,
    priority INT NOT NULL DEFAULT 0,
    use_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_enabled (is_enabled)
);

CREATE TABLE chatbot_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(64) NOT NULL,
    user_id INT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    knowledge_id INT NULL,
    matched_category VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (knowledge_id) REFERENCES chatbot_knowledge(id) ON DELETE SET NULL
);

CREATE TABLE chatbot_faq (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_question VARCHAR(500) NOT NULL,
    knowledge_id INT NULL,
    hit_count INT NOT NULL DEFAULT 1,
    last_asked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hits (hit_count DESC),
    FOREIGN KEY (knowledge_id) REFERENCES chatbot_knowledge(id) ON DELETE SET NULL
);

CREATE TABLE sms_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(50) NOT NULL DEFAULT 'general',
    provider VARCHAR(30) NOT NULL DEFAULT 'simulate',
    provider_message_id VARCHAR(100) NULL,
    status ENUM('pending', 'sent', 'failed', 'simulated') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    metadata JSON NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sms_status (status),
    INDEX idx_sms_type (message_type),
    INDEX idx_sms_phone (phone),
    INDEX idx_sms_created (created_at)
);

-- System Logs / Audit Trail
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Smart Settings
CREATE TABLE smart_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value JSON NOT NULL,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

INSERT INTO smart_settings (setting_key, setting_value, description) VALUES
('route_optimization', '{"enabled": true, "algorithm": "nearest_neighbor"}', 'AI route optimization settings'),
('bin_fullness_prediction', '{"enabled": true, "threshold_percent": 80}', 'Smart bin fullness estimation'),
('demand_prediction', '{"enabled": true, "lookback_days": 30}', 'Collection demand prediction'),
('auto_reschedule', '{"enabled": true, "delay_minutes": 60}', 'Auto rescheduling after breakdown'),
('reminder_system', '{"payment_days_before": 3, "pickup_hours_before": 24}', 'Automated reminder settings');

-- Seed default zones
INSERT INTO collection_zones (name, description, region) VALUES
('Accra Central', 'Central Accra collection zone', 'Greater Accra'),
('East Legon', 'East Legon residential area', 'Greater Accra'),
('Madina', 'Madina residential and market area', 'Greater Accra'),
('Tema', 'Tema industrial and residential zone', 'Greater Accra'),
('Tema Community 1', 'Tema Community 1 zone', 'Greater Accra'),
('Kumasi Central', 'Central Kumasi zone', 'Ashanti'),
('Cape Coast', 'Cape Coast municipality', 'Central');

-- Seed operational fleet (5 vehicles)
INSERT INTO trucks (plate_number, model, capacity_kg, status, zone_id, last_maintenance) VALUES
('SW-001', 'Garbage Collection Truck', 8000, 'active', 1, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
('SW-002', 'Garbage Collection Truck', 8000, 'on_route', 2, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
('SW-003', 'Garbage Collection Truck', 5000, 'active', 3, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
('SW-004', 'Garbage Collection Truck', 8000, 'on_route', 4, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
('SW-005', 'Garbage Collection Truck', 5000, 'maintenance', 1, CURDATE());
INSERT INTO dustbins (bin_code, size, color, capacity_liters, status, warehouse_location) VALUES
('BIN-S-GN-001', 'small', 'green', 120, 'available', 'Warehouse A'),
('BIN-S-BL-001', 'small', 'blue', 120, 'available', 'Warehouse A'),
('BIN-S-BK-001', 'small', 'black', 120, 'available', 'Warehouse A'),
('BIN-M-GN-001', 'medium', 'green', 240, 'available', 'Warehouse A'),
('BIN-M-BL-001', 'medium', 'blue', 240, 'available', 'Warehouse A'),
('BIN-M-BK-001', 'medium', 'black', 240, 'available', 'Warehouse B'),
('BIN-L-GN-001', 'large', 'green', 360, 'available', 'Warehouse B'),
('BIN-L-BL-001', 'large', 'blue', 360, 'available', 'Warehouse B'),
('BIN-L-BK-001', 'large', 'black', 360, 'available', 'Warehouse B');

-- Default admin user (password: password)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_active, email_verified) VALUES
(4, 'admin@smartwaste.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '+233201234567', 1, 1);

-- Default finance manager (password: password)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_active, email_verified) VALUES
(5, 'finance@smartwaste.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kwame', 'Mensah', '+233209876543', 1, 1);

-- Default inventory manager (password: password)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_active, email_verified) VALUES
(3, 'inventory@smartwaste.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ama', 'Osei', '+233245678901', 1, 1);

-- Default collector (password: password)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_active, email_verified) VALUES
(2, 'collector@smartwaste.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kofi', 'Asante', '+233267890123', 1, 1);

INSERT INTO collectors (user_id, employee_id, zone_id)
SELECT id, 'COL-001', 1 FROM users WHERE email = 'collector@smartwaste.gh' LIMIT 1;
