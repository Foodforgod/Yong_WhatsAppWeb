<?php
require_once 'includes/config.php';

$message = '';
$newTokenDisplay = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_worker') {
        $name = trim($_POST['worker_name']);
        if (!empty($name)) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            $stmt = $pdo->prepare("INSERT INTO workers (name, token_hash, status) VALUES (?, ?, 'idle')");
            $stmt->execute([$name, $tokenHash]);
            
            $newTokenDisplay = $rawToken;
            $message = "Worker registered successfully! Copy your secret token below.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_worker') {
        $id = intval($_POST['worker_id']);
        $stmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Worker deleted successfully.";
    }
}

$workers = $pdo->query("SELECT id, name, status, last_seen, created_at FROM workers ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API & Worker Management</title>
    <style>
        :root { --primary: #075e54; --success: #25d366; --bg: #f8f9fa; --card-bg: #ffffff; --text: #333333; --border: #e2e8f0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; background: var(--bg); color: var(--text); }
        .sidebar { width: 260px; background: var(--primary); position: fixed; top: 0; bottom: 0; left: 0; color: white; padding: 25px 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 20px; margin-top: 0; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; font-weight: 500; display: block; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        .main-content { margin-left: 280px; padding: 35px; max-width: 1200px; }
        .card { background: var(--card-bg); padding: 25px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 25px; }
        input[type="text"] { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 280px; }
        button { background: var(--primary); color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; }
        button.danger { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px; }
        th, td { padding: 14px 18px; border-bottom: 1px solid var(--border); font-size: 13px; }
        th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .alert { background: #dcfce7; color: #166534; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-size: 14px; }
        .token-box { background: #1e293b; color: #38bdf8; padding: 12px; border-radius: 6px; font-family: monospace; word-break: break-all; margin-top: 10px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>📱 WhatsApp Suite</h2>
        <a href="index.php">📊 Queue Monitor</a>
        <a href="api_keys.php" class="active">🔑 API Management</a>
        <a href="settings.php">⚙️ System Settings</a>
    </div>

    <div class="main-content">
        <h1 style="margin:0 0 5px 0; font-size: 22px;">Worker & API Management</h1>
        <p style="margin: 0 0 25px 0; color: #64748b; font-size: 13px;">Generate and manage secure authentication tokens for Python worker nodes.</p>

        <?php if ($message): ?>
            <div class="alert">
                <?= htmlspecialchars($message) ?>
                <?php if ($newTokenDisplay): ?>
                    <div class="token-box"><?= htmlspecialchars($newTokenDisplay) ?></div>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #166534;">Paste this token directly into your <code>.env</code> file under <code>WORKER_TOKEN</code>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-top:0; font-size: 16px;">Register New Worker Node</h3>
            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="action" value="create_worker">
                <input type="text" name="worker_name" placeholder="Worker Name (e.g. Office PC)" required>
                <button type="submit">➕ Generate Worker Token</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0; font-size: 16px;">Active Registered Nodes</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Worker Name</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($workers)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">No workers registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($workers as $w): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($w['id']) ?></td>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><span style="color: var(--success); font-weight: 600;">● Online</span></td>
                            <td><?= htmlspecialchars($w['last_seen'] ?? 'Never') ?></td>
                            <td><?= htmlspecialchars($w['created_at']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Revoke this worker?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_worker">
                                    <input type="hidden" name="worker_id" value="<?= $w['id'] ?>">
                                    <button type="submit" class="danger" style="padding: 6px 12px; font-size: 11px;">Revoke</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>