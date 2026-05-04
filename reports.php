<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer']);

$pdo = db();
$startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
$endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-t')));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-t');
}
if ($startDate > $endDate) {
    $tmp = $startDate;
    $startDate = $endDate;
    $endDate = $tmp;
}

$quoteSummaryStmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM quotes
     WHERE DATE(created_at) BETWEEN :start_date AND :end_date
     GROUP BY status'
);
$quoteSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$quoteSummary = $quoteSummaryStmt->fetchAll();

$policySummaryStmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM policies
     WHERE DATE(issued_at) BETWEEN :start_date AND :end_date
     GROUP BY status'
);
$policySummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$policySummary = $policySummaryStmt->fetchAll();

$claimSummaryStmt = $pdo->prepare(
    'SELECT claim_status, COUNT(*) AS total, COALESCE(SUM(claim_amount), 0) AS total_amount
     FROM claims
     WHERE DATE(created_at) BETWEEN :start_date AND :end_date
     GROUP BY claim_status'
);
$claimSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$claimSummary = $claimSummaryStmt->fetchAll();

$claimPaymentSummaryStmt = $pdo->prepare(
    'SELECT COUNT(*) AS payment_count, COALESCE(SUM(amount), 0) AS total_paid
     FROM claim_payments
     WHERE paid_date BETWEEN :start_date AND :end_date'
);
$claimPaymentSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$claimPaymentSummary = $claimPaymentSummaryStmt->fetch();

$renewalSummaryStmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM renewals
     WHERE renewal_date BETWEEN :start_date AND :end_date
     GROUP BY status'
);
$renewalSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$renewalSummary = $renewalSummaryStmt->fetchAll();

$meetingSummaryStmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total
     FROM meeting_schedules
     WHERE DATE(meeting_at) BETWEEN :start_date AND :end_date
     GROUP BY status'
);
$meetingSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$meetingSummary = $meetingSummaryStmt->fetchAll();

$documentSummaryStmt = $pdo->prepare(
    'SELECT uploaded_by, COUNT(*) AS total
     FROM documents
     WHERE DATE(date_uploaded) BETWEEN :start_date AND :end_date
     GROUP BY uploaded_by'
);
$documentSummaryStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$documentSummary = $documentSummaryStmt->fetchAll();

$clientTrendStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(created_at, "%Y-%m") AS period,
            COUNT(*) AS total_clients
     FROM clients
     WHERE DATE(created_at) BETWEEN :start_date AND :end_date
     GROUP BY DATE_FORMAT(created_at, "%Y-%m")
     ORDER BY period ASC'
);
$clientTrendStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$clientTrend = $clientTrendStmt->fetchAll();

$policyTrendStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(issued_at, "%Y-%m") AS period,
            policy_type,
            COUNT(*) AS total_policies,
            COALESCE(SUM(premium), 0) AS total_premium
     FROM policies
     WHERE DATE(issued_at) BETWEEN :start_date AND :end_date
     GROUP BY DATE_FORMAT(issued_at, "%Y-%m"), policy_type
     ORDER BY period ASC, policy_type ASC'
);
$policyTrendStmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate,
]);
$policyTrend = $policyTrendStmt->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $fileName = 'risksecure_summary_' . $startDate . '_to_' . $endDate . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $out = fopen('php://output', 'wb');
    fputcsv($out, ['Section', 'Metric', 'Value']);

    foreach ($quoteSummary as $row) {
        fputcsv($out, ['Quotes', statusLabel((string) $row['status']), (int) $row['total']]);
    }
    foreach ($policySummary as $row) {
        fputcsv($out, ['Policies', statusLabel((string) $row['status']), (int) $row['total']]);
    }
    foreach ($claimSummary as $row) {
        fputcsv($out, ['Claims', statusLabel((string) $row['claim_status']), (int) $row['total']]);
        fputcsv($out, ['Claims Amount', statusLabel((string) $row['claim_status']), number_format((float) $row['total_amount'], 2, '.', '')]);
    }
    fputcsv($out, ['Claim Payments', 'Payment Count', (int) ($claimPaymentSummary['payment_count'] ?? 0)]);
    fputcsv($out, ['Claim Payments', 'Total Paid', number_format((float) ($claimPaymentSummary['total_paid'] ?? 0), 2, '.', '')]);

    foreach ($renewalSummary as $row) {
        fputcsv($out, ['Renewals', statusLabel((string) $row['status']), (int) $row['total']]);
    }
    foreach ($meetingSummary as $row) {
        fputcsv($out, ['Appointments', statusLabel((string) $row['status']), (int) $row['total']]);
    }
    foreach ($documentSummary as $row) {
        fputcsv($out, ['Documents', statusLabel((string) $row['uploaded_by']), (int) $row['total']]);
    }
    foreach ($clientTrend as $row) {
        fputcsv($out, ['Client Trend', (string) $row['period'], (int) $row['total_clients']]);
    }
    foreach ($policyTrend as $row) {
        fputcsv($out, ['Policy Trend Count', (string) $row['period'] . ' - ' . statusLabel((string) $row['policy_type']), (int) $row['total_policies']]);
        fputcsv($out, ['Policy Trend Premium', (string) $row['period'] . ' - ' . statusLabel((string) $row['policy_type']), number_format((float) $row['total_premium'], 2, '.', '')]);
    }

    fclose($out);
    exit;
}

renderHeader('Reports');
?>

<section class="card">
    <h2>Generate Data Summaries</h2>
    <p>Review KPI summaries by date range and export report data as CSV.</p>
    <form method="get" class="grid cols-2">
        <div>
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= e($startDate); ?>" required>
        </div>
        <div>
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= e($endDate); ?>" required>
        </div>
        <div style="grid-column: 1 / -1; display:flex; gap:0.6rem;">
            <button type="submit">Refresh Summary</button>
            <button type="submit" name="export" value="csv">Export CSV</button>
        </div>
    </form>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Quote Summary</h2>
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($quoteSummary as $row): ?>
                    <tr>
                        <td><?= e(statusLabel((string) $row['status'])); ?></td>
                        <td><?= (int) $row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($quoteSummary) === 0): ?>
                    <tr><td colspan="2">No quote data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Policy Summary</h2>
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($policySummary as $row): ?>
                    <tr>
                        <td><?= e(statusLabel((string) $row['status'])); ?></td>
                        <td><?= (int) $row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($policySummary) === 0): ?>
                    <tr><td colspan="2">No policy data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Claim Summary</h2>
        <table>
            <thead><tr><th>Status</th><th>Total Claims</th><th>Total Amount</th></tr></thead>
            <tbody>
                <?php foreach ($claimSummary as $row): ?>
                    <tr>
                        <td><?= e(statusLabel((string) $row['claim_status'])); ?></td>
                        <td><?= (int) $row['total']; ?></td>
                        <td>PHP <?= number_format((float) $row['total_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($claimSummary) === 0): ?>
                    <tr><td colspan="3">No claim data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Claim Payment Summary</h2>
        <table>
            <tbody>
                <tr>
                    <th>Payment Count</th>
                    <td><?= (int) ($claimPaymentSummary['payment_count'] ?? 0); ?></td>
                </tr>
                <tr>
                    <th>Total Paid</th>
                    <td>PHP <?= number_format((float) ($claimPaymentSummary['total_paid'] ?? 0), 2); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Renewal Summary</h2>
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($renewalSummary as $row): ?>
                    <tr>
                        <td><?= e(statusLabel((string) $row['status'])); ?></td>
                        <td><?= (int) $row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($renewalSummary) === 0): ?>
                    <tr><td colspan="2">No renewal data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Appointment Summary</h2>
        <table>
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($meetingSummary as $row): ?>
                    <tr>
                        <td><?= e(statusLabel((string) $row['status'])); ?></td>
                        <td><?= (int) $row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($meetingSummary) === 0): ?>
                    <tr><td colspan="2">No appointment data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="card">
    <h2>Document Upload Summary</h2>
    <table>
        <thead><tr><th>Uploaded By</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($documentSummary as $row): ?>
                <tr>
                    <td><?= e(statusLabel((string) $row['uploaded_by'])); ?></td>
                    <td><?= (int) $row['total']; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($documentSummary) === 0): ?>
                <tr><td colspan="2">No document uploads in range.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Client Growth Trend</h2>
        <table>
            <thead><tr><th>Month</th><th>New Clients</th></tr></thead>
            <tbody>
                <?php foreach ($clientTrend as $row): ?>
                    <tr>
                        <td><?= e((string) $row['period']); ?></td>
                        <td><?= (int) $row['total_clients']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($clientTrend) === 0): ?>
                    <tr><td colspan="2">No client trend data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Policy Trend by Type</h2>
        <table>
            <thead><tr><th>Month</th><th>Type</th><th>Policies</th><th>Total Premium</th></tr></thead>
            <tbody>
                <?php foreach ($policyTrend as $row): ?>
                    <tr>
                        <td><?= e((string) $row['period']); ?></td>
                        <td><?= e(statusLabel((string) $row['policy_type'])); ?></td>
                        <td><?= (int) $row['total_policies']; ?></td>
                        <td>PHP <?= number_format((float) $row['total_premium'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($policyTrend) === 0): ?>
                    <tr><td colspan="4">No policy trend data in range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<?php
renderFooter();
