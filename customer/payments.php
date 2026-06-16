<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/includes/db_helpers.php';

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

<section class="card" id="history">
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
                    <th>Action</th>
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
                        <td>
                            <?php if ($payment['status'] === 'pending' || $payment['status'] === 'overdue'): ?>
                                <form action="initiate_payment.php" method="POST" style="display:inline;">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id']; ?>">
                                    <button type="submit" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Pay Online</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($accountPayments) === 0): ?>
                    <tr><td colspan="7" class="empty-state">No payment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card" id="methods" style="margin-top: 2rem;">
    <h2><?= iconMarkup('payments'); ?> Payment Methods</h2>
    <div class="grid cols-2">
        <div class="info-group">
            <p><strong>Linked Accounts</strong></p>
            <p class="text-muted">Manage your credit cards and bank accounts.</p>
            <div style="margin-top: 1rem;">
                <button class="btn-action">Add New Method</button>
            </div>
        </div>
        <div class="info-group" id="autopay">
            <p><strong>Auto-Pay Settings</strong></p>
            <p class="text-muted">Ensure your coverage never lapses with automatic payments.</p>
            <div style="margin-top: 1rem;">
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                    Enable Auto-Pay
                </label>
            </div>
        </div>
    </div>
</section>

<?php
renderFooter();
