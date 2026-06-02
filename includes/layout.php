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

function iconMarkup(string $name): string
{
    $icons = [
        'dashboard' => '<path d="M4 4h6v6H4z"></path><path d="M14 4h6v4h-6z"></path><path d="M14 10h6v10h-6z"></path><path d="M4 12h6v8H4z"></path>',
        'group' => '<circle cx="8" cy="8" r="3"></circle><path d="M2.8 20c.9-3 3.1-4.6 5.2-4.6S12.3 17 13.2 20"></path><circle cx="18" cy="9" r="2.3"></circle><path d="M15.2 20c.4-1.9 1.5-3.2 3.2-3.2 1 0 1.9.3 2.6 1"></path>',
        'request_quote' => '<path d="M5 3h10l4 4v14H5z"></path><path d="M15 3v5h5"></path><path d="M8 11h8"></path><path d="M8 15h5"></path>',
        'policy' => '<path d="M6 3h8l4 4v14H6z"></path><path d="M14 3v5h4"></path><path d="M8 11h6"></path><path d="M8 15h4"></path>',
        'cycle' => '<path d="M5 7a7 7 0 0 1 12-1"></path><path d="M17 4v4h-4"></path><path d="M19 17a7 7 0 0 1-12 1"></path><path d="M7 20v-4h4"></path>',
        'gavel' => '<path d="M8 4l3 3-2 2-3-3z"></path><path d="M11 7l5 5"></path><path d="M4 16h8"></path><path d="M14 14l-4 4"></path>',
        'folder_open' => '<path d="M3 6h6l2 2h10v10H3z"></path><path d="M3 8h20"></path>',
        'event' => '<rect x="4" y="6" width="16" height="14" rx="2"></rect><path d="M8 4v4M16 4v4M4 10h16"></path><path d="M8 14h4M8 17h6"></path>',
        'payments' => '<rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18"></path><path d="M7 14h4"></path><circle cx="15.5" cy="14.5" r="1.4"></circle>',
        'monitoring' => '<path d="M4 19h16"></path><rect x="6" y="12" width="2.5" height="5"></rect><rect x="11" y="9" width="2.5" height="8"></rect><rect x="16" y="6" width="2.5" height="11"></rect>',
        'business' => '<path d="M4 8h16v12H4z"></path><path d="M8 20V4h8v16"></path><path d="M7 12h2M7 16h2M13 12h2M13 16h2"></path>',
        'manage_accounts' => '<circle cx="8" cy="8" r="3"></circle><path d="M2.8 20c.8-2.8 2.9-4.3 5.2-4.3 1.5 0 2.8.4 3.9 1.2"></path><path d="M16.5 12.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4"></path><path d="M16.5 9.5v1.2M16.5 17.3v1.2M13.8 12.5l1.1.6M18.1 14.9l1.1.6M13.8 16.9l1.1-.6M18.1 12.5l1.1-.6"></path>',
        'logout' => '<path d="M10 5H4v14h6"></path><path d="M13 12H4"></path><path d="M16 9l4 3-4 3"></path>',
        'person' => '<circle cx="12" cy="8" r="3.5"></circle><path d="M4.5 20c1.2-3.2 3.7-5 7.5-5s6.3 1.8 7.5 5"></path>',
        'admin_panel_settings' => '<path d="M12 3l7 3v5c0 4.5-2.9 7.8-7 10-4.1-2.2-7-5.5-7-10V6z"></path><circle cx="12" cy="11" r="1.8"></circle><path d="M12 13.1v3"></path>',
        'login' => '<path d="M10 5H4v14h6"></path><path d="M13 12h7"></path><path d="M16 9l4 3-4 3"></path>',
        'person_add' => '<circle cx="9" cy="8" r="3"></circle><path d="M3.8 20c1-2.9 3-4.3 5.2-4.3 1.7 0 3.2.5 4.3 1.4"></path><path d="M18 9v6M15 12h6"></path>',
        'workspace_premium' => '<path d="M12 3l7 4-1.4 7.5L12 21 6.4 14.5 5 7z"></path><path d="M12 8l1.2 2.4 2.7.4-2 1.9.5 2.7L12 13.8 9.6 15.4l.5-2.7-2-1.9 2.7-.4z"></path>',
        'logout_default' => '<path d="M10 5H4v14h6"></path><path d="M13 12H4"></path><path d="M16 9l4 3-4 3"></path>',
    ];

    $path = $icons[$name] ?? $icons['logout_default'];

    return '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function navLink(string $href, string $label, string $currentPage, string $icon = 'radio_button_unchecked'): void
{
    $activeClass = $currentPage === $href ? ' class="active"' : '';
    echo '<a href="' . e($href) . '"' . $activeClass . '>';
    echo iconMarkup($icon);
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

    // Determine if request is secure. Respect proxy headers and env overrides.
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (is_string($forwardedProto) && strtolower($forwardedProto) === 'https')
        || (getenv('FORCE_HTTPS') === '1');

    // Redirect to HTTPS when FORCE_HTTPS=1 and request is not secure.
    if (!$isSecure && (getenv('FORCE_HTTPS') === '1')) {
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $redirect = 'https://' . $host . $uri;
        header('Location: ' . $redirect, true, 301);
        exit;
    }

    // Send HSTS header when connection is secure. Allow disabling via ENABLE_HSTS=0.
    if ($isSecure && getenv('ENABLE_HSTS') !== '0') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' | RiskSecure Insurance</title>';
    echo '<link rel="stylesheet" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<a class="skip-link" href="#main-content">Skip to content</a>';
    echo '<div class="app-shell">';
    echo '<aside class="sidebar">';
    echo '<div class="brand">';
    echo '<img class="brand-logo" src="icon/649536819_912363384772670_6676616353184671990_n.jpg" alt="RiskSecure logo">';
    echo '<div class="brand-copy">';
    echo '<span class="brand-title">RiskSecure Insurance</span>';
    echo '<span class="brand-subtitle">Operations Workflow</span>';
    echo '</div>';
    echo '</div>';
    echo '<nav class="nav sidebar-nav" id="primary-navigation" aria-label="Primary navigation">';

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
    echo '</aside>';
    echo '<div class="app-content">';
    echo '<button class="sidebar-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation menu">';
    echo '<span class="sidebar-toggle-lines" aria-hidden="true"><span></span><span></span><span></span></span>';
    echo '<span class="sidebar-toggle-text">Menu</span>';
    echo '</button>';
    echo '<div class="sidebar-backdrop" hidden></div>';
    echo '<main class="container" id="main-content" tabindex="-1">';
    echo '<section class="page-banner">';
    echo '<p class="page-banner-kicker">' . iconMarkup('workspace_premium') . ' RiskSecure Operations Console</p>';
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
    echo '</div>';
    echo '</div>';
    echo '<div class="footer">Sample educational system for life/non-life insurance operations.</div>';
    echo '<script src="assets/js/sidebar.js"></script>';
    echo '<script src="assets/js/form-validate.js"></script>';
    echo '</body>';
    echo '</html>';
}
