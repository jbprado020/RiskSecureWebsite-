# RiskSecure - Insurance Management System

## Project Description

**RiskSecure** is a comprehensive web-based insurance management system designed to streamline policy administration, claims processing, and document management. The system provides separate portals for customers and staff members, enabling efficient collaboration between insurance agents, underwriters, claims officers, and billing personnel.

### Core Purpose
- Manage insurance policies and quotations across multiple insurance partners
- Process and track insurance claims with comprehensive documentation requirements
- Handle policy renewals and scheduled meetings with customers
- Track premium payments and claim payments
- Maintain a centralized document repository for policies and claims

### Target Users
- **Customers**: View policies, file claims, upload documents, schedule meetings
- **Staff Members**: 
  - Admins: System-wide management and oversight
  - Underwriters: Quote generation and underwriting decisions
  - Claims Officers: Claim review and payment processing
  - Billing Officers: Payment tracking and financial reporting
  - Managers: Oversight and reporting

---

## Technology Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 8.3+ |
| **Database** | MySQL 8.4+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Authentication** | Bcrypt password hashing |
| **Session Management** | PHP native sessions |

---

## Setup Instructions

### Prerequisites
- PHP 8.3 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server with PHP support
- `mysqli` extension enabled
- Git (optional, for cloning)

### Installation Steps

1. **Clone or Download the Repository**
   ```bash
   git clone <repository-url>
   cd RiskSecureWebsite
   ```

2. **Create Database**
   ```bash
   mysql -u root -p
   CREATE DATABASE risk_secure_db;
   exit;
   ```

3. **Import Database Schema**
   ```bash
   mysql -u root -p risk_secure_db < database/risk_secure_db.sql
   ```

4. **Configure Database Connection**
   - Update `config/db.php` with your database credentials:
   ```php
   $host = "localhost";
   $user = "root";           // Your MySQL username
   $password = "";           // Your MySQL password
   $database = "risk_secure_db";
   ```

5. **Set File Permissions**
   - Ensure the web server has write permissions to the project directory
   - Create uploads directory if needed: `mkdir -p uploads/`

6. **Start Web Server**
   ```bash
   # Using PHP built-in server (development only)
   php -S localhost:8000
   ```
   - Access the application at `http://localhost:8000/index.php`

### Default Test Credentials

**Admin Account**
- Email: `admin@risksecure.local`
- Password: `password` (change after first login)

**Sample Customers**
- Juan Dela Cruz: `juan.delacruz@example.com`
- Maria Santos: `maria.santos@example.com`
- Leo Navarro: `leo.navarro@example.com`

---

## Features Implemented

### 1. User Authentication & Authorization
- ✅ Separate login portals for customers and staff
- ✅ Role-based access control (Admin, Manager, Underwriter, Claims Officer, Billing Officer)
- ✅ Secure password hashing using Bcrypt
- ✅ Session management and logout functionality

### 2. Customer Portal
- ✅ View personal policies with coverage details
- ✅ Request insurance quotes
- ✅ File insurance claims with incident details
- ✅ Upload supporting documents (policies, medical records, etc.)
- ✅ Track claim status and requirements
- ✅ View payment history
- ✅ Schedule meetings with staff (Zoom, phone, in-person)

### 3. Policy Management
- ✅ Quote generation with customizable coverage and premium calculation
- ✅ Quote approval workflow
- ✅ Policy issuance from approved quotes
- ✅ Multiple insurance partner support (InLife, AIA, Generali, Philinsure, Pacific Union)
- ✅ Policy type management (Life, Non-Life)
- ✅ Policy status tracking (Active, Expired, Pending Renewal, Cancelled)
- ✅ Coverage amount and premium management

### 4. Claims Processing
- ✅ Claim filing with incident details and claim amount
- ✅ Claim status tracking (Filed, Reviewing, Approved, Rejected, Paid)
- ✅ Requirements checklist (soft copy, hard copy, original documents)
- ✅ Claim payment recording with reference numbers
- ✅ Staff decision notes and approval dates
- ✅ Claim history and reporting

### 5. Document Management
- ✅ Document upload for policies and claims
- ✅ File type validation (document_type field)
- ✅ File path management with server-side storage
- ✅ Uploaded by tracking (customer vs. staff)
- ✅ Hard copy receipt tracking
- ✅ Document retrieval and listing

### 6. Payment Management
- ✅ Premium payment tracking by policy
- ✅ Payment status (Pending, Paid, Overdue)
- ✅ Due date and paid date management
- ✅ Claim payment processing with reference numbers
- ✅ Payment history per policy
- ✅ Billing officer oversight

### 7. Policy Renewals
- ✅ Renewal date scheduling
- ✅ Renewal task management with status tracking
- ✅ Renewal notifications (notified, in_progress, renewed, lapsed)
- ✅ Previous and new expiry date tracking
- ✅ Renewal notes and documentation

### 8. Meeting Scheduling
- ✅ Schedule meetings with customers
- ✅ Multiple channel support (Zoom, Phone, In-person)
- ✅ Meeting status tracking (Scheduled, Completed, Cancelled, No-show)
- ✅ Agent/staff assignment
- ✅ Meeting notes and purpose documentation

### 9. Insurance Partner Management
- ✅ Partner company registration and maintenance (Admin/Manager-only interface)
- ✅ Insurance type categorization (Life, Non-Life, Both)
- ✅ Contact person and email management
- ✅ Support for multiple insurance partners
- ✅ Policy count tracking per partner
- ✅ Partner deletion with active policy protection
- **Access**: Navigate to **Insurance Partners** in staff dashboard

### 10. Staff Management
- ✅ Staff account creation with role assignment (Admin-only interface)
- ✅ All 5 staff roles: Admin, Manager, Underwriter, Claims Officer, Billing Officer
- ✅ Staff status tracking (Active/Inactive)
- ✅ Contact number management
- ✅ Inline edit forms for updating staff details
- ✅ Password reset functionality
- ✅ Delete staff with self-deletion prevention
- **Access**: Navigate to **Staff Mgmt** in staff dashboard

---

## Staff Management Pages (Admin-Only)

### Staff Management (`staff_management.php`)
- **Purpose**: Complete CRUD operations for staff accounts
- **Access Control**: Administrators only
- **Features**:
  - Create new staff with email uniqueness validation
  - Edit staff details (name, email, role, contact, status)
  - Reset staff passwords securely
  - Delete staff accounts with confirmation
  - View complete staff directory

### Insurance Partner Management (`insurance_partners.php`)
- **Purpose**: Manage partnerships with insurance companies
- **Access Control**: Administrators and Managers
- **Features**:
  - Add new insurance partners with validation
  - Edit partner information
  - View active policy counts per partner
  - Delete partners (prevented if active policies exist)
  - Partner listing with contact details

---

## Database Schema Overview

### Core Tables (14 tables)

| Table | Purpose |
|-------|---------|
| **clients** | Customer profiles with contact information |
| **customer_accounts** | Customer login credentials |
| **staff_accounts** | Staff login credentials with role assignment |
| **insurance_partners** | Insurance company partnerships |
| **quotes** | Insurance quotes for customers |
| **policies** | Issued insurance policies linked to quotes |
| **claims** | Insurance claim records and status |
| **claim_requirements** | Checklist of documents needed for claims |
| **claim_payments** | Payment records for approved claims |
| **payments** | Premium payment tracking |
| **documents** | Stored documents for policies and claims |
| **renewals** | Policy renewal records |
| **renewal_tasks** | Renewal task management |
| **meeting_schedules** | Customer meeting appointments |

### Relationships
- **One-to-One**: Customers ↔ Accounts, Quotes ↔ Policies, Claims ↔ Payments
- **One-to-Many**: Policies → Claims/Payments/Renewals, Claims → Requirements, Staff → Claims (decisions)
- **Cascade Deletes**: Maintained for data integrity on key relationships

---

## Project Structure

```
RiskSecureWebsite/
├── index.php              # Home page and routing
├── Signup.php             # Customer registration
├── config/
│   └── db.php            # Database connection configuration
├── auth/
│   ├── authenticate.php  # Staff login handler
│   ├── verify.php        # Customer login handler
│   └── logout.php        # Logout handler
├── handlers/
│   └── save_registration.php  # Registration form handler
├── pages/
│   ├── login.php         # Staff login form
│   ├── register.php      # Customer registration form
│   ├── dashboard.php     # Main dashboard
│   └── ForgotPassword.php # Password recovery
├── assets/
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript files
├── database/
│   ├── risk_secure_db.sql  # Database schema and seed data
│   └── library_erd.drawio  # Database diagram
├── includes/
│   └── session.php       # Session management utilities
└── README.md             # This file
```

---

## Usage Guide

### Customer Workflow
1. **Register**: Create account on Signup.php
2. **Login**: Access customer portal
3. **Request Quote**: Submit insurance requirement
4. **View Policies**: Check active and expired policies
5. **File Claim**: Submit claim with incident details
6. **Upload Documents**: Attach supporting documents
7. **Track Status**: Monitor claim progress
8. **Schedule Meeting**: Book appointment with staff

### Staff Workflow
1. **Login**: Access staff portal with credentials
2. **Review Queue**: Check pending quotes and claims
3. **Underwriting**: Approve/reject quotes
4. **Claims Review**: Process and decide on claims
5. **Payments**: Record claim or premium payments
6. **Reporting**: Generate financial reports
7. **Renewals**: Manage policy renewal tasks

---

## Security Features

✅ **Password Security**: Bcrypt hashing for all credentials
✅ **Session Management**: HTTP-only session cookies
✅ **Input Validation**: Server-side validation for forms
✅ **Database Prepared Statements**: Protection against SQL injection (mysqli)
✅ **Role-Based Access Control**: Different permissions per staff role
✅ **Unique Constraints**: Email and policy number uniqueness enforced
✅ **Cascade Deletes**: Data integrity through foreign keys

---

## Future Enhancements

- 🔄 Email notifications for claim status updates
- 🔄 SMS alerts for payment reminders
- 🔄 Advanced reporting and analytics dashboard
- 🔄 API for third-party integrations
- 🔄 Two-factor authentication for staff
- 🔄 Mobile app for customers
- 🔄 Payment gateway integration (online payments)
- 🔄 Automated renewal reminders
- 🔄 Audit logging for compliance

---

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running: `mysql -u root -p`
- Check credentials in `config/db.php`
- Ensure `risk_secure_db` database exists

### Login Issues
- Check if staff/customer account exists in database
- Verify password matches (case-sensitive)
- Clear browser cookies and try again

### File Upload Issues
- Ensure `uploads/` directory exists and is writable
- Check file size limits in `php.ini`
- Verify file type restrictions

---

## License

This project is proprietary and confidential. All rights reserved.

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | May 2026 | Initial release with core features |

---

**Last Updated**: May 4, 2026
**Status**: Active Development
3. Create customer auth table (for existing databases):
   - Run `database/add_customer_accounts.sql`
4. Create staff auth table and sample accounts (for existing databases):
   - Run `database/add_staff_accounts.sql`
5. Create process monitoring tables (for existing databases):
   - Run `database/add_process_tables.sql`
6. Configure DB credentials in `config/db.php`:
   - host, port, db name, username, password
7. Serve project via Apache/XAMPP or PHP built-in server:
   - `php -S localhost:8000`
8. Open:
   - `http://localhost:8000/index.php`

## Staff Login (Role-Based)

Use `staff_login.php` for back-office pages.

Default sample password for all seeded staff accounts: `password`

- admin@risksecure.local (role: admin)
- manager@risksecure.local (role: manager)
- underwriter@risksecure.local (role: underwriter)
- claims@risksecure.local (role: claims_officer)
- billing@risksecure.local (role: billing_officer)

Role permissions:

- Admin: full access (including Staff Management and Partner Management)
- Manager: dashboard, clients, quotes, policies, claims, documents, renewals, meetings, payments, reports, partner management
- Underwriter: dashboard, clients, quotes, policies, claims, documents, renewals, meetings, reports
- Claims Officer: dashboard, claims, documents, meetings, reports
- Billing Officer: dashboard, payments, reports

Navigation and dashboard quick action cards are role-aware and automatically hidden when not permitted for the signed-in role.

## Suggested Demo Flow (for your report/presentation)

1. Add a new client in `clients.php`
2. Create a quote in `quotes.php`
3. Approve the quote
4. Issue the policy in `policies.php`
5. Create a payment schedule in `payments.php`
6. File and process a claim in `claims.php`
7. Track claim requirements checklist in `claims.php`
8. Record claim decision and claim payment in `claims.php`
9. Upload and retrieve client/policy documents in `documents.php`
10. Process renewals and update renewal status in `renewals.php`
11. Schedule and track meetings in `meetings.php`
12. Generate summaries/export CSV in `reports.php`

## Customer-Side Flow

1. Open `customer_portal.php`
2. Register in `customer_register.php`
3. Login in `customer_login.php`
4. Submit an insurance application as a customer
5. Back-office team reviews it in `quotes.php`
6. After approval, issue policy in `policies.php`
7. Customer can file claims and track status in `customer_portal.php`
8. Customer can schedule appointments and upload documents in `customer_portal.php`

## Notes on Real-World Gaps (Next Iteration)

- Audit trails and activity logs
- Stronger underwriting model and fraud checks
- Email/SMS notifications
- API layer and integration with payment gateways
