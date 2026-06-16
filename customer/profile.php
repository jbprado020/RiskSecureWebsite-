<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/includes/db_helpers.php';
require_once __DIR__ . '/../shared/includes/validation.php';

requireCustomerLogin();

$title = 'Accounts Center';
$pdo = db();
$clientId = customerClientId();

// Fetch full client details
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: auth/logout.php');
    exit;
}

$name = $client['full_name'];
$email = $client['email'];
$phone = $client['phone'] ?? '';
$address = $client['address'] ?? '';
$dob = $client['date_of_birth'] ?? '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
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

renderHeader($title);
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1><?= e($title); ?></h1>
        <p>Manage your account credentials, contact information, and security preferences.</p>
    </div>
</div>

<div class="profile-progress-container">
    <div class="profile-progress-header">
        <h3>Profile Completion</h3>
        <span class="progress-percentage" id="progress-pct">0%</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" id="progress-bar"></div>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="notice ok"><?= e($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <?php renderNotice($error, 'error'); ?>
<?php endif; ?>

<section class="card" id="credentials">
    <h2><?= iconMarkup('manage_accounts'); ?> Account Information</h2>
    <form action="profile.php" method="post" class="grid cols-2 profile-form" id="profileForm">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-field">
            <input type="text" name="full_name" id="full_name" value="<?= e($name); ?>" required data-progress="true" placeholder=" ">
            <label for="full_name">Full Name</label>
        </div>
        <div class="form-field">
            <input type="email" name="email" id="email" value="<?= e($email); ?>" required data-progress="true" placeholder=" ">
            <label for="email">Email Address</label>
        </div>
        <div class="form-field">
            <input type="tel" name="phone" id="phone" value="<?= e($phone); ?>" placeholder=" " data-progress="true">
            <label for="phone">Phone Number</label>
        </div>
        <div class="form-field">
            <input type="text" name="address" id="address" value="<?= e($address); ?>" placeholder=" " data-progress="true">
            <label for="address">Mailing Address</label>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Update Information</button>
        </div>
    </form>
</section>

<section class="card" id="security">
    <h2><?= iconMarkup('admin_panel_settings'); ?> Account Security</h2>
    <form action="#" method="post" class="grid cols-3" data-validate="true">
        <div class="form-field">
            <input type="password" name="current_password" id="current_password" required placeholder=" ">
            <label for="current_password">Current Password</label>
        </div>
        <div class="form-field">
            <input type="password" name="new_password" id="new_password" required placeholder=" ">
            <label for="new_password">New Password</label>
        </div>
        <div class="form-field">
            <input type="password" name="confirm_password" id="confirm_password" required placeholder=" ">
            <label for="confirm_password">Confirm New Password</label>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit" class="btn-secondary">Change Password</button>
        </div>
    </form>
</section>

<section class="card" id="privacy">
    <h2><?= iconMarkup('policy'); ?> Privacy Settings</h2>
    <div class="grid cols-2">
        <div class="info-group">
            <p><strong>Marketing Preferences</strong></p>
            <p class="text-muted">Control how we communicate with you about new products and offers.</p>
            <div style="margin-top: 1rem;">
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                    Email Notifications
                </label>
            </div>
            <div style="margin-top: 0.5rem;">
                <label class="toggle-switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                    SMS Alerts
                </label>
            </div>
        </div>
        <div class="info-group">
            <p><strong>Data Privacy</strong></p>
            <p class="text-muted">Manage your data sharing preferences and request data exports.</p>
            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="button" class="btn-action">Download My Data</button>
                <button type="button" class="btn-action text-danger">Request Deletion</button>
            </div>
        </div>
    </div>
</section>

<!-- Custom Confirmation Modal -->
<div id="confirmModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal">
        <div class="custom-modal-icon">
            <?= iconMarkup('manage_accounts'); ?>
        </div>
        <h3>Confirm Profile Update</h3>
        <p>Are you sure you want to update your account information? This will overwrite your current details.</p>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
            <button type="button" id="confirmBtn">Yes, Update</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[data-progress="true"]');
    const progressBar = document.getElementById('progress-bar');
    const progressPct = document.getElementById('progress-pct');
    const profileForm = document.getElementById('profileForm');
    const confirmModal = document.getElementById('confirmModal');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');

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

    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmModal.style.display = 'flex';
        });
    }

    confirmBtn.addEventListener('click', function() {
        profileForm.submit();
    });

    cancelBtn.addEventListener('click', function() {
        confirmModal.style.display = 'none';
    });

    // Close modal if clicking overlay
    confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) {
            confirmModal.style.display = 'none';
        }
    });

    inputs.forEach(input => {
        input.addEventListener('input', updateProgress);
    });

    updateProgress(); // Initial check
});
</script>

<?php
renderFooter();
