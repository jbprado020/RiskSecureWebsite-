<?php

declare(strict_types=1);

require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/includes/layout.php';
require_once __DIR__ . '/../shared/includes/auth.php';
require_once __DIR__ . '/security/gatekeeper.php';
require_once __DIR__ . '/../shared/includes/db_helpers.php';
require_once __DIR__ . '/../shared/includes/validation.php';

requireStaffLogin();

$title = 'Staff Profile Settings';
$name = staffName();
$email = staffEmail();
$pdo = db();

$message = '';
$error = '';

// Password update logic could be added here similar to staff_management.php

renderHeader($title);
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1><?= e($title); ?></h1>
        <p>Manage your staff account credentials and security preferences.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="notice ok"><?= e($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <?php renderNotice($error, 'error'); ?>
<?php endif; ?>

<section class="grid cols-2">
    <article class="card" id="credentials">
        <h2><?= iconMarkup('manage_accounts'); ?> Account Information</h2>
        <form action="profile.php" method="post" class="grid cols-2 profile-form">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-field">
                <input type="text" name="full_name" id="full_name_profile" value="<?= e($name); ?>" readonly disabled placeholder=" ">
                <label for="full_name_profile">Full Name</label>
                <small>Contact your administrator to change your name.</small>
            </div>
            <div class="form-field">
                <input type="email" name="email" id="email_profile" value="<?= e($email); ?>" readonly disabled placeholder=" ">
                <label for="email_profile">Email Address</label>
                <small>Contact your administrator to change your email.</small>
            </div>
            <div style="grid-column: 1 / -1;">
                <p class="notice info">Staff identity is managed by system administrators.</p>
            </div>
        </form>
    </article>

    <article class="card" id="security">
        <h2><?= iconMarkup('admin_panel_settings'); ?> Account Security</h2>
        <form action="#" method="post" class="grid" data-validate="true">
            <div class="form-field">
                <input type="password" name="current_password" id="current_password_security" required placeholder=" ">
                <label for="current_password_security">Current Password</label>
            </div>
            <div class="form-field">
                <input type="password" name="new_password" id="new_password_security" required placeholder=" ">
                <label for="new_password_security">New Password</label>
            </div>
            <div class="form-field">
                <input type="password" name="confirm_password" id="confirm_password_security" required placeholder=" ">
                <label for="confirm_password_security">Confirm New Password</label>
            </div>
            <div>
                <button type="submit">Change Password</button>
            </div>
        </form>
    </article>
</section>

<?php
renderFooter();
