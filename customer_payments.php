<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_helpers.php';

requireCustomerLogin();

$pdo = db();
$clientId = customerClientId();

$paymentsStmt = $pdo->prepare(
    'SELECT pay.id, p.policy_number, pay.amount, pay.due_date, pay.paid_date, pay.status
     FROM payments pay
     INNER JOIN policies p ON p.id = pay.policy_id
    WHERE p.client_id = :client_id
     ORDER BY pay.created_at DESC'
);
$paymentsStmt->execute([':client_id' => $clientId]);
$accountPayments = $paymentsStmt->fetchAll();

renderHeader('Payments & Billings');
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Payments & Billings</h1>
        <p>Review your payment history and upcoming insurance premiums.</p>
    </div>
</div>

<section class="card">
    <div class="section-header">
        <h2><span style="display: inline-flex; align-items: center; gap: 0.5rem;"><?= iconMarkup('payments'); ?> Your Payment History</span></h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Policy Number</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Paid Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accountPayments as $payment): ?>
                    <tr>
                        <td>#<?= (int) $payment['id']; ?></td>
                        <td><?= e((string) $payment['policy_number']); ?></td>
                        <td>PHP <?= number_format((float) $payment['amount'], 2); ?></td>
                        <td><?= e((string) $payment['due_date']); ?></td>
                        <td><?= e((string) ($payment['paid_date'] ?? '-')); ?></td>
                        <td><span class="badge <?= badgeClass((string) $payment['status']); ?>"><?= e(statusLabel((string) $payment['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($accountPayments) === 0): ?>
                    <tr><td colspan="6" class="empty-state">No payment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
renderFooter();
