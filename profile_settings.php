<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_helpers.php';
require_once __DIR__ . '/includes/validation.php';

if (isStaffLoggedIn()) {
    $title = 'Staff Profile Settings';
    $isCustomer = false;
    $name = staffName();
    $email = staffEmail();
} elseif (isCustomerLoggedIn()) {
    $title = 'Accounts Center';
    $isCustomer = true;
    $pdo = db();
    $clientId = customerClientId();
    
    // Fetch full client details
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        header('Location: customer_logout.php');
        exit;
    }
    
    $name = $client['full_name'];
    $email = $client['email'];
    $phone = $client['phone'] ?? '';
    $address = $client['address'] ?? '';
    $dob = $client['date_of_birth'] ?? '';
} else {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if ($isCustomer) {
        $newName = trim((string) ($_POST['full_name'] ?? ''));
        $newEmail = trim((string) ($_POST['email'] ?? ''));
        $newPhone = trim((string) ($_POST['phone'] ?? ''));
        $newAddress = trim((string) ($_POST['address'] ?? ''));
        
        if ($newName !== '' && isValidEmail($newEmail)) {
            try {
                $stmt = $pdo->prepare('UPDATE clients SET full_name = :name, email = :email, phone = :phone, address = :address WHERE id = :id');
                $stmt->execute([
                    ':name' => $newName,
                    ':email' => $newEmail,
                    ':phone' => $newPhone,
                    ':address' => $newAddress,
                    ':id' => $clientId
                ]);
                $_SESSION['customer_name'] = $newName;
                $_SESSION['customer_email'] = $newEmail;
                $message = 'Profile updated successfully.';
                
                // Refresh local variables
                $name = $newName;
                $email = $newEmail;
                $phone = $newPhone;
                $address = $newAddress;
            } catch (Throwable $e) {
                $error = 'Failed to update profile. Email might be in use.';
            }
        } else {
            $error = 'Invalid name or email.';
        }
    }
}

renderHeader($title);
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1><?= e($title); ?></h1>
        <p>Manage your account credentials, contact information, and security preferences.</p>
    </div>
</div>

<?php if ($isCustomer): ?>
<div class="profile-progress-container">
    <div class="profile-progress-header">
        <h3>Profile Completion</h3>
        <span class="progress-percentage" id="progress-pct">0%</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" id="progress-bar"></div>
    </div>
</div>
<?php endif; ?>

<?php if ($message !== ''): ?>
    <div class="notice ok"><?= e($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <?php renderNotice($error, 'error'); ?>
<?php endif; ?>

<section class="grid <?= $isCustomer ? 'cols-1' : 'cols-2'; ?>">
    <article class="card" id="credentials">
        <h2><?= iconMarkup('manage_accounts'); ?> <?= $isCustomer ? 'Account Information' : 'Edit Credentials'; ?></h2>
        <form action="profile_settings.php" method="post" class="grid cols-2 profile-form" data-validate="true">
            <input type="hidden" name="update_profile" value="1">
            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= e($name); ?>" required data-progress="true">
            </div>
            <div>
                <label>Email Address</label>
                <input type="email" name="email" value="<?= e($email); ?>" required data-progress="true">
            </div>
            <?php if ($isCustomer): ?>
            <div>
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= e($phone); ?>" placeholder="+63 9xx xxx xxxx" data-progress="true">
            </div>
            <div>
                <label>Mailing Address</label>
                <input type="text" name="address" value="<?= e($address); ?>" placeholder="House No., Street, City" data-progress="true">
            </div>
            <?php endif; ?>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Update Information</button>
            </div>
        </form>
    </article>

    <?php if (!$isCustomer): ?>
    <article class="card" id="security">
        <h2><?= iconMarkup('admin_panel_settings'); ?> Account Security</h2>
        <form action="#" method="post" class="grid" data-validate="true">
            <div>
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div>
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div>
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div>
                <button type="submit">Change Password</button>
            </div>
        </form>
    </article>
    <?php endif; ?>
</section>

<?php if ($isCustomer): ?>
<section class="card" id="security">
    <h2><?= iconMarkup('admin_panel_settings'); ?> Account Security</h2>
    <form action="#" method="post" class="grid cols-3" data-validate="true">
        <div>
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div>
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        <div>
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit" class="btn-secondary">Change Password</button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[data-progress="true"]');
    const progressBar = document.getElementById('progress-bar');
    const progressPct = document.getElementById('progress-pct');

    function updateProgress() {
        let filled = 0;
        inputs.forEach(input => {
            if (input.value.trim() !== '') {
                filled++;
            }
        });
        const percentage = Math.round((filled / inputs.length) * 100);
        progressBar.style.width = percentage + '%';
        progressPct.textContent = percentage + '%';
    }

    inputs.forEach(input => {
        input.addEventListener('input', updateProgress);
    });

    updateProgress(); // Initial check
});
</script>
<?php endif; ?>

<?php
renderFooter();
