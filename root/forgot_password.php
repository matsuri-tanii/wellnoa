<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';
session_start();
adopt_incoming_code();

// CSRF
if (empty($_SESSION['csrf_fp'])) {
  $_SESSION['csrf_fp'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_fp'];

// ここでは pop_flash を呼ばない（本文内で1回だけ表示する）
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>パスワード再発行</title>
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
        <h1>パスワード再発行</h1>
        <p>ご登録のメールアドレスを入力してください。再設定用のリンクをお送りします。</p>

        <?php $flash = pop_flash(); if ($flash): ?>
          <div class="notice notice-<?= h($flash['type'] ?? 'info') ?>">
            <?= h($flash['message'] ?? ($flash['msg'] ?? '')) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="send_reset_mail.php" class="form">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
          <div class="field">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" required autocomplete="email">
          </div>
          <button class="btn">送信する</button>
          <div class="links" style="margin-top:10px;">
            <a href="login.php">ログインに戻る</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>