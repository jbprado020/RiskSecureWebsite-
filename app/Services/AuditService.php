<?php

namespace App\Services;

use App\Utilities\Database;

declare(strict_types=1);

class AuditService
{
    public function log(int $staffId, string $action, string $table, int $recordId, ?array $old = null, ?array $new = null): void
    {
        $pdo = Database::getPdo();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (staff_id, action, table_name, record_id, old_values, new_values) VALUES (:staff_id, :action, :table_name, :record_id, :old_values, :new_values)');
        $stmt->execute([
            ':staff_id' => $staffId,
            ':action' => $action,
            ':table_name' => $table,
            ':record_id' => $recordId,
            ':old_values' => $old ? json_encode($old) : null,
            ':new_values' => $new ? json_encode($new) : null,
        ]);
    }
}
