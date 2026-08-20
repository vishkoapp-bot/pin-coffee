<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
$config = require __DIR__ . '/api/config.php';

if (!empty($_SESSION['admin_authenticated'])) {
    header('Location: /admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $validUser = hash_equals($config['admin_username'] ?? '', $username);
    $validPass = password_verify($password, $config['admin_password_hash'] ?? '');

    if ($validUser && $validPass) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: /admin.php');
        exit;
    }

    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود ادمین | کافه میان</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Vazirmatn',sans-serif;min-height:100vh;margin:0;display:grid;place-items:center;background:linear-gradient(135deg,#21140f,#0f0b0a);color:#f5f2e9}
    .card{width:min(420px,92vw);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:28px;backdrop-filter:blur(16px)}
    h1{margin:0 0 8px;font-size:1.4rem}
    p{margin:0 0 20px;color:#cfc5b8}
    label{display:block;margin:14px 0 8px;font-size:.9rem}
    input{width:100%;box-sizing:border-box;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:#181210;color:#f5f2e9;font:inherit}
    button{width:100%;margin-top:18px;padding:14px 16px;border:0;border-radius:14px;background:#7a0a1f;color:#fff;font:700 1rem inherit;cursor:pointer}
    .error{margin-top:14px;color:#ffb4b4;background:rgba(255,0,0,.08);padding:12px 14px;border-radius:12px}
    .hint{margin-top:12px;font-size:.85rem;color:#b9aca0}
  </style>
</head>
<body>
  <form class="card" method="post" action="login.php" autocomplete="off">
    <h1>ورود به پنل ادمین</h1>
    <p>برای مدیریت منو وارد شوید.</p>
    <label for="username">نام کاربری</label>
    <input id="username" name="username" type="text" required autofocus>
    <label for="password">رمز عبور</label>
    <input id="password" name="password" type="password" required>
    <button type="submit">ورود</button>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="hint">مسیر پنل بعد از ورود: <code>/admin.php</code></div>
  </form>
</body>
</html>
