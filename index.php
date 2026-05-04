<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer']);

$pdo = db();

$kpis = [
    'clients' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
    'quotes_pending' => (int) $pdo->query("SELECT COUNT(*) FROM quotes WHERE status = 'pending'")->fetchColumn(),
    'active_policies' => (int) $pdo->query("SELECT COUNT(*) FROM policies WHERE status = 'active'")->fetchColumn(),
    'open_claims' => (int) $pdo->query("SELECT COUNT(*) FROM claims WHERE claim_status IN ('pending','under_review')")->fetchColumn(),
    'pending_renewals' => (int) $pdo->query("SELECT COUNT(*) FROM policies WHERE status = 'pending_renewal'")->fetchColumn(),
];

$recentQuotes = $pdo->query(
    'SELECT q.id, c.full_name, q.policy_type, q.premium_amount, q.status, q.created_at
     FROM quotes q
     INNER JOIN clients c ON c.id = q.client_id
     ORDER BY q.created_at DESC
     LIMIT 5'
)->fetchAll();

$recentClaims = $pdo->query(
    'SELECT cl.id, p.policy_number, cl.claim_amount, cl.claim_status, cl.created_at
     FROM claims cl
     INNER JOIN policies p ON p.id = cl.policy_id
     ORDER BY cl.created_at DESC
     LIMIT 5'
)->fetchAll();

renderHeader('Dashboard');
?>

<section class="grid cols-4">
    <article class="card kpi">
        <h3>Total Clients</h3>
        <p><?= $kpis['clients']; ?></p>
    </article>
    <article class="card kpi">
        <h3>Pending Quotes</h3>
        <p><?= $kpis['quotes_pending']; ?></p>
    </article>
    <article class="card kpi">
        <h3>Active Policies</h3>
        <p><?= $kpis['active_policies']; ?></p>
    </article>
    <article class="card kpi">
        <h3>Open Claims</h3>
        <p><?= $kpis['open_claims']; ?></p>
    </article>
    <article class="card kpi">
        <h3>Pending Renewals</h3>
        <p><?= $kpis['pending_renewals']; ?></p>
    </article>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Recent Quotes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Premium</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentQuotes as $quote): ?>
                <tr>
                    <td><?= (int) $quote['id']; ?></td>
                    <td><?= e($quote['full_name']); ?></td>
                    <td><?= e($quote['policy_type']); ?></td>
                    <td>PHP <?= number_format((float) $quote['premium_amount'], 2); ?></td>
                    <td><span class="badge <?= badgeClass($quote['status']); ?>"><?= e($quote['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Recent Claims</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Policy No.</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentClaims as $claim): ?>
                <tr>
                    <td><?= (int) $claim['id']; ?></td>
                    <td><?= e($claim['policy_number']); ?></td>
                    <td>PHP <?= number_format((float) $claim['claim_amount'], 2); ?></td>
                    <td><span class="badge <?= badgeClass((string) $claim['claim_status']); ?>"><?= e(statusLabel((string) $claim['claim_status'])); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>

<?php
renderFooter();
