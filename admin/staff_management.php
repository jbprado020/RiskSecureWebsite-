<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/audit_helpers.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/pagination.php';

requireStaffRole(['admin']);

$pdo = db();
$message = '';
$error = '';

// Helper function to get role display label
function roleLabel(string $role): string {
    $labels = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'underwriter' => 'Underwriter',
        'claims_officer' => 'Claims Officer',
        'billing_officer' => 'Billing Officer',
    ];
    return $labels[$role] ?? $role;
}

// Create new staff account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    requireCsrfToken();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'underwriter');
    $contact = trim((string) ($_POST['contact_number'] ?? ''));

    $validRoles = ['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer'];

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Full name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (($passwordError = validatePasswordStrength($password)) !== null) {
        $error = $passwordError;
    } elseif (!in_array($role, $validRoles, true)) {
        $error = 'Invalid role selected.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM staff_accounts WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $exists = (int) $stmt->fetchColumn();

            if ($exists > 0) {
                $error = 'Email already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'INSERT INTO staff_accounts (full_name, email, password_hash, role, contact_number, is_active)
                     VALUES (:full_name, :email, :password_hash, :role, :contact_number, :is_active)'
                );
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':password_hash' => $hashedPassword,
                    ':role' => $role,
                    ':contact_number' => $contact,
                    ':is_active' => 1,
                ]);
                logAuditEvent($pdo, 'create_staff', [
                    'entity_type' => 'staff_accounts',
                    'entity_id' => (int) $pdo->lastInsertId(),
                    'status' => 'success',
                    'details' => 'Created staff account for ' . $fullName . ' (' . $email . ').',
                ]);
                $message = "Staff account created for {$fullName}.";
            }
        } catch (Throwable $e) {
            $error = 'Failed to create staff account: ' . $e->getMessage();
        }
    }
}

// Update staff account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    requireCsrfToken();

    $staffId = (int) ($_POST['staff_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $role = (string) ($_POST['role'] ?? 'underwriter');
    $contact = trim((string) ($_POST['contact_number'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $validRoles = ['admin', 'manager', 'underwriter', 'claims_officer', 'billing_officer'];

    if ($staffId <= 0) {
        $error = 'Invalid staff member.';
    } elseif ($fullName === '' || $email === '') {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, $validRoles, true)) {
        $error = 'Invalid role selected.';
    } else {
        try {
            // Check if email is taken by another staff
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM staff_accounts WHERE email = :email AND id != :id LIMIT 1'
            );
            $stmt->execute([':email' => $email, ':id' => $staffId]);
            $exists = (int) $stmt->fetchColumn();

            if ($exists > 0) {
                $error = 'Email already registered by another staff member.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE staff_accounts 
                     SET full_name = :full_name, email = :email, role = :role, contact_number = :contact_number, is_active = :is_active
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':role' => $role,
                    ':contact_number' => $contact,
                    ':is_active' => $isActive,
                    ':id' => $staffId,
                ]);
                logAuditEvent($pdo, 'update_staff', [
                    'entity_type' => 'staff_accounts',
                    'entity_id' => $staffId,
                    'status' => 'success',
                    'details' => 'Updated staff account for ' . $fullName . ' (' . $email . ').',
                ]);
                $message = "Staff account updated.";
            }
        } catch (Throwable $e) {
            $error = 'Failed to update staff account: ' . $e->getMessage();
        }
    }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    requireCsrfToken();

    $staffId = (int) ($_POST['staff_id'] ?? 0);
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if ($staffId <= 0) {
        $error = 'Invalid staff member.';
    } elseif ($newPassword === '' || ($passwordError = validatePasswordStrength($newPassword)) !== null) {
        $error = $passwordError ?? 'Password is required.';
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE staff_accounts SET password_hash = :password WHERE id = :id');
            $stmt->execute([':password' => $hashedPassword, ':id' => $staffId]);
            logAuditEvent($pdo, 'reset_staff_password', [
                'entity_type' => 'staff_accounts',
                'entity_id' => $staffId,
                'status' => 'success',
                'details' => 'Reset password for staff account ID ' . $staffId . '.',
            ]);
            $message = 'Password reset successfully.';
        } catch (Throwable $e) {
            $error = 'Failed to reset password: ' . $e->getMessage();
        }
    }
}

// Delete staff account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    requireCsrfToken();

    $staffId = (int) ($_POST['staff_id'] ?? 0);

    if ($staffId <= 0) {
        $error = 'Invalid staff member.';
    } elseif ($staffId === staffId()) {
        $error = 'Cannot delete your own account.';
    } else {
        try {
            $pdo->prepare('DELETE FROM staff_accounts WHERE id = :id')->execute([':id' => $staffId]);
            logAuditEvent($pdo, 'delete_staff', [
                'entity_type' => 'staff_accounts',
                'entity_id' => $staffId,
                'status' => 'success',
                'details' => 'Deleted staff account ID ' . $staffId . '.',
            ]);
            $message = 'Staff account deleted.';
        } catch (Throwable $e) {
            $error = 'Failed to delete staff account: ' . $e->getMessage();
        }
    }
}

$staffP = paginatedQuery(
    $pdo,
    'SELECT COUNT(*) FROM staff_accounts',
    'SELECT id, full_name, email, role, is_active, contact_number, created_at
     FROM staff_accounts
     ORDER BY full_name',
    [],
    25,
    'staff_page'
);
$staff = $staffP['data'];

renderHeader('Staff Management');
?>

<div class="welcome-hero">
    <div class="hero-content">
        <h1>Staff Management</h1>
        <p>Manage internal user accounts, assign roles, and control system access.</p>
    </div>
</div>

<section class="card">
    <h2>Add Staff Account</h2>
    
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error !== ''): ?>
        <div class="notice error"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <input type="hidden" name="create_staff" value="1">
        
        <div>
            <label>Full Name</label>
            <input name="full_name" required placeholder="e.g. John Smith" autocomplete="name">
        </div>
        <div>
            <label>Email</label>
            <input name="email" type="email" required placeholder="staff@risksecure.local" autocomplete="email">
        </div>
        <div>
            <label>Password</label>
            <input name="password" type="password" required placeholder="Minimum 12 characters" autocomplete="new-password">
        </div>
        <div>
            <label>Role</label>
            <select name="role" required>
                <option value="underwriter">Underwriter</option>
                <option value="claims_officer">Claims Officer</option>
                <option value="billing_officer">Billing Officer</option>
                <option value="manager">Manager</option>
                <option value="admin">Administrator</option>
            </select>
        </div>
        <div style="grid-column: 1 / -1;">
            <label>Contact Number (Optional)</label>
            <input name="contact_number" type="tel" placeholder="e.g. +63-917-123-4567" autocomplete="tel">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Create Staff Account</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Staff Directory (<?= (int) $staffP['total']; ?> members)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff as $member): ?>
            <tr>
                <td><?= (int) $member['id']; ?></td>
                <td><?= e($member['full_name']); ?></td>
                <td><?= e($member['email']); ?></td>
                <td><?= e(roleLabel((string) $member['role'])); ?></td>
                <td><?= e((string) ($member['contact_number'] ?? '—')); ?></td>
                <td>
                    <span class="badge <?= (int) $member['is_active'] === 1 ? 'success' : 'danger'; ?>">
                        <?= (int) $member['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime((string) $member['created_at'])); ?></td>
                <td>
                    <button class="btn-small" type="button" data-toggle="edit-form" data-target="edit-form-<?= (int) $member['id']; ?>" aria-expanded="false" aria-controls="edit-form-<?= (int) $member['id']; ?>">Edit</button>
                </td>
            </tr>
            <tr id="edit-form-<?= (int) $member['id']; ?>" hidden aria-hidden="true">
                <td colspan="8">
                    <div class="panel-inline">
                        <h4>Edit Staff Member</h4>
                        <form method="post" class="grid cols-3" data-validate="true">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_staff" value="1">
                            <input type="hidden" name="staff_id" value="<?= (int) $member['id']; ?>">
                            
                            <div>
                                <label>Full Name</label>
                                <input name="full_name" required value="<?= e($member['full_name']); ?>" autocomplete="name">
                            </div>
                            <div>
                                <label>Email</label>
                                <input name="email" type="email" required value="<?= e($member['email']); ?>" autocomplete="email">
                            </div>
                            <div>
                                <label>Role</label>
                                <select name="role" required>
                                    <option value="underwriter" <?= $member['role'] === 'underwriter' ? 'selected' : ''; ?>>Underwriter</option>
                                    <option value="claims_officer" <?= $member['role'] === 'claims_officer' ? 'selected' : ''; ?>>Claims Officer</option>
                                    <option value="billing_officer" <?= $member['role'] === 'billing_officer' ? 'selected' : ''; ?>>Billing Officer</option>
                                    <option value="manager" <?= $member['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                            <div>
                                <label>Contact Number</label>
                                <input name="contact_number" type="tel" value="<?= e((string) ($member['contact_number'] ?? '')); ?>" autocomplete="tel">
                            </div>
                            <div class="checkbox-field">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_active" <?= (int) $member['is_active'] === 1 ? 'checked' : ''; ?>>
                                    Active
                                </label>
                            </div>
                            <div></div>
                            
                            <div class="form-actions">
                                <button type="submit">Save Changes</button>
                                <button type="button" data-toggle="edit-form" data-target="edit-form-<?= (int) $member['id']; ?>" aria-expanded="true" aria-controls="edit-form-<?= (int) $member['id']; ?>" class="btn-secondary">Cancel</button>
                            </div>
                        </form>

                        <hr class="divider">
                        
                        <h4>Reset Password</h4>
                        <form method="post" class="grid cols-2" data-validate="true">
                            <?= csrfField(); ?>
                            <input type="hidden" name="reset_password" value="1">
                            <input type="hidden" name="staff_id" value="<?= (int) $member['id']; ?>">
                            
                            <div>
                                <label>New Password</label>
                                <input name="new_password" type="password" required placeholder="Minimum 12 characters" autocomplete="new-password">
                            </div>
                            <div class="form-actions align-end">
                                <button type="submit" class="btn-secondary">Reset Password</button>
                            </div>
                        </form>

                        <hr class="divider">
                        
                        <h4 class="text-danger">Delete Account</h4>
                        <form method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                            <?= csrfField(); ?>
                            <input type="hidden" name="delete_staff" value="1">
                            <input type="hidden" name="staff_id" value="<?= (int) $member['id']; ?>">
                            <button type="submit" class="btn-danger">Delete Staff Account</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= renderPagination($staffP); ?>
</section>

<style>
    .btn-small {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 0.3rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .btn-small:hover {
        background: var(--primary-2);
    }
    
    .btn-secondary {
        background: var(--muted);
        color: white;
    }
    
    .btn-secondary:hover {
        background: var(--ink);
    }
    
    .btn-danger {
        background: var(--danger);
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.3rem;
        cursor: pointer;
    }
    
    .btn-danger:hover {
        opacity: 0.8;
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .badge.success {
        background: rgba(27, 139, 82, 0.2);
        color: var(--success);
    }
    
    .badge.danger {
        background: rgba(173, 47, 61, 0.2);
        color: var(--danger);
    }
    
    .notice {
        padding: 0.8rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-weight: 500;
    }
    
    .notice.ok {
        background: rgba(27, 139, 82, 0.2);
        color: var(--success);
        border-left: 4px solid var(--success);
    }
    
    .notice.error {
        background: rgba(173, 47, 61, 0.2);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }
    
    hr {
        border: none;
        border-top: 1px solid var(--border);
    }
