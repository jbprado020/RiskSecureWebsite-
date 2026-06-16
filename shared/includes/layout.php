<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Configure PHP error display based on environment. In production, hide errors from output.
$env = getenv('APP_ENV') ?: getenv('ENV') ?: '';
if (strtolower($env) === 'production' || getenv('FORCE_HTTPS') === '1') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Render an accessible notice/flash message.
 * Uses role="alert" and aria-live to ensure assistive tech announces it.
 */
function renderNotice(string $message, string $type = 'error'): void
{
    $class = 'notice';
    if ($type === 'ok') {
        $class .= ' ok';
    } elseif ($type === 'error') {
        $class .= ' error';
    }

    echo '<div role="alert" aria-live="assertive" class="' . $class . '" tabindex="-1">' . e($message) . '</div>';
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
        'notifications' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'logout_default' => '<path d="M10 5H4v14h6"></path><path d="M13 12H4"></path><path d="M16 9l4 3-4 3"></path>',
        'hub' => '<circle cx="12" cy="12" r="3"></circle><circle cx="12" cy="5" r="2"></circle><circle cx="12" cy="19" r="2"></circle><circle cx="5" cy="12" r="2"></circle><circle cx="19" cy="12" r="2"></circle>',
        'assignment' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>',
        'account_balance' => '<path d="M3 22h18M5 22V10M19 22V10M12 2v6M3 10h18M7 10v12M17 10v12M12 10v12"></path>',
        'analytics' => '<path d="M12 20V10M18 20V4M6 20v-4"></path>',
    ];

    $path = $icons[$name] ?? $icons['logout_default'];

    return '<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function navCategory(string $label, string $icon): void
{
    echo '<div class="nav-category">';
    echo iconMarkup($icon);
    echo '<span>' . e($label) . '</span>';
    echo '</div>';
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

function getRelativePath(): string
{
    // Get the path of the current script relative to the document root
    $currentPath = $_SERVER['SCRIPT_NAME'];
    
    // We want to find the project root. 
    // If the script is in /admin/dashboard.php, we are 1 level deep from project root.
    // If the script is in /admin/auth/login.php, we are 2 levels deep.
    
    // This is a bit tricky if the project is in a subdirectory of the domain.
    // But we can assume the structure is:
    // [ProjectRoot]/admin/*.php
    // [ProjectRoot]/admin/auth/*.php
    // [ProjectRoot]/shared/includes/layout.php
    
    // Let's use a simpler heuristic for this specific project structure:
    if (strpos($currentPath, '/auth/') !== false) {
        return '../../';
    }
    
    return '../';
}

function renderHeader(string $title, bool $showBanner = true, string $containerClass = 'container'): void
{
    ensureSessionStarted();
    $currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
    $relPath = getRelativePath();

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

    // Common security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' | RiskSecure Insurance</title>';
    echo '<link rel="stylesheet" href="' . $relPath . 'shared/styling/styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<a class="skip-link" href="#main-content">Skip to content</a>';
    echo '<div class="app-shell">';
    
    // Main Navigation Drawer (hidden by default)
    echo '<aside class="nav-drawer" id="primary-navigation">';
    echo '<div class="drawer-header">';
    echo '<div class="top-bar-brand">';
    echo '<img class="brand-logo" src="' . $relPath . 'shared/assets/icon/649536819_912363384772670_6676616353184671990_n.jpg" alt="RiskSecure logo">';
    echo '<span class="brand-title">RiskSecure Insurance</span>';
    echo '</div>';
    echo '</div>';
    echo '<nav class="nav drawer-nav" aria-label="Primary navigation">';

    if (isStaffLoggedIn()) {
        navLink($relPath . 'admin/dashboard.php', 'Dashboard', $currentPage, 'dashboard');

        if (canAccess(['admin', 'manager', 'underwriter'])) {
            navCategory('Operations', 'hub');
            navLink($relPath . 'admin/clients.php', 'Clients', $currentPage, 'group');
            navLink($relPath . 'admin/quotes.php', 'Quotes', $currentPage, 'request_quote');
            navLink($relPath . 'admin/policies.php', 'Policies', $currentPage, 'policy');
            navLink($relPath . 'admin/renewals.php', 'Renewals', $currentPage, 'cycle');
        }

        if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer'])) {
            navCategory('Cases', 'assignment');
            navLink($relPath . 'admin/claims.php', 'Claims', $currentPage, 'gavel');
            navLink($relPath . 'admin/documents.php', 'Documents', $currentPage, 'folder_open');
            navLink($relPath . 'admin/meetings.php', 'Meetings', $currentPage, 'event');
        }

        if (canAccess(['admin', 'manager', 'billing_officer'])) {
            navCategory('Finance', 'account_balance');
            navLink($relPath . 'admin/payments.php', 'Payments', $currentPage, 'payments');
        }

        if (canAccess(['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer'])) {
            navCategory('Analytics', 'analytics');
            navLink($relPath . 'admin/reports.php', 'Reports', $currentPage, 'monitoring');
        }

        if (canAccess(['admin', 'manager'])) {
            navCategory('Relationships', 'business');
            navLink($relPath . 'admin/insurance_partners.php', 'Partners', $currentPage, 'business');
        }

        if (canAccess(['admin'])) {
            navCategory('System', 'admin_panel_settings');
            navLink($relPath . 'admin/staff_management.php', 'Staff Mgmt', $currentPage, 'manage_accounts');
        }
    } elseif (isCustomerLoggedIn()) {
        navLink($relPath . 'customer/dashboard.php', 'Client Dashboard', $currentPage, 'dashboard');
        navLink($relPath . 'customer/payments.php', 'Payments & Billing', $currentPage, 'payments');
        navLink($relPath . 'customer/support.php', 'Contact Support', $currentPage, 'notifications');
    } else {
        navLink($relPath . 'admin/auth/login.php', 'Staff Login', $currentPage, 'admin_panel_settings');
        navLink($relPath . 'customer/auth/login.php', 'Customer Login', $currentPage, 'login');
        navLink($relPath . 'customer/auth/register.php', 'Customer Register', $currentPage, 'person_add');
    }

    echo '</nav>';

    if (isStaffLoggedIn() || isCustomerLoggedIn()) {
        echo '<div class="drawer-footer">';
        if (isStaffLoggedIn()) {
            navLink($relPath . 'admin/auth/logout.php', 'Staff Logout', $currentPage, 'logout');
        } else {
            navLink($relPath . 'customer/auth/logout.php', 'Logout', $currentPage, 'logout');
        }
        echo '</div>';
    }

    echo '</aside>';
    echo '<div class="sidebar-backdrop" hidden></div>';

    echo '<div class="app-content">';

    $displayName = '';
    if (isStaffLoggedIn()) {
        $displayName = staffName() !== '' ? staffName() : staffEmail();
    } elseif (isCustomerLoggedIn()) {
        $displayName = customerName() !== '' ? customerName() : customerEmail();
    }

    echo '<header class="top-bar">';
    echo '<div class="container top-bar-inner">';
    
    echo '<div class="top-bar-left">';
    echo '<button class="sidebar-toggle" type="button" aria-label="Open menu" aria-controls="primary-navigation" aria-expanded="false">';
    echo '<div class="sidebar-toggle-lines"><span></span><span></span><span></span></div>';
    echo '</button>';
    echo '<div class="top-bar-brand top-bar-brand-main">';
    echo '<img class="brand-logo" src="' . $relPath . 'shared/assets/icon/649536819_912363384772670_6676616353184671990_n.jpg" alt="RiskSecure logo">';
    echo '<span class="brand-title">RiskSecure Insurance</span>';
    echo '</div>';
    echo '</div>';

    echo '<div class="top-bar-actions">';
    
    if (isStaffLoggedIn() || isCustomerLoggedIn()) {
        echo '<div class="dropdown top-bar-item" id="notif-dropdown">';
        echo '<button class="top-bar-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">';
        echo iconMarkup('notifications');
        echo '<span class="badge-dot"></span>';
        echo '</button>';
        echo '<div class="dropdown-menu" hidden>';
        echo '<div class="dropdown-header">Notifications</div>';
        echo '<div class="dropdown-item">Welcome to the new portal!</div>';
        echo '<div class="dropdown-item">Your policy was updated.</div>';
        echo '<hr class="dropdown-divider">';
        echo '<a href="#" class="dropdown-item">View All Notifications</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="dropdown top-bar-item" id="profile-dropdown">';
        echo '<button class="top-bar-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="User Profile">';
        echo iconMarkup('person');
        echo '</button>';
        echo '<div class="dropdown-menu" hidden>';
        echo '<div class="dropdown-header">' . e($displayName) . '</div>';
        if (isCustomerLoggedIn()) {
            echo '<div class="dropdown-section-label">Account & Security</div>';
            echo '<a href="' . $relPath . 'customer/profile.php" class="dropdown-item">' . iconMarkup('manage_accounts') . '<span>Manage Profile</span></a>';
            echo '<a href="' . $relPath . 'customer/profile.php#security" class="dropdown-item">' . iconMarkup('admin_panel_settings') . '<span>Login & Security</span></a>';
            echo '<a href="' . $relPath . 'customer/profile.php#privacy" class="dropdown-item">' . iconMarkup('policy') . '<span>Privacy Settings</span></a>';
            
            echo '<hr class="dropdown-divider">';
            echo '<div class="dropdown-section-label">Policies & Coverage</div>';
            echo '<a href="' . $relPath . 'customer/dashboard.php#policies" class="dropdown-item">' . iconMarkup('policy') . '<span>My Policies</span></a>';
            echo '<a href="' . $relPath . 'customer/dashboard.php#documents" class="dropdown-item">' . iconMarkup('folder_open') . '<span>ID Cards & Documents</span></a>';
            echo '<a href="' . $relPath . 'customer/dashboard.php#certificates" class="dropdown-item">' . iconMarkup('workspace_premium') . '<span>Certificates of Insurance</span></a>';

            echo '<hr class="dropdown-divider">';
            echo '<div class="dropdown-section-label">Claims Center</div>';
            echo '<a href="' . $relPath . 'customer/dashboard.php#file-claim" class="dropdown-item">' . iconMarkup('gavel') . '<span>File a Claim</span></a>';
            echo '<a href="' . $relPath . 'customer/dashboard.php#claims" class="dropdown-item">' . iconMarkup('assignment') . '<span>Claim History</span></a>';

            echo '<hr class="dropdown-divider">';
            echo '<div class="dropdown-section-label">Billing & Payments</div>';
            echo '<a href="' . $relPath . 'customer/payments.php#methods" class="dropdown-item">' . iconMarkup('payments') . '<span>Payment Methods</span></a>';
            echo '<a href="' . $relPath . 'customer/payments.php#history" class="dropdown-item">' . iconMarkup('account_balance') . '<span>Billing History</span></a>';
            echo '<a href="' . $relPath . 'customer/payments.php#autopay" class="dropdown-item">' . iconMarkup('cycle') . '<span>Auto-Pay Settings</span></a>';

            echo '<hr class="dropdown-divider">';
            echo '<div class="dropdown-section-label">Support & Company Info</div>';
            echo '<a href="' . $relPath . 'customer/support.php#agent" class="dropdown-item">' . iconMarkup('group') . '<span>Contact My Agent</span></a>';
            echo '<a href="' . $relPath . 'customer/support.php#faq" class="dropdown-item">' . iconMarkup('notifications') . '<span>Help Center / FAQ</span></a>';
        } else {
            echo '<a href="' . $relPath . 'admin/profile.php" class="dropdown-item">' . iconMarkup('manage_accounts') . '<span>Edit Credentials</span></a>';
            echo '<a href="' . $relPath . 'admin/profile.php#contact" class="dropdown-item">' . iconMarkup('notifications') . '<span>Contact Information</span></a>';
            echo '<a href="' . $relPath . 'admin/profile.php#security" class="dropdown-item">' . iconMarkup('admin_panel_settings') . '<span>Account Security</span></a>';
        }
        echo '<hr class="dropdown-divider">';
        if (isStaffLoggedIn()) {
            echo '<a href="' . $relPath . 'admin/auth/logout.php" class="dropdown-item text-danger">' . iconMarkup('logout') . '<span>Logout</span></a>';
        } elseif (isCustomerLoggedIn()) {
            echo '<a href="' . $relPath . 'customer/auth/logout.php" class="dropdown-item text-danger">' . iconMarkup('logout') . '<span>Logout</span></a>';
        } else {
            echo '<a href="' . $relPath . 'customer/auth/login.php" class="dropdown-item">' . iconMarkup('login') . '<span>Login</span></a>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</header>';

    echo '<div class="sidebar-backdrop" hidden></div>';
    echo '<main class="' . e($containerClass) . '" id="main-content" tabindex="-1">';
}

function renderFooter(): void
{
    $relPath = getRelativePath();
    echo '</main>';
    echo '</div>';
    echo '</div>';
    echo '<div class="footer">Sample educational system for life/non-life insurance operations.</div>';
    echo '<script src="' . $relPath . 'shared/assets/js/sidebar.js"></script>';
    echo '<script src="' . $relPath . 'shared/assets/js/form-validate.js"></script>';
    echo '<script src="' . $relPath . 'shared/assets/js/admin-forms.js"></script>';
    echo '</body>';
    echo '</html>';
}
