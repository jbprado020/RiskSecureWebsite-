<?php

declare(strict_types=1);

require_once __DIR__ . '/../../shared/config/db.php';
require_once __DIR__ . '/../../shared/includes/layout.php';
require_once __DIR__ . '/../../shared/includes/auth.php';
require_once __DIR__ . '/../../shared/includes/rate_limit_helpers.php';
require_once __DIR__ . '/../../shared/includes/audit_helpers.php';

ensureSessionStarted();

if (isCustomerLoggedIn()) {
    header('Location: ../dashboard.php');
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
        $rateCheck = checkLoginRateLimit($pdo, 'customer', $email);
        if (!$rateCheck['allowed']) {
            logAuditEvent($pdo, 'customer_login', [
                'actor_type' => 'customer',
                'actor_email' => $email,
                'status' => 'failure',
                'entity_type' => 'login',
                'details' => $rateCheck['reason'] ?? 'Login blocked by rate limit.',
            ]);
            $error = $rateCheck['reason'] ?? 'Too many failed attempts. Please try again later.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT c.id, c.full_name, c.email, ca.password_hash
                 FROM clients c
                 INNER JOIN customer_accounts ca ON ca.client_id = c.id
                 WHERE c.email = :email
                 LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch();

            if (!$account || !password_verify($password, (string) $account['password_hash'])) {
                recordLoginAttempt($pdo, 'customer', $email, false);
                logAuditEvent($pdo, 'customer_login', [
                    'actor_type' => 'customer',
                    'actor_email' => $email,
                    'status' => 'failure',
                    'entity_type' => 'login',
                    'details' => 'Invalid customer credentials.',
                ]);
                $error = 'Invalid login credentials.';
            } else {
                recordLoginAttempt($pdo, 'customer', $email, true);
                session_regenerate_id(true);
                $_SESSION['customer_client_id'] = (int) $account['id'];
                $_SESSION['customer_email'] = (string) $account['email'];
                $_SESSION['customer_name'] = (string) $account['full_name'];
                logAuditEvent($pdo, 'customer_login', [
                    'actor_type' => 'customer',
                    'actor_id' => (int) $account['id'],
                    'actor_name' => (string) $account['full_name'],
                    'actor_email' => (string) $account['email'],
                    'status' => 'success',
                    'entity_type' => 'login',
                    'details' => 'Customer login successful.',
                ]);

                header('Location: ../dashboard.php');
                exit;
            }
        }
    }
}

renderHeader('Customer Login', true, 'container auth-main');
?>

<section class="card login-form-container" style="max-width: 560px; margin: 0 auto 1rem;">
    <h2>Customer Login</h2>
    <p>Sign in to submit applications, file claims, and track your account.</p>

    <?php if ($error !== ''): ?>
        <?php renderNotice($error, 'error'); ?>
    <?php endif; ?>

    <form method="post" class="grid" data-validate="true">
        <?= csrfField(); ?>
        <div class="form-field">
            <input type="email" name="email" id="email" required autocomplete="email" placeholder=" ">
            <label for="email">Email Address</label>
        </div>
        <div class="form-field">
            <input type="password" name="password" id="password" required autocomplete="current-password" placeholder=" ">
            <label for="password">Password</label>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
    <p style="margin-top: 1rem; text-align: center;">No account yet? <a href="register.php">Register here</a>.</p>
</section>

<?php
renderFooter();
