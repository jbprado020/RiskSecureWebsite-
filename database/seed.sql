USE risk_secure_db;

INSERT INTO clients (full_name, email, phone, address, date_of_birth) VALUES
('Juan Dela Cruz', 'juan.delacruz@example.com', '+63-917-111-2222', 'Makati City, Metro Manila', '1992-05-11'),
('Maria Santos', 'maria.santos@example.com', '+63-917-333-4444', 'Quezon City, Metro Manila', '1988-09-20'),
('Leo Navarro', 'leo.navarro@example.com', '+63-917-555-6666', 'Cebu City, Cebu', '1996-01-15');

INSERT INTO customer_accounts (client_id, password_hash) VALUES
(1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO staff_accounts (full_name, email, contact_number, password_hash, role, is_active) VALUES
('RiskSecure Admin', 'admin@risksecure.local', '+63-917-700-1000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Maya Manager', 'manager@risksecure.local', '+63-917-700-1001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1),
('Uma Underwriter', 'underwriter@risksecure.local', '+63-917-700-1002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'underwriter', 1),
('Clark Claims', 'claims@risksecure.local', '+63-917-700-1003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'claims_officer', 1),
('Bella Billing', 'billing@risksecure.local', '+63-917-700-1004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'billing_officer', 1);

INSERT INTO insurance_partners (company_name, insurance_type, contact_person, contact_email) VALUES
('InLife', 'life', 'InLife Liaison', 'inlife.partner@risksecure.local'),
('AIA', 'life', 'AIA Liaison', 'aia.partner@risksecure.local'),
('Generali', 'life', 'Generali Liaison', 'generali.partner@risksecure.local'),
('Philinsure', 'non-life', 'Philinsure Liaison', 'philinsure.partner@risksecure.local'),
('Pacific Union', 'non-life', 'Pacific Union Liaison', 'pacificunion.partner@risksecure.local');

INSERT INTO quotes (client_id, policy_type, product_name, coverage_amount, term_months, risk_level, premium_amount, status) VALUES
(1, 'life', 'SecureLife Plus', 1000000.00, 12, 'medium', 18000.00, 'approved'),
(2, 'non-life', 'Auto Shield Premium', 500000.00, 12, 'low', 12500.00, 'approved'),
(3, 'non-life', 'Property Guard Basic', 800000.00, 24, 'high', 60000.00, 'pending');

INSERT INTO policies (quote_id, client_id, partner_id, policy_number, policy_type, coverage_amount, premium, start_date, end_date, status) VALUES
(1, 1, 1, 'RS-2026-0001', 'life', 1000000.00, 18000.00, '2026-01-01', '2026-12-31', 'active'),
(2, 2, 4, 'RS-2026-0002', 'non-life', 500000.00, 12500.00, '2026-02-01', '2027-01-31', 'pending_renewal');

INSERT INTO claims (
	policy_id, incident_date, date_filed, claim_amount, description,
	claim_status, requirements_complete, approval_date, decision_notes,
	decision_by_staff_id, resolved_at
) VALUES
(1, '2026-02-10', '2026-02-11', 120000.00, 'Hospitalization reimbursement request.', 'under_review', 0, NULL, 'Pending remaining original hard-copy documents.', 3, NULL),
(2, '2026-03-01', '2026-03-02', 45000.00, 'Minor vehicular accident damage claim.', 'approved', 1, '2026-03-08', 'Approved by underwriter after final assessment.', 3, '2026-03-08 10:15:00');

INSERT INTO claim_requirements (claim_id, requirement_name, requires_original, soft_copy_received, hard_copy_received, status) VALUES
(1, 'ORCR', 1, 1, 0, 'pending'),
(1, 'Police Report', 1, 1, 0, 'pending'),
(2, 'Inspection Report', 1, 1, 1, 'complete');

INSERT INTO documents (client_id, policy_id, claim_id, document_type, file_path, uploaded_by, is_hard_copy_received) VALUES
(1, 1, 1, 'Medical Certificate', 'uploads/medical_certificate_claim_1.pdf', 'customer', 0),
(1, 1, 1, 'Hospital Receipt', 'uploads/hospital_receipt_claim_1.pdf', 'customer', 0),
(2, 2, 2, 'Inspection Report', 'uploads/inspection_report_claim_2.pdf', 'staff', 1);

INSERT INTO claim_payments (claim_id, amount, paid_date, reference_no, recorded_by_staff_id) VALUES
(2, 45000.00, '2026-03-10', 'CLM-PAY-2026-0002', 4);

INSERT INTO payments (policy_id, amount, due_date, paid_date, status) VALUES
(1, 1500.00, '2026-03-10', '2026-03-09', 'paid'),
(2, 1041.67, '2026-03-15', NULL, 'pending');

INSERT INTO renewals (policy_id, renewal_date, previous_expiry, new_expiry, status, notes) VALUES
(1, '2026-11-20', '2026-12-31', '2027-12-31', 'in_progress', 'Client evaluating revised premium option.'),
(2, '2026-12-20', '2027-01-31', '2028-01-31', 'notified', 'Initial reminder sent to client.');

INSERT INTO meeting_schedules (client_id, agent_id, meeting_at, channel, purpose, status, notes) VALUES
(1, 3, '2026-04-15 14:00:00', 'zoom', 'Policy renewal discussion', 'scheduled', 'Send Zoom link 1 day before.'),
(2, 4, '2026-04-08 10:30:00', 'phone', 'Claims document follow-up', 'completed', 'Confirmed missing hard copy receipt.');
