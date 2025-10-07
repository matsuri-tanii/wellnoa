<?php
// register.php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

// ✅ 既にログイン済みなら登録ページに来れないように
if (is_logged_in()) {
  redirect('index.php');
}

// ✅ 匿名で来た人はそのまま通す（QR経由コード採用＆未登録クッキー明示）
adopt_incoming_code();
mark_unregistered_mode();

$errors = [];
$email  = (string)($_POST['email'] ?? '');
$pw     = (string)($_POST['password'] ?? '');
$pw2    = (string)($_POST['password_confirm'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // --- バリデーション ---
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '正しいメールアドレスを入力してください。';
  }
  if ($pw === '' || strlen($pw) < 8) {
    $errors[] = 'パスワードは8文字以上にしてください。';
  }
  if ($pw !== $pw2) {
    $errors[] = '確認用パスワードが一致しません。';
  }

  if (!$errors) {
    try {
      $pdo = db_conn();

      // 既存チェック
      $st = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
      $st->execute([':e'=>$email]);
      if ($st->fetch()) {
        $errors[] = 'このメールアドレスは既に登録されています。';
      } else {
        // 新規作成
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare('
          INSERT INTO users(email, password_hash, created_at, updated_at)
          VALUES(:e, :p, NOW(), NOW())
        ');
        $ins->execute([':e'=>$email, ':p'=>$hash]);
        $userId = (int)$pdo->lastInsertId();

        // ログイン状態にする
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['user_id'] = $userId;
        session_regenerate_id(true); // セッション固定化対策

        // ✅ 未登録モードCookieを削除
        setcookie('unregistered', '', time()-3600, '/');

        // ✅ 「登録済み」フラグをCookieに保持
        setcookie('has_account', '1', [
          'expires'  => time() + 60*60*24*365,
          'path'     => '/',
          'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
          'httponly' => false,
          'samesite' => 'Lax',
        ]);

        // ✅ 匿名利用データをこのユーザーに統合して引き継ぐ
        link_and_merge_current_anon_for_user($pdo, $userId);

        // ✅ 主ID（最古のanonymous_users）の anon_code をCookieに設定
        $primary = get_primary_anon_code_for_user($pdo, $userId);
        if ($primary) {
          setcookie('anon_code', $primary, time()+3600*24*365, '/');
          $_COOKIE['anon_code'] = $primary; // 即参照可能に
        }

        set_flash('アカウントを作成しました！');
        redirect('index.php');
      }
    } catch (Throwable $e) {
      $errors[] = '登録に失敗しました：' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Wellnoa - 新規登録</title>
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
    <h1>新規登録</h1>

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
        <label for="password">パスワード（8文字以上）</label>
        <input type="password" id="password" name="password" minlength="8" required>
      </div>
      <div class="field">
        <label for="password_confirm">パスワード（確認）</label>
        <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
      </div>
      <div class="field">
        <button class="btn" type="submit">登録してはじめる</button>
      </div>
    </form>

    <div class="links">
      <a href="login.php">すでにアカウントをお持ちですか？ ログイン</a>
    </div>
  </div>
</body>
</html>