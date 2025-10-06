<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';
session_start();

$token = (string)($_GET['token'] ?? '');
if ($token === '') { http_response_code(400); exit('トークンがありません'); }

// CSRF
if (empty($_SESSION['csrf_rp'])) { $_SESSION['csrf_rp'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf_rp'];

$pdo = db_conn();
$hash = hash('sha256', $token);

// トークン検証（期限内）
$sql = "SELECT id FROM users WHERE reset_token_hash = :h AND reset_expires > NOW() LIMIT 1";
$st  = $pdo->prepare($sql);
$st->execute([':h' => $hash]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { http_response_code(400); exit('このリンクは無効か、期限切れです。'); }

// POST（更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf_rp'] ?? '')) {
    http_response_code(400); exit('invalid csrf');
  }
  $p1 = (string)($_POST['new_pass'] ?? '');
  $p2 = (string)($_POST['new_pass2'] ?? '');

  // 簡易バリデーション
  $err = null;
  if ($p1 === '' || $p2 === '')             $err = 'パスワードを入力してください。';
  elseif ($p1 !== $p2)                       $err = '確認用パスワードが一致しません。';
  elseif (mb_strlen($p1) < 8)                $err = '8文字以上で設定してください。';

  if (!$err) {
    $hashPw = password_hash($p1, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password=:pw, reset_token_hash=NULL, reset_expires=NULL WHERE id=:id");
    $upd->execute([':pw'=>$hashPw, ':id'=>(int)$user['id']]);

    set_flash('パスワードを更新しました。新しいパスワードでログインしてください。', 'success');
    header('Location: login.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>パスワード再設定</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
  <div class="layout">
    <?php require __DIR__.'/inc/header.php'; ?>

    <main class="main">
      <div class="auth-card">
        <h1>パスワード再設定</h1>
        <p>新しいパスワードを入力してください。</p>

        <?php if (!empty($err)): ?>
          <div class="errors"><?= h($err) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <div class="field">
            <label for="np">新しいパスワード（8文字以上）</label>
            <input id="np" type="password" name="new_pass" required minlength="8" autocomplete="new-password">
          </div>
          <div class="field">
            <label for="np2">確認のため再入力</label>
            <input id="np2" type="password" name="new_pass2" required minlength="8" autocomplete="new-password">
          </div>
          <button class="btn">更新する</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>