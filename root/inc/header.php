<?php
// inc/header.php
if (!function_exists('pop_flash') || !function_exists('is_unregistered_mode') || !function_exists('is_logged_in')) {
  require_once __DIR__ . '/../funcs.php';
}

$flash     = pop_flash();
$isUnreg   = is_unregistered_mode(); // 未登録（匿名利用）
$loggedIn  = function_exists('is_logged_in') ? is_logged_in() : false;
?>
<header class="site-header" role="banner" aria-label="アプリヘッダー">
  <div class="site-header__inner">
    <div class="brand">
      <a href="index.php" class="brand__link">
        <img src="images/title_logo.png" alt="Wellnoa ロゴ" width="380" />
      </a>
      <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
    </div>

    <!-- 右上アクション -->
    <div class="header-actions" aria-label="アカウント状態と操作">
      <?php if ($isUnreg): ?>
        <span class="badge badge-warn" title="未登録モード"><span class="hide-sm">未登録モード</span></span>
        <a class="btn btn-sm btn-strong" href="register.php">登録</a>
        <a class="link-qr" href="qr.php" title="匿名IDのQR">QR</a>
      <?php elseif ($loggedIn): ?>
        <span class="badge badge-ok" title="ログイン中"><span class="hide-sm">ログイン中</span></span>
        <a class="btn btn-sm btn-outline" href="logout.php">ログアウト</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline" href="login.php">ログイン</a>
        <a class="btn btn-sm btn-strong" href="register.php">登録</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash" role="status" aria-live="polite">
      <?= htmlspecialchars($flash['msg'] ?? ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
</header>