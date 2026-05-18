<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function auditClientIp(): string
{
    $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');

    if ($forwardedFor !== '') {
        $parts = array_map('trim', explode(',', $forwardedFor));
        foreach ($parts as $part) {
            if ($part !== '') {
                return $part;
            }
        }
    }

    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function auditUserAgent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function auditActorContext(): array
{
    ensureSessionStarted();

    if (isStaffLoggedIn()) {
        return [
            'actor_type' => 'staff',
            'actor_id' => staffId(),
            'actor_name' => staffName(),
            'actor_email' => staffEmail(),
            'actor_role' => staffRole(),
        ];
    }

    if (isCustomerLoggedIn()) {
        return [
            'actor_type' => 'customer',
            'actor_id' => customerClientId(),
            'actor_name' => customerName(),
            'actor_email' => customerEmail(),
            'actor_role' => null,
        ];
    }

    return [
        'actor_type' => 'system',
        'actor_id' => null,
        'actor_name' => null,
        'actor_email' => null,
        'actor_role' => null,
    ];
}

function logAuditEvent(PDO $pdo, string $action, array $context = []): void
{
    try {
        $actor = array_merge(auditActorContext(), $context);
        $ipAddress = auditClientIp();
        $userAgent = auditUserAgent();

        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (
                actor_type, actor_id, actor_name, actor_email, actor_role,
                action, entity_type, entity_id, status, details, ip_address, user_agent
             ) VALUES (
                :actor_type, :actor_id, :actor_name, :actor_email, :actor_role,
                :action, :entity_type, :entity_id, :status, :details, :ip_address, :user_agent
             )'
        );
        $stmt->execute([
            ':actor_type' => (string) ($actor['actor_type'] ?? 'system'),
            ':actor_id' => $actor['actor_id'] !== null ? (int) $actor['actor_id'] : null,
            ':actor_name' => $actor['actor_name'] !== null ? (string) $actor['actor_name'] : null,
            ':actor_email' => $actor['actor_email'] !== null ? (string) $actor['actor_email'] : null,
            ':actor_role' => $actor['actor_role'] !== null ? (string) $actor['actor_role'] : null,
            ':action' => $action,
            ':entity_type' => isset($actor['entity_type']) ? (string) $actor['entity_type'] : null,
            ':entity_id' => isset($actor['entity_id']) && $actor['entity_id'] !== null ? (int) $actor['entity_id'] : null,
            ':status' => (string) ($actor['status'] ?? 'success'),
            ':details' => isset($actor['details']) && $actor['details'] !== '' ? (string) $actor['details'] : null,
            ':ip_address' => $ipAddress !== '' ? $ipAddress : null,
            ':user_agent' => $userAgent !== '' ? $userAgent : null,
        ]);
    } catch (Throwable $_) {
        // Audit logging must never break the primary request path.
    }
}