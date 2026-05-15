-- Comprehensive indexing for RiskSecure system
-- Procedure-free + idempotent for phpMyAdmin/MySQL compatibility

-- Helper pattern per index:
-- 1) Check if index exists
-- 2) Build CREATE INDEX only when missing
-- 3) Execute prepared statement

-- Indexes on Foreign Keys
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'customer_accounts' AND index_name = 'idx_customer_accounts_client_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_customer_accounts_client_id ON customer_accounts(client_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'quotes' AND index_name = 'idx_quotes_client_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_quotes_client_id ON quotes(client_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_quote_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_quote_id ON policies(quote_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_client_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_client_id ON policies(client_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_partner_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_partner_id ON policies(partner_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_policy_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_policy_id ON claims(policy_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_decision_staff_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_decision_staff_id ON claims(decision_by_staff_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claim_requirements' AND index_name = 'idx_claim_requirements_claim_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claim_requirements_claim_id ON claim_requirements(claim_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'documents' AND index_name = 'idx_documents_client_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_documents_client_id ON documents(client_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'documents' AND index_name = 'idx_documents_policy_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_documents_policy_id ON documents(policy_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'documents' AND index_name = 'idx_documents_claim_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_documents_claim_id ON documents(claim_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claim_payments' AND index_name = 'idx_claim_payments_claim_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claim_payments_claim_id ON claim_payments(claim_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claim_payments' AND index_name = 'idx_claim_payments_staff_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claim_payments_staff_id ON claim_payments(recorded_by_staff_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payments' AND index_name = 'idx_payments_policy_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_policy_id ON payments(policy_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'renewals' AND index_name = 'idx_renewals_policy_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_renewals_policy_id ON renewals(policy_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'meeting_schedules' AND index_name = 'idx_meeting_schedules_client_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_meeting_schedules_client_id ON meeting_schedules(client_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'meeting_schedules' AND index_name = 'idx_meeting_schedules_agent_id');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_meeting_schedules_agent_id ON meeting_schedules(agent_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes on Status/Enum columns
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'quotes' AND index_name = 'idx_quotes_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_quotes_status ON quotes(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_status ON policies(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_status ON claims(claim_status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claim_requirements' AND index_name = 'idx_claim_requirements_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claim_requirements_status ON claim_requirements(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payments' AND index_name = 'idx_payments_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_status ON payments(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'renewals' AND index_name = 'idx_renewals_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_renewals_status ON renewals(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'meeting_schedules' AND index_name = 'idx_meeting_schedules_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_meeting_schedules_status ON meeting_schedules(status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'staff_accounts' AND index_name = 'idx_staff_accounts_is_active');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_staff_accounts_is_active ON staff_accounts(is_active)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'staff_accounts' AND index_name = 'idx_staff_accounts_role');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_staff_accounts_role ON staff_accounts(role)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes on Date columns
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_date_filed');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_date_filed ON claims(date_filed)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_incident_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_incident_date ON claims(incident_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'documents' AND index_name = 'idx_documents_date_uploaded');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_documents_date_uploaded ON documents(date_uploaded)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payments' AND index_name = 'idx_payments_due_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_due_date ON payments(due_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payments' AND index_name = 'idx_payments_paid_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_paid_date ON payments(paid_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'meeting_schedules' AND index_name = 'idx_meeting_schedules_meeting_at');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_meeting_schedules_meeting_at ON meeting_schedules(meeting_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'renewals' AND index_name = 'idx_renewals_renewal_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_renewals_renewal_date ON renewals(renewal_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Composite indexes
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'documents' AND index_name = 'idx_documents_client_policy');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_documents_client_policy ON documents(client_id, policy_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'idx_claims_policy_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_claims_policy_status ON claims(policy_id, claim_status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payments' AND index_name = 'idx_payments_policy_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_policy_status ON payments(policy_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_client_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_client_status ON policies(client_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'meeting_schedules' AND index_name = 'idx_meeting_schedules_client_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_meeting_schedules_client_status ON meeting_schedules(client_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'quotes' AND index_name = 'idx_quotes_client_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_quotes_client_status ON quotes(client_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'renewals' AND index_name = 'idx_renewals_policy_status');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_renewals_policy_status ON renewals(policy_id, status)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Lookup / unique identifier indexes
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'staff_accounts' AND index_name = 'idx_staff_accounts_email');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_staff_accounts_email ON staff_accounts(email)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'clients' AND index_name = 'idx_clients_email');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_clients_email ON clients(email)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'policies' AND index_name = 'idx_policies_policy_number');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_policies_policy_number ON policies(policy_number)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
