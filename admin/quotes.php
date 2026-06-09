<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/insurance_service.php';
require_once __DIR__ . '/../shared/includes/audit_helpers.php';
require_once __DIR__ . '/../shared/includes/validation.php';
require_once __DIR__ . '/../shared/includes/pagination.php';

requireStaffRole(['admin', 'manager', 'underwriter']);

$pdo = db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $policyType = (string) ($_POST['policy_type'] ?? 'life');
    $productName = trim($_POST['product_name'] ?? '');
    $coverageAmount = (float) ($_POST['coverage_amount'] ?? 0);
    $termMonths = (int) ($_POST['term_months'] ?? 12);
    $riskLevel = (string) ($_POST['risk_level'] ?? 'medium');
    $allowedPolicyTypes = ['life', 'non-life'];
    $allowedRiskLevels = ['low', 'medium', 'high'];

    if ($clientId > 0 && $productName !== '' && $coverageAmount > 0 && $termMonths > 0
        && in_array($policyType, $allowedPolicyTypes, true) && in_array($riskLevel, $allowedRiskLevels, true)
    ) {
        $premium = calculatePremium($coverageAmount, $policyType, $riskLevel, $termMonths);
        $stmt = $pdo->prepare(
            'INSERT INTO quotes (client_id, policy_type, product_name, coverage_amount, term_months, risk_level, premium_amount, status)
             VALUES (:client_id, :policy_type, :product_name, :coverage_amount, :term_months, :risk_level, :premium_amount, :status)'
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':policy_type' => $policyType,
            ':product_name' => $productName,
            ':coverage_amount' => $coverageAmount,
            ':term_months' => $termMonths,
            ':risk_level' => $riskLevel,
            ':premium_amount' => $premium,
            ':status' => 'pending',
        ]);
        logAuditEvent($pdo, 'create_quote', [
            'actor_type' => 'staff',
            'entity_type' => 'quotes',
            'entity_id' => (int) $pdo->lastInsertId(),
            'status' => 'success',
            'details' => 'Created quote for client ID ' . $clientId . ' with premium ' . number_format($premium, 2, '.', '') . '.',
        ]);
        $message = 'Quote created. Premium is PHP ' . number_format($premium, 2) . '.';
    } else {
        $error = 'Please provide valid quote details.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quote_status'])) {
    requireCsrfToken();

    $quoteId = (int) ($_POST['quote_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');

    if ($quoteId > 0 && in_array($status, ['approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE quotes SET status = :status WHERE id = :id')->execute([
            ':status' => $status,
            ':id' => $quoteId,
        ]);
        logAuditEvent($pdo, 'update_quote_status', [
            'actor_type' => 'staff',
            'entity_type' => 'quotes',
            'entity_id' => $quoteId,
            'status' => 'success',
            'details' => 'Quote status updated to ' . $status . '.',
        ]);
        header('Location: quotes.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_quote'])) {
    requireCsrfToken();

    $quoteId = (int) ($_POST['quote_id'] ?? 0);
    $productName = trim($_POST['product_name'] ?? '');
    $coverageAmount = (float) ($_POST['coverage_amount'] ?? 0);
    $termMonths = (int) ($_POST['term_months'] ?? 12);
    $riskLevel = (string) ($_POST['risk_level'] ?? 'medium');
    $policyType = (string) ($_POST['policy_type'] ?? 'life');

    if ($quoteId > 0 && $productName !== '' && $coverageAmount > 0 && $termMonths > 0) {
        $premium = calculatePremium($coverageAmount, $policyType, $riskLevel, $termMonths);
        $stmt = $pdo->prepare(
            'UPDATE quotes 
             SET product_name = :product_name, coverage_amount = :coverage_amount, term_months = :term_months, 
                 risk_level = :risk_level, premium_amount = :premium_amount
             WHERE id = :id'
        );
        $stmt->execute([
            ':product_name' => $productName,
            ':coverage_amount' => $coverageAmount,
            ':term_months' => $termMonths,
            ':risk_level' => $riskLevel,
            ':premium_amount' => $premium,
            ':id' => $quoteId,
        ]);
        logAuditEvent($pdo, 'edit_quote', [
            'actor_type' => 'staff',
            'entity_type' => 'quotes',
            'entity_id' => $quoteId,
            'status' => 'success',
            'details' => 'Edited quote ID ' . $quoteId . '. New premium: ' . number_format($premium, 2, '.', '') . '.',
        ]);
        $message = 'Quote updated successfully.';
    } else {
        $error = 'Invalid quote details for editing.';
    }
}

$clients = $pdo->query('SELECT id, full_name FROM clients ORDER BY full_name')->fetchAll();
$quotesP = paginatedQuery(
    $pdo,
    'SELECT COUNT(*) FROM quotes q INNER JOIN clients c ON c.id = q.client_id',
    'SELECT q.*, c.full_name FROM quotes q INNER JOIN clients c ON c.id = q.client_id ORDER BY q.created_at DESC',
    [],
    25,
    'quotes_page'
);
$quotes = $quotesP['data'];

renderHeader('Quotes');
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Quote Management</h1>
        <p>Review and process insurance applications and premium estimates.</p>
    </div>
</div>

<section class="card">
    <h2>Create Quote</h2>
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <?php renderNotice($error, 'error'); ?>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <div>
            <label>Client</label>
            <select name="client_id" required aria-label="Client" aria-required="true">
                <option value="">Select a client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id']; ?>"><?= e($client['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Policy Type</label>
            <select name="policy_type" required aria-label="Policy Type" aria-required="true">
                <option value="life">Life</option>
                <option value="non-life">Non-Life</option>
            </select>
        </div>
        <div>
            <label>Product Name</label>
            <input name="product_name" required placeholder="e.g. Family Life Shield" aria-label="Product Name" aria-required="true">
        </div>
        <div>
            <label>Risk Level</label>
            <select name="risk_level" required aria-label="Risk Level" aria-required="true">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>
        <div>
            <label>Coverage Amount (PHP)</label>
            <input name="coverage_amount" type="number" step="0.01" min="1" required aria-label="Coverage Amount" aria-required="true">
        </div>
        <div>
            <label>Term (Months)</label>
            <input name="term_months" type="number" min="1" value="12" required aria-label="Term (Months)" aria-required="true">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Compute Premium and Save Quote</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Quote List</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Product</th>
                <th>Coverage</th>
                <th>Premium</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quotes as $quote): ?>
            <tr>
                <td><?= (int) $quote['id']; ?></td>
                <td><?= e($quote['full_name']); ?></td>
                <td>
                    <?php if ($quote['status'] === 'pending'): ?>
                        <form method="post" id="edit-form-<?= (int) $quote['id']; ?>" class="grid" style="gap: 0.5rem; display: contents;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="edit_quote" value="1">
                            <input type="hidden" name="quote_id" value="<?= (int) $quote['id']; ?>">
                            <input type="hidden" name="policy_type" value="<?= e($quote['policy_type']); ?>">
                            <td><input name="product_name" value="<?= e($quote['product_name']); ?>" required style="padding: 0.25rem; font-size: 0.85rem;"> (<?= e($quote['policy_type']); ?>)</td>
                            <td>PHP <input name="coverage_amount" type="number" step="0.01" value="<?= (float) $quote['coverage_amount']; ?>" required style="padding: 0.25rem; font-size: 0.85rem; width: 100px;"></td>
                            <td>
                                PHP <?= number_format((float) $quote['premium_amount'], 2); ?>
                                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.25rem;">
                                    Term: <input name="term_months" type="number" value="<?= (int) $quote['term_months']; ?>" required style="padding: 0.1rem; width: 40px; font-size: 0.75rem;"> mo
                                    Risk: <select name="risk_level" style="padding: 0.1rem; font-size: 0.75rem; width: auto;">
                                        <option value="low" <?= $quote['risk_level'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?= $quote['risk_level'] === 'medium' ? 'selected' : ''; ?>>Med</option>
                                        <option value="high" <?= $quote['risk_level'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    </select>
                                </div>
                            </td>
                            <td><span class="badge <?= badgeClass((string) $quote['status']); ?>"><?= e(statusLabel((string) $quote['status'])); ?></span></td>
                            <td style="display: flex; flex-direction: column; gap: 0.4rem;">
                                <button type="submit" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--primary-light);">Update Details</button>
                        </form>
                        <form method="post" style="display: contents;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_quote_status" value="1">
                            <input type="hidden" name="quote_id" value="<?= (int) $quote['id']; ?>">
                            <button type="submit" name="status" value="approved" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--success);">Approve</button>
                            <button type="submit" name="status" value="rejected" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--danger);">Reject</button>
                        </form>
                            </td>
                    <?php else: ?>
                        <td><?= e($quote['product_name']); ?> (<?= e($quote['policy_type']); ?>)</td>
                        <td>PHP <?= number_format((float) $quote['coverage_amount'], 2); ?></td>
                        <td>PHP <?= number_format((float) $quote['premium_amount'], 2); ?></td>
                        <td><span class="badge <?= badgeClass((string) $quote['status']); ?>"><?= e(statusLabel((string) $quote['status'])); ?></span></td>
                        <td>-</td>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= renderPagination($quotesP); ?>
    </div>
</section>

<?php
renderFooter();
