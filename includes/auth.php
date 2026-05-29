<?php

declare(strict_types=1);

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        // Determine if the request is secure. Respect common proxy headers
        // and allow an override via FORCE_HTTPS environment variable.
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (is_string($forwardedProto) && strtolower($forwardedProto) === 'https')
            || (getenv('FORCE_HTTPS') === '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => getenv('SESSION_SAMESITE') ?: 'Lax',
        ]);

        session_start();
    }
}

function csrfToken(): string
{
    ensureSessionStarted();

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function requireCsrfToken(): void
{
    ensureSessionStarted();

    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    $given = (string) ($_POST['csrf_token'] ?? '');

    if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function isCustomerLoggedIn(): bool
{
    ensureSessionStarted();
    return isset($_SESSION['customer_client_id'], $_SESSION['customer_email']);
}

function requireCustomerLogin(): void
{
    ensureSessionStarted();

    if (!isCustomerLoggedIn()) {
        header('Location: customer_login.php');
        exit;
    }
}

function customerClientId(): int
{
    ensureSessionStarted();
    return (int) ($_SESSION['customer_client_id'] ?? 0);
}

function customerEmail(): string
{
    ensureSessionStarted();
    return (string) ($_SESSION['customer_email'] ?? '');
}

function customerName(): string
{
    ensureSessionStarted();
    return (string) ($_SESSION['customer_name'] ?? '');
}

function isStaffLoggedIn(): bool
{
    ensureSessionStarted();
    return isset($_SESSION['staff_id'], $_SESSION['staff_email'], $_SESSION['staff_role']);
}

function requireStaffLogin(): void
{
    ensureSessionStarted();

    if (!isStaffLoggedIn()) {
        header('Location: staff_login.php');
        exit;
    }
}

function requireStaffRole(array $allowedRoles): void
{
    requireStaffLogin();

    $role = staffRole();

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        echo 'Forbidden: you do not have permission to access this page.';
        exit;
    }
}

function staffId(): int
{
    ensureSessionStarted();
    return (int) ($_SESSION['staff_id'] ?? 0);
}

function staffName(): string
{
    ensureSessionStarted();
    return (string) ($_SESSION['staff_name'] ?? '');
}

function staffEmail(): string
{
    ensureSessionStarted();
    return (string) ($_SESSION['staff_email'] ?? '');
}

function staffRole(): string
{
    ensureSessionStarted();
    return (string) ($_SESSION['staff_role'] ?? '');
}
