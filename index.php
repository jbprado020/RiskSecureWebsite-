<?php

declare(strict_types=1);

require_once __DIR__ . '/shared/includes/auth.php';

ensureSessionStarted();

if (isStaffLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

if (isCustomerLoggedIn()) {
    header('Location: customer/dashboard.php');
    exit;
}

// If no session, default to customer login or a landing page.
// Given this is an insurance site, customer login is a good default.
header('Location: customer/auth/login.php');
exit;
