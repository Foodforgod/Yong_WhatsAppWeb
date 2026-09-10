<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Set your desired dashboard login credentials here
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - WhatsApp Suite</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 360px; }
        h2 { margin-top: 0; color: #004d33; font-size: 22px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 13px; color: #333; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #006644; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
        button:hover { background: #004d33; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>WhatsApp Suite Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="admin" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required value="admin123" />
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>