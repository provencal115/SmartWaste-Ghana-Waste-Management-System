-- SmartWaste upgrade — welcome email tracking + SMS messages
-- Run once: mysql -u root smart_waste_db < database/upgrade_welcome_sms.sql

ALTER TABLE users
    ADD COLUMN welcome_email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending' AFTER email_verified,
    ADD COLUMN welcome_email_sent_at DATETIME NULL AFTER welcome_email_status;

CREATE TABLE IF NOT EXISTS sms_messages (
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
