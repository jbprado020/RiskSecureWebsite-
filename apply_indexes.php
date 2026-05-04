<?php
/**
 * Apply database indexes to RiskSecure system
 * Run once: php apply_indexes.php
 */

require_once __DIR__ . '/config/db.php';

try {
    $pdo = db();
    
    // Read index SQL file
    $indexSql = file_get_contents(__DIR__ . '/database/add_indexes.sql');
    
    if (!$indexSql) {
        echo "Error: Could not read add_indexes.sql\n";
        exit(1);
    }
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $indexSql)), function($stmt) {
        return strlen($stmt) > 0 && !str_starts_with(trim($stmt), '--');
    });
    
    $count = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $count++;
            echo "✓ Executed index statement $count\n";
        } catch (Exception $e) {
            // Index may already exist; continue
            echo "ℹ Statement $count: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Indexing completed. Total statements processed: $count\n";
    
} catch (Throwable $exception) {
    echo "Error: " . $exception->getMessage() . "\n";
    exit(1);
}
