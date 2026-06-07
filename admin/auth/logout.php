<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit_helpers.php';

ensureSessionStarted();

$pdo = db();
logAuditEvent($pdo, 'staff_logout', [
	'entity_type' => 'session',
	'status' => 'success',
	'details' => 'Staff logged out.',
]);

session_regenerate_id(true);

if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(
		session_name(),
		'',
		time() - 42000,
		$params['path'],
		$params['domain'],
		$params['secure'],
		$params['httponly']
	);
}

unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_email'], $_SESSION['staff_role']);

session_destroy();

header('Location: staff_login.php');
exit;
