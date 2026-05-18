<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/insurance_service.php';
require_once __DIR__ . '/includes/audit_helpers.php';

requireStaffRole(['admin', 'manager', 'underwriter']);

$pdo = db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $policyType = $_POST['policy_type'] ?? 'life';
    $productName = trim($_POST['product_name'] ?? '');
    $coverageAmount = (float) ($_POST['coverage_amount'] ?? 0);
    $termMonths = (int) ($_POST['term_months'] ?? 12);
    $riskLevel = $_POST['risk_level'] ?? 'medium';

    if ($clientId > 0 && $productName !== '' && $coverageAmount > 0 && $termMonths > 0) {
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

$clients = $pdo->query('SELECT id, full_name FROM clients ORDER BY full_name')->fetchAll();
$quotes = $pdo->query(
    'SELECT q.*, c.full_name
     FROM quotes q
     INNER JOIN clients c ON c.id = q.client_id
     ORDER BY q.created_at DESC'
)->fetchAll();

renderHeader('Quotes');
?>

<section class="card">
    <h2>Create Quote</h2>
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2">
        <?= csrfField(); ?>
        <div>
            <label>Client</label>
            <select name="client_id" required>
                <option value="">Select a client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id']; ?>"><?= e($client['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Policy Type</label>
            <select name="policy_type" required>
                <option value="life">Life</option>
                <option value="non-life">Non-Life</option>
            </select>
        </div>
        <div>
            <label>Product Name</label>
            <input name="product_name" required placeholder="e.g. Family Life Shield">
        </div>
        <div>
            <label>Risk Level</label>
            <select name="risk_level" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>
        <div>
            <label>Coverage Amount (PHP)</label>
            <input name="coverage_amount" type="number" step="0.01" min="1" required>
        </div>
        <div>
            <label>Term (Months)</label>
            <input name="term_months" type="number" min="1" value="12" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Compute Premium and Save Quote</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Quote List</h2>
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
                <td><?= e($quote['product_name']); ?> (<?= e($quote['policy_type']); ?>)</td>
                <td>PHP <?= number_format((float) $quote['coverage_amount'], 2); ?></td>
                <td>PHP <?= number_format((float) $quote['premium_amount'], 2); ?></td>
                <td><span class="badge <?= badgeClass((string) $quote['status']); ?>"><?= e(statusLabel((string) $quote['status'])); ?></span></td>
                <td>
                    <?php if ($quote['status'] === 'pending'): ?>
                        <form method="post" style="display:inline-flex; gap:0.4rem; align-items:center;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_quote_status" value="1">
                            <input type="hidden" name="quote_id" value="<?= (int) $quote['id']; ?>">
                            <button type="submit" name="status" value="approved">Approve</button>
                            <button type="submit" name="status" value="rejected">Reject</button>
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
