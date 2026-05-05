<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function badgeClass(string $status): string
{
    $success = ['active', 'approved', 'paid', 'complete', 'renewed', 'completed'];
    $warning = ['pending', 'under_review', 'overdue', 'contacted', 'notified', 'in_progress', 'pending_renewal', 'scheduled'];

    if (in_array($status, $success, true)) {
        return 'success';
    }

    if (in_array($status, $warning, true)) {
        return 'warn';
    }

    if (in_array($status, ['rejected', 'declined', 'cancelled', 'lapsed', 'expired', 'no_show'], true)) {
        return 'danger';
    }

    return 'info';
}

function statusLabel(string $status): string
{
    $labels = [
        'pending_renewal' => 'Pending Renewal',
        'under_review' => 'Under Review',
        'declined' => 'Declined',
        'no_show' => 'No-Show',
        'in_progress' => 'In Progress',
    ];

    if (isset($labels[$status])) {
        return $labels[$status];
    }

    return ucwords(str_replace('_', ' ', $status));
}

function navLink(string $href, string $label, string $currentPage, string $icon = 'radio_button_unchecked'): void
{
    $activeClass = $currentPage === $href ? ' class="active"' : '';
    echo '<a href="' . e($href) . '"' . $activeClass . '>';
    echo '<span class="material-symbols-rounded nav-icon" aria-hidden="true">' . e($icon) . '</span>';
    echo '<span class="nav-text">' . e($label) . '</span>';
    echo '</a>';
}

function canAccess(array $allowedRoles): bool
{
    if (!isStaffLoggedIn()) {
        return false;
    }

    return in_array(staffRole(), $allowedRoles, true);
}

function renderHeader(string $title): void
{
    ensureSessionStarted();
    $currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' | RiskSecure Insurance</title>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0">';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<header class="topbar">';
    echo '<div class="container">';
    echo '<div class="brand">';
    echo '<img class="brand-logo" src="icon/649536819_912363384772670_6676616353184671990_n.jpg" alt="RiskSecure logo">';
    echo '<div class="brand-copy">';
    echo '<span class="brand-title">RiskSecure Insurance</span>';
    echo '<span class="brand-subtitle">Operations Workflow</span>';
    echo '</div>';
    echo '</div>';
    echo '<nav class="nav">';

    if (isStaffLoggedIn()) {
        navLink('index.php', 'Dashboard', $currentPage, 'dashboard');

        if (canAccess(['admin', 'manager', 'underwriter'])) {
            navLink('clients.php', 'Clients', $currentPage, 'group');
            navLink('quotes.php', 'Quotes', $currentPage, 'request_quote');
            navLink('policies.php', 'Policies', $currentPage, 'policy');
            navLink('renewals.php', 'Renewals', $currentPage, 'cycle');
        }

        if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer'])) {
            navLink('claims.php', 'Claims', $currentPage, 'gavel');
            navLink('documents.php', 'Documents', $currentPage, 'folder_open');
            navLink('meetings.php', 'Meetings', $currentPage, 'event');
        }

        if (canAccess(['admin', 'manager', 'billing_officer'])) {
            navLink('payments.php', 'Payments', $currentPage, 'payments');
        }

        if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer'])) {
            navLink('reports.php', 'Reports', $currentPage, 'monitoring');
        }

        if (canAccess(['admin', 'manager'])) {
            navLink('insurance_partners.php', 'Partners', $currentPage, 'business');
        }

        if (canAccess(['admin'])) {
            navLink('staff_management.php', 'Staff Mgmt', $currentPage, 'manage_accounts');
        }

        navLink('staff_logout.php', 'Staff Logout (' . statusLabel(staffRole()) . ')', $currentPage, 'logout');
    } elseif (isCustomerLoggedIn()) {
        navLink('customer_portal.php', 'Customer Portal', $currentPage, 'person');
        navLink('customer_logout.php', 'Customer Logout', $currentPage, 'logout');
    } else {
        navLink('staff_login.php', 'Staff Login', $currentPage, 'admin_panel_settings');
        navLink('customer_login.php', 'Customer Login', $currentPage, 'login');
        navLink('customer_register.php', 'Customer Register', $currentPage, 'person_add');
    }

    echo '</nav>';
    echo '</div>';
    echo '</header>';
    echo '<main class="container">';
    echo '<section class="page-banner">';
    echo '<p class="page-banner-kicker"><span class="material-symbols-rounded" aria-hidden="true">workspace_premium</span> RiskSecure Operations Console</p>';
    echo '<h1>' . e($title) . '</h1>';

    if (isStaffLoggedIn()) {
        $displayName = staffName() !== '' ? staffName() : staffEmail();
        echo '<p class="page-banner-meta">Signed in as ' . e($displayName) . ' | Role: ' . e(statusLabel(staffRole())) . '</p>';
    } elseif (isCustomerLoggedIn()) {
        $displayName = customerName() !== '' ? customerName() : customerEmail();
        echo '<p class="page-banner-meta">Signed in as ' . e($displayName) . ' | Customer Portal</p>';
    } else {
        echo '<p class="page-banner-meta">Unified insurance workflow for policy, claims, payments, renewals, meetings, and reporting.</p>';
    }

    echo '</section>';
}

function renderFooter(): void
{
    echo '</main>';
    echo '<div class="footer">Sample educational system for life/non-life insurance operations.</div>';
    echo '</body>';
    echo '</html>';
}
