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

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthDate = trim($_POST['date_of_birth'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (
        $fullName === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone === '' ||
        $address === '' ||
        $birthDate === '' ||
        strlen($password) < 8 ||
        $password !== $confirmPassword
    ) {
        $error = 'Please complete all fields. Password must be at least 8 characters and match confirmation.';
    } else {
        $pdo->beginTransaction();

        try {
            $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE email = :email LIMIT 1');
            $clientStmt->execute([':email' => $email]);
            $client = $clientStmt->fetch();

            if ($client) {
                $clientId = (int) $client['id'];
                $updateClientStmt = $pdo->prepare(
                    'UPDATE clients
                     SET full_name = :full_name, phone = :phone, address = :address, date_of_birth = :date_of_birth
                     WHERE id = :id'
                );
                $updateClientStmt->execute([
                    ':full_name' => $fullName,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':date_of_birth' => $birthDate,
                    ':id' => $clientId,
                ]);
            } else {
                $insertClientStmt = $pdo->prepare(
                    'INSERT INTO clients (full_name, email, phone, address, date_of_birth)
                     VALUES (:full_name, :email, :phone, :address, :date_of_birth)'
                );
                $insertClientStmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':date_of_birth' => $birthDate,
                ]);
                $clientId = (int) $pdo->lastInsertId();
            }

            $accountStmt = $pdo->prepare('SELECT id FROM customer_accounts WHERE client_id = :client_id LIMIT 1');
            $accountStmt->execute([':client_id' => $clientId]);
            $account = $accountStmt->fetch();

            if ($account) {
                $error = 'An account already exists for this email. Please log in instead.';
                $pdo->rollBack();
            } else {
                $insertAccountStmt = $pdo->prepare(
                    'INSERT INTO customer_accounts (client_id, password_hash) VALUES (:client_id, :password_hash)'
                );
                $insertAccountStmt->execute([
                    ':client_id' => $clientId,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $pdo->commit();

                session_regenerate_id(true);
                $_SESSION['customer_client_id'] = $clientId;
                $_SESSION['customer_email'] = $email;
                $_SESSION['customer_name'] = $fullName;

                header('Location: customer_portal.php');
                exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Unable to create account right now. Please try again.';
        }
    }
}

renderHeader('Customer Register');
?>

<section class="card" style="max-width: 760px; margin: 0 auto 1rem;">
    <h2>Create Customer Account</h2>
    <p>Use this once, then use Customer Login for next visits.</p>

    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <div>
            <label>Full Name</label>
            <input name="full_name" required>
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Phone</label>
            <input name="phone" required>
        </div>
        <div>
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <label>Address</label>
            <textarea name="address" required></textarea>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" minlength="8" required aria-label="Password" aria-required="true">
        </div>
        <div>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" minlength="8" required data-confirm-target="password" aria-label="Confirm Password" aria-required="true">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Register Account</button>
        </div>
    </form>
    <p style="margin-top: 1rem;">Already registered? <a href="customer_login.php">Sign in here</a>.</p>
</section>

<?php
renderFooter();
