<?php
// inc/notices.php（共通お知らせ＋フラッシュ統一）
if (!function_exists('pop_flash') || !function_exists('is_unregistered_mode')) {
  require_once __DIR__ . '/../funcs.php';
}
if (session_status() === PHP_SESSION_NONE) session_start();

$flash   = pop_flash();            // funcs.phpの _flash を1回だけ取得
$isUnreg = is_unregistered_mode(); // 未登録モード？
?>
<div class="notice-area">
  <?php if ($isUnreg): ?>
    <div class="notice notice-warn">
      未登録モードで利用中です。端末変更やQR紛失でデータが見られなくなる可能性があります。
      <a href="register.php" class="link-strong">登録する</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($flash['message'])): ?>
    <div class="notice notice-success">
      <?= h($flash['message']) ?>
    </div>
  <?php endif; ?>
</div>