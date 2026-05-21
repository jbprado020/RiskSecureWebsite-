<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireStaffRole(['admin', 'manager', 'underwriter', 'claims_officer']);

$pdo = db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_meeting'])) {
    requireCsrfToken();

    $clientIdRaw = trim((string) ($_POST['client_id'] ?? ''));
    $clientId = $clientIdRaw === '' ? null : (int) $clientIdRaw;
    $agentId = (int) ($_POST['agent_id'] ?? 0);
    $meetingAt = trim((string) ($_POST['meeting_at'] ?? ''));
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 30);
    $channel = (string) ($_POST['channel'] ?? 'zoom');
    $purpose = trim((string) ($_POST['purpose'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $allowedChannels = ['zoom', 'phone', 'in-person'];

    if ($meetingAt !== '' && $purpose !== '' && $agentId > 0 && in_array($channel, $allowedChannels, true)) {
        // Normalize datetime-local value
        $startAt = str_replace('T', ' ', $meetingAt);
        if ($durationMinutes <= 0) {
            $durationMinutes = 30;
        }
        $endAt = date('Y-m-d H:i:s', strtotime($startAt) + ($durationMinutes * 60));

        // Overlap check using start/end ranges
        $conflictStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM meeting_schedules
             WHERE agent_id = :agent_id AND status = "scheduled"
               AND (meeting_at < :end_at AND COALESCE(end_at, meeting_at) > :start_at)'
        );
        $conflictStmt->execute([
            ':agent_id' => $agentId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
        ]);

        if ((int) $conflictStmt->fetchColumn() > 0) {
            $error = 'Selected agent already has an appointment overlapping that time range. Choose another time or agent.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO meeting_schedules (client_id, agent_id, meeting_at, end_at, duration_minutes, channel, purpose, status, notes)
                 VALUES (:client_id, :agent_id, :meeting_at, :end_at, :duration_minutes, :channel, :purpose, :status, :notes)'
            );
            $stmt->execute([
                ':client_id' => $clientId,
                ':agent_id' => $agentId,
                ':meeting_at' => $startAt,
                ':end_at' => $endAt,
                ':duration_minutes' => $durationMinutes,
                ':channel' => $channel,
                ':purpose' => $purpose,
                ':status' => 'scheduled',
                ':notes' => $notes,
            ]);
            $message = 'Meeting scheduled.';
        }
    } else {
        $error = 'Please provide valid meeting details.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meeting'])) {
    requireCsrfToken();

    $meetingId = (int) ($_POST['meeting_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'scheduled');
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $allowedStatuses = ['scheduled', 'completed', 'cancelled', 'no_show'];

    if ($meetingId > 0 && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE meeting_schedules SET status = :status, notes = :notes WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':notes' => $notes,
            ':id' => $meetingId,
        ]);
        $message = 'Meeting updated.';
    } else {
        $error = 'Invalid meeting update request.';
    }
}

$selectedMonth = trim((string) ($_GET['month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}
$monthStart = $selectedMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

$clients = $pdo->query('SELECT id, full_name FROM clients ORDER BY full_name')->fetchAll();
$agents = $pdo->query(
    'SELECT id, full_name, role
     FROM staff_accounts
     WHERE is_active = 1 AND role IN ("manager", "underwriter", "claims_officer", "admin")
     ORDER BY full_name'
)->fetchAll();

$meetings = $pdo->query(
    'SELECT ms.*, c.full_name, sa.full_name AS agent_name, sa.role AS agent_role
     FROM meeting_schedules ms
     LEFT JOIN clients c ON c.id = ms.client_id
     INNER JOIN staff_accounts sa ON sa.id = ms.agent_id
     ORDER BY ms.meeting_at ASC'
)->fetchAll();

$calendarStmt = $pdo->prepare(
    'SELECT ms.*, c.full_name, sa.full_name AS agent_name
     FROM meeting_schedules ms
     LEFT JOIN clients c ON c.id = ms.client_id
     INNER JOIN staff_accounts sa ON sa.id = ms.agent_id
     WHERE DATE(ms.meeting_at) BETWEEN :month_start AND :month_end
     ORDER BY ms.meeting_at ASC'
);
$calendarStmt->execute([
    ':month_start' => $monthStart,
    ':month_end' => $monthEnd,
]);
$calendarMeetings = $calendarStmt->fetchAll();

renderHeader('Meetings');
?>

<section class="card">
    <h2>Meeting Scheduler</h2>
    <p>Schedule, view, and update client appointments by assigned agent.</p>

    <?php if ($message !== ''): ?>
        <div class="notice ok"><?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="notice" style="background:#fdecec; color:#7d1c1c;"><?= e($error); ?></div>
    <?php endif; ?>
</section>

<section class="grid cols-2">
    <article class="card">
        <h2>Create Meeting</h2>
        <form method="post" class="grid cols-2">
            <?= csrfField(); ?>
            <input type="hidden" name="create_meeting" value="1">
            <div>
                <label>Client (optional)</label>
                <select name="client_id">
                    <option value="">No specific client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id']; ?>"><?= e((string) $client['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Assigned Agent</label>
                <select name="agent_id" required>
                    <option value="">Select agent</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= (int) $agent['id']; ?>">
                            <?= e((string) $agent['full_name']); ?> (<?= e(statusLabel((string) $agent['role'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Date and Time</label>
                <input type="datetime-local" name="meeting_at" required>
            </div>
            <div>
                <label>Duration (minutes)</label>
                <input type="number" name="duration_minutes" min="5" max="480" step="5" value="30" required>
            </div>
            <div>
                <label>Channel</label>
                <select name="channel" required>
                    <option value="zoom">Zoom</option>
                    <option value="phone">Phone</option>
                    <option value="in-person">In-person</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <label>Purpose</label>
                <input name="purpose" placeholder="e.g. Renewal discussion" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <label>Notes</label>
                <textarea name="notes" placeholder="e.g. Send invite link and checklist"></textarea>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit">Schedule Meeting</button>
            </div>
        </form>
    </article>

    <article class="card">
        <h2>Calendar View</h2>
        <form method="get" style="display:flex; gap:0.6rem; align-items:end; margin-bottom:0.8rem;">
            <div style="flex:1;">
                <label>Month</label>
                <input type="month" name="month" value="<?= e($selectedMonth); ?>">
            </div>
            <div style="flex:0 0 auto;">
                <button type="submit">Load</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Client</th>
                    <th>Agent</th>
                    <th>Purpose</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendarMeetings as $meeting): ?>
                    <tr>
                        <td><?= e((string) $meeting['meeting_at']); ?></td>
                        <td><?= e((string) ($meeting['full_name'] ?? 'N/A')); ?></td>
                        <td><?= e((string) $meeting['agent_name']); ?></td>
                        <td><?= e((string) $meeting['purpose']); ?></td>
                        <td><span class="badge <?= badgeClass((string) $meeting['status']); ?>"><?= e(statusLabel((string) $meeting['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($calendarMeetings) === 0): ?>
                    <tr><td colspan="5">No meetings in selected month.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="card">
    <h2>Meeting Tracker</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date/Time</th>
                <th>Client</th>
                <th>Agent</th>
                <th>Channel</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($meetings as $meeting): ?>
                <tr>
                    <td><?= (int) $meeting['id']; ?></td>
                    <td><?= e((string) $meeting['meeting_at']); ?></td>
                    <td><?= e((string) ($meeting['full_name'] ?? 'N/A')); ?></td>
                    <td><?= e((string) $meeting['agent_name']); ?> (<?= e(statusLabel((string) $meeting['agent_role'])); ?>)</td>
                    <td><?= e((string) $meeting['channel']); ?></td>
                    <td><?= e((string) $meeting['purpose']); ?></td>
                    <td><span class="badge <?= badgeClass((string) $meeting['status']); ?>"><?= e(statusLabel((string) $meeting['status'])); ?></span></td>
                    <td>
                        <form method="post" class="grid" style="gap:0.4rem; min-width:220px;">
                            <?= csrfField(); ?>
                            <input type="hidden" name="update_meeting" value="1">
                            <input type="hidden" name="meeting_id" value="<?= (int) $meeting['id']; ?>">
                            <select name="status">
                                <?php $statusOptions = ['scheduled', 'completed', 'cancelled', 'no_show']; ?>
                                <?php foreach ($statusOptions as $statusOption): ?>
                                    <option value="<?= e($statusOption); ?>" <?= (string) $meeting['status'] === $statusOption ? 'selected' : ''; ?>>
                                        <?= e(statusLabel($statusOption)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input name="notes" value="<?= e((string) ($meeting['notes'] ?? '')); ?>" placeholder="Notes">
                            <button type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($meetings) === 0): ?>
                <tr><td colspan="8">No meetings recorded.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
renderFooter();
