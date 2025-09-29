<?php
session_start();
require_once __DIR__ . '/secure/env.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = $_POST['user'] ?? '';
  $pass = $_POST['pass'] ?? '';

  if ($user === ADMIN_USER && $pass === ADMIN_PASSWORD) {
    $_SESSION['is_admin_logged_in'] = true;
    header('Location: admin_articles.php');
    exit;
  } else {
    $error = 'ユーザー名またはパスワードが違います。';
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>管理ログイン</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .login-wrap{max-width:420px;margin:80px auto;padding:24px;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:var(--shadow)}
    .login-wrap h1{margin:0 0 12px}
    .error{color:#d00;margin:.5rem 0}
    .row{display:flex;gap:8px;align-items:center;margin:.5rem 0}
    .row label{width:110px;color:var(--muted)}
    .row input{flex:1;padding:.5rem;border:1px solid var(--border);border-radius:8px}
    .actions{margin-top:12px;text-align:right}
  </style>
</head>
<body>
  <div class="login-wrap">
    <h1>管理ログイン</h1>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post">
      <div class="row">
        <label>ユーザー名</label>
        <input type="text" name="user" required>
      </div>
      <div class="row">
        <label>パスワード</label>
        <input type="password" name="pass" required>
      </div>
      <div class="actions">
        <button class="btn" type="submit">ログイン</button>
      </div>
    </form>
  </div>
</body>
</html>