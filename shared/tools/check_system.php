<?php
// tools/check_system.php
// Usage: run from project root: php tools/check_system.php
$root = realpath(__DIR__ . '/..');
if (!file_exists($root . '/config/db.php')) {
    echo "config/db.php not found at expected location: {$root}/config/db.php\n";
    exit(1);
}
require_once $root . '/config/db.php';

echo "Project root: $root\n";
echo "PHP version: " . PHP_VERSION . "\n";

try {
    $pdo = db();
    echo "DB: connection OK\n";
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Database selected: " . ($dbName ?: 'NULL') . "\n";

    $tables = [
        'login_attempts',
        'audit_logs',
        'documents',
        'staff_accounts',
        'customer_accounts',
        'users',
        'customers'
    ];

    foreach ($tables as $t) {
        $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " . $pdo->quote($t);
        $exists = (int)$pdo->query($sql)->fetchColumn();
        echo sprintf("Table %-20s : %s\n", $t, $exists ? 'FOUND' : 'MISSING');
    }

    echo "\nAll tables in current database:\n";
    $rows = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    if ($rows) {
        foreach ($rows as $r) {
            echo " - $r\n";
        }
    } else {
        echo " (no tables found or insufficient privileges)\n";
    }

} catch (PDOException $e) {
    echo "DB: connection FAILED: " . $e->getMessage() . "\n";
    exit(2);
}

// End
