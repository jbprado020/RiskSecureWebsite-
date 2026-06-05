<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/insurance_service.php';
require_once __DIR__ . '/includes/db_helpers.php';
require_once __DIR__ . '/includes/upload_helpers.php';
require_once __DIR__ . '/includes/validation.php';

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

    if ($policyId <= 0 || $incidentDate === '' || !isValidDate($incidentDate) || $claimAmount <= 0 || $description === '') {
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
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 30);
    $channel = (string) ($_POST['channel'] ?? 'zoom');
    $purpose = trim((string) ($_POST['purpose'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $agentId = (int) ($_POST['agent_id'] ?? 0);
    $allowedChannels = ['zoom', 'phone', 'in-person'];

    $startAt = parseDateTimeLocal($meetingAt);
    if ($startAt === null || $purpose === '' || $agentId <= 0 || !in_array($channel, $allowedChannels, true)) {
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
            // Normalize datetime-local value (browser uses 'T')
            if ($durationMinutes <= 0) {
                $durationMinutes = 30;
            }
            // compute end_at
            $endAt = date('Y-m-d H:i:s', strtotime($startAt) + ($durationMinutes * 60));

            // Overlap check: any scheduled meeting where start < new_end AND coalesce(end_at, start) > new_start
            $conflictStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM meeting_schedules
                 WHERE agent_id = :agent_id AND status = "scheduled"
                   AND (meeting_at < :end_at AND COALESCE(end_at, meeting_at) > :start_at)'
            );
            $conflictStmt->execute([
                ':agent_id' => $agentId,
                ':start_at' => $startAt,
                ':end_at' => $endAt,
            ]);

            if ((int) $conflictStmt->fetchColumn() > 0) {
                $error = 'Selected agent has a conflicting appointment during that time range. Please choose a different time or agent.';
            } else {
                $insertMeetingStmt = $pdo->prepare(
                    'INSERT INTO meeting_schedules (client_id, agent_id, meeting_at, end_at, duration_minutes, channel, purpose, status, notes)
                     VALUES (:client_id, :agent_id, :meeting_at, :end_at, :duration_minutes, :channel, :purpose, :status, :notes)'
                );
                $insertMeetingStmt->execute([
                    ':client_id' => $clientId,
                    ':agent_id' => $agentId,
                    ':meeting_at' => $startAt,
                    ':end_at' => $endAt,
                    ':duration_minutes' => $durationMinutes,
                    ':channel' => $channel,
                    ':purpose' => $purpose,
                    ':status' => 'scheduled',
                    ':notes' => $notes,
                ]);
                $message = 'Appointment scheduled successfully.';
            }
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
                $allowedMimeTypes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ];
                $maxBytes = 10 * 1024 * 1024; // 10 MB

                try {
                    $uploadInfo = processUpload($file, $uploadDir, 'cust', $allowedExt, $allowedMimeTypes, $maxBytes);

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

// KPI Calculations
$activePoliciesCount = count(array_filter($accountPolicies, fn($p) => $p['status'] === 'active'));
$pendingClaimsCount = count(array_filter($accountClaims, fn($c) => $c['claim_status'] === 'pending'));
$totalActivePremium = array_reduce($accountQuotes, function($acc, $q) use ($accountPolicies) {
    // Only count premium if it corresponds to an active policy (simplified for demo)
    return $acc + (float)$q['premium_amount'];
}, 0);

$nextMeeting = null;
foreach ($accountMeetings as $m) {
    if ($m['status'] === 'scheduled' && strtotime($m['meeting_at']) > time()) {
        if ($nextMeeting === null || strtotime($m['meeting_at']) < strtotime($nextMeeting['meeting_at'])) {
            $nextMeeting = $m;
        }
    }
}

renderHeader('Customer Portal', false);
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Welcome back, <?= e($customerName); ?>!</h1>
        <p>Manage your insurance portfolio, track claims, and stay protected with RiskSecure.</p>
    </div>
</div>

<section class="grid cols-4 kpi-container">
    <div class="card kpi">
        <div class="kpi-header">
            <?= iconMarkup('policy'); ?>
            <h3>Active Policies</h3>
        </div>
        <p><?= (int) $activePoliciesCount; ?></p>
        <div class="kpi-note">Protecting what matters</div>
    </div>
    <div class="card kpi">
        <div class="kpi-header">
            <?= iconMarkup('gavel'); ?>
            <h3>Pending Claims</h3>
        </div>
        <p><?= (int) $pendingClaimsCount; ?></p>
        <div class="kpi-note">Currently in review</div>
    </div>
    <div class="card kpi">
        <div class="kpi-header">
            <?= iconMarkup('payments'); ?>
            <h3>Total Premium</h3>
        </div>
        <p>PHP <?= number_format($totalActivePremium, 2); ?></p>
        <div class="kpi-note">Annual commitment</div>
    </div>
    <div class="card kpi">
        <div class="kpi-header">
            <?= iconMarkup('event'); ?>
            <h3>Next Meeting</h3>
        </div>
        <p><?= $nextMeeting ? date('M d, H:i', strtotime($nextMeeting['meeting_at'])) : 'No schedule'; ?></p>
        <div class="kpi-note"><?= $nextMeeting ? e((string)$nextMeeting['purpose']) : 'Book one below'; ?></div>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <h2><span style="display: inline-flex; align-items: center; gap: 0.5rem;"><?= iconMarkup('dashboard'); ?> Quick Actions</span></h2>
        <p>How can we help you today?</p>
    </div>
    
    <div class="grid cols-4 action-grid">
        <article class="card action-tile">
            <div class="action-icon"><?= iconMarkup('request_quote'); ?></div>
            <h3>Apply for Insurance</h3>
            <p>Get a new quote for life or non-life insurance.</p>
            <button type="button" class="btn-action" data-toggle="edit-form" data-target="form-apply">Get Started</button>
        </article>

        <article class="card action-tile">
            <div class="action-icon"><?= iconMarkup('gavel'); ?></div>
            <h3>File a Claim</h3>
            <p>Report an incident and start your claim process.</p>
            <button type="button" class="btn-action" data-toggle="edit-form" data-target="form-claim">File Now</button>
        </article>

        <article class="card action-tile">
            <div class="action-icon"><?= iconMarkup('event'); ?></div>
            <h3>Schedule Meeting</h3>
            <p>Book a consultation with our insurance agents.</p>
            <button type="button" class="btn-action" data-toggle="edit-form" data-target="form-meeting">Book Now</button>
        </article>

        <article class="card action-tile">
            <div class="action-icon"><?= iconMarkup('folder_open'); ?></div>
            <h3>Upload Document</h3>
            <p>Submit requirements for your policy or claim.</p>
            <button type="button" class="btn-action" data-toggle="edit-form" data-target="form-upload">Upload</button>
        </article>
    </div>

    <!-- Hidden Forms -->
    <div id="form-apply" class="form-drawer" hidden>
        <div class="card form-container">
            <h3><?= iconMarkup('request_quote'); ?> New Insurance Application</h3>
            <form method="post" class="grid" data-validate="true">
                <?= csrfField(); ?>
                <input type="hidden" name="submit_application" value="1">
                <div class="grid cols-2">
                    <div>
                        <label>Policy Type</label>
                        <select name="policy_type" required>
                            <option value="life">Life</option>
                            <option value="non-life">Non-Life</option>
                        </select>
                    </div>
                    <div>
                        <label>Product Name</label>
                        <input name="product_name" placeholder="e.g. Life Shield" required>
                    </div>
                </div>
                <div class="grid cols-3">
                    <div>
                        <label>Coverage (PHP)</label>
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
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Submit Application</button>
                    <button type="button" class="btn-secondary" data-toggle="edit-form" data-target="form-apply">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="form-claim" class="form-drawer" hidden>
        <div class="card form-container">
            <h3><?= iconMarkup('gavel'); ?> Submit a New Claim</h3>
            <form method="post" class="grid" data-validate="true">
                <?= csrfField(); ?>
                <input type="hidden" name="file_customer_claim" value="1">
                <div class="grid cols-2">
                    <div>
                        <label>Select Policy</label>
                        <select name="policy_id" required>
                            <option value="">Choose policy</option>
                            <?php foreach ($accountPolicies as $p): ?>
                                <option value="<?= (int)$p['id']; ?>"><?= e((string)$p['policy_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Claim Amount (PHP)</label>
                        <input name="claim_amount" type="number" step="0.01" min="1" required>
                    </div>
                </div>
                <div class="grid cols-2">
                    <div>
                        <label>Incident Date</label>
                        <input type="date" name="incident_date" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label>Description</label>
                        <input name="description" placeholder="Brief details of the incident" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Submit Claim</button>
                    <button type="button" class="btn-secondary" data-toggle="edit-form" data-target="form-claim">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="form-meeting" class="form-drawer" hidden>
        <div class="card form-container">
            <h3><?= iconMarkup('event'); ?> Schedule an Appointment</h3>
            <form method="post" class="grid" data-validate="true">
                <?= csrfField(); ?>
                <input type="hidden" name="schedule_customer_appointment" value="1">
                <div class="grid cols-2">
                    <div>
                        <label>Date & Time</label>
                        <input type="datetime-local" name="meeting_at" required>
                    </div>
                    <div>
                        <label>Preferred Agent</label>
                        <select name="agent_id" required>
                            <option value="">Select agent</option>
                            <?php foreach ($availableAgents as $a): ?>
                                <option value="<?= (int)$a['id']; ?>"><?= e((string)$a['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid cols-2">
                    <div>
                        <label>Meeting Channel</label>
                        <select name="channel" required>
                            <option value="zoom">Zoom / Online</option>
                            <option value="phone">Phone Call</option>
                            <option value="in-person">In-Person</option>
                        </select>
                    </div>
                    <div>
                        <label>Purpose</label>
                        <input name="purpose" placeholder="e.g. Policy Review" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Book Appointment</button>
                    <button type="button" class="btn-secondary" data-toggle="edit-form" data-target="form-meeting">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="form-upload" class="form-drawer" hidden>
        <div class="card form-container">
            <h3><?= iconMarkup('folder_open'); ?> Upload Required Documents</h3>
            <form method="post" enctype="multipart/form-data" class="grid" data-validate="true">
                <?= csrfField(); ?>
                <input type="hidden" name="upload_customer_document" value="1">
                <div class="grid cols-2">
                    <div>
                        <label>Select Policy</label>
                        <select name="policy_id" required>
                            <option value="">Choose policy</option>
                            <?php foreach ($accountPolicies as $p): ?>
                                <option value="<?= (int)$p['id']; ?>"><?= e((string)$p['policy_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Document Type</label>
                        <input name="document_type" placeholder="e.g. ID, Receipt, Medical Report" required>
                    </div>
                </div>
                <div>
                    <label>File Upload (PDF, JPG, PNG)</label>
                    <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-actions">
                    <button type="submit">Upload Document</button>
                    <button type="button" class="btn-secondary" data-toggle="edit-form" data-target="form-upload">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="card data-section">
    <div class="section-header">
        <h2><span style="display: inline-flex; align-items: center; gap: 0.5rem;"><?= iconMarkup('monitoring'); ?> Your Account Portfolio</span></h2>
    </div>

    <div class="tab-container">
        <h3>Recent Quotes</h3>
        <div class="table-wrap">
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
                        <td><?= e((string) $quote['product_name']); ?> <small>(<?= e((string) $quote['policy_type']); ?>)</small></td>
                        <td>PHP <?= number_format((float) $quote['coverage_amount'], 2); ?></td>
                        <td>PHP <?= number_format((float) $quote['premium_amount'], 2); ?></td>
                        <td><span class="badge <?= badgeClass((string) $quote['status']); ?>"><?= e(statusLabel((string) $quote['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($accountQuotes) === 0): ?>
                    <tr><td colspan="5" class="empty-state">No quotes yet. Start by applying for one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <h3>Active Policies</h3>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Policy Number</th>
                    <th>Coverage Period</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accountPolicies as $policy): ?>
                    <tr>
                        <td><strong><?= e((string) $policy['policy_number']); ?></strong></td>
                        <td><?= e((string) $policy['start_date']); ?> to <?= e((string) $policy['end_date']); ?></td>
                        <td><span class="badge <?= badgeClass((string) $policy['status']); ?>"><?= e(statusLabel((string) $policy['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($accountPolicies) === 0): ?>
                    <tr><td colspan="3" class="empty-state">No active policies found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="grid cols-2" style="margin-top: 1rem;">
            <div>
                <h3>Claims</h3>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountClaims as $claim): ?>
                            <tr>
                                <td>#<?= (int) $claim['id']; ?></td>
                                <td>PHP <?= number_format((float) $claim['claim_amount'], 2); ?></td>
                                <td><span class="badge <?= badgeClass((string) $claim['claim_status']); ?>"><?= e(statusLabel((string) $claim['claim_status'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($accountClaims) === 0): ?>
                            <tr><td colspan="3" class="empty-state">No claims filed.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div>
                <h3>Upcoming Appointments</h3>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountMeetings as $meeting): ?>
                            <tr>
                                <td><?= date('M d, H:i', strtotime($meeting['meeting_at'])); ?></td>
                                <td><?= e((string) $meeting['purpose']); ?></td>
                                <td><span class="badge <?= badgeClass((string) $meeting['status']); ?>"><?= e(statusLabel((string) $meeting['status'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($accountMeetings) === 0): ?>
                            <tr><td colspan="3" class="empty-state">No appointments.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
renderFooter();
