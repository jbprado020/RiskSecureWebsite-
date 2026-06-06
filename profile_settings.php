<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/auth.php';

if (isStaffLoggedIn()) {
    $title = 'Staff Profile Settings';
    $role = staffRole();
    $name = staffName();
    $email = staffEmail();
} elseif (isCustomerLoggedIn()) {
    $title = 'Customer Profile Settings';
    $role = 'Customer';
    $name = customerName();
    $email = customerEmail();
} else {
    header('Location: index.php');
    exit;
}

renderHeader($title);
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Profile Settings</h1>
        <p>Manage your account credentials, contact information, and security preferences.</p>
    </div>
</div>

<section class="grid cols-2">
    <article class="card" id="credentials">
        <h2><?= iconMarkup('manage_accounts'); ?> Edit Credentials</h2>
        <form action="#" method="post" class="grid" data-validate="true">
            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= e($name); ?>" required>
            </div>
            <div>
                <label>Email Address</label>
                <input type="email" name="email" value="<?= e($email); ?>" required>
            </div>
            <div>
                <button type="submit">Update Basic Info</button>
            </div>
        </form>
    </article>

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
</section>

<section class="card" id="contact">
    <h2><?= iconMarkup('notifications'); ?> Contact Information</h2>
    <form action="#" method="post" class="grid cols-2" data-validate="true">
        <div>
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="+63 9xx xxx xxxx">
        </div>
        <div>
            <label>Mailing Address</label>
            <input type="text" name="address" placeholder="House No., Street, City">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Save Contact Details</button>
        </div>
    </form>
</section>

<?php
renderFooter();
