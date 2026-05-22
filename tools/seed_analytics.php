<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$pdo = db();
$markerEmail = 'analytics.demo@risksecure.local';

$markerStmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE email = :email');
$markerStmt->execute([':email' => $markerEmail]);

if ((int) $markerStmt->fetchColumn() > 0) {
    echo "Analytics seed already applied.\n";
    exit(0);
}

$pdo->beginTransaction();

try {
    $clients = [
        ['Analytics Demo One', 'analytics.demo@risksecure.local', '+63-917-800-1001', 'Makati City, Metro Manila', '1991-03-14'],
        ['Analytics Demo Two', 'analytics.demo2@risksecure.local', '+63-917-800-1002', 'Quezon City, Metro Manila', '1987-08-22'],
        ['Analytics Demo Three', 'analytics.demo3@risksecure.local', '+63-917-800-1003', 'Cebu City, Cebu', '1994-12-05'],
        ['Analytics Demo Four', 'analytics.demo4@risksecure.local', '+63-917-800-1004', 'Davao City, Davao del Sur', '1998-06-30'],
        ['Analytics Demo Five', 'analytics.demo5@risksecure.local', '+63-917-800-1005', 'Pasig City, Metro Manila', '1985-11-18'],
        ['Analytics Demo Six', 'analytics.demo6@risksecure.local', '+63-917-800-1006', 'Baguio City, Benguet', '1990-01-09'],
    ];

    $clientIds = [];
    $insertClientStmt = $pdo->prepare(
        'INSERT INTO clients (full_name, email, phone, address, date_of_birth, created_at)
         VALUES (:full_name, :email, :phone, :address, :date_of_birth, :created_at)'
    );

    foreach ($clients as $index => [$fullName, $email, $phone, $address, $dob]) {
        $insertClientStmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':date_of_birth' => $dob,
            ':created_at' => date('Y-m-d H:i:s', strtotime('+'.($index + 1).' days')),
        ]);
        $clientIds[] = (int) $pdo->lastInsertId();
    }

    $insertAccountStmt = $pdo->prepare(
        'INSERT INTO customer_accounts (client_id, password_hash)
         VALUES (:client_id, :password_hash)'
    );
    foreach (array_slice($clientIds, 0, 4) as $clientId) {
        $insertAccountStmt->execute([
            ':client_id' => $clientId,
            ':password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
        ]);
    }

    $quotes = [
        [$clientIds[0], 'life', 'SecureLife Elite', 1500000.00, 24, 'medium', 22500.00, 'approved', '2026-05-02 09:15:00'],
        [$clientIds[1], 'non-life', 'Auto Shield Plus', 650000.00, 12, 'low', 15250.00, 'approved', '2026-05-04 10:45:00'],
        [$clientIds[2], 'life', 'Family Protect Max', 2000000.00, 36, 'medium', 38950.00, 'pending', '2026-05-06 14:20:00'],
        [$clientIds[3], 'non-life', 'Property Guard Pro', 1200000.00, 24, 'high', 54200.00, 'rejected', '2026-05-08 11:05:00'],
        [$clientIds[4], 'life', 'Income Secure', 900000.00, 12, 'low', 16800.00, 'approved', '2026-05-10 16:00:00'],
        [$clientIds[5], 'non-life', 'Travel Shield Basic', 300000.00, 6, 'low', 6200.00, 'pending', '2026-05-12 13:40:00'],
    ];

    $quoteIds = [];
    $insertQuoteStmt = $pdo->prepare(
        'INSERT INTO quotes (client_id, policy_type, product_name, coverage_amount, term_months, risk_level, premium_amount, status, created_at)
         VALUES (:client_id, :policy_type, :product_name, :coverage_amount, :term_months, :risk_level, :premium_amount, :status, :created_at)'
    );
    foreach ($quotes as $quote) {
        [$clientId, $policyType, $productName, $coverageAmount, $termMonths, $riskLevel, $premiumAmount, $status, $createdAt] = $quote;
        $insertQuoteStmt->execute([
            ':client_id' => $clientId,
            ':policy_type' => $policyType,
            ':product_name' => $productName,
            ':coverage_amount' => $coverageAmount,
            ':term_months' => $termMonths,
            ':risk_level' => $riskLevel,
            ':premium_amount' => $premiumAmount,
            ':status' => $status,
            ':created_at' => $createdAt,
        ]);
        $quoteIds[] = (int) $pdo->lastInsertId();
    }

    $policyRows = [
        [$quoteIds[0], $clientIds[0], 1, 'RS-2026-0101', 'life', 1500000.00, 22500.00, '2026-05-02', '2027-05-01', 'active', '2026-05-03 08:15:00'],
        [$quoteIds[1], $clientIds[1], 4, 'RS-2026-0102', 'non-life', 650000.00, 15250.00, '2026-05-04', '2027-05-03', 'pending_renewal', '2026-05-05 09:00:00'],
        [$quoteIds[4], $clientIds[4], 2, 'RS-2026-0103', 'life', 900000.00, 16800.00, '2026-05-10', '2027-05-09', 'active', '2026-05-11 10:20:00'],
        [$quoteIds[2], $clientIds[2], 3, 'RS-2026-0104', 'life', 2000000.00, 38950.00, '2026-05-16', '2027-05-15', 'active', '2026-05-17 12:30:00'],
        [$quoteIds[5], $clientIds[5], 5, 'RS-2026-0105', 'non-life', 300000.00, 6200.00, '2026-05-18', '2027-05-17', 'pending_renewal', '2026-05-19 15:05:00'],
    ];

    $policyIds = [];
    $insertPolicyStmt = $pdo->prepare(
        'INSERT INTO policies (quote_id, client_id, partner_id, policy_number, policy_type, coverage_amount, premium, start_date, end_date, status, issued_at)
         VALUES (:quote_id, :client_id, :partner_id, :policy_number, :policy_type, :coverage_amount, :premium, :start_date, :end_date, :status, :issued_at)'
    );
    foreach ($policyRows as $policy) {
        [$quoteId, $clientId, $partnerId, $policyNumber, $policyType, $coverageAmount, $premium, $startDate, $endDate, $status, $issuedAt] = $policy;
        $insertPolicyStmt->execute([
            ':quote_id' => $quoteId,
            ':client_id' => $clientId,
            ':partner_id' => $partnerId,
            ':policy_number' => $policyNumber,
            ':policy_type' => $policyType,
            ':coverage_amount' => $coverageAmount,
            ':premium' => $premium,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':issued_at' => $issuedAt,
        ]);
        $policyIds[] = (int) $pdo->lastInsertId();
    }

    $claims = [
        [$policyIds[0], '2026-05-07', '2026-05-08', 54000.00, 'Hospital reimbursement for outpatient treatment.', 'under_review', 0, null, 'Awaiting final medical documents.', 3, null],
        [$policyIds[1], '2026-05-11', '2026-05-12', 23500.00, 'Vehicle repair estimate submission.', 'approved', 1, '2026-05-18', 'Approved after review.', 3, '2026-05-18 10:30:00'],
        [$policyIds[2], '2026-05-14', '2026-05-15', 76000.00, 'Theft claim for household contents.', 'pending', 0, null, 'Initial assessment pending.', null, null],
        [$policyIds[3], '2026-05-17', '2026-05-18', 38000.00, 'Fire damage claim.', 'declined', 0, null, 'Coverage exclusion applied.', 2, '2026-05-20 16:10:00'],
    ];

    $claimIds = [];
    $insertClaimStmt = $pdo->prepare(
        'INSERT INTO claims (policy_id, incident_date, date_filed, claim_amount, description, claim_status, requirements_complete, approval_date, decision_notes, decision_by_staff_id, resolved_at, status)
         VALUES (:policy_id, :incident_date, :date_filed, :claim_amount, :description, :claim_status, :requirements_complete, :approval_date, :decision_notes, :decision_by_staff_id, :resolved_at, :status)'
    );
    foreach ($claims as $claim) {
        [$policyId, $incidentDate, $dateFiled, $claimAmount, $description, $claimStatus, $requirementsComplete, $approvalDate, $decisionNotes, $decisionByStaffId, $resolvedAt] = $claim;
        $status = match ($claimStatus) {
            'approved' => 'approved',
            'declined' => 'rejected',
            'under_review' => 'reviewing',
            default => 'filed',
        };
        $insertClaimStmt->execute([
            ':policy_id' => $policyId,
            ':incident_date' => $incidentDate,
            ':date_filed' => $dateFiled,
            ':claim_amount' => $claimAmount,
            ':description' => $description,
            ':claim_status' => $claimStatus,
            ':requirements_complete' => $requirementsComplete,
            ':approval_date' => $approvalDate,
            ':decision_notes' => $decisionNotes,
            ':decision_by_staff_id' => $decisionByStaffId,
            ':resolved_at' => $resolvedAt,
            ':status' => $status,
        ]);
        $claimIds[] = (int) $pdo->lastInsertId();
    }

    $claimRequirements = [
        [$claimIds[0], 'Medical Certificate', 1, 1, 0, 'pending'],
        [$claimIds[0], 'Doctor Receipt', 1, 1, 0, 'pending'],
        [$claimIds[1], 'Repair Estimate', 1, 1, 1, 'complete'],
        [$claimIds[2], 'Police Report', 1, 0, 0, 'pending'],
        [$claimIds[3], 'Fire Incident Report', 1, 1, 1, 'complete'],
    ];
    $insertRequirementStmt = $pdo->prepare(
        'INSERT INTO claim_requirements (claim_id, requirement_name, requires_original, soft_copy_received, hard_copy_received, status)
         VALUES (:claim_id, :requirement_name, :requires_original, :soft_copy_received, :hard_copy_received, :status)'
    );
    foreach ($claimRequirements as $requirement) {
        [$claimId, $name, $requiresOriginal, $softCopyReceived, $hardCopyReceived, $status] = $requirement;
        $insertRequirementStmt->execute([
            ':claim_id' => $claimId,
            ':requirement_name' => $name,
            ':requires_original' => $requiresOriginal,
            ':soft_copy_received' => $softCopyReceived,
            ':hard_copy_received' => $hardCopyReceived,
            ':status' => $status,
        ]);
    }

    $documents = [
        [$clientIds[0], $policyIds[0], $claimIds[0], 'Medical Certificate', 'uploads/analytics_medical_certificate.pdf', 'customer', 0],
        [$clientIds[1], $policyIds[1], $claimIds[1], 'Repair Estimate', 'uploads/analytics_repair_estimate.pdf', 'staff', 1],
        [$clientIds[2], $policyIds[2], $claimIds[2], 'Police Report', 'uploads/analytics_police_report.pdf', 'customer', 0],
        [$clientIds[3], $policyIds[3], $claimIds[3], 'Fire Incident Report', 'uploads/analytics_fire_report.pdf', 'staff', 1],
    ];
    $insertDocumentStmt = $pdo->prepare(
        'INSERT INTO documents (client_id, policy_id, claim_id, document_type, file_path, uploaded_by, is_hard_copy_received, date_uploaded)
         VALUES (:client_id, :policy_id, :claim_id, :document_type, :file_path, :uploaded_by, :is_hard_copy_received, :date_uploaded)'
    );
    foreach ($documents as $index => $document) {
        [$clientId, $policyId, $claimId, $documentType, $filePath, $uploadedBy, $isHardCopyReceived] = $document;
        $insertDocumentStmt->execute([
            ':client_id' => $clientId,
            ':policy_id' => $policyId,
            ':claim_id' => $claimId,
            ':document_type' => $documentType,
            ':file_path' => $filePath,
            ':uploaded_by' => $uploadedBy,
            ':is_hard_copy_received' => $isHardCopyReceived,
            ':date_uploaded' => date('Y-m-d H:i:s', strtotime('+'.($index + 1).' hours')),
        ]);
    }

    $claimPayments = [
        [$claimIds[1], 23500.00, '2026-05-20', 'CLM-ANALYTICS-0001', 4],
    ];
    $insertClaimPaymentStmt = $pdo->prepare(
        'INSERT INTO claim_payments (claim_id, amount, paid_date, reference_no, recorded_by_staff_id)
         VALUES (:claim_id, :amount, :paid_date, :reference_no, :recorded_by_staff_id)'
    );
    foreach ($claimPayments as $payment) {
        [$claimId, $amount, $paidDate, $referenceNo, $staffId] = $payment;
        $insertClaimPaymentStmt->execute([
            ':claim_id' => $claimId,
            ':amount' => $amount,
            ':paid_date' => $paidDate,
            ':reference_no' => $referenceNo,
            ':recorded_by_staff_id' => $staffId,
        ]);
    }

    $payments = [
        [$policyIds[0], 1875.00, '2026-05-08', '2026-05-07', 'paid'],
        [$policyIds[1], 1270.83, '2026-05-15', null, 'pending'],
        [$policyIds[2], 1575.00, '2026-05-18', '2026-05-18', 'paid'],
        [$policyIds[3], 3150.00, '2026-05-21', null, 'overdue'],
    ];
    $insertPaymentStmt = $pdo->prepare(
        'INSERT INTO payments (policy_id, amount, due_date, paid_date, status)
         VALUES (:policy_id, :amount, :due_date, :paid_date, :status)'
    );
    foreach ($payments as $payment) {
        [$policyId, $amount, $dueDate, $paidDate, $status] = $payment;
        $insertPaymentStmt->execute([
            ':policy_id' => $policyId,
            ':amount' => $amount,
            ':due_date' => $dueDate,
            ':paid_date' => $paidDate,
            ':status' => $status,
        ]);
    }

    $renewals = [
        [$policyIds[0], '2026-11-20', '2027-05-01', '2027-05-01', 'in_progress', 'Client comparing renewal options.'],
        [$policyIds[1], '2026-12-05', '2027-05-03', '2027-05-03', 'notified', 'Renewal reminder sent.'],
        [$policyIds[3], '2026-12-18', '2027-05-15', '2027-05-15', 'renewed', 'Renewed after premium review.'],
    ];
    $insertRenewalStmt = $pdo->prepare(
        'INSERT INTO renewals (policy_id, renewal_date, previous_expiry, new_expiry, status, notes)
         VALUES (:policy_id, :renewal_date, :previous_expiry, :new_expiry, :status, :notes)'
    );
    foreach ($renewals as $renewal) {
        [$policyId, $renewalDate, $previousExpiry, $newExpiry, $status, $notes] = $renewal;
        $insertRenewalStmt->execute([
            ':policy_id' => $policyId,
            ':renewal_date' => $renewalDate,
            ':previous_expiry' => $previousExpiry,
            ':new_expiry' => $newExpiry,
            ':status' => $status,
            ':notes' => $notes,
        ]);
    }

    $meetings = [
        [$clientIds[0], 2, '2026-05-09 10:00:00', 'zoom', 'Policy review call', 'completed', 'Reviewed renewal quote and next steps.'],
        [$clientIds[1], 3, '2026-05-13 14:30:00', 'phone', 'Claims follow-up', 'scheduled', 'Call client about missing documents.'],
        [$clientIds[2], 4, '2026-05-15 09:15:00', 'in-person', 'Coverage consultation', 'completed', 'Discussed additional riders.'],
        [$clientIds[3], 1, '2026-05-21 16:00:00', 'zoom', 'Billing clarification', 'scheduled', 'Prepare premium statement.'],
    ];
    $insertMeetingStmt = $pdo->prepare(
        'INSERT INTO meeting_schedules (client_id, agent_id, meeting_at, channel, purpose, status, notes)
         VALUES (:client_id, :agent_id, :meeting_at, :channel, :purpose, :status, :notes)'
    );
    foreach ($meetings as $meeting) {
        [$clientId, $agentId, $meetingAt, $channel, $purpose, $status, $notes] = $meeting;
        $insertMeetingStmt->execute([
            ':client_id' => $clientId,
            ':agent_id' => $agentId,
            ':meeting_at' => $meetingAt,
            ':channel' => $channel,
            ':purpose' => $purpose,
            ':status' => $status,
            ':notes' => $notes,
        ]);
    }

    $pdo->commit();
    echo "Analytics seed inserted successfully.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Analytics seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}