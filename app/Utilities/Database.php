<?php

namespace App\Utilities;

declare(strict_types=1);

/**
 * Lightweight Database utility that wraps the existing `db()` function.
 * This file does not change existing `config/db.php` behavior; it simply
 * provides a namespaced accessor for new service classes.
 */
class Database
{
    public static function getPdo(): \PDO
    {
        // Use existing config/db.php which defines a db() function
        $configPath = __DIR__ . '/../../config/db.php';
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Database config not found.');
        }

        require_once $configPath;

        if (!function_exists('db')) {
            throw new \RuntimeException('db() function not available in config/db.php');
        }

        return \db();
    }
}
