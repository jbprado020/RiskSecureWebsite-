<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit_helpers.php';
require_once __DIR__ . '/includes/audit_helpers.php';

ensureSessionStarted();

if (isStaffLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        // Check rate limiting before attempting login
        $rateCheck = checkLoginRateLimit($pdo, 'staff', $email);
        if (!$rateCheck['allowed']) {
            logAuditEvent($pdo, 'staff_login', [
                'actor_type' => 'staff',
                'actor_email' => $email,
                'status' => 'failure',
                'entity_type' => 'login',
                'details' => $rateCheck['reason'] ?? 'Login blocked by rate limit.',
            ]);
            $error = $rateCheck['reason'] ?? 'Too many failed attempts. Please try again later.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password_hash, role, is_active
                 FROM staff_accounts
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $staff = $stmt->fetch();

            if (!$staff || (int) $staff['is_active'] !== 1 || !password_verify($password, (string) $staff['password_hash'])) {
                recordLoginAttempt($pdo, 'staff', $email, false);
                logAuditEvent($pdo, 'staff_login', [
                    'actor_type' => 'staff',
                    'actor_email' => $email,
                    'status' => 'failure',
                    'entity_type' => 'login',
                    'details' => 'Invalid staff credentials.',
                ]);
                $error = 'Invalid staff credentials.';
            } else {
                recordLoginAttempt($pdo, 'staff', $email, true);
                session_regenerate_id(true);
                $_SESSION['staff_id'] = (int) $staff['id'];
                $_SESSION['staff_name'] = (string) $staff['full_name'];
                $_SESSION['staff_email'] = (string) $staff['email'];
                $_SESSION['staff_role'] = (string) $staff['role'];
                logAuditEvent($pdo, 'staff_login', [
                    'actor_type' => 'staff',
                    'actor_id' => (int) $staff['id'],
                    'actor_name' => (string) $staff['full_name'],
                    'actor_email' => (string) $staff['email'],
                    'actor_role' => (string) $staff['role'],
                    'status' => 'success',
                    'entity_type' => 'login',
                    'details' => 'Staff login successful.',
                ]);

                header('Location: index.php');
                exit;
            }
        }
    }
}

renderHeader('Staff Login');
?>

<section class="card" style="max-width: 560px; margin: 0 auto 1rem;">
    <h2>Staff Login</h2>
    <p>Use your staff account to access back-office operations.</p>

    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid">
        <?= csrfField(); ?>
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <button type="submit">Login as Staff</button>
        </div>
    </form>
</section>

<?php
renderFooter();
