<?php

declare(strict_types=1);

/**
 * Rate Limiting Helper Functions
 * Provides brute force protection on login endpoints
 */

/**
 * Check if a login account/IP is currently rate limited (locked).
 * Returns array with 'allowed' and optional 'reason' for lockout message.
 */
function checkLoginRateLimit(PDO $pdo, string $accountType, string $identifier): array
{
    if (!loginAttemptsTableExists($pdo)) {
        return ['allowed' => true];
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Query existing attempt record
    try {
        $stmt = $pdo->prepare(
            'SELECT * FROM login_attempts 
             WHERE ip_address = :ip AND account_type = :account_type AND account_identifier = :identifier 
             LIMIT 1'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':account_type' => $accountType,
            ':identifier' => $identifier,
        ]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) === 1146) {
            return ['allowed' => true];
        }
        throw $exception;
    }

    // No record yet = allowed
    if (!$attempt) {
        return ['allowed' => true];
    }

    // Check if locked
    if ((int)$attempt['is_locked'] === 1) {
        if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()) {
            $minutesRemaining = ceil((strtotime($attempt['locked_until']) - time()) / 60);
            return [
                'allowed' => false,
                'reason' => "Too many failed attempts. Account locked for $minutesRemaining more minute(s)."
            ];
        } else {
            // Lockout period expired; unlock
            $unlockStmt = $pdo->prepare(
                'UPDATE login_attempts 
                 SET is_locked = 0, attempt_count = 0, last_attempt = NOW()
                 WHERE id = :id'
            );
            $unlockStmt->execute([':id' => (int)$attempt['id']]);
            return ['allowed' => true];
        }
    }

    return ['allowed' => true];
}

/**
 * Record a login attempt (successful or failed).
 * If failed, increments counter and locks if threshold exceeded.
 */
function recordLoginAttempt(PDO $pdo, string $accountType, string $identifier, bool $success): void
{
    if (!loginAttemptsTableExists($pdo)) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $maxAttempts = 5;
    $lockoutMinutes = 15;

    if ($success) {
        // Clear attempts on successful login
        $stmt = $pdo->prepare(
            'DELETE FROM login_attempts 
             WHERE ip_address = :ip AND account_type = :account_type AND account_identifier = :identifier'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':account_type' => $accountType,
            ':identifier' => $identifier,
        ]);
    } else {
        // Increment failed attempts
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (ip_address, account_type, account_identifier, attempt_count)
             VALUES (:ip, :account_type, :identifier, 1)
             ON DUPLICATE KEY UPDATE 
                attempt_count = attempt_count + 1,
                last_attempt = NOW(),
                is_locked = IF(attempt_count + 1 >= :max_attempts, 1, 0),
                locked_until = IF(
                    attempt_count + 1 >= :max_attempts,
                    DATE_ADD(NOW(), INTERVAL :lockout_minutes MINUTE),
                    NULL
                )'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':account_type' => $accountType,
            ':identifier' => $identifier,
            ':max_attempts' => $maxAttempts,
            ':lockout_minutes' => $lockoutMinutes,
        ]);
    }
}

/**
 * Check whether the login_attempts table exists.
 */
function loginAttemptsTableExists(PDO $pdo): bool
{
    static $knownState = null;

    if ($knownState !== null) {
        return $knownState;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
             LIMIT 1'
        );
        $stmt->execute([':table_name' => 'login_attempts']);
        $knownState = (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        $knownState = false;
    }

    return $knownState;
}
