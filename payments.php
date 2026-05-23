<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager', 'billing_officer']);

$pdo = db();
$message = '';

$pdo->exec("UPDATE payments SET status = 'overdue' WHERE status = 'pending' AND due_date < CURDATE()");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $dueDate = $_POST['due_date'] ?? date('Y-m-d');

    if ($policyId > 0 && $amount > 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO payments (policy_id, amount, due_date, status) VALUES (:policy_id, :amount, :due_date, :status)'
        );
        $stmt->execute([
            ':policy_id' => $policyId,
            ':amount' => $amount,
            ':due_date' => $dueDate,
            ':status' => 'pending',
        ]);
        $message = 'Payment schedule added.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    requireCsrfToken();

    $paymentId = (int) ($_POST['payment_id'] ?? 0);

    if ($paymentId > 0) {
        $stmt = $pdo->prepare('UPDATE payments SET status = :status, paid_date = :paid_date WHERE id = :id');
        $stmt->execute([
            ':status' => 'paid',
            ':paid_date' => date('Y-m-d'),
            ':id' => $paymentId,
        ]);
        $message = 'Payment marked as paid.';
    }
}

$policies = $pdo->query(
    'SELECT p.id, p.policy_number, c.full_name
     FROM policies p
    INNER JOIN clients c ON c.id = p.client_id
     ORDER BY p.issued_at DESC'
)->fetchAll();

$payments = $pdo->query(
    'SELECT pay.*, p.policy_number
     FROM payments pay
     INNER JOIN policies p ON p.id = pay.policy_id
     ORDER BY pay.created_at DESC'
)->fetchAll();

renderHeader('Payments');
?>

<section class="card">
    <h2>Create Payment Schedule</h2>
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <input type="hidden" name="create_payment" value="1">
        <div>
            <label>Policy</label>
            <select name="policy_id" required aria-label="Policy" aria-required="true">
                <option value="">Select policy</option>
                <?php foreach ($policies as $policy): ?>
                    <option value="<?= (int) $policy['id']; ?>">
                        <?= e($policy['policy_number']); ?> - <?= e($policy['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Amount (PHP)</label>
            <input type="number" step="0.01" min="1" name="amount" required aria-label="Amount" aria-required="true">
        </div>
        <div>
            <label>Due Date</label>
            <input type="date" name="due_date" value="<?= date('Y-m-d'); ?>" required aria-label="Due Date" aria-required="true">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Save Payment Schedule</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Payment Ledger</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy No.</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Paid Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= (int) $payment['id']; ?></td>
                <td><?= e($payment['policy_number']); ?></td>
                <td>PHP <?= number_format((float) $payment['amount'], 2); ?></td>
                <td><?= e($payment['due_date']); ?></td>
                <td><?= e($payment['paid_date'] ?? '-'); ?></td>
                <td><span class="badge <?= badgeClass((string) $payment['status']); ?>"><?= e(statusLabel((string) $payment['status'])); ?></span></td>
                <td>
                    <?php if ($payment['status'] !== 'paid'): ?>
                    <form method="post">
                        <?= csrfField(); ?>
                        <input type="hidden" name="mark_paid" value="1">
                        <input type="hidden" name="payment_id" value="<?= (int) $payment['id']; ?>">
                        <button type="submit">Mark Paid</button>
                    </form>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php
renderFooter();
