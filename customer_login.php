<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

ensureSessionStarted();

if (isCustomerLoggedIn()) {
    header('Location: customer_portal.php');
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
            $error = 'Invalid login credentials.';
        } else {
            session_regenerate_id(true);
            $_SESSION['customer_client_id'] = (int) $account['id'];
            $_SESSION['customer_email'] = (string) $account['email'];
            $_SESSION['customer_name'] = (string) $account['full_name'];

            header('Location: customer_portal.php');
            exit;
        }
    }
}

renderHeader('Customer Login');
?>

<section class="card" style="max-width: 560px; margin: 0 auto 1rem;">
    <h2>Customer Login</h2>
    <p>Sign in to submit applications, file claims, and track your account.</p>

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
            <button type="submit">Login</button>
        </div>
    </form>
    <p style="margin-top: 1rem;">No account yet? <a href="customer_register.php">Register here</a>.</p>
</section>

<?php
renderFooter();
