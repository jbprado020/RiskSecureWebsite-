<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager']);

$pdo = db();
$message = '';
$error = '';

// Helper function to get insurance type label
function insuranceTypeLabel(string $type): string {
    $labels = [
        'life' => 'Life Insurance',
        'non-life' => 'Non-Life Insurance',
        'both' => 'Life & Non-Life',
    ];
    return $labels[$type] ?? $type;
}

// Create new insurance partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_partner'])) {
    requireCsrfToken();

    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $insuranceType = (string) ($_POST['insurance_type'] ?? 'both');
    $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
    $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));

    $validTypes = ['life', 'non-life', 'both'];

    if ($companyName === '' || $contactPerson === '' || $contactEmail === '') {
        $error = 'Company name, contact person, and email are required.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($insuranceType, $validTypes, true)) {
        $error = 'Invalid insurance type selected.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM insurance_partners WHERE contact_email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $contactEmail]);
            $exists = (int) $stmt->fetchColumn();

            if ($exists > 0) {
                $error = 'Contact email already registered.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO insurance_partners (company_name, insurance_type, contact_person, contact_email)
                     VALUES (:company_name, :insurance_type, :contact_person, :contact_email)'
                );
                $stmt->execute([
                    ':company_name' => $companyName,
                    ':insurance_type' => $insuranceType,
                    ':contact_person' => $contactPerson,
                    ':contact_email' => $contactEmail,
                ]);
                $message = "Insurance partner '{$companyName}' created successfully.";
            }
        } catch (Throwable $e) {
            $error = 'Failed to create partner: ' . $e->getMessage();
        }
    }
}

// Update insurance partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_partner'])) {
    requireCsrfToken();

    $partnerId = (int) ($_POST['partner_id'] ?? 0);
    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $insuranceType = (string) ($_POST['insurance_type'] ?? 'both');
    $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
    $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));

    $validTypes = ['life', 'non-life', 'both'];

    if ($partnerId <= 0) {
        $error = 'Invalid partner.';
    } elseif ($companyName === '' || $contactPerson === '' || $contactEmail === '') {
        $error = 'Company name, contact person, and email are required.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($insuranceType, $validTypes, true)) {
        $error = 'Invalid insurance type selected.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM insurance_partners WHERE contact_email = :email AND id != :id LIMIT 1'
            );
            $stmt->execute([':email' => $contactEmail, ':id' => $partnerId]);
            $exists = (int) $stmt->fetchColumn();

            if ($exists > 0) {
                $error = 'Contact email already registered by another partner.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE insurance_partners 
                     SET company_name = :company_name, insurance_type = :insurance_type, 
                         contact_person = :contact_person, contact_email = :contact_email
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':company_name' => $companyName,
                    ':insurance_type' => $insuranceType,
                    ':contact_person' => $contactPerson,
                    ':contact_email' => $contactEmail,
                    ':id' => $partnerId,
                ]);
                $message = 'Insurance partner updated successfully.';
            }
        } catch (Throwable $e) {
            $error = 'Failed to update partner: ' . $e->getMessage();
        }
    }
}

// Delete insurance partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_partner'])) {
    requireCsrfToken();

    $partnerId = (int) ($_POST['partner_id'] ?? 0);

    if ($partnerId <= 0) {
        $error = 'Invalid partner.';
    } else {
        try {
            // Check if partner has active policies
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM policies WHERE partner_id = :id AND status = "active" LIMIT 1'
            );
            $stmt->execute([':id' => $partnerId]);
            $activePolicies = (int) $stmt->fetchColumn();

            if ($activePolicies > 0) {
                $error = "Cannot delete partner with {$activePolicies} active policy/policies. Deactivate policies first.";
            } else {
                $pdo->prepare('DELETE FROM insurance_partners WHERE id = :id')->execute([':id' => $partnerId]);
                $message = 'Insurance partner deleted successfully.';
            }
        } catch (Throwable $e) {
            $error = 'Failed to delete partner: ' . $e->getMessage();
        }
    }
}

$partners = $pdo->query(
    'SELECT id, company_name, insurance_type, contact_person, contact_email, created_at,
            (SELECT COUNT(*) FROM policies WHERE partner_id = insurance_partners.id) as policy_count,
            (SELECT COUNT(*) FROM policies WHERE partner_id = insurance_partners.id AND status = "active") as active_count
     FROM insurance_partners
     ORDER BY company_name'
)->fetchAll();

renderHeader('Insurance Partner Management');
?>

<section class="card">
    <h2>Add Insurance Partner</h2>
    
    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error !== ''): ?>
        <div class="notice error"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid cols-2">
        <?= csrfField(); ?>
        <input type="hidden" name="create_partner" value="1">
        
        <div>
            <label>Company Name</label>
            <input name="company_name" required placeholder="e.g. AIA Philippines">
        </div>
        <div>
            <label>Insurance Type</label>
            <select name="insurance_type" required>
                <option value="life">Life Insurance</option>
                <option value="non-life">Non-Life Insurance</option>
                <option value="both">Life & Non-Life</option>
            </select>
        </div>
        <div>
            <label>Contact Person</label>
            <input name="contact_person" required placeholder="e.g. John Smith">
        </div>
        <div>
            <label>Contact Email</label>
            <input name="contact_email" type="email" required placeholder="liaison@partner.local">
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Add Insurance Partner</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Insurance Partners (<?= count($partners); ?> registered)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Company Name</th>
                <th>Type</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Policies</th>
                <th>Active</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partners as $partner): ?>
            <tr>
                <td><?= (int) $partner['id']; ?></td>
                <td><?= e($partner['company_name']); ?></td>
                <td><?= e(insuranceTypeLabel((string) $partner['insurance_type'])); ?></td>
                <td><?= e($partner['contact_person']); ?></td>
                <td><?= e($partner['contact_email']); ?></td>
                <td><?= (int) $partner['policy_count']; ?></td>
                <td>
                    <span class="badge success">
                        <?= (int) $partner['active_count']; ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime((string) $partner['created_at'])); ?></td>
                <td>
                    <button class="btn-small" onclick="toggleEditForm(<?= (int) $partner['id']; ?>)">Edit</button>
                </td>
            </tr>
            <tr id="edit-form-<?= (int) $partner['id']; ?>" style="display:none;">
                <td colspan="9">
                    <div style="padding: 1rem; background: var(--panel-soft); border-radius: 0.5rem;">
                        <h4>Edit Insurance Partner</h4>
                        <form method="post" class="grid cols-2">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_partner" value="1">
                            <input type="hidden" name="partner_id" value="<?= (int) $partner['id']; ?>">
                            
                            <div>
                                <label>Company Name</label>
                                <input name="company_name" required value="<?= e($partner['company_name']); ?>">
                            </div>
                            <div>
                                <label>Insurance Type</label>
                                <select name="insurance_type" required>
                                    <option value="life" <?= $partner['insurance_type'] === 'life' ? 'selected' : ''; ?>>Life Insurance</option>
                                    <option value="non-life" <?= $partner['insurance_type'] === 'non-life' ? 'selected' : ''; ?>>Non-Life Insurance</option>
                                    <option value="both" <?= $partner['insurance_type'] === 'both' ? 'selected' : ''; ?>>Life & Non-Life</option>
                                </select>
                            </div>
                            <div>
                                <label>Contact Person</label>
                                <input name="contact_person" required value="<?= e($partner['contact_person']); ?>">
                            </div>
                            <div>
                                <label>Contact Email</label>
                                <input name="contact_email" type="email" required value="<?= e($partner['contact_email']); ?>">
                            </div>
                            
                            <div style="grid-column: 1 / -1; display: flex; gap: 0.5rem;">
                                <button type="submit">Save Changes</button>
                                <button type="button" onclick="toggleEditForm(<?= (int) $partner['id']; ?>)" class="btn-secondary">Cancel</button>
                            </div>
                        </form>

                        <hr style="margin: 1rem 0;">
                        
                        <h4 style="color: var(--danger);">Delete Partner</h4>
                        <p style="font-size: 0.9rem; color: var(--muted);">
                            This partner has <strong><?= (int) $partner['policy_count']; ?></strong> total policies 
                            (<strong><?= (int) $partner['active_count']; ?></strong> active).
                        </p>
                        <form method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                            <?= csrfField(); ?>
                            <input type="hidden" name="delete_partner" value="1">
                            <input type="hidden" name="partner_id" value="<?= (int) $partner['id']; ?>">
                            <button type="submit" class="btn-danger" <?= (int) $partner['active_count'] > 0 ? 'disabled' : ''; ?>>Delete Partner</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
    
    .btn-danger:hover:not(:disabled) {
        opacity: 0.8;
    }
    
    .btn-danger:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
</style>

<script>
function toggleEditForm(partnerId) {
    const form = document.getElementById(`edit-form-${partnerId}`);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'table-row' : 'none';
    }
}
</script>
