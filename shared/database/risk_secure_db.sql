-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 04, 2026 at 08:50 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `risk_secure_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int NOT NULL,
  `policy_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `claim_amount` decimal(12,2) NOT NULL,
  `description` text NOT NULL,
  `status` enum('filed','reviewing','approved','rejected','paid') NOT NULL DEFAULT 'filed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  `date_filed` date NOT NULL,
  `claim_status` enum('pending','under_review','approved','declined') NOT NULL DEFAULT 'pending',
  `requirements_complete` tinyint(1) NOT NULL DEFAULT '0',
  `approval_date` date DEFAULT NULL,
  `decision_notes` varchar(255) DEFAULT NULL,
  `decision_by_staff_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `policy_id`, `incident_date`, `claim_amount`, `description`, `status`, `created_at`, `resolved_at`, `date_filed`, `claim_status`, `requirements_complete`, `approval_date`, `decision_notes`, `decision_by_staff_id`) VALUES
(1, 1, '2026-02-10', 120000.00, 'Hospitalization reimbursement request.', 'reviewing', '2026-04-17 10:29:06', NULL, '2026-04-17', 'under_review', 0, NULL, NULL, NULL),
(2, 2, '2026-03-01', 45000.00, 'Minor vehicular accident damage claim.', 'approved', '2026-04-17 10:29:06', '2026-03-08 10:15:00', '2026-04-17', 'approved', 0, '2026-03-08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `claim_payments`
--

CREATE TABLE `claim_payments` (
  `id` int NOT NULL,
  `claim_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_date` date NOT NULL,
  `reference_no` varchar(80) NOT NULL,
  `recorded_by_staff_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claim_requirements`
--

CREATE TABLE `claim_requirements` (
  `id` int NOT NULL,
  `claim_id` int NOT NULL,
  `requirement_name` varchar(160) NOT NULL,
  `requires_original` tinyint(1) NOT NULL DEFAULT '1',
  `soft_copy_received` tinyint(1) NOT NULL DEFAULT '0',
  `hard_copy_received` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','complete') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `full_name`, `email`, `phone`, `address`, `date_of_birth`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'juan.delacruz@example.com', '+63-917-111-2222', 'Makati City, Metro Manila', '1992-05-11', '2026-04-17 10:29:06'),
(2, 'Maria Santos', 'maria.santos@example.com', '+63-917-333-4444', 'Quezon City, Metro Manila', '1988-09-20', '2026-04-17 10:29:06'),
(3, 'Leo Navarro', 'leo.navarro@example.com', '+63-917-555-6666', 'Cebu City, Cebu', '1996-01-15', '2026-04-17 10:29:06'),
(4, 'Julianne Benedict Prado', 'jbprado013@gmail.com', '09614165184', '27 Rosa Sanz St. Barangay 14-B', '2005-07-15', '2026-04-28 06:15:50');

-- --------------------------------------------------------

--
-- Table structure for table `customer_accounts`
--

CREATE TABLE `customer_accounts` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_accounts`
--

INSERT INTO `customer_accounts` (`id`, `client_id`, `password_hash`, `created_at`) VALUES
(1, 4, '$2y$10$iuxLRNJrVoKjcdkxIoafv./0/x6n9nK8r7mZhR5SNWJXXOBUwjCdW', '2026-04-28 06:15:50');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `policy_id` int NOT NULL,
  `claim_id` int DEFAULT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` enum('customer','staff') NOT NULL DEFAULT 'customer',
  `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_hard_copy_received` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_partners`
--

CREATE TABLE `insurance_partners` (
  `id` int NOT NULL,
  `company_name` varchar(120) NOT NULL,
  `insurance_type` enum('life','non-life','both') NOT NULL DEFAULT 'both',
  `contact_person` varchar(120) NOT NULL,
  `contact_email` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `insurance_partners`
--

INSERT INTO `insurance_partners` (`id`, `company_name`, `insurance_type`, `contact_person`, `contact_email`, `created_at`) VALUES
(1, 'InLife', 'life', 'InLife Liaison', 'inlife.partner@risksecure.local', '2026-04-21 06:02:54'),
(2, 'AIA', 'life', 'AIA Liaison', 'aia.partner@risksecure.local', '2026-04-21 06:02:54'),
(3, 'Generali', 'life', 'Generali Liaison', 'generali.partner@risksecure.local', '2026-04-21 06:02:54'),
(4, 'Philinsure', 'non-life', 'Philinsure Liaison', 'philinsure.partner@risksecure.local', '2026-04-21 06:02:54'),
(5, 'Pacific Union', 'non-life', 'Pacific Union Liaison', 'pacificunion.partner@risksecure.local', '2026-04-21 06:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `meeting_schedules`
--

CREATE TABLE `meeting_schedules` (
  `id` int NOT NULL,
  `client_id` int DEFAULT NULL,
  `meeting_at` datetime NOT NULL,
  `channel` enum('zoom','phone','in-person') NOT NULL DEFAULT 'zoom',
  `purpose` varchar(160) NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `agent_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `policy_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `policy_id`, `amount`, `due_date`, `paid_date`, `status`, `created_at`) VALUES
(1, 1, 1500.00, '2026-03-10', '2026-03-09', 'paid', '2026-04-17 10:29:06'),
(2, 2, 1041.67, '2026-03-15', NULL, 'overdue', '2026-04-17 10:29:06');

-- --------------------------------------------------------

--
-- Table structure for table `policies`
--

CREATE TABLE `policies` (
  `id` int NOT NULL,
  `quote_id` int NOT NULL,
  `policy_number` varchar(40) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','pending_renewal','cancelled') NOT NULL DEFAULT 'active',
  `issued_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `client_id` int NOT NULL,
  `partner_id` int NOT NULL,
  `policy_type` enum('life','non-life') NOT NULL,
  `coverage_amount` decimal(12,2) NOT NULL,
  `premium` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `policies`
--

INSERT INTO `policies` (`id`, `quote_id`, `policy_number`, `start_date`, `end_date`, `status`, `issued_at`, `client_id`, `partner_id`, `policy_type`, `coverage_amount`, `premium`) VALUES
(1, 1, 'RS-2026-0001', '2026-01-01', '2026-12-31', 'active', '2026-04-17 10:29:06', 1, 1, 'life', 1000000.00, 18000.00),
(2, 2, 'RS-2026-0002', '2026-02-01', '2027-01-31', 'active', '2026-04-17 10:29:06', 2, 1, 'non-life', 500000.00, 12500.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `policy_type` enum('life','non-life') NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `coverage_amount` decimal(12,2) NOT NULL,
  `term_months` int NOT NULL,
  `risk_level` enum('low','medium','high') NOT NULL,
  `premium_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `client_id`, `policy_type`, `product_name`, `coverage_amount`, `term_months`, `risk_level`, `premium_amount`, `status`, `created_at`) VALUES
(1, 1, 'life', 'SecureLife Plus', 1000000.00, 12, 'medium', 18000.00, 'approved', '2026-04-17 10:29:06'),
(2, 2, 'non-life', 'Auto Shield Premium', 500000.00, 12, 'low', 12500.00, 'approved', '2026-04-17 10:29:06'),
(3, 3, 'non-life', 'Property Guard Basic', 800000.00, 24, 'high', 60000.00, 'pending', '2026-04-17 10:29:06');

-- --------------------------------------------------------

--
-- Table structure for table `renewals`
--

CREATE TABLE `renewals` (
  `id` int NOT NULL,
  `policy_id` int NOT NULL,
  `renewal_date` date NOT NULL,
  `previous_expiry` date NOT NULL,
  `new_expiry` date NOT NULL,
  `status` enum('notified','in_progress','renewed','lapsed') NOT NULL DEFAULT 'notified',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renewal_tasks`
--

CREATE TABLE `renewal_tasks` (
  `id` int NOT NULL,
  `policy_id` int NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','contacted','renewed','closed') NOT NULL DEFAULT 'pending',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_accounts`
--

CREATE TABLE `staff_accounts` (
  `id` int NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','underwriter','claims_officer','billing_officer') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `contact_number` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_accounts`
--

INSERT INTO `staff_accounts` (`id`, `full_name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `contact_number`) VALUES
(1, 'RiskSecure Admin', 'admin@risksecure.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-04-17 10:28:58', NULL),
(2, 'Uma Underwriter', 'underwriter@risksecure.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'underwriter', 1, '2026-04-17 10:28:58', NULL),
(3, 'Clark Claims', 'claims@risksecure.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'claims_officer', 1, '2026-04-17 10:28:58', NULL),
(4, 'Bella Billing', 'billing@risksecure.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'billing_officer', 1, '2026-04-17 10:28:58', NULL),
(6, 'Maya Manager', 'manager@risksecure.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1, '2026-04-21 06:06:12', '+63-917-700-1001');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_claims_policy_id` (`policy_id`),
  ADD KEY `idx_claims_decision_staff_id` (`decision_by_staff_id`),
  ADD KEY `idx_claims_status` (`claim_status`),
  ADD KEY `idx_claims_date_filed` (`date_filed`),
  ADD KEY `idx_claims_incident_date` (`incident_date`),
  ADD KEY `idx_claims_policy_status` (`policy_id`,`claim_status`);

--
-- Indexes for table `claim_payments`
--
ALTER TABLE `claim_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `claim_id` (`claim_id`),
  ADD KEY `idx_claim_payments_claim_id` (`claim_id`),
  ADD KEY `idx_claim_payments_staff_id` (`recorded_by_staff_id`);

--
-- Indexes for table `claim_requirements`
--
ALTER TABLE `claim_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_claim_requirements_claim_id` (`claim_id`),
  ADD KEY `idx_claim_requirements_status` (`status`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_clients_email` (`email`);

--
-- Indexes for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`),
  ADD KEY `idx_customer_accounts_client_id` (`client_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_documents_client_id` (`client_id`),
  ADD KEY `idx_documents_policy_id` (`policy_id`),
  ADD KEY `idx_documents_claim_id` (`claim_id`),
  ADD KEY `idx_documents_date_uploaded` (`date_uploaded`),
  ADD KEY `idx_documents_client_policy` (`client_id`,`policy_id`);

--
-- Indexes for table `insurance_partners`
--
ALTER TABLE `insurance_partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact_email` (`contact_email`);

--
-- Indexes for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meeting_schedules_client_id` (`client_id`),
  ADD KEY `idx_meeting_schedules_agent_id` (`agent_id`),
  ADD KEY `idx_meeting_schedules_status` (`status`),
  ADD KEY `idx_meeting_schedules_meeting_at` (`meeting_at`),
  ADD KEY `idx_meeting_schedules_client_status` (`client_id`,`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_policy_id` (`policy_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_due_date` (`due_date`),
  ADD KEY `idx_payments_paid_date` (`paid_date`),
  ADD KEY `idx_payments_policy_status` (`policy_id`,`status`);

--
-- Indexes for table `policies`
--
ALTER TABLE `policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quote_id` (`quote_id`),
  ADD UNIQUE KEY `policy_number` (`policy_number`),
  ADD KEY `idx_policies_quote_id` (`quote_id`),
  ADD KEY `idx_policies_client_id` (`client_id`),
  ADD KEY `idx_policies_partner_id` (`partner_id`),
  ADD KEY `idx_policies_status` (`status`),
  ADD KEY `idx_policies_client_status` (`client_id`,`status`),
  ADD KEY `idx_policies_policy_number` (`policy_number`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quotes_client_id` (`client_id`),
  ADD KEY `idx_quotes_status` (`status`),
  ADD KEY `idx_quotes_client_status` (`client_id`,`status`);

--
-- Indexes for table `renewals`
--
ALTER TABLE `renewals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_renewals_policy_id` (`policy_id`),
  ADD KEY `idx_renewals_status` (`status`),
  ADD KEY `idx_renewals_renewal_date` (`renewal_date`),
  ADD KEY `idx_renewals_policy_status` (`policy_id`,`status`);

--
-- Indexes for table `renewal_tasks`
--
ALTER TABLE `renewal_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_renewal_tasks_policy` (`policy_id`);

--
-- Indexes for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_staff_accounts_is_active` (`is_active`),
  ADD KEY `idx_staff_accounts_role` (`role`),
  ADD KEY `idx_staff_accounts_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `claim_payments`
--
ALTER TABLE `claim_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_requirements`
--
ALTER TABLE `claim_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_partners`
--
ALTER TABLE `insurance_partners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `policies`
--
ALTER TABLE `policies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `renewals`
--
ALTER TABLE `renewals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renewal_tasks`
--
ALTER TABLE `renewal_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `fk_claims_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `claim_payments`
--
ALTER TABLE `claim_payments`
  ADD CONSTRAINT `fk_claim_payments_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_claim_payments_staff` FOREIGN KEY (`recorded_by_staff_id`) REFERENCES `staff_accounts` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `claim_requirements`
--
ALTER TABLE `claim_requirements`
  ADD CONSTRAINT `fk_claim_requirements_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD CONSTRAINT `fk_customer_accounts_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_documents_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_documents_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  ADD CONSTRAINT `fk_meeting_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `policies`
--
ALTER TABLE `policies`
  ADD CONSTRAINT `fk_policies_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `fk_quotes_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `renewals`
--
ALTER TABLE `renewals`
  ADD CONSTRAINT `fk_renewals_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `renewal_tasks`
--
ALTER TABLE `renewal_tasks`
  ADD CONSTRAINT `fk_renewal_tasks_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
