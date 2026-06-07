-- Migration: Add login attempts tracking for rate limiting
-- Prevents brute force attacks on login endpoints

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `account_type` ENUM('customer', 'staff') NOT NULL,
    `account_identifier` VARCHAR(255) NOT NULL,
    `attempt_count` INT DEFAULT 1,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_locked` BOOLEAN DEFAULT FALSE,
    `locked_until` TIMESTAMP NULL,
    UNIQUE KEY `unique_attempt` (`ip_address`, `account_type`, `account_identifier`),
    INDEX `idx_is_locked` (`is_locked`),
    INDEX `idx_locked_until` (`locked_until`),
    INDEX `idx_ip_address` (`ip_address`)
);
