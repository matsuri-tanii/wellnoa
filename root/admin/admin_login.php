<?php
// /admin/admin_login.php
declare(strict_types=1);
require_once __DIR__.'/../funcs.php';
require_once __DIR__.'/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_same_origin_check() || !admin_csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Bad Request (CSRF)');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['user'] ?? '');
    $p = (string)($_POST['pass'] ?? '');
    $t = (string)($_POST['csrf'] ?? '');

    if (!admin_csrf_check($t)) {
        $error = 'セッションの有効期限が切れました。もう一度お試しください。';
    } elseif ($u !== ADMIN_USER) {
        $error = 'ユーザー名またはパスワードが違います。';
    } elseif (!password_verify($p, ADMIN_PASSWORD_HASH)) {
        $error = 'ユーザー名またはパスワードが違います。';
    } else {
        // セッション固定化対策
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $u;
        header('Location: /admin/admin_articles.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="utf-8"><title>管理ログイン</title></head>
<body>
  <h1>管理ログイン</h1>
  <?php if ($error): ?>
    <div style="color:#b00020"><?= h($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
    <div><label>ユーザー名 <input name="user" required></label></div>
    <div><label>パスワード <input name="pass" type="password" required></label></div>
    <button type="submit">ログイン</button>
  </form>
</body>
</html>