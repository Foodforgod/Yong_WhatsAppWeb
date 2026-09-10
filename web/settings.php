<?php
$envPath = __DIR__ . '/../../.env';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiUrl = trim($_POST['api_url'] ?? '');
    $timezone = trim($_POST['timezone'] ?? '');
    
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        $envContent = preg_replace('/API_URL=.*/', 'API_URL=' . $apiUrl, $envContent);
        $envContent = preg_replace('/APP_TIMEZONE=.*/', 'APP_TIMEZONE=' . $timezone, $envContent);
        file_put_contents($envPath, $envContent);
        $message = "System configurations updated successfully!";
    }
}

$currentUrl = '';
$currentTimezone = '';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'API_URL=') === 0) $currentUrl = trim(substr($line, 8));
        if (strpos($line, 'APP_TIMEZONE=') === 0) $currentTimezone = trim(substr($line, 13));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - WhatsApp Suite</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; background: #f8f9fa; color: #333; }
        .sidebar { width: 260px; background: #075e54; position: fixed; top: 0; bottom: 0; left: 0; color: white; padding: 25px 20px; }
        .sidebar h2 { font-size: 20px; margin-top: 0; margin-bottom: 30px; }
        .sidebar a { color: #cbd5e1; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        .main { margin-left: 280px; padding: 35px; max-width: 800px; }
        .panel { background: white; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #475569; }
        input[type="text"] { padding: 10px 14px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        button { background: #075e54; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .alert { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>📱 WhatsApp Suite</h2>
        <a href="index.php">📊 Queue Monitor</a>
        <a href="api_keys.php">🔑 API Management</a>
        <a href="settings.php" class="active">⚙️ System Settings</a>
    </div>
    <div class="main">
        <h1>System Configurations</h1>
        <p style="color: #64748b;">Manage global environment configurations and core endpoints.</p>

        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST">
                <div class="form-group">
                    <label>API Base URL Endpoint</label>
                    <input type="text" name="api_url" value="<?= htmlspecialchars($currentUrl) ?>" required>
                </div>
                <div class="form-group">
                    <label>Application Timezone</label>
                    <input type="text" name="timezone" value="<?= htmlspecialchars($currentTimezone) ?>" required>
                </div>
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>