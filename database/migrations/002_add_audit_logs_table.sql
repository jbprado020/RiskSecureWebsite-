-- Migration: Add audit logging table for important account and admin actions

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actor_type` ENUM('staff', 'customer', 'system') NOT NULL DEFAULT 'system',
    `actor_id` INT NULL,
    `actor_name` VARCHAR(255) NULL,
    `actor_email` VARCHAR(255) NULL,
    `actor_role` VARCHAR(100) NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id` INT NULL,
    `status` ENUM('success', 'failure') NOT NULL DEFAULT 'success',
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_logs_action` (`action`),
    INDEX `idx_audit_logs_actor_type` (`actor_type`),
    INDEX `idx_audit_logs_entity_type` (`entity_type`),
    INDEX `idx_audit_logs_created_at` (`created_at`)
);