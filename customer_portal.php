<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/insurance_service.php';
require_once __DIR__ . '/includes/db_helpers.php';
require_once __DIR__ . '/includes/upload_helpers.php';

requireCustomerLogin();

$pdo = db();
$message = '';
$error = '';
$accountQuotes = [];
$accountPolicies = [];
$accountClaims = [];
$accountPayments = [];
$accountMeetings = [];
$accountDocuments = [];
$clientId = customerClientId();
$customerEmail = customerEmail();
$customerName = customerName();
$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    requireCsrfToken();

    $policyType = (string) ($_POST['policy_type'] ?? 'life');
    $productName = trim((string) ($_POST['product_name'] ?? ''));
    $coverageAmount = (float) ($_POST['coverage_amount'] ?? 0);
    $termMonths = (int) ($_POST['term_months'] ?? 12);
    $riskLevel = (string) ($_POST['risk_level'] ?? 'medium');

    $allowedPolicyTypes = ['life', 'non-life'];
    $allowedRiskLevels = ['low', 'medium', 'high'];

    if (
        !in_array($policyType, $allowedPolicyTypes, true) ||
        $productName === '' ||
        $coverageAmount <= 0 ||
        $termMonths <= 0 ||
        !in_array($riskLevel, $allowedRiskLevels, true)
    ) {
        $error = 'Please complete all required fields with valid values.';
    } else {
        $premium = calculatePremium($coverageAmount, $policyType, $riskLevel, $termMonths);
        try {
            $insertQuoteStmt = $pdo->prepare(
                'INSERT INTO quotes (client_id, policy_type, product_name, coverage_amount, term_months, risk_level, premium_amount, status)
                 VALUES (:client_id, :policy_type, :product_name, :coverage_amount, :term_months, :risk_level, :premium_amount, :status)'
            );
            $insertQuoteStmt->execute([
                ':client_id' => $clientId,
                ':policy_type' => $policyType,
                ':product_name' => $productName,
                ':coverage_amount' => $coverageAmount,
                ':term_months' => $termMonths,
                ':risk_level' => $riskLevel,
                ':premium_amount' => $premium,
                ':status' => 'pending',
            ]);

            $quoteId = (int) $pdo->lastInsertId();
            $message = 'Application submitted. Quote reference #' . $quoteId . ' is pending review. Estimated premium: PHP ' . number_format($premium, 2) . '.';
        } catch (Throwable $exception) {
            $error = 'Failed to submit application. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_customer_claim'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $incidentDate = trim((string) ($_POST['incident_date'] ?? ''));
    $claimAmount = (float) ($_POST['claim_amount'] ?? 0);
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($policyId <= 0 || $incidentDate === '' || $claimAmount <= 0 || $description === '') {
        $error = 'Please fill in valid claim details.';
    } else {
        $policyLookupStmt = $pdo->prepare(
            'SELECT p.id
             FROM policies p
               WHERE p.id = :policy_id AND p.client_id = :client_id
             LIMIT 1'
        );
        $policyLookupStmt->execute([
            ':policy_id' => $policyId,
            ':client_id' => $clientId,
        ]);
        $policy = $policyLookupStmt->fetch();

        if (!$policy) {
            $error = 'Policy verification failed.';
        } else {
            $insertClaimStmt = $pdo->prepare(
                'INSERT INTO claims (policy_id, incident_date, date_filed, claim_amount, description, claim_status, requirements_complete)
                 VALUES (:policy_id, :incident_date, :date_filed, :claim_amount, :description, :claim_status, :requirements_complete)'
            );
            $insertClaimStmt->execute([
                ':policy_id' => (int) $policy['id'],
                ':incident_date' => $incidentDate,
                ':date_filed' => date('Y-m-d'),
                ':claim_amount' => $claimAmount,
                ':description' => $description,
                ':claim_status' => 'pending',
                ':requirements_complete' => 0,
            ]);
            $message = 'Claim submitted successfully and is now in pending status.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_customer_appointment'])) {
    requireCsrfToken();

    $meetingAt = trim((string) ($_POST['meeting_at'] ?? ''));
    $channel = (string) ($_POST['channel'] ?? 'zoom');
    $purpose = trim((string) ($_POST['purpose'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $agentId = (int) ($_POST['agent_id'] ?? 0);
    $allowedChannels = ['zoom', 'phone', 'in-person'];

    if ($meetingAt === '' || $purpose === '' || $agentId <= 0 || !in_array($channel, $allowedChannels, true)) {
        $error = 'Please complete appointment details with a valid channel and agent.';
    } else {
        $agentLookupStmt = $pdo->prepare(
            'SELECT id
             FROM staff_accounts
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $agentLookupStmt->execute([':id' => $agentId]);
        $agent = $agentLookupStmt->fetch();

        if (!$agent) {
            $error = 'Selected agent is not available.';
        } else {
            $insertMeetingStmt = $pdo->prepare(
                'INSERT INTO meeting_schedules (client_id, agent_id, meeting_at, channel, purpose, status, notes)
                 VALUES (:client_id, :agent_id, :meeting_at, :channel, :purpose, :status, :notes)'
            );
            $insertMeetingStmt->execute([
                ':client_id' => $clientId,
                ':agent_id' => $agentId,
                ':meeting_at' => $meetingAt,
                ':channel' => $channel,
                ':purpose' => $purpose,
                ':status' => 'scheduled',
                ':notes' => $notes,
            ]);
            $message = 'Appointment scheduled successfully.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_customer_document'])) {
    requireCsrfToken();

    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $claimIdRaw = trim((string) ($_POST['claim_id'] ?? ''));
    $claimId = $claimIdRaw === '' ? null : (int) $claimIdRaw;
    $documentType = trim((string) ($_POST['document_type'] ?? ''));

    if ($policyId <= 0 || $documentType === '' || !isset($_FILES['document_file'])) {
        $error = 'Please select policy, document type, and file.';
    } else {
        $policyLookupStmt = $pdo->prepare(
            'SELECT p.id
             FROM policies p
               WHERE p.id = :policy_id AND p.client_id = :client_id
             LIMIT 1'
        );
        $policyLookupStmt->execute([
            ':policy_id' => $policyId,
            ':client_id' => $clientId,
        ]);
        $policy = $policyLookupStmt->fetch();

        if (!$policy) {
            $error = 'Policy validation failed.';
        } else {
            if ($claimId !== null) {
                $claimLookupStmt = $pdo->prepare(
                    'SELECT cl.id
                     FROM claims cl
                     INNER JOIN policies p ON p.id = cl.policy_id
                     WHERE cl.id = :claim_id AND p.client_id = :client_id
                     LIMIT 1'
                );
                $claimLookupStmt->execute([
                    ':claim_id' => $claimId,
                    ':client_id' => $clientId,
                ]);
                $claim = $claimLookupStmt->fetch();
                if (!$claim) {
                    $error = 'Claim validation failed.';
                }
            }

            if ($error === '') {
                $file = $_FILES['document_file'];
                $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

                if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload failed.';
                } else {
                    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        $error = 'Unsupported file type. Allowed: pdf, jpg, jpeg, png, doc, docx.';
                    } else {
                        try {
                            $uploadInfo = prepareUploadTemp($file, $uploadDir, 'cust');

                            $insertId = runTransactionWithRetries($pdo, function (PDO $pdo) use ($clientId, $policyId, $claimId, $documentType, $uploadInfo) {
                                $insertDocumentStmt = $pdo->prepare(
                                    'INSERT INTO documents (client_id, policy_id, claim_id, document_type, file_path, uploaded_by)
                                     VALUES (:client_id, :policy_id, :claim_id, :document_type, :file_path, :uploaded_by)'
                                );
                                $insertDocumentStmt->execute([
                                    ':client_id' => $clientId,
                                    ':policy_id' => $policyId,
                                    ':claim_id' => $claimId,
                                    ':document_type' => $documentType,
                                    ':file_path' => $uploadInfo['relativePath'],
                                    ':uploaded_by' => 'customer',
                                ]);

                                return (int) $pdo->lastInsertId();
                            });

                            try {
                                finalizeUploadMove($uploadInfo['tempPath'], $uploadInfo['finalPath'], function (?int $id) use ($pdo) {
                                    if ($id !== null) {
                                        $del = $pdo->prepare('DELETE FROM documents WHERE id = :id');
                                        $del->execute([':id' => $id]);
                                    }
                                }, $insertId);

                                $message = 'Document uploaded successfully.';
                            } catch (Throwable $e) {
                                $error = 'Unable to finalize uploaded file after save. Please contact admin.';
                            }
                        } catch (Throwable $e) {
                            $error = $e->getMessage() ?: 'Unable to store uploaded file.';
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_customer_appointment'])) {
    requireCsrfToken();

    $meetingId = (int) ($_POST['meeting_id'] ?? 0);
    if ($meetingId > 0) {
        $stmt = $pdo->prepare(
            'UPDATE meeting_schedules
             SET status = :status
             WHERE id = :id AND client_id = :client_id AND status = "scheduled"'
        );
        $stmt->execute([
            ':status' => 'cancelled',
            ':id' => $meetingId,
            ':client_id' => $clientId,
        ]);
        $message = 'Appointment cancelled.';
    }
}

$agentsStmt = $pdo->prepare(
    'SELECT id, full_name, role
     FROM staff_accounts
     WHERE is_active = 1 AND role IN ("manager", "underwriter", "claims_officer", "admin")
     ORDER BY full_name ASC'
);
$agentsStmt->execute();
$availableAgents = $agentsStmt->fetchAll();

$quotesStmt = $pdo->prepare(
    'SELECT q.id, q.policy_type, q.product_name, q.coverage_amount, q.premium_amount, q.status, q.created_at
     FROM quotes q
     WHERE q.client_id = :client_id
     ORDER BY q.created_at DESC'
);
$quotesStmt->execute([':client_id' => $clientId]);
$accountQuotes = $quotesStmt->fetchAll();

$policiesStmt = $pdo->prepare(
    'SELECT p.id, p.policy_number, p.start_date, p.end_date, p.status, p.issued_at
     FROM policies p
    WHERE p.client_id = :client_id
     ORDER BY p.issued_at DESC'
);
$policiesStmt->execute([':client_id' => $clientId]);
$accountPolicies = $policiesStmt->fetchAll();

$claimsStmt = $pdo->prepare(
    'SELECT cl.id, p.policy_number, cl.claim_amount, cl.claim_status, cl.created_at
     FROM claims cl
     INNER JOIN policies p ON p.id = cl.policy_id
    WHERE p.client_id = :client_id
     ORDER BY cl.created_at DESC'
);
$claimsStmt->execute([':client_id' => $clientId]);
$accountClaims = $claimsStmt->fetchAll();

$paymentsStmt = $pdo->prepare(
    'SELECT pay.id, p.policy_number, pay.amount, pay.due_date, pay.paid_date, pay.status
     FROM payments pay
     INNER JOIN policies p ON p.id = pay.policy_id
    WHERE p.client_id = :client_id
     ORDER BY pay.created_at DESC'
);
$paymentsStmt->execute([':client_id' => $clientId]);
$accountPayments = $paymentsStmt->fetchAll();

$meetingsStmt = $pdo->prepare(
    'SELECT ms.id, ms.meeting_at, ms.channel, ms.purpose, ms.status, ms.notes,
            sa.full_name AS agent_name, sa.role AS agent_role
     FROM meeting_schedules ms
     INNER JOIN staff_accounts sa ON sa.id = ms.agent_id
     WHERE ms.client_id = :client_id
     ORDER BY ms.meeting_at ASC'
);
$meetingsStmt->execute([':client_id' => $clientId]);
$accountMeetings = $meetingsStmt->fetchAll();

$documentsStmt = $pdo->prepare(
    'SELECT d.id, d.document_type, d.file_path, d.date_uploaded, d.is_hard_copy_received,
            p.policy_number, d.claim_id
     FROM documents d
     INNER JOIN policies p ON p.id = d.policy_id
     WHERE d.client_id = :client_id
     ORDER BY d.date_uploaded DESC'
);
$documentsStmt->execute([':client_id' => $clientId]);
$accountDocuments = $documentsStmt->fetchAll();

renderHeader('Customer Portal');
?>

<section class="card">
    <h2>Customer Self-Service Portal</h2>
    <p>Welcome, <?= e($customerName); ?> (<?= e($customerEmail); ?>). <a href="customer_logout.php">Logout</a></p>

    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Apply for Insurance</h2>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="submit_application" value="1">
            <div>
                <label>Policy Type</label>
                <select name="policy_type" required>
                    <option value="life">Life</option>
                    <option value="non-life">Non-Life</option>
                </select>
            </div>
            <div>
                <label>Product Name</label>
                <input name="product_name" placeholder="e.g. Family Life Shield" required>
            </div>
            <div>
                <label>Coverage Amount (PHP)</label>
                <input name="coverage_amount" type="number" step="0.01" min="1" required>
            </div>
            <div>
                <label>Term (Months)</label>
                <input name="term_months" type="number" min="1" value="12" required>
            </div>
            <div>
                <label>Risk Level</label>
                <select name="risk_level" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Submit Application</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>File a Claim</h2>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="file_customer_claim" value="1">
            <div>
                <label>Policy Number</label>
                <select name="policy_id" required>
                    <option value="">Select your policy</option>
                    <?php foreach ($accountPolicies as $policy): ?>
                        <option value="<?= (int) $policy['id']; ?>"><?= e((string) $policy['policy_number']); ?></option>
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
    </article>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Schedule Appointment</h2>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="schedule_customer_appointment" value="1">
            <div>
                <label>Date and Time</label>
                <input type="datetime-local" name="meeting_at" required>
            </div>
            <div>
                <label>Preferred Channel</label>
                <select name="channel" required>
                    <option value="zoom">Zoom</option>
                    <option value="phone">Phone</option>
                    <option value="in-person">In-person</option>
                </select>
            </div>
            <div>
                <label>Assign Agent</label>
                <select name="agent_id" required>
                    <option value="">Select agent</option>
                    <?php foreach ($availableAgents as $agent): ?>
                        <option value="<?= (int) $agent['id']; ?>">
                            <?= e((string) $agent['full_name']); ?> (<?= e(statusLabel((string) $agent['role'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Purpose</label>
                <input name="purpose" placeholder="e.g. Claim follow-up" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <label>Notes</label>
                <textarea name="notes" placeholder="Additional details for the meeting"></textarea>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Schedule Appointment</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Upload Documents</h2>
        <form method="post" enctype="multipart/form-data" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="upload_customer_document" value="1">
            <div>
                <label>Policy</label>
                <select name="policy_id" required>
                    <option value="">Select your policy</option>
                    <?php foreach ($accountPolicies as $policy): ?>
                        <option value="<?= (int) $policy['id']; ?>"><?= e((string) $policy['policy_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Claim (Optional)</label>
                <select name="claim_id">
                    <option value="">No specific claim</option>
                    <?php foreach ($accountClaims as $claim): ?>
                        <option value="<?= (int) $claim['id']; ?>">#<?= (int) $claim['id']; ?> - <?= e((string) $claim['policy_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Document Type</label>
                <input name="document_type" placeholder="e.g. ORCR, Policy Form" required>
            </div>
            <div>
                <label>File</label>
                <input type="file" name="document_file" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Upload Document</button>
            </div>
        </form>
    </article>
</section>

<section class="card">
    <h2>Your Account Status</h2>

    <h3>Quotes</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Coverage</th>
                <th>Premium</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountQuotes as $quote): ?>
                <tr>
                    <td><?= (int) $quote['id']; ?></td>
                    <td><?= e((string) $quote['product_name']); ?> (<?= e((string) $quote['policy_type']); ?>)</td>
                    <td>PHP <?= number_format((float) $quote['coverage_amount'], 2); ?></td>
                    <td>PHP <?= number_format((float) $quote['premium_amount'], 2); ?></td>
                    <td><span class="badge <?= badgeClass((string) $quote['status']); ?>"><?= e(statusLabel((string) $quote['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountQuotes) === 0): ?>
                <tr><td colspan="5">No quotes yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Policies</h3>
    <table>
        <thead>
            <tr>
                <th>Policy Number</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountPolicies as $policy): ?>
                <tr>
                    <td><?= e((string) $policy['policy_number']); ?></td>
                    <td><?= e((string) $policy['start_date']); ?></td>
                    <td><?= e((string) $policy['end_date']); ?></td>
                    <td><span class="badge <?= badgeClass((string) $policy['status']); ?>"><?= e(statusLabel((string) $policy['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountPolicies) === 0): ?>
                <tr><td colspan="4">No policies yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Claims</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountClaims as $claim): ?>
                <tr>
                    <td><?= (int) $claim['id']; ?></td>
                    <td><?= e((string) $claim['policy_number']); ?></td>
                    <td>PHP <?= number_format((float) $claim['claim_amount'], 2); ?></td>
                    <td><span class="badge <?= badgeClass((string) $claim['claim_status']); ?>"><?= e(statusLabel((string) $claim['claim_status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountClaims) === 0): ?>
                <tr><td colspan="4">No claims yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Payments</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy</th>
                <th>Amount</th>
                <th>Due</th>
                <th>Paid Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountPayments as $payment): ?>
                <tr>
                    <td><?= (int) $payment['id']; ?></td>
                    <td><?= e((string) $payment['policy_number']); ?></td>
                    <td>PHP <?= number_format((float) $payment['amount'], 2); ?></td>
                    <td><?= e((string) $payment['due_date']); ?></td>
                    <td><?= e((string) ($payment['paid_date'] ?? '-')); ?></td>
                    <td><span class="badge <?= badgeClass((string) $payment['status']); ?>"><?= e(statusLabel((string) $payment['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountPayments) === 0): ?>
                <tr><td colspan="6">No payments yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Appointments</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date/Time</th>
                <th>Channel</th>
                <th>Purpose</th>
                <th>Assigned Agent</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountMeetings as $meeting): ?>
                <tr>
                    <td><?= (int) $meeting['id']; ?></td>
                    <td><?= e((string) $meeting['meeting_at']); ?></td>
                    <td><?= e((string) $meeting['channel']); ?></td>
                    <td><?= e((string) $meeting['purpose']); ?></td>
                    <td><?= e((string) $meeting['agent_name']); ?> (<?= e(statusLabel((string) $meeting['agent_role'])); ?>)</td>
                    <td><span class="badge <?= badgeClass((string) $meeting['status']); ?>"><?= e(statusLabel((string) $meeting['status'])); ?></span></td>
                    <td>
                        <?php if ((string) $meeting['status'] === 'scheduled'): ?>
                            <form method="post">
                                <?= csrfField(); ?>
                                <input type="hidden" name="cancel_customer_appointment" value="1">
                                <input type="hidden" name="meeting_id" value="<?= (int) $meeting['id']; ?>">
                                <button type="submit">Cancel</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountMeetings) === 0): ?>
                <tr><td colspan="7">No appointments yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Documents</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Policy</th>
                <th>Claim</th>
                <th>Type</th>
                <th>Uploaded</th>
                <th>Hard Copy</th>
                <th>File</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accountDocuments as $document): ?>
                <tr>
                    <td><?= (int) $document['id']; ?></td>
                    <td><?= e((string) $document['policy_number']); ?></td>
                    <td><?= $document['claim_id'] !== null ? '#' . (int) $document['claim_id'] : '-'; ?></td>
                    <td><?= e((string) $document['document_type']); ?></td>
                    <td><?= e((string) $document['date_uploaded']); ?></td>
                    <td><?= (int) $document['is_hard_copy_received'] === 1 ? 'Received' : 'Pending'; ?></td>
                    <td><a href="<?= e((string) $document['file_path']); ?>" target="_blank" rel="noopener noreferrer">View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($accountDocuments) === 0): ?>
                <tr><td colspan="7">No documents yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
renderFooter();
