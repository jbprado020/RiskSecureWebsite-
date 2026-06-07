USE risk_secure_db;

CREATE TABLE IF NOT EXISTS insurance_partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(120) NOT NULL,
    insurance_type ENUM('life', 'non-life', 'both') NOT NULL DEFAULT 'both',
    contact_person VARCHAR(120) NOT NULL,
    contact_email VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO insurance_partners (company_name, insurance_type, contact_person, contact_email)
VALUES
('InLife', 'life', 'InLife Liaison', 'inlife.partner@risksecure.local'),
('AIA', 'life', 'AIA Liaison', 'aia.partner@risksecure.local'),
('Generali', 'life', 'Generali Liaison', 'generali.partner@risksecure.local'),
('Philinsure', 'non-life', 'Philinsure Liaison', 'philinsure.partner@risksecure.local'),
('Pacific Union', 'non-life', 'Pacific Union Liaison', 'pacificunion.partner@risksecure.local')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);

SET @has_staff_contact_number = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'staff_accounts'
      AND column_name = 'contact_number'
);

SET @add_staff_contact_sql = IF(
    @has_staff_contact_number = 0,
    'ALTER TABLE staff_accounts ADD COLUMN contact_number VARCHAR(30) NULL',
    'SELECT 1'
);

PREPARE add_staff_contact_stmt FROM @add_staff_contact_sql;
EXECUTE add_staff_contact_stmt;
DEALLOCATE PREPARE add_staff_contact_stmt;

ALTER TABLE staff_accounts
    MODIFY COLUMN role ENUM('admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer') NOT NULL;

SET @has_policies_client_id = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'policies'
      AND column_name = 'client_id'
);

SET @add_policies_client_sql = IF(
    @has_policies_client_id = 0,
    'ALTER TABLE policies ADD COLUMN client_id INT NULL',
    'SELECT 1'
);

PREPARE add_policies_client_stmt FROM @add_policies_client_sql;
EXECUTE add_policies_client_stmt;
DEALLOCATE PREPARE add_policies_client_stmt;

SET @has_policies_partner_id = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'policies'
      AND column_name = 'partner_id'
);

SET @add_policies_partner_sql = IF(
    @has_policies_partner_id = 0,
    'ALTER TABLE policies ADD COLUMN partner_id INT NULL',
    'SELECT 1'
);

PREPARE add_policies_partner_stmt FROM @add_policies_partner_sql;
EXECUTE add_policies_partner_stmt;
DEALLOCATE PREPARE add_policies_partner_stmt;

SET @has_policies_policy_type = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'policies'
      AND column_name = 'policy_type'
);

SET @add_policies_policy_type_sql = IF(
    @has_policies_policy_type = 0,
    'ALTER TABLE policies ADD COLUMN policy_type ENUM(\'life\', \'non-life\') NULL',
    'SELECT 1'
);

PREPARE add_policies_policy_type_stmt FROM @add_policies_policy_type_sql;
EXECUTE add_policies_policy_type_stmt;
DEALLOCATE PREPARE add_policies_policy_type_stmt;

SET @has_policies_coverage_amount = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'policies'
      AND column_name = 'coverage_amount'
);

SET @add_policies_coverage_amount_sql = IF(
    @has_policies_coverage_amount = 0,
    'ALTER TABLE policies ADD COLUMN coverage_amount DECIMAL(12,2) NULL',
    'SELECT 1'
);

PREPARE add_policies_coverage_amount_stmt FROM @add_policies_coverage_amount_sql;
EXECUTE add_policies_coverage_amount_stmt;
DEALLOCATE PREPARE add_policies_coverage_amount_stmt;

SET @has_policies_premium = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'policies'
      AND column_name = 'premium'
);

SET @add_policies_premium_sql = IF(
    @has_policies_premium = 0,
    'ALTER TABLE policies ADD COLUMN premium DECIMAL(12,2) NULL',
    'SELECT 1'
);

PREPARE add_policies_premium_stmt FROM @add_policies_premium_sql;
EXECUTE add_policies_premium_stmt;
DEALLOCATE PREPARE add_policies_premium_stmt;

UPDATE policies p
INNER JOIN quotes q ON q.id = p.quote_id
SET
    p.client_id = COALESCE(p.client_id, q.client_id),
    p.policy_type = COALESCE(p.policy_type, q.policy_type),
    p.coverage_amount = COALESCE(p.coverage_amount, q.coverage_amount),
    p.premium = COALESCE(p.premium, q.premium_amount);

UPDATE policies
SET partner_id = COALESCE(partner_id, (SELECT id FROM insurance_partners ORDER BY id LIMIT 1));

UPDATE policies SET status = 'expired' WHERE status = 'lapsed';

ALTER TABLE policies
    MODIFY COLUMN client_id INT NOT NULL,
    MODIFY COLUMN policy_type ENUM('life', 'non-life') NOT NULL,
    MODIFY COLUMN coverage_amount DECIMAL(12,2) NOT NULL,
    MODIFY COLUMN premium DECIMAL(12,2) NOT NULL,
    MODIFY COLUMN status ENUM('active', 'expired', 'pending_renewal', 'cancelled') NOT NULL DEFAULT 'active',
    MODIFY COLUMN partner_id INT NOT NULL;

CREATE TABLE IF NOT EXISTS claim_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    requirement_name VARCHAR(160) NOT NULL,
    requires_original TINYINT(1) NOT NULL DEFAULT 1,
    soft_copy_received TINYINT(1) NOT NULL DEFAULT 0,
    hard_copy_received TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending', 'complete') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_requirements_claim FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE
);

SET @has_claims_date_filed = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'date_filed'
);

SET @add_claims_date_filed_sql = IF(
    @has_claims_date_filed = 0,
    'ALTER TABLE claims ADD COLUMN date_filed DATE NULL',
    'SELECT 1'
);

PREPARE add_claims_date_filed_stmt FROM @add_claims_date_filed_sql;
EXECUTE add_claims_date_filed_stmt;
DEALLOCATE PREPARE add_claims_date_filed_stmt;

SET @has_claims_claim_status = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'claim_status'
);

SET @add_claims_claim_status_sql = IF(
    @has_claims_claim_status = 0,
    'ALTER TABLE claims ADD COLUMN claim_status ENUM(\'pending\', \'under_review\', \'approved\', \'declined\') NULL',
    'SELECT 1'
);

PREPARE add_claims_claim_status_stmt FROM @add_claims_claim_status_sql;
EXECUTE add_claims_claim_status_stmt;
DEALLOCATE PREPARE add_claims_claim_status_stmt;

SET @has_claims_requirements_complete = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'requirements_complete'
);

SET @add_claims_requirements_complete_sql = IF(
    @has_claims_requirements_complete = 0,
    'ALTER TABLE claims ADD COLUMN requirements_complete TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);

PREPARE add_claims_requirements_complete_stmt FROM @add_claims_requirements_complete_sql;
EXECUTE add_claims_requirements_complete_stmt;
DEALLOCATE PREPARE add_claims_requirements_complete_stmt;

SET @has_claims_approval_date = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'approval_date'
);

SET @add_claims_approval_date_sql = IF(
    @has_claims_approval_date = 0,
    'ALTER TABLE claims ADD COLUMN approval_date DATE NULL',
    'SELECT 1'
);

PREPARE add_claims_approval_date_stmt FROM @add_claims_approval_date_sql;
EXECUTE add_claims_approval_date_stmt;
DEALLOCATE PREPARE add_claims_approval_date_stmt;

SET @has_claims_decision_notes = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'decision_notes'
);

SET @add_claims_decision_notes_sql = IF(
    @has_claims_decision_notes = 0,
    'ALTER TABLE claims ADD COLUMN decision_notes VARCHAR(255) NULL',
    'SELECT 1'
);

PREPARE add_claims_decision_notes_stmt FROM @add_claims_decision_notes_sql;
EXECUTE add_claims_decision_notes_stmt;
DEALLOCATE PREPARE add_claims_decision_notes_stmt;

SET @has_claims_decision_by_staff_id = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'decision_by_staff_id'
);

SET @add_claims_decision_by_staff_sql = IF(
    @has_claims_decision_by_staff_id = 0,
    'ALTER TABLE claims ADD COLUMN decision_by_staff_id INT NULL',
    'SELECT 1'
);

PREPARE add_claims_decision_by_staff_stmt FROM @add_claims_decision_by_staff_sql;
EXECUTE add_claims_decision_by_staff_stmt;
DEALLOCATE PREPARE add_claims_decision_by_staff_stmt;

UPDATE claims
SET date_filed = COALESCE(date_filed, DATE(created_at));

SET @has_legacy_claim_status = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'claims'
      AND column_name = 'status'
);

SET @map_claim_status_sql = IF(
    @has_legacy_claim_status > 0,
    'UPDATE claims
     SET claim_status = CASE
         WHEN claim_status IS NOT NULL THEN claim_status
         WHEN status = "filed" THEN "pending"
         WHEN status = "reviewing" THEN "under_review"
         WHEN status = "approved" THEN "approved"
         WHEN status = "rejected" THEN "declined"
         WHEN status = "paid" THEN "approved"
         ELSE "pending"
     END',
    'UPDATE claims
     SET claim_status = COALESCE(claim_status, "pending")'
);

PREPARE map_claim_status_stmt FROM @map_claim_status_sql;
EXECUTE map_claim_status_stmt;
DEALLOCATE PREPARE map_claim_status_stmt;

UPDATE claims
SET approval_date = CASE
    WHEN approval_date IS NOT NULL THEN approval_date
    WHEN claim_status = 'approved' THEN DATE(COALESCE(resolved_at, created_at))
    ELSE NULL
END;

UPDATE claims c
LEFT JOIN (
    SELECT claim_id, MIN(status = 'pending') AS has_pending
    FROM claim_requirements
    GROUP BY claim_id
) r ON r.claim_id = c.id
SET c.requirements_complete = CASE
    WHEN r.claim_id IS NULL THEN 0
    WHEN r.has_pending = 0 THEN 1
    ELSE 0
END;

ALTER TABLE claims
    MODIFY COLUMN date_filed DATE NOT NULL,
    MODIFY COLUMN claim_status ENUM('pending', 'under_review', 'approved', 'declined') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    policy_id INT NOT NULL,
    claim_id INT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by ENUM('customer', 'staff') NOT NULL DEFAULT 'customer',
    date_uploaded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_hard_copy_received TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_documents_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_policy FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_claim FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS claim_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL,
    paid_date DATE NOT NULL,
    reference_no VARCHAR(80) NOT NULL,
    recorded_by_staff_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_claim_payments_claim FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_payments_staff FOREIGN KEY (recorded_by_staff_id) REFERENCES staff_accounts(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS renewals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_id INT NOT NULL,
    renewal_date DATE NOT NULL,
    previous_expiry DATE NOT NULL,
    new_expiry DATE NOT NULL,
    status ENUM('notified', 'in_progress', 'renewed', 'lapsed') NOT NULL DEFAULT 'notified',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_renewals_policy FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meeting_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    agent_id INT NULL,
    meeting_at DATETIME NOT NULL,
    channel ENUM('zoom', 'phone', 'in-person') NOT NULL DEFAULT 'zoom',
    purpose VARCHAR(160) NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meeting_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    CONSTRAINT fk_meeting_agent FOREIGN KEY (agent_id) REFERENCES staff_accounts(id) ON DELETE SET NULL
);

SET @has_meetings_agent_id = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'meeting_schedules'
      AND column_name = 'agent_id'
);

SET @add_meetings_agent_sql = IF(
    @has_meetings_agent_id = 0,
    'ALTER TABLE meeting_schedules ADD COLUMN agent_id INT NULL',
    'SELECT 1'
);

PREPARE add_meetings_agent_stmt FROM @add_meetings_agent_sql;
EXECUTE add_meetings_agent_stmt;
DEALLOCATE PREPARE add_meetings_agent_stmt;

ALTER TABLE meeting_schedules
    MODIFY COLUMN status ENUM('planned', 'scheduled', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled';

UPDATE meeting_schedules SET status = 'scheduled' WHERE status = 'planned';

ALTER TABLE meeting_schedules
    MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled';

SET @default_agent_id = (
    SELECT id FROM staff_accounts
    WHERE role IN ('underwriter', 'manager', 'admin')
    ORDER BY id
    LIMIT 1
);

UPDATE meeting_schedules
SET agent_id = COALESCE(agent_id, @default_agent_id)
WHERE agent_id IS NULL;

ALTER TABLE clients ENGINE=InnoDB;
ALTER TABLE customer_accounts ENGINE=InnoDB;
ALTER TABLE staff_accounts ENGINE=InnoDB;
ALTER TABLE insurance_partners ENGINE=InnoDB;
ALTER TABLE quotes ENGINE=InnoDB;
ALTER TABLE policies ENGINE=InnoDB;
ALTER TABLE claims ENGINE=InnoDB;
ALTER TABLE claim_requirements ENGINE=InnoDB;
ALTER TABLE documents ENGINE=InnoDB;
ALTER TABLE claim_payments ENGINE=InnoDB;
ALTER TABLE payments ENGINE=InnoDB;
ALTER TABLE renewals ENGINE=InnoDB;
ALTER TABLE meeting_schedules ENGINE=InnoDB;
