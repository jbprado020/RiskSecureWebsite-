<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db_helpers.php';
require_once __DIR__ . '/includes/upload_helpers.php';

requireStaffRole(['admin', 'manager', 'underwriter', 'claims_officer']);

$pdo = db();
$message = '';
$error = '';
$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    requireCsrfToken();

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $policyId = (int) ($_POST['policy_id'] ?? 0);
    $claimIdRaw = trim((string) ($_POST['claim_id'] ?? ''));
    $claimId = $claimIdRaw === '' ? null : (int) $claimIdRaw;
    $documentType = trim((string) ($_POST['document_type'] ?? ''));

    if ($clientId <= 0 || $policyId <= 0 || $documentType === '' || !isset($_FILES['document_file'])) {
        $error = 'Please provide client, policy, document type, and file.';
    } else {
        $policyStmt = $pdo->prepare('SELECT id, client_id FROM policies WHERE id = :policy_id LIMIT 1');
        $policyStmt->execute([':policy_id' => $policyId]);
        $policy = $policyStmt->fetch();

        if (!$policy || (int) $policy['client_id'] !== $clientId) {
            $error = 'Selected policy does not belong to the selected client.';
        } elseif ($claimId !== null) {
            $claimStmt = $pdo->prepare('SELECT id FROM claims WHERE id = :claim_id AND policy_id = :policy_id LIMIT 1');
            $claimStmt->execute([
                ':claim_id' => $claimId,
                ':policy_id' => $policyId,
            ]);
            if (!$claimStmt->fetch()) {
                $error = 'Selected claim does not match the selected policy.';
            }
        }

        if ($error === '') {
            $file = $_FILES['document_file'];
            $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                $error = 'File upload failed.';
            } else {
                $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $error = 'Unsupported file type. Allowed: pdf, jpg, jpeg, png, doc, docx.';
                } elseif (!validateUploadMimeType($file['tmp_name'], $allowedMimeTypes)) {
                    $error = 'Invalid file content. File does not match declared type.';
                } else {
                        try {
                            $uploadInfo = prepareUploadTemp($file, $uploadDir, 'doc');

                            // Insert record inside a retryable transaction, then move temp -> final
                            $insertId = runTransactionWithRetries($pdo, function (PDO $pdo) use ($clientId, $policyId, $claimId, $documentType, $uploadInfo) {
                                $stmt = $pdo->prepare(
                                    'INSERT INTO documents (client_id, policy_id, claim_id, document_type, file_path, uploaded_by, is_hard_copy_received)
                                     VALUES (:client_id, :policy_id, :claim_id, :document_type, :file_path, :uploaded_by, :is_hard_copy_received)'
                                );
                                $stmt->execute([
                                    ':client_id' => $clientId,
                                    ':policy_id' => $policyId,
                                    ':claim_id' => $claimId,
                                    ':document_type' => $documentType,
                                    ':file_path' => $uploadInfo['relativePath'],
                                    ':uploaded_by' => 'staff',
                                    ':is_hard_copy_received' => 0,
                                ]);

                                return (int) $pdo->lastInsertId();
                            });

                            try {
                                finalizeUploadMove($uploadInfo['tempPath'], $uploadInfo['finalPath'], function (?int $id) use ($pdo) {
                                    if ($id !== null) {
                                        $del = $pdo->prepare('DELETE FROM documents WHERE id = :id');
                                        $del->execute([':id' => $id]);
                                    }
                                }, $insertId);

                                $message = 'Document uploaded and categorized.';
                            } catch (Throwable $e) {
                                $error = 'Unable to finalize uploaded file after save. Please contact admin.';
                            }
                        } catch (Throwable $e) {
                            $error = $e->getMessage() ?: 'Unable to store uploaded file.';
                        }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hard_copy'])) {
    requireCsrfToken();

    $documentId = (int) ($_POST['document_id'] ?? 0);
    $isHardCopyReceived = isset($_POST['is_hard_copy_received']) ? 1 : 0;

    if ($documentId > 0) {
        $stmt = $pdo->prepare('UPDATE documents SET is_hard_copy_received = :is_hard_copy_received WHERE id = :id');
        $stmt->execute([
            ':is_hard_copy_received' => $isHardCopyReceived,
            ':id' => $documentId,
        ]);
        $message = 'Hard copy status updated.';
    }
}

$clients = $pdo->query('SELECT id, full_name FROM clients ORDER BY full_name ASC')->fetchAll();
$policies = $pdo->query(
    'SELECT p.id, p.policy_number, c.full_name
     FROM policies p
    INNER JOIN clients c ON c.id = p.client_id
     ORDER BY p.policy_number ASC'
)->fetchAll();
$claims = $pdo->query(
    'SELECT cl.id, p.policy_number
     FROM claims cl
     INNER JOIN policies p ON p.id = cl.policy_id
     ORDER BY cl.id DESC'
)->fetchAll();

$documents = $pdo->query(
    'SELECT d.*, c.full_name, p.policy_number, cl.claim_status
     FROM documents d
     INNER JOIN clients c ON c.id = d.client_id
     INNER JOIN policies p ON p.id = d.policy_id
     LEFT JOIN claims cl ON cl.id = d.claim_id
     ORDER BY d.date_uploaded DESC'
)->fetchAll();

renderHeader('Documents');
?>

<section class="card">
    <h2>Upload and Categorize Documents</h2>
    <p>Attach documents to client, policy, and optionally claim records.</p>

    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid cols-2">
        <?= csrfField(); ?>
        <input type="hidden" name="upload_document" value="1">
        <div>
            <label>Client</label>
            <select name="client_id" required>
                <option value="">Select client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id']; ?>"><?= e((string) $client['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Policy</label>
            <select name="policy_id" required>
                <option value="">Select policy</option>
                <?php foreach ($policies as $policy): ?>
                    <option value="<?= (int) $policy['id']; ?>">
                        <?= e((string) $policy['policy_number']); ?> - <?= e((string) $policy['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Claim (Optional)</label>
            <select name="claim_id">
                <option value="">No specific claim</option>
                <?php foreach ($claims as $claim): ?>
                    <option value="<?= (int) $claim['id']; ?>">
                        #<?= (int) $claim['id']; ?> - <?= e((string) $claim['policy_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Document Type</label>
            <input name="document_type" placeholder="e.g. ORCR, Policy Form, Valid ID" required>
        </div>
        <div>
            <label>File</label>
            <input type="file" name="document_file" required>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Upload Document</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Retrieve Client/Policy Documents</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Policy</th>
                <th>Claim</th>
                <th>Document Type</th>
                <th>Uploaded By</th>
                <th>Uploaded At</th>
                <th>Hard Copy</th>
                <th>File</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $document): ?>
                <tr>
                    <td><?= (int) $document['id']; ?></td>
                    <td><?= e((string) $document['full_name']); ?></td>
                    <td><?= e((string) $document['policy_number']); ?></td>
                    <td>
                        <?php if ($document['claim_id'] !== null): ?>
                            #<?= (int) $document['claim_id']; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $document['document_type']); ?></td>
                    <td><?= e(statusLabel((string) $document['uploaded_by'])); ?></td>
                    <td><?= e((string) $document['date_uploaded']); ?></td>
                    <td>
                        <span class="badge <?= badgeClass((int) $document['is_hard_copy_received'] === 1 ? 'complete' : 'pending'); ?>">
                            <?= (int) $document['is_hard_copy_received'] === 1 ? 'Received' : 'Pending'; ?>
                        </span>
                    </td>
                    <td><a href="<?= e((string) $document['file_path']); ?>" target="_blank" rel="noopener noreferrer">View</a></td>
                    <td>
                        <form method="post" style="display:flex; gap:0.5rem; align-items:center;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_hard_copy" value="1">
                            <input type="hidden" name="document_id" value="<?= (int) $document['id']; ?>">
                            <label style="display:flex; gap:0.35rem; align-items:center; margin:0;">
                                <input type="checkbox" name="is_hard_copy_received" style="width:auto;" <?= (int) $document['is_hard_copy_received'] === 1 ? 'checked' : ''; ?>>
                                Received
                            </label>
                            <button type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($documents) === 0): ?>
                <tr><td colspan="10">No documents found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
renderFooter();
