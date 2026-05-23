<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db_helpers.php';
require_once __DIR__ . '/includes/audit_helpers.php';

requireStaffRole(['admin', 'manager', 'claims_officer', 'underwriter']);

$pdo = db();
$message = '';
$error = '';

function syncClaimRequirementsComplete(PDO $pdo, int $claimId): void
{
    $stmt = $pdo->prepare(
        'UPDATE claims c
         LEFT JOIN (
             SELECT claim_id,
                  MAX(status = "pending") AS has_pending,
                    COUNT(*) AS total_requirements
             FROM claim_requirements
             WHERE claim_id = :claim_id
             GROUP BY claim_id
         ) cr ON cr.claim_id = c.id
         SET c.requirements_complete = CASE
             WHEN cr.total_requirements IS NULL OR cr.total_requirements = 0 THEN 0
             WHEN cr.has_pending = 0 THEN 1
             ELSE 0
         END
         WHERE c.id = :claim_id_2'
    );
    $stmt->execute([
        ':claim_id' => $claimId,
        ':claim_id_2' => $claimId,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_claim'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $incidentDate = $_POST['incident_date'] ?? date('Y-m-d');
    $claimAmount = (float) ($_POST['claim_amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($policyId > 0 && $claimAmount > 0 && $description !== '') {
        $stmt = $pdo->prepare(
            'INSERT INTO claims (policy_id, incident_date, date_filed, claim_amount, description, claim_status, requirements_complete)
             VALUES (:policy_id, :incident_date, :date_filed, :claim_amount, :description, :claim_status, :requirements_complete)'
        );
        $stmt->execute([
            ':policy_id' => $policyId,
            ':incident_date' => $incidentDate,
            ':date_filed' => date('Y-m-d'),
            ':claim_amount' => $claimAmount,
            ':description' => $description,
            ':claim_status' => 'pending',
            ':requirements_complete' => 0,
        ]);
        logAuditEvent($pdo, 'create_claim', [
            'entity_type' => 'claims',
            'entity_id' => (int) $pdo->lastInsertId(),
            'status' => 'success',
            'details' => 'Created claim for policy ID ' . $policyId . ' amount ' . number_format($claimAmount, 2, '.', '') . '.',
        ]);
        $message = 'Claim has been filed.';
    } else {
        $error = 'Please complete the required claim fields.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_claim'])) {
    requireCsrfToken();

    $claimId = (int) ($_POST['claim_id'] ?? 0);
    $claimStatus = (string) ($_POST['claim_status'] ?? 'pending');
    $decisionNotes = trim((string) ($_POST['decision_notes'] ?? ''));
    $allowedStatuses = ['pending', 'under_review', 'approved', 'declined'];

    if ($claimId > 0 && in_array($claimStatus, $allowedStatuses, true)) {
        $approvalDate = $claimStatus === 'approved' ? date('Y-m-d') : null;
        $resolvedAt = ($claimStatus === 'approved' || $claimStatus === 'declined') ? date('Y-m-d H:i:s') : null;
        $decisionByStaffId = ($claimStatus === 'approved' || $claimStatus === 'declined' || $claimStatus === 'under_review')
            ? staffId()
            : null;

        $stmt = $pdo->prepare(
            'UPDATE claims
             SET claim_status = :claim_status,
                 approval_date = :approval_date,
                 resolved_at = :resolved_at,
                 decision_notes = :decision_notes,
                 decision_by_staff_id = :decision_by_staff_id
             WHERE id = :id'
        );
        $stmt->execute([
            ':claim_status' => $claimStatus,
            ':approval_date' => $approvalDate,
            ':resolved_at' => $resolvedAt,
            ':decision_notes' => $decisionNotes !== '' ? $decisionNotes : null,
            ':decision_by_staff_id' => $decisionByStaffId,
            ':id' => $claimId,
        ]);
        logAuditEvent($pdo, 'update_claim', [
            'entity_type' => 'claims',
            'entity_id' => $claimId,
            'status' => 'success',
            'details' => 'Updated claim status to ' . $claimStatus . '.',
        ]);
        $message = 'Claim status updated.';
    } else {
        $error = 'Invalid claim status update request.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_requirement'])) {
    requireCsrfToken();

    $claimId = (int) ($_POST['claim_id'] ?? 0);
    $requirementName = trim($_POST['requirement_name'] ?? '');
    $requiresOriginal = isset($_POST['requires_original']) ? 1 : 0;

    if ($claimId > 0 && $requirementName !== '') {
        try {
            runTransactionWithRetries($pdo, function (PDO $pdo) use ($claimId, $requirementName, $requiresOriginal) {
                $stmt = $pdo->prepare(
                    'INSERT INTO claim_requirements (claim_id, requirement_name, requires_original, status)
                     VALUES (:claim_id, :requirement_name, :requires_original, :status)'
                );
                $stmt->execute([
                    ':claim_id' => $claimId,
                    ':requirement_name' => $requirementName,
                    ':requires_original' => $requiresOriginal,
                    ':status' => 'pending',
                ]);
                syncClaimRequirementsComplete($pdo, $claimId);
                return true;
            });
            logAuditEvent($pdo, 'create_claim_requirement', [
                'entity_type' => 'claim_requirements',
                'status' => 'success',
                'details' => 'Added requirement ' . $requirementName . ' to claim ID ' . $claimId . '.',
            ]);
            $message = 'Claim requirement added.';
        } catch (Throwable $exception) {
            $error = 'Unable to add requirement right now. Please try again.';
        }
    } else {
        $error = 'Claim and requirement name are required.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_requirement'])) {
    requireCsrfToken();

    $requirementId = (int) ($_POST['requirement_id'] ?? 0);
    $claimId = (int) ($_POST['claim_id'] ?? 0);
    $requiresOriginal = (int) ($_POST['requires_original'] ?? 1);
    $softCopyReceived = isset($_POST['soft_copy_received']) ? 1 : 0;
    $hardCopyReceived = isset($_POST['hard_copy_received']) ? 1 : 0;
    $status = ($softCopyReceived === 1 && ($requiresOriginal === 0 || $hardCopyReceived === 1))
        ? 'complete'
        : 'pending';

    if ($requirementId > 0) {
        try {
            runTransactionWithRetries($pdo, function (PDO $pdo) use ($requirementId, $softCopyReceived, $hardCopyReceived, $status, $claimId) {
                $stmt = $pdo->prepare(
                    'UPDATE claim_requirements
                     SET soft_copy_received = :soft_copy_received,
                         hard_copy_received = :hard_copy_received,
                         status = :status
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':soft_copy_received' => $softCopyReceived,
                    ':hard_copy_received' => $hardCopyReceived,
                    ':status' => $status,
                    ':id' => $requirementId,
                ]);
                if ($claimId > 0) {
                    syncClaimRequirementsComplete($pdo, $claimId);
                }
                return true;
            });

            logAuditEvent($pdo, 'update_claim_requirement', [
                'entity_type' => 'claim_requirements',
                'entity_id' => $requirementId,
                'status' => 'success',
                'details' => 'Updated requirement status to ' . $status . '.',
            ]);
            $message = 'Requirement status updated.';
        } catch (Throwable $exception) {
            $error = 'Unable to update requirement right now. Please try again.';
        }
    } else {
        $error = 'Invalid requirement update request.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_claim_payment'])) {
    requireCsrfToken();

    $claimId = (int) ($_POST['claim_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $paidDate = trim((string) ($_POST['paid_date'] ?? date('Y-m-d')));
    $referenceNo = trim((string) ($_POST['reference_no'] ?? ''));

    if ($claimId > 0 && $amount > 0 && $paidDate !== '' && $referenceNo !== '') {
        try {
            runTransactionWithRetries($pdo, function (PDO $pdo) use ($claimId, $amount, $paidDate, $referenceNo) {
                $claimStmt = $pdo->prepare(
                    'SELECT id
                     FROM claims
                     WHERE id = :id AND claim_status = "approved"
                     LIMIT 1
                     FOR UPDATE'
                );
                $claimStmt->execute([':id' => $claimId]);
                $claim = $claimStmt->fetch();

                if (!$claim) {
                    throw new RuntimeException('Only approved claims can receive claim payments.');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO claim_payments (claim_id, amount, paid_date, reference_no, recorded_by_staff_id)
                     VALUES (:claim_id, :amount, :paid_date, :reference_no, :recorded_by_staff_id)'
                );
                $stmt->execute([
                    ':claim_id' => $claimId,
                    ':amount' => $amount,
                    ':paid_date' => $paidDate,
                    ':reference_no' => $referenceNo,
                    ':recorded_by_staff_id' => staffId(),
                ]);

                return true;
            });

            logAuditEvent($pdo, 'record_claim_payment', [
                'entity_type' => 'claim_payments',
                'status' => 'success',
                'details' => 'Recorded claim payment for claim ID ' . $claimId . ' amount ' . number_format($amount, 2, '.', '') . '.',
            ]);
            $message = 'Claim payment recorded.';
        } catch (RuntimeException $re) {
            $error = $re->getMessage();
        } catch (Throwable $exception) {
            $error = 'Unable to record claim payment right now. Please try again.';
        }
    } else {
        $error = 'Provide approved claim, amount, paid date, and payment reference.';
    }
}

$activePolicies = $pdo->query(
    'SELECT p.id, p.policy_number, c.full_name
     FROM policies p
    INNER JOIN clients c ON c.id = p.client_id
     WHERE p.status = "active"
    ORDER BY p.id ASC'
)->fetchAll();

$claims = $pdo->query(
    'SELECT cl.*, p.policy_number,
            COALESCE(req.requirements_complete, 0) AS requirements_complete,
            COALESCE(req.requirements_total, 0) AS requirements_total,
            cp.id AS claim_payment_id,
            cp.amount AS claim_payment_amount,
            cp.paid_date AS claim_paid_date,
            cp.reference_no AS claim_reference_no
     FROM claims cl
     INNER JOIN policies p ON p.id = cl.policy_id
     LEFT JOIN (
         SELECT claim_id,
                SUM(CASE WHEN status = "complete" THEN 1 ELSE 0 END) AS requirements_complete,
                COUNT(*) AS requirements_total
         FROM claim_requirements
         GROUP BY claim_id
     ) req ON req.claim_id = cl.id
     LEFT JOIN claim_payments cp ON cp.claim_id = cl.id
    ORDER BY cl.id ASC'
)->fetchAll();

$requirements = $pdo->query(
    'SELECT cr.*, p.policy_number
     FROM claim_requirements cr
     INNER JOIN claims cl ON cl.id = cr.claim_id
     INNER JOIN policies p ON p.id = cl.policy_id
    ORDER BY cr.id ASC'
)->fetchAll();

$approvedClaimsForPayment = $pdo->query(
    'SELECT cl.id, p.policy_number, cl.claim_amount
    FROM claims cl
    INNER JOIN policies p ON p.id = cl.policy_id
    LEFT JOIN claim_payments cp ON cp.claim_id = cl.id
    WHERE cl.claim_status = "approved" AND cp.id IS NULL
    ORDER BY cl.id ASC'
)->fetchAll();

$claimPayments = $pdo->query(
    'SELECT cp.*, p.policy_number, c.full_name
    FROM claim_payments cp
    INNER JOIN claims cl ON cl.id = cp.claim_id
    INNER JOIN policies p ON p.id = cl.policy_id
    INNER JOIN clients c ON c.id = p.client_id
    ORDER BY cp.id ASC'
)->fetchAll();

renderHeader('Claims');
?>

<section class="card">
    <h2>File Claim</h2>
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2">
        <?= csrfField(); ?>
        <input type="hidden" name="create_claim" value="1">
        <div>
            <label>Policy</label>
            <select name="policy_id" required>
                <option value="">Select active policy</option>
                <?php foreach ($activePolicies as $policy): ?>
                    <option value="<?= (int) $policy['id']; ?>">
                        <?= e($policy['policy_number']); ?> - <?= e($policy['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Incident Date</label>
            <input type="date" name="incident_date" value="<?= date('Y-m-d'); ?>" required>
        </div>
        <div>
            <label>Claim Amount (PHP)</label>
            <input type="number" step="0.01" min="1" name="claim_amount" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <label>Description</label>
            <textarea name="description" required></textarea>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Submit Claim</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Claim Handling</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy No.</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Status</th>
                <th>Requirements</th>
                <th>Decision Notes</th>
                <th>Payment</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($claims as $claim): ?>
            <tr>
                <td><?= (int) $claim['id']; ?></td>
                <td><?= e($claim['policy_number']); ?></td>
                <td>PHP <?= number_format((float) $claim['claim_amount'], 2); ?></td>
                <td><?= e($claim['description']); ?></td>
                <td><span class="badge <?= badgeClass((string) $claim['claim_status']); ?>"><?= e(statusLabel((string) $claim['claim_status'])); ?></span></td>
                <td><?= (int) $claim['requirements_complete']; ?>/<?= (int) $claim['requirements_total']; ?></td>
                <td><?= e((string) ($claim['decision_notes'] ?? '-')); ?></td>
                <td>
                    <?php if ($claim['claim_payment_id'] !== null): ?>
                        Paid PHP <?= number_format((float) $claim['claim_payment_amount'], 2); ?><br>
                        <small><?= e((string) $claim['claim_paid_date']); ?> / <?= e((string) $claim['claim_reference_no']); ?></small>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" style="display:grid; gap:0.35rem; min-width:220px;">
                        <?= csrfField(); ?>
                        <input type="hidden" name="update_claim" value="1">
                        <input type="hidden" name="claim_id" value="<?= (int) $claim['id']; ?>">
                        <select name="claim_status">
                            <option value="pending" <?= (string) $claim['claim_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?= (string) $claim['claim_status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?= (string) $claim['claim_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="declined" <?= (string) $claim['claim_status'] === 'declined' ? 'selected' : ''; ?>>Declined</option>
                        </select>
                        <input name="decision_notes" placeholder="Decision notes" value="<?= e((string) ($claim['decision_notes'] ?? '')); ?>">
                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Claim Requirements Checklist</h2>
    <form method="post" class="grid cols-2">
        <?= csrfField(); ?>
        <input type="hidden" name="add_requirement" value="1">
        <div>
            <label>Claim</label>
            <select name="claim_id" required>
                <option value="">Select claim</option>
                <?php foreach ($claims as $claim): ?>
                    <option value="<?= (int) $claim['id']; ?>">
                        #<?= (int) $claim['id']; ?> - <?= e($claim['policy_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Requirement Name</label>
            <input name="requirement_name" placeholder="e.g. ORCR, Police Report" required>
        </div>
        <div>
            <label style="display:flex; align-items:center; gap:0.4rem; margin-top:1.8rem;">
                <input type="checkbox" name="requires_original" checked style="width:auto;">
                Requires Original Hard Copy
            </label>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Add Requirement</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Claim</th>
                <th>Requirement</th>
                <th>Original Required</th>
                <th>Soft Copy</th>
                <th>Hard Copy</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requirements as $requirement): ?>
                <tr>
                    <td><?= (int) $requirement['id']; ?></td>
                    <td>#<?= (int) $requirement['claim_id']; ?> - <?= e($requirement['policy_number']); ?></td>
                    <td><?= e($requirement['requirement_name']); ?></td>
                    <td><?= (int) $requirement['requires_original'] === 1 ? 'Yes' : 'No'; ?></td>
                    <td><?= (int) $requirement['soft_copy_received'] === 1 ? 'Received' : 'Pending'; ?></td>
                    <td><?= (int) $requirement['hard_copy_received'] === 1 ? 'Received' : 'Pending'; ?></td>
                    <td><span class="badge <?= badgeClass($requirement['status']); ?>"><?= e($requirement['status']); ?></span></td>
                    <td>
                        <form method="post" style="display:flex; align-items:center; gap:0.4rem;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_requirement" value="1">
                            <input type="hidden" name="requirement_id" value="<?= (int) $requirement['id']; ?>">
                            <input type="hidden" name="claim_id" value="<?= (int) $requirement['claim_id']; ?>">
                            <input type="hidden" name="requires_original" value="<?= (int) $requirement['requires_original']; ?>">
                            <label style="display:flex; align-items:center; gap:0.3rem; margin:0;">
                                <input type="checkbox" name="soft_copy_received" style="width:auto;" <?= (int) $requirement['soft_copy_received'] === 1 ? 'checked' : ''; ?>>
                                Soft
                            </label>
                            <label style="display:flex; align-items:center; gap:0.3rem; margin:0;">
                                <input type="checkbox" name="hard_copy_received" style="width:auto;" <?= (int) $requirement['hard_copy_received'] === 1 ? 'checked' : ''; ?>>
                                Hard
                            </label>
                            <button type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($requirements) === 0): ?>
                <tr><td colspan="8">No claim requirements yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Record Claim Payment</h2>
        <p>Only approved claims can be paid.</p>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="record_claim_payment" value="1">
            <div>
                <label>Approved Claim</label>
                <select name="claim_id" required>
                    <option value="">Select claim</option>
                    <?php foreach ($approvedClaimsForPayment as $approvedClaim): ?>
                        <option value="<?= (int) $approvedClaim['id']; ?>">
                            #<?= (int) $approvedClaim['id']; ?> - <?= e((string) $approvedClaim['policy_number']); ?> (PHP <?= number_format((float) $approvedClaim['claim_amount'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Amount (PHP)</label>
                <input type="number" min="1" step="0.01" name="amount" required>
            </div>
            <div>
                <label>Paid Date</label>
                <input type="date" name="paid_date" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Reference No.</label>
                <input name="reference_no" placeholder="e.g. CLM-PAY-2026-0003" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Record Claim Payment</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Claim Payment Ledger</h2>
        <table>
            <thead>
                <tr>
                    <th>Claim</th>
                    <th>Policy</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Paid Date</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claimPayments as $claimPayment): ?>
                    <tr>
                        <td>#<?= (int) $claimPayment['claim_id']; ?></td>
                        <td><?= e((string) $claimPayment['policy_number']); ?></td>
                        <td><?= e((string) $claimPayment['full_name']); ?></td>
                        <td>PHP <?= number_format((float) $claimPayment['amount'], 2); ?></td>
                        <td><?= e((string) $claimPayment['paid_date']); ?></td>
                        <td><?= e((string) $claimPayment['reference_no']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($claimPayments) === 0): ?>
                    <tr><td colspan="6">No claim payments yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<?php
renderFooter();
