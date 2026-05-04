-- Comprehensive indexing for RiskSecure system
-- Indexes on Foreign Keys (explicit for clarity and performance guarantee)
CREATE INDEX idx_customer_accounts_client_id ON customer_accounts(client_id);
CREATE INDEX idx_quotes_client_id ON quotes(client_id);
CREATE INDEX idx_policies_quote_id ON policies(quote_id);
CREATE INDEX idx_policies_client_id ON policies(client_id);
CREATE INDEX idx_policies_partner_id ON policies(partner_id);
CREATE INDEX idx_claims_policy_id ON claims(policy_id);
CREATE INDEX idx_claims_decision_staff_id ON claims(decision_by_staff_id);
CREATE INDEX idx_claim_requirements_claim_id ON claim_requirements(claim_id);
CREATE INDEX idx_documents_client_id ON documents(client_id);
CREATE INDEX idx_documents_policy_id ON documents(policy_id);
CREATE INDEX idx_documents_claim_id ON documents(claim_id);
CREATE INDEX idx_claim_payments_claim_id ON claim_payments(claim_id);
CREATE INDEX idx_claim_payments_staff_id ON claim_payments(recorded_by_staff_id);
CREATE INDEX idx_payments_policy_id ON payments(policy_id);
CREATE INDEX idx_renewals_policy_id ON renewals(policy_id);
CREATE INDEX idx_meeting_schedules_client_id ON meeting_schedules(client_id);
CREATE INDEX idx_meeting_schedules_agent_id ON meeting_schedules(agent_id);

-- Indexes on Status and Enum columns (used for filtering)
CREATE INDEX idx_quotes_status ON quotes(status);
CREATE INDEX idx_policies_status ON policies(status);
CREATE INDEX idx_claims_status ON claims(claim_status);
CREATE INDEX idx_claim_requirements_status ON claim_requirements(status);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_renewals_status ON renewals(status);
CREATE INDEX idx_meeting_schedules_status ON meeting_schedules(status);
CREATE INDEX idx_staff_accounts_is_active ON staff_accounts(is_active);
CREATE INDEX idx_staff_accounts_role ON staff_accounts(role);

-- Indexes on Date columns (used for sorting, filtering, and range queries)
CREATE INDEX idx_claims_date_filed ON claims(date_filed);
CREATE INDEX idx_claims_incident_date ON claims(incident_date);
CREATE INDEX idx_documents_date_uploaded ON documents(date_uploaded);
CREATE INDEX idx_payments_due_date ON payments(due_date);
CREATE INDEX idx_payments_paid_date ON payments(paid_date);
CREATE INDEX idx_meeting_schedules_meeting_at ON meeting_schedules(meeting_at);
CREATE INDEX idx_renewals_renewal_date ON renewals(renewal_date);

-- Composite indexes for common multi-column queries
-- For document lookups by client + policy
CREATE INDEX idx_documents_client_policy ON documents(client_id, policy_id);

-- For claim lookups by policy + status
CREATE INDEX idx_claims_policy_status ON claims(policy_id, claim_status);

-- For payment lookups by policy + status
CREATE INDEX idx_payments_policy_status ON payments(policy_id, status);

-- For policy lookups by client + status (used in customer portal)
CREATE INDEX idx_policies_client_status ON policies(client_id, status);

-- For meeting lookups by client + status
CREATE INDEX idx_meeting_schedules_client_status ON meeting_schedules(client_id, status);

-- For quote lookups by client + status (used in customer portal and staff dashboards)
CREATE INDEX idx_quotes_client_status ON quotes(client_id, status);

-- For renewals lookups by policy + status
CREATE INDEX idx_renewals_policy_status ON renewals(policy_id, status);

-- Indexes on lookup/unique identifiers (speed up login and specific lookups)
CREATE INDEX idx_staff_accounts_email ON staff_accounts(email);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_policies_policy_number ON policies(policy_number);
