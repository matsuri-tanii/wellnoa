<?php
// login.php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

if (is_logged_in()) {
  redirect('index.php');
}

$errors = [];
$email  = (string)($_POST['email'] ?? '');
$pw     = (string)($_POST['password'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($email === '' || $pw === '') {
    $errors[] = 'メールアドレスとパスワードを入力してください。';
  } else {
    try {
      $pdo = db_conn();
      $st = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :e LIMIT 1');
      $st->execute([':e'=>$email]);
      $u = $st->fetch();

      if (!$u || !password_verify($pw, (string)$u['password_hash'])) {
        $errors[] = 'メールアドレスまたはパスワードが正しくありません。';
      } else {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['user_id'] = (int)$u['id'];

        // セッション固定化対策
        session_regenerate_id(true);

        setcookie('unregistered', '', time()-3600, '/'); // ← ここ！

        // 一度でもアカウントを持った人のフラグ
        setcookie('has_account', '1', [
          'expires'  => time() + 60*60*24*365,
          'path'     => '/',
          'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
          'httponly' => false,
          'samesite' => 'Lax',
        ]);
        // いまの anon_code を自分にひも付け（匿名で貯めた記録を引き継ぐため）
        link_current_anon_to_user($pdo, (int)$u['id']);

        set_flash('ログインしました。');
        redirect('index.php');
      }
    } catch (Throwable $e) {
      $errors[] = 'ログインに失敗しました：' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ログイン</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/page-overrides.css">
</head>
<body>
  <div class="auth-card">
    <h1>ログイン</h1>

    <?php if ($errors): ?>
      <div class="errors">
        <ul style="margin:0;padding-left:16px">
          <?php foreach ($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="field">
        <label for="email">メールアドレス</label>
        <input type="email" id="email" name="email" required value="<?=h($email)?>">
      </div>
      <div class="field">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div class="field">
        <button class="btn" type="submit">ログイン</button>
      </div>
    </form>

    <p class="help-link">
      <a href="forgot_password.php">パスワードをお忘れの方はこちら</a>
    </p>
    <div class="links">
      <a href="register.php">アカウントをお持ちでないですか？ 新規登録</a>
    </div>
  </div>
</body>
</html>