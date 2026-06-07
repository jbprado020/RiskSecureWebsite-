<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'risk_secure_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Set a sane default isolation level for transactions to avoid
    // non-repeatable/phantom reads under high concurrency.
    // Use READ COMMITTED to reduce locking while providing reasonable consistency.
    try {
        $pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
    } catch (Throwable $e) {
        // Non-fatal: if the server doesn't support setting the isolation level here,
        // the connection will continue using the server default.
    }

    return $pdo;
}
