<?php
// inc/header.php（共通お知らせ＋ヘッダーナビ）
if (!function_exists('pop_flash') || !function_exists('pop_action_hint') || !function_exists('is_unregistered_mode')) {
  require_once __DIR__ . '/../funcs.php';
}

$flash     = pop_flash();            // 1回出たら消える
$show_hint = pop_action_hint();      // 登録誘導ヒント（条件満たしたら1日1回）
$isUnreg   = is_unregistered_mode(); // 未登録モード？
?>
<!-- 共通お知らせエリア -->
<div class="notice-area">
  <?php if ($isUnreg): ?>
    <div class="notice notice-warn">
      未登録モードで利用中です。端末変更やQR紛失でデータが見られなくなる可能性があります。
      <a href="register.php" class="link-strong">登録する</a>
    </div>
  <?php endif; ?>

  <?php if ($flash): ?>
    <div class="notice notice-success">
      <?= htmlspecialchars($flash['msg'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($show_hint): ?>
    <div class="notice notice-hint">
      継続ありがとうございます！アカウント登録をすると端末を変えても記録を引き継げます。<br>
      <a href="/register.php">メール/パスワードで登録</a> ｜ 
      <a href="/qr.php">QRで匿名IDを保存</a>
    </div>
  <?php endif; ?>
</div>

<!-- 上部ヘッダー（PC/タブレットで表示、モバイルでは非表示） -->
<header class="site-header" role="banner" aria-label="アプリヘッダー">
  <div class="site-header__inner">
    <div class="brand">
      <a href="index.php" class="brand__link">
        <img src="images/title_logo.png" alt="Wellnoa ロゴ" width="380" />
      </a>
      <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
    </div>
  </div>
</header>