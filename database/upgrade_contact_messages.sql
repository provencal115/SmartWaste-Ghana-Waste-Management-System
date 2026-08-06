-- SmartWaste upgrade — contact message management
-- Run once: mysql -u root smart_waste_db < database/upgrade_contact_messages.sql

CREATE TABLE IF NOT EXISTS contact_messages (
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

CREATE TABLE IF NOT EXISTS contact_message_replies (
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
