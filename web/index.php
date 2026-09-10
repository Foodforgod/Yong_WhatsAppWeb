<?php
require_once 'includes/config.php';

$message = '';
// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_job') {
            $phone = trim($_POST['phone']);
            $body = trim($_POST['body']);
            if (!empty($phone) && !empty($body)) {
                $stmt = $pdo->prepare("INSERT INTO message_jobs (recipient_phone, message_body, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$phone, $body]);
                $message = "Message successfully added to queue!";
            }
        } elseif ($_POST['action'] === 'clear_failed') {
            $pdo->query("DELETE FROM message_jobs WHERE status = 'failed'");
            $message = "All failed jobs cleared.";
        } elseif ($_POST['action'] === 'retry_failed') {
            $pdo->query("UPDATE message_jobs SET status = 'pending', attempts = 0, error_message = NULL WHERE status = 'failed'");
            $message = "Failed jobs reset to pending.";
        }
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$query = "SELECT id, recipient_phone, message_body, status, attempts, scheduled_at, completed_at, error_message FROM message_jobs";
if ($statusFilter !== 'all') {
    $query .= " WHERE status = " . $pdo->quote($statusFilter);
}
$query .= " ORDER BY id DESC LIMIT 50";
$jobs = $pdo->query($query)->fetchAll();

$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM message_jobs")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM message_jobs WHERE status='pending'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM message_jobs WHERE status='processing'")->fetchColumn(),
    'sent' => $pdo->query("SELECT COUNT(*) FROM message_jobs WHERE status='sent'")->fetchColumn(),
    'failed' => $pdo->query("SELECT COUNT(*) FROM message_jobs WHERE status='failed'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15">
    <title>WhatsApp Bot Management Suite</title>
    <style>
        :root {
            --primary: #075e54;
            --success: #25d366;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #333333;
            --border: #e2e8f0;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; background: var(--bg); color: var(--text); }
        .sidebar { width: 260px; background: var(--primary); position: fixed; top: 0; bottom: 0; left: 0; color: white; padding: 25px 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-top: 0; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; font-weight: 500; display: block; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        .main-content { margin-left: 280px; padding: 35px; max-width: 1200px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: var(--card-bg); padding: 20px 25px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
        .card { background: var(--card-bg); padding: 15px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); text-decoration: none; color: inherit; display: block; transition: transform 0.1s; }
        .card:hover { transform: translateY(-2px); border-color: var(--primary); }
        .card h3 { margin: 0 0 5px 0; font-size: 12px; color: #64748b; text-transform: uppercase; }
        .card span { font-size: 22px; font-weight: bold; }
        .actions-panel { background: var(--card-bg); padding: 20px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 25px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .actions-panel form { display: flex; gap: 10px; align-items: center; width: 100%; }
        input[type="text"], textarea { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
        input[type="text"] { width: 200px; }
        textarea { flex-grow: 1; height: 36px; resize: none; }
        button { background: var(--primary); color: white; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; }
        button.secondary { background: #64748b; }
        button.danger { background: #dc2626; }
        .table-wrap { background: var(--card-bg); border-radius: 10px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 18px; border-bottom: 1px solid var(--border); font-size: 13px; }
        th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .badge { padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .pending { background: #fef3c7; color: #92400e; }
        .processing { background: #e0f2fe; color: #0369a1; }
        .sent { background: #dcfce7; color: #166534; }
        .failed { background: #fee2e2; color: #991b1b; }
        .msg-text { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .alert { background: #dcfce7; color: #166534; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>📱 WhatsApp Suite</h2>
        <a href="index.php" class="active">📊 Queue Monitor</a>
        <a href="api_keys.php">🔑 API Management</a>
        <a href="settings.php">⚙️ System Settings</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1 style="margin:0; font-size: 20px;">Message Queue & Dispatch Control</h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px;">Live automated monitoring and quick queue control</p>
            </div>
            <div>Worker Status: <strong style="color: var(--success);">● Operational</strong></div>
        </div>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <a href="index.php?status=all" class="card"><h3>Total Jobs</h3><span style="color: #0f172a;"><?= $counts['all'] ?></span></a>
            <a href="index.php?status=pending" class="card"><h3>Pending</h3><span style="color: #b45309;"><?= $counts['pending'] ?></span></a>
            <a href="index.php?status=processing" class="card"><h3>Processing</h3><span style="color: #0284c7;"><?= $counts['processing'] ?></span></a>
            <a href="index.php?status=sent" class="card"><h3>Sent</h3><span style="color: #15803d;"><?= $counts['sent'] ?></span></a>
            <a href="index.php?status=failed" class="card"><h3>Failed</h3><span style="color: #b91c1c;"><?= $counts['failed'] ?></span></a>
        </div>

        <div class="actions-panel">
            <form method="POST">
                <input type="hidden" name="action" value="add_job">
                <input type="text" name="phone" placeholder="Recipient Phone (e.g. 60123456789)" required>
                <textarea name="body" placeholder="Type quick message body..." required></textarea>
                <button type="submit">➕ Queue Message</button>
            </form>
        </div>

        <div style="margin-bottom: 15px; display: flex; gap: 10px;">
            <form method="POST" style="display:inline;" onsubmit="return confirm('Reset all failed jobs to pending?');">
                <input type="hidden" name="action" value="retry_failed">
                <button type="submit" class="secondary">🔄 Retry All Failed</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete all failed jobs?');">
                <input type="hidden" name="action" value="clear_failed">
                <button type="submit" class="danger">🗑️ Clear Failed Logs</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Error Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">No jobs found for the selected filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($job['id']) ?></td>
                            <td><strong><?= htmlspecialchars($job['recipient_phone']) ?></strong></td>
                            <td><div class="msg-text" title="<?= htmlspecialchars($job['message_body']) ?>"><?= htmlspecialchars($job['message_body']) ?></div></td>
                            <td><span class="badge <?= htmlspecialchars($job['status']) ?>"><?= htmlspecialchars($job['status']) ?></span></td>
                            <td><?= htmlspecialchars($job['attempts']) ?></td>
                            <td><?= htmlspecialchars($job['scheduled_at']) ?></td>
                            <td><?= htmlspecialchars($job['completed_at'] ?? '—') ?></td>
                            <td style="color: #ef4444;"><?= htmlspecialchars($job['error_message'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>