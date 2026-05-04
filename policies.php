<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/insurance_service.php';

requireStaffRole(['admin', 'manager', 'underwriter']);

$pdo = db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_policy'])) {
    requireCsrfToken();

    $quoteId = (int) ($_POST['quote_id'] ?? 0);
    $partnerId = (int) ($_POST['partner_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? date('Y-m-d');

    if ($quoteId > 0 && $partnerId > 0) {
        try {
            $pdo->beginTransaction();

            $quoteStmt = $pdo->prepare(
                'SELECT q.client_id, q.term_months, q.policy_type, q.coverage_amount, q.premium_amount
                 FROM quotes q
                 LEFT JOIN policies p ON p.quote_id = q.id
                 WHERE q.id = :id AND q.status = "approved" AND p.id IS NULL
                 FOR UPDATE'
            );
            $quoteStmt->execute([':id' => $quoteId]);
            $quote = $quoteStmt->fetch();

            if (!$quote) {
                $pdo->rollBack();
                $error = 'Selected quote is not eligible for policy issuance.';
            } else {
                $endDate = date('Y-m-d', strtotime($startDate . ' + ' . ((int) $quote['term_months']) . ' months -1 day'));
                $policyNumber = generatePolicyNumber();

                $stmt = $pdo->prepare(
                    'INSERT INTO policies (quote_id, client_id, partner_id, policy_number, policy_type, coverage_amount, premium, start_date, end_date, status)
                     VALUES (:quote_id, :client_id, :partner_id, :policy_number, :policy_type, :coverage_amount, :premium, :start_date, :end_date, :status)'
                );
                $stmt->execute([
                    ':quote_id' => $quoteId,
                    ':client_id' => (int) $quote['client_id'],
                    ':partner_id' => $partnerId,
                    ':policy_number' => $policyNumber,
                    ':policy_type' => $quote['policy_type'],
                    ':coverage_amount' => $quote['coverage_amount'],
                    ':premium' => $quote['premium_amount'],
                    ':start_date' => $startDate,
                    ':end_date' => $endDate,
                    ':status' => 'active',
                ]);

                $pdo->commit();
                $message = 'Policy issued successfully with number ' . $policyNumber . '.';
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Unable to issue policy right now. Please try again.';
        }
    } else {
        $error = 'Select both an approved quote and an insurance partner.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $partnerId = (int) ($_POST['partner_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'active');
    $allowedStatuses = ['active', 'expired', 'pending_renewal', 'cancelled'];

    if ($policyId > 0 && $partnerId > 0 && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare(
            'UPDATE policies
             SET partner_id = :partner_id,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            ':partner_id' => $partnerId,
            ':status' => $status,
            ':id' => $policyId,
        ]);
        $message = 'Policy details updated.';
    } else {
        $error = 'Invalid policy update request.';
    }
}

$partners = $pdo->query(
    'SELECT id, company_name, insurance_type
     FROM insurance_partners
     ORDER BY company_name ASC'
)->fetchAll();

$approvedQuotes = $pdo->query(
    'SELECT q.id, q.product_name, q.term_months, q.policy_type, c.full_name
     FROM quotes q
     INNER JOIN clients c ON c.id = q.client_id
     LEFT JOIN policies p ON p.quote_id = q.id
     WHERE q.status = "approved" AND p.id IS NULL
     ORDER BY q.created_at DESC'
)->fetchAll();

$policies = $pdo->query(
    'SELECT p.*, q.product_name, c.full_name, ip.company_name
     FROM policies p
     INNER JOIN quotes q ON q.id = p.quote_id
    INNER JOIN clients c ON c.id = p.client_id
     INNER JOIN insurance_partners ip ON ip.id = p.partner_id
     ORDER BY p.issued_at DESC'
)->fetchAll();

renderHeader('Policies');
?>

<section class="card">
    <h2>Issue Policy From Approved Quote</h2>
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2">
        <?= csrfField(); ?>
        <input type="hidden" name="issue_policy" value="1">
        <div>
            <label>Approved Quote</label>
            <select name="quote_id" required>
                <option value="">Select approved quote</option>
                <?php foreach ($approvedQuotes as $quote): ?>
                    <option value="<?= (int) $quote['id']; ?>">
                        #<?= (int) $quote['id']; ?> - <?= e($quote['full_name']); ?> (<?= e($quote['product_name']); ?>, <?= e(statusLabel((string) $quote['policy_type'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Insurance Partner</label>
            <select name="partner_id" required>
                <option value="">Select partner</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?= (int) $partner['id']; ?>">
                        <?= e((string) $partner['company_name']); ?> (<?= e(statusLabel((string) $partner['insurance_type'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Policy Start Date</label>
            <input type="date" name="start_date" value="<?= date('Y-m-d'); ?>" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Issue Policy</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Policy List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy Number</th>
                <th>Client</th>
                <th>Partner</th>
                <th>Policy Type</th>
                <th>Product</th>
                <th>Coverage</th>
                <th>Premium</th>
                <th>Coverage Period</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($policies as $policy): ?>
            <tr>
                <td><?= (int) $policy['id']; ?></td>
                <td><?= e($policy['policy_number']); ?></td>
                <td><?= e($policy['full_name']); ?></td>
                <td><?= e((string) $policy['company_name']); ?></td>
                <td><?= e(statusLabel((string) $policy['policy_type'])); ?></td>
                <td><?= e($policy['product_name']); ?></td>
                <td>PHP <?= number_format((float) $policy['coverage_amount'], 2); ?></td>
                <td>PHP <?= number_format((float) $policy['premium'], 2); ?></td>
                <td><?= e($policy['start_date']); ?> to <?= e($policy['end_date']); ?></td>
                <td><span class="badge <?= badgeClass((string) $policy['status']); ?>"><?= e(statusLabel((string) $policy['status'])); ?></span></td>
                <td>
                    <form method="post" style="display:grid; gap:0.35rem; min-width:220px;">
                        <?= csrfField(); ?>
                        <input type="hidden" name="update_policy" value="1">
                        <input type="hidden" name="policy_id" value="<?= (int) $policy['id']; ?>">
                        <select name="partner_id">
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?= (int) $partner['id']; ?>" <?= (int) $policy['partner_id'] === (int) $partner['id'] ? 'selected' : ''; ?>>
                                    <?= e((string) $partner['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status">
                            <?php $statusOptions = ['active', 'expired', 'pending_renewal', 'cancelled']; ?>
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= e($statusOption); ?>" <?= (string) $policy['status'] === $statusOption ? 'selected' : ''; ?>>
                                    <?= e(statusLabel($statusOption)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($policies) === 0): ?>
                <tr><td colspan="11">No policies issued yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
renderFooter();
