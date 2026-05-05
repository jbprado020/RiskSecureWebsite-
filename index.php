<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer']);

$pdo = db();

$canViewClientPipeline = canAccess(['admin', 'manager', 'underwriter']);
$canViewClaims = canAccess(['admin', 'manager', 'underwriter', 'claims_officer']);
$canViewPayments = canAccess(['admin', 'manager', 'billing_officer']);

$kpis = [
    'clients' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
    'quotes_pending' => (int) $pdo->query("SELECT COUNT(*) FROM quotes WHERE status = 'pending'")->fetchColumn(),
    'active_policies' => (int) $pdo->query("SELECT COUNT(*) FROM policies WHERE status = 'active'")->fetchColumn(),
    'open_claims' => (int) $pdo->query("SELECT COUNT(*) FROM claims WHERE claim_status IN ('pending','under_review')")->fetchColumn(),
    'pending_renewals' => (int) $pdo->query("SELECT COUNT(*) FROM policies WHERE status = 'pending_renewal'")->fetchColumn(),
    'pending_payments' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','overdue')")->fetchColumn(),
];

$kpiCards = [];

if ($canViewClientPipeline) {
    $kpiCards[] = ['label' => 'Total Clients', 'value' => $kpis['clients']];
    $kpiCards[] = ['label' => 'Pending Quotes', 'value' => $kpis['quotes_pending']];
    $kpiCards[] = ['label' => 'Active Policies', 'value' => $kpis['active_policies']];
    $kpiCards[] = ['label' => 'Pending Renewals', 'value' => $kpis['pending_renewals']];
}

if ($canViewClaims) {
    $kpiCards[] = ['label' => 'Open Claims', 'value' => $kpis['open_claims']];
}

if ($canViewPayments) {
    $kpiCards[] = ['label' => 'Pending Payments', 'value' => $kpis['pending_payments']];
}

$recentQuotes = [];
if ($canViewClientPipeline) {
    $recentQuotes = $pdo->query(
        'SELECT q.id, c.full_name, q.policy_type, q.premium_amount, q.status, q.created_at
         FROM quotes q
         INNER JOIN clients c ON c.id = q.client_id
         ORDER BY q.created_at DESC
         LIMIT 5'
    )->fetchAll();
}

$recentClaims = [];
if ($canViewClaims) {
    $recentClaims = $pdo->query(
        'SELECT cl.id, p.policy_number, cl.claim_amount, cl.claim_status, cl.created_at
         FROM claims cl
         INNER JOIN policies p ON p.id = cl.policy_id
         ORDER BY cl.created_at DESC
         LIMIT 5'
    )->fetchAll();
}

$recentPayments = [];
if ($canViewPayments) {
    $recentPayments = $pdo->query(
        'SELECT pm.id, p.policy_number, pm.amount, pm.status, pm.due_date
         FROM payments pm
         INNER JOIN policies p ON p.id = pm.policy_id
         ORDER BY pm.created_at DESC
         LIMIT 5'
    )->fetchAll();
}

renderHeader('Dashboard');

$quickActions = [];

if (canAccess(['admin', 'manager', 'underwriter'])) {
    $quickActions[] = [
        'title' => 'Client Onboarding',
        'description' => 'Create and maintain customer profiles.',
        'href' => 'clients.php',
        'cta' => 'Open Clients',
    ];
    $quickActions[] = [
        'title' => 'Quote Management',
        'description' => 'Create, review, and approve insurance quotes.',
        'href' => 'quotes.php',
        'cta' => 'Open Quotes',
    ];
    $quickActions[] = [
        'title' => 'Policy Issuance',
        'description' => 'Issue policies from approved quotes.',
        'href' => 'policies.php',
        'cta' => 'Open Policies',
    ];
    $quickActions[] = [
        'title' => 'Renewal Tracking',
        'description' => 'Monitor and process upcoming renewals.',
        'href' => 'renewals.php',
        'cta' => 'Open Renewals',
    ];
}

if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer'])) {
    $quickActions[] = [
        'title' => 'Claims Processing',
        'description' => 'Review claims and complete decision workflow.',
        'href' => 'claims.php',
        'cta' => 'Open Claims',
    ];
    $quickActions[] = [
        'title' => 'Document Review',
        'description' => 'Inspect uploaded policy and claim documents.',
        'href' => 'documents.php',
        'cta' => 'Open Documents',
    ];
    $quickActions[] = [
        'title' => 'Meeting Schedule',
        'description' => 'Manage customer appointments and follow-ups.',
        'href' => 'meetings.php',
        'cta' => 'Open Meetings',
    ];
}

if (canAccess(['admin', 'manager', 'billing_officer'])) {
    $quickActions[] = [
        'title' => 'Payment Operations',
        'description' => 'Track premiums and record payment activity.',
        'href' => 'payments.php',
        'cta' => 'Open Payments',
    ];
}

if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer'])) {
    $quickActions[] = [
        'title' => 'Reports and Insights',
        'description' => 'Review operational and financial summaries.',
        'href' => 'reports.php',
        'cta' => 'Open Reports',
    ];
}

if (canAccess(['admin', 'manager'])) {
    $quickActions[] = [
        'title' => 'Insurance Partners',
        'description' => 'Maintain partner companies and contacts.',
        'href' => 'insurance_partners.php',
        'cta' => 'Open Partners',
    ];
}

if (canAccess(['admin'])) {
    $quickActions[] = [
        'title' => 'Staff Management',
        'description' => 'Manage staff accounts, roles, and access.',
        'href' => 'staff_management.php',
        'cta' => 'Open Staff Mgmt',
    ];
}
?>

<section class="grid cols-4">
    <?php foreach ($kpiCards as $card): ?>
    <article class="card kpi">
        <h3><?= e($card['label']); ?></h3>
        <p><?= (int) $card['value']; ?></p>
    </article>
    <?php endforeach; ?>
</section>

<?php if ($quickActions !== []): ?>
<section class="grid cols-2">
    <?php foreach ($quickActions as $action): ?>
    <article class="card admin-action">
        <h3><?= e($action['title']); ?></h3>
        <p><?= e($action['description']); ?></p>
        <a href="<?= e($action['href']); ?>" class="btn-action"><?= e($action['cta']); ?> -&gt;</a>
    </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($canViewClientPipeline || $canViewClaims || $canViewPayments): ?>
<section class="grid cols-2">
    <?php if ($canViewClientPipeline): ?>
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
                <?php if ($recentQuotes === []): ?>
                <tr>
                    <td colspan="5">No recent quotes found.</td>
                </tr>
                <?php endif; ?>
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
    <?php endif; ?>

    <?php if ($canViewClaims): ?>
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
                <?php if ($recentClaims === []): ?>
                <tr>
                    <td colspan="4">No recent claims found.</td>
                </tr>
                <?php endif; ?>
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
    <?php endif; ?>

    <?php if ($canViewPayments): ?>
    <article class="card">
        <h2>Recent Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Policy No.</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentPayments === []): ?>
                <tr>
                    <td colspan="5">No recent payments found.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($recentPayments as $payment): ?>
                <tr>
                    <td><?= (int) $payment['id']; ?></td>
                    <td><?= e($payment['policy_number']); ?></td>
                    <td>PHP <?= number_format((float) $payment['amount'], 2); ?></td>
                    <td><?= e((string) $payment['due_date']); ?></td>
                    <td><span class="badge <?= badgeClass((string) $payment['status']); ?>"><?= e(statusLabel((string) $payment['status'])); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php
renderFooter();
