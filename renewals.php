<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/audit_helpers.php';

requireStaffRole(['admin', 'manager', 'underwriter']);

$pdo = db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_renewal'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $renewalDate = trim((string) ($_POST['renewal_date'] ?? date('Y-m-d')));
    $newExpiry = trim((string) ($_POST['new_expiry'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'notified');
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $allowedStatuses = ['notified', 'in_progress', 'renewed', 'lapsed'];

    if ($policyId > 0 && $renewalDate !== '' && $newExpiry !== '' && in_array($status, $allowedStatuses, true)) {
        try {
            $pdo->beginTransaction();

            $policyStmt = $pdo->prepare('SELECT id, end_date FROM policies WHERE id = :id LIMIT 1 FOR UPDATE');
            $policyStmt->execute([':id' => $policyId]);
            $policy = $policyStmt->fetch();

            if (!$policy) {
                $pdo->rollBack();
                $error = 'Selected policy was not found.';
            } else {
                $previousExpiry = (string) $policy['end_date'];

                $stmt = $pdo->prepare(
                    'INSERT INTO renewals (policy_id, renewal_date, previous_expiry, new_expiry, status, notes)
                     VALUES (:policy_id, :renewal_date, :previous_expiry, :new_expiry, :status, :notes)'
                );
                $stmt->execute([
                    ':policy_id' => $policyId,
                    ':renewal_date' => $renewalDate,
                    ':previous_expiry' => $previousExpiry,
                    ':new_expiry' => $newExpiry,
                    ':status' => $status,
                    ':notes' => $notes,
                ]);

                if ($status === 'renewed') {
                    $pdo->prepare('UPDATE policies SET end_date = :end_date, status = :status WHERE id = :id')->execute([
                        ':end_date' => $newExpiry,
                        ':status' => 'active',
                        ':id' => $policyId,
                    ]);
                } elseif ($status === 'lapsed') {
                    $pdo->prepare('UPDATE policies SET status = :status WHERE id = :id')->execute([
                        ':status' => 'expired',
                        ':id' => $policyId,
                    ]);
                } else {
                    $pdo->prepare('UPDATE policies SET status = :status WHERE id = :id')->execute([
                        ':status' => 'pending_renewal',
                        ':id' => $policyId,
                    ]);
                }

                $pdo->commit();
                logAuditEvent($pdo, 'create_renewal', [
                    'entity_type' => 'renewals',
                    'entity_id' => (int) $pdo->lastInsertId(),
                    'status' => 'success',
                    'details' => 'Created renewal for policy ID ' . $policyId . ' with status ' . $status . '.',
                ]);
                $message = 'Renewal process entry created.';
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Unable to create renewal entry right now. Please try again.';
        }
    } else {
        $error = 'Please provide valid renewal details.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_renewal'])) {
    requireCsrfToken();

    $renewalId = (int) ($_POST['renewal_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'notified');
    $newExpiry = trim((string) ($_POST['new_expiry'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $allowedStatuses = ['notified', 'in_progress', 'renewed', 'lapsed'];

    if ($renewalId > 0 && in_array($status, $allowedStatuses, true)) {
        try {
            $pdo->beginTransaction();

            $renewalStmt = $pdo->prepare('SELECT id, policy_id, new_expiry FROM renewals WHERE id = :id LIMIT 1 FOR UPDATE');
            $renewalStmt->execute([':id' => $renewalId]);
            $renewal = $renewalStmt->fetch();

            if (!$renewal) {
                $pdo->rollBack();
                $error = 'Renewal record was not found.';
            } else {
                $finalNewExpiry = $newExpiry !== '' ? $newExpiry : (string) $renewal['new_expiry'];

                $stmt = $pdo->prepare(
                    'UPDATE renewals
                     SET status = :status,
                         new_expiry = :new_expiry,
                         notes = :notes
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':status' => $status,
                    ':new_expiry' => $finalNewExpiry,
                    ':notes' => $notes,
                    ':id' => $renewalId,
                ]);

                if ($status === 'renewed') {
                    $pdo->prepare('UPDATE policies SET end_date = :end_date, status = :status WHERE id = :id')->execute([
                        ':end_date' => $finalNewExpiry,
                        ':status' => 'active',
                        ':id' => (int) $renewal['policy_id'],
                    ]);
                } elseif ($status === 'lapsed') {
                    $pdo->prepare('UPDATE policies SET status = :status WHERE id = :id')->execute([
                        ':status' => 'expired',
                        ':id' => (int) $renewal['policy_id'],
                    ]);
                } else {
                    $pdo->prepare('UPDATE policies SET status = :status WHERE id = :id')->execute([
                        ':status' => 'pending_renewal',
                        ':id' => (int) $renewal['policy_id'],
                    ]);
                }

                $pdo->commit();
                logAuditEvent($pdo, 'update_renewal', [
                    'entity_type' => 'renewals',
                    'entity_id' => $renewalId,
                    'status' => 'success',
                    'details' => 'Updated renewal status to ' . $status . '.',
                ]);
                $message = 'Renewal status updated.';
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Unable to update renewal right now. Please try again.';
        }
    } else {
        $error = 'Invalid renewal update request.';
    }
}

$policies = $pdo->query(
    'SELECT p.id, p.policy_number, p.end_date, p.status, c.full_name
     FROM policies p
    INNER JOIN clients c ON c.id = p.client_id
     ORDER BY p.end_date ASC'
)->fetchAll();

$expiringSoon = $pdo->query(
    'SELECT p.policy_number, p.end_date, c.full_name, DATEDIFF(p.end_date, CURDATE()) AS days_to_expiry
     FROM policies p
    INNER JOIN clients c ON c.id = p.client_id
     WHERE p.status IN ("active", "pending_renewal")
       AND p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY p.end_date ASC'
)->fetchAll();

$renewals = $pdo->query(
    'SELECT r.*, p.policy_number, c.full_name
     FROM renewals r
     INNER JOIN policies p ON p.id = r.policy_id
    INNER JOIN clients c ON c.id = p.client_id
     ORDER BY r.renewal_date DESC, r.created_at DESC'
)->fetchAll();

renderHeader('Renewals');
?>

<section class="card">
    <h2>Renewals and Expiry Monitoring</h2>
    <p>Monitor expiring policies, process renewals, and update renewal status.</p>

    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Process Policy Renewal</h2>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="create_renewal" value="1">
            <div>
                <label>Policy</label>
                <select name="policy_id" required>
                    <option value="">Select policy</option>
                    <?php foreach ($policies as $policy): ?>
                        <option value="<?= (int) $policy['id']; ?>">
                            <?= e((string) $policy['policy_number']); ?> - <?= e((string) $policy['full_name']); ?> (expires <?= e((string) $policy['end_date']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Renewal Date</label>
                <input type="date" name="renewal_date" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>New Expiry Date</label>
                <input type="date" name="new_expiry" required>
            </div>
            <div>
                <label>Renewal Status</label>
                <select name="status" required>
                    <option value="notified">Notified</option>
                    <option value="in_progress">In Progress</option>
                    <option value="renewed">Renewed</option>
                    <option value="lapsed">Lapsed</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <label>Notes</label>
                <textarea name="notes" placeholder="e.g. Waiting for client confirmation"></textarea>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Create Renewal Entry</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>View Expiring Policies Dashboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Policy</th>
                    <th>Client</th>
                    <th>Expiry</th>
                    <th>Days Left</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expiringSoon as $policy): ?>
                    <tr>
                        <td><?= e((string) $policy['policy_number']); ?></td>
                        <td><?= e((string) $policy['full_name']); ?></td>
                        <td><?= e((string) $policy['end_date']); ?></td>
                        <td><?= (int) $policy['days_to_expiry']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($expiringSoon) === 0): ?>
                    <tr><td colspan="4">No policies expiring within 60 days.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="card">
    <h2>Update Renewal Status</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Policy</th>
                    <th>Client</th>
                    <th>Renewal Date</th>
                    <th>Previous Expiry</th>
                    <th>New Expiry</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($renewals as $renewal): ?>
                    <tr>
                        <td><?= (int) $renewal['id']; ?></td>
                        <td><?= e((string) $renewal['policy_number']); ?></td>
                        <td><?= e((string) $renewal['full_name']); ?></td>
                        <td><?= e((string) $renewal['renewal_date']); ?></td>
                        <td><?= e((string) $renewal['previous_expiry']); ?></td>
                        <td><?= e((string) $renewal['new_expiry']); ?></td>
                        <td><span class="badge <?= badgeClass((string) $renewal['status']); ?>"><?= e(statusLabel((string) $renewal['status'])); ?></span></td>
                        <td>
                            <form method="post" class="grid renewal-update-form">
                                <?= csrfField(); ?>
                                <input type="hidden" name="update_renewal" value="1">
                                <input type="hidden" name="renewal_id" value="<?= (int) $renewal['id']; ?>">
                                <select name="status">
                                    <?php $statusOptions = ['notified', 'in_progress', 'renewed', 'lapsed']; ?>
                                    <?php foreach ($statusOptions as $statusOption): ?>
                                        <option value="<?= e($statusOption); ?>" <?= (string) $renewal['status'] === $statusOption ? 'selected' : ''; ?>>
                                            <?= e(statusLabel($statusOption)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" name="new_expiry" value="<?= e((string) $renewal['new_expiry']); ?>">
                                <input name="notes" value="<?= e((string) ($renewal['notes'] ?? '')); ?>" placeholder="Notes">
                                <button type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($renewals) === 0): ?>
                    <tr><td colspan="8">No renewal records yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
renderFooter();
