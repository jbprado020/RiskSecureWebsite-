<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/validation.php';

requireStaffRole(['admin', 'manager', 'underwriter']);

$pdo = db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    requireCsrfToken();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $birthDate = trim((string) ($_POST['date_of_birth'] ?? ''));

    if ($fullName !== '' && isValidEmail($email) && $phone !== '' && $address !== '' && isValidDate($birthDate)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO clients (full_name, email, phone, address, date_of_birth)
                 VALUES (:full_name, :email, :phone, :address, :date_of_birth)'
            );
            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':date_of_birth' => $birthDate,
            ]);
            $message = 'Client added successfully.';
        } catch (Throwable $exception) {
            $error = 'Unable to add client. Check if email is already in use.';
        }
    } else {
        $error = 'Please complete all required client fields with valid values.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_client'])) {
    requireCsrfToken();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $birthDate = trim((string) ($_POST['date_of_birth'] ?? ''));

    if ($clientId > 0 && $fullName !== '' && isValidEmail($email) && $phone !== '' && $address !== '' && isValidDate($birthDate)) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE clients
                 SET full_name = :full_name,
                     email = :email,
                     phone = :phone,
                     address = :address,
                     date_of_birth = :date_of_birth
                 WHERE id = :id'
            );
            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':date_of_birth' => $birthDate,
                ':id' => $clientId,
            ]);
            $message = 'Client profile updated.';
        } catch (Throwable $exception) {
            $error = 'Unable to update client. Check if email is already in use.';
        }
    } else {
        $error = 'Please provide complete client details with valid values for update.';
    }
}

$clients = $pdo->query('SELECT * FROM clients ORDER BY id ASC')->fetchAll();

renderHeader('Clients');
?>

<section class="card">
    <h2>Manage Client Profiles</h2>
    <?php if ($message !== ''): ?>
        <?php renderNotice($message, 'ok'); ?>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <?php renderNotice($error, 'error'); ?>
    <?php endif; ?>

    <form method="post" class="grid cols-2" data-validate="true">
        <?= csrfField(); ?>
        <input type="hidden" name="create_client" value="1">
        <div>
            <label>Full Name</label>
            <input name="full_name" required aria-label="Full Name" aria-required="true" autocomplete="name">
        </div>
        <div>
            <label>Email</label>
            <input name="email" type="email" required aria-label="Email" aria-required="true" autocomplete="email">
        </div>
        <div>
            <label>Phone</label>
            <input name="phone" required aria-label="Phone" aria-required="true" autocomplete="tel">
        </div>
        <div>
            <label>Date of Birth</label>
            <input name="date_of_birth" type="date" required aria-label="Date of Birth" aria-required="true" autocomplete="bday">
        </div>
        <div style="grid-column: 1 / -1;">
            <label>Address</label>
            <textarea name="address" required aria-label="Address" aria-required="true" autocomplete="street-address"></textarea>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Save Client</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Client List</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Date of Birth</th>
                <th>Address</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= (int) $client['id']; ?></td>
                <td><?= e((string) $client['full_name']); ?></td>
                <td><?= e((string) $client['email']); ?></td>
                <td><?= e((string) $client['phone']); ?></td>
                <td><?= e((string) $client['date_of_birth']); ?></td>
                <td><?= e((string) $client['address']); ?></td>
                <td>
                    <details>
                        <summary>Edit</summary>
                        <form method="post" class="grid" style="gap:0.45rem; min-width:260px; margin-top:0.4rem;" data-validate="true">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_client" value="1">
                            <input type="hidden" name="client_id" value="<?= (int) $client['id']; ?>">
                            <input name="full_name" value="<?= e((string) $client['full_name']); ?>" required aria-label="Full Name" aria-required="true" autocomplete="name">
                            <input name="email" type="email" value="<?= e((string) $client['email']); ?>" required aria-label="Email" aria-required="true" autocomplete="email">
                            <input name="phone" value="<?= e((string) $client['phone']); ?>" required aria-label="Phone" aria-required="true" autocomplete="tel">
                            <input name="date_of_birth" type="date" value="<?= e((string) $client['date_of_birth']); ?>" required aria-label="Date of Birth" aria-required="true" autocomplete="bday">
                            <textarea name="address" required aria-label="Address" aria-required="true" autocomplete="street-address"><?= e((string) $client['address']); ?></textarea>
                            <button type="submit">Update</button>
                        </form>
                    </details>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($clients) === 0): ?>
                <tr><td colspan="7">No clients found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

<?php
renderFooter();
