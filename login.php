<?php
require_once __DIR__.'/config.php';
if (is_logged_in()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (is_login_locked($username)) {
        $error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            reset_login_attempts($username);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        }
        record_login_failure($username);
        $error = 'Incorrect username or password.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · <?=e(APP_NAME)?></title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><div class="login-shell"><div class="login-brand"><div class="brand-mark">NI</div><div><div class="brand-name">NetTrack</div><div class="brand-sub">IT ASSET MANAGEMENT</div></div></div>
<div class="login-card"><div class="eyebrow">ORGANIZATION INVENTORY</div><h1>Welcome back</h1><p class="muted">Sign in to manage network equipment and maintenance records.</p>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Username</label><input name="username" required autofocus autocomplete="username" placeholder="Enter username"><label>Password</label><input type="password" name="password" required autocomplete="current-password" placeholder="Enter password"><button class="btn primary full" type="submit">Sign in <span>→</span></button></form>
</div><div class="login-footer">Network Equipment Inventory System · School project edition</div></div></body></html>
