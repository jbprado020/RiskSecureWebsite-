USE risk_secure_db;

CREATE TABLE IF NOT EXISTS staff_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    contact_number VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO staff_accounts (full_name, email, contact_number, password_hash, role, is_active) VALUES
('RiskSecure Admin', 'admin@risksecure.local', '+63-917-700-1000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Maya Manager', 'manager@risksecure.local', '+63-917-700-1001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1),
('Uma Underwriter', 'underwriter@risksecure.local', '+63-917-700-1002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'underwriter', 1),
('Clark Claims', 'claims@risksecure.local', '+63-917-700-1003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'claims_officer', 1),
('Bella Billing', 'billing@risksecure.local', '+63-917-700-1004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'billing_officer', 1)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
