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
  <link rel="stylesheet" href="css/style.css">
  <style>
    .auth-card{max-width:420px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:12px;background:#fff}
    .auth-card h1{font-size:20px;margin-bottom:12px}
    .field{margin:10px 0}
    label{display:block;font-weight:600;margin-bottom:4px}
    input[type=email],input[type=password]{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
    .btn{display:inline-block;padding:10px 14px;border:0;border-radius:10px;background:#17a877;color:#fff;font-weight:700;cursor:pointer}
    .errors{background:#fff5f5;border:1px solid #fecaca;color:#9b1c1c;border-radius:8px;padding:10px;margin-bottom:10px}
    .links{margin-top:10px}
  </style>
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

    <div class="links">
      アカウントをお持ちでないですか？ <a href="register.php">新規登録</a>
    </div>
  </div>
</body>
</html>