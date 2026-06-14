<?php
declare(strict_types=1);

require_once __DIR__ . '/../../shared/config/db.php';
require_once __DIR__ . '/../../shared/includes/layout.php';
require_once __DIR__ . '/../../shared/includes/auth.php';
require_once __DIR__ . '/../../shared/includes/validation.php';

ensureSessionStarted();

if (isCustomerLoggedIn()) {
    header('Location: ../dashboard.php');
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

    $passwordError = validatePasswordStrength($password);

    if (
        $fullName === '' ||
        !isValidEmail($email) ||
        $phone === '' ||
        $address === '' ||
        !isValidDate($birthDate) ||
        $passwordError !== null ||
        $password !== $confirmPassword
    ) {
        $error = $passwordError ?? 'Please complete all fields with valid values.';
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

                header('Location: ../dashboard.php');
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

renderHeader('Customer Register', true, 'container auth-main');
?>

<section class="card login-form-container" style="max-width: 760px; margin: 0 auto 1rem;">
    <h2 style="text-align: center;">Create Customer Account</h2>
    <p>Use this once, then use Customer Login for next visits.</p>

    <?php if ($error !== ''): ?>
        <?php renderNotice($error, 'error'); ?>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <div class="form-field">
            <input name="full_name" id="full_name" required autocomplete="name" placeholder=" ">
            <label for="full_name">Full Name</label>
        </div>
        <div class="form-field">
            <input type="email" name="email" id="email" required autocomplete="email" placeholder=" ">
            <label for="email">Email Address</label>
        </div>
        <div class="form-field">
            <input name="phone" id="phone" required autocomplete="tel" placeholder=" ">
            <label for="phone">Phone Number</label>
        </div>
        <div class="form-field">
            <input type="date" name="date_of_birth" id="date_of_birth" required autocomplete="bday" placeholder=" ">
            <label for="date_of_birth">Date of Birth</label>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
            <textarea name="address" id="address" required autocomplete="street-address" placeholder=" "></textarea>
            <label for="address">Residential Address</label>
        </div>
        <div class="form-field">
            <input type="password" name="password" id="password" minlength="12" required autocomplete="new-password" aria-label="Password" aria-required="true" placeholder=" ">
            <label for="password">Password</label>
        </div>
        <div class="form-field">
            <input type="password" name="confirm_password" id="confirm_password" minlength="12" required data-confirm-target="password" autocomplete="new-password" aria-label="Confirm Password" aria-required="true" placeholder=" ">
            <label for="confirm_password">Confirm Password</label>
        </div>
        <div style="grid-column: 1 / -1; text-align: center;">
            <button type="submit" class="btn-full">Register Account</button>
        </div>
    </form>
    <p style="margin-top: 1rem; text-align: center;">Already registered? <a href="login.php">Sign in here</a>.</p>
</section>

<?php
renderFooter();
