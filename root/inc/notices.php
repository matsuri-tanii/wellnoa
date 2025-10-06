<?php
// 共通：必要な関数が無ければ読み込む
if (!function_exists('pop_flash') || !function_exists('is_unregistered_mode') || !function_exists('h')) {
  require_once __DIR__ . '/../funcs.php';
}
if (session_status() === PHP_SESSION_NONE) session_start();

// ページ側で $suppress_register_nudge = true; を事前にセットすれば未登録バナーを非表示にできる
$hideRegisterNudge = !empty($suppress_register_nudge);

// フラッシュ（成功/失敗などの一時メッセージ）
$flash = function_exists('pop_flash') ? pop_flash() : null;
$flashMsg  = $flash['message'] ?? ($flash['msg'] ?? '');
$flashType = $flash['type']    ?? 'info'; // 'success' | 'info' | 'warn' | 'error' など

// 未登録モード判定
$isUnreg = function_exists('is_unregistered_mode') ? is_unregistered_mode() : false;
?>
<div class="notice-area">
  <?php if ($flashMsg !== ''): ?>
    <div class="notice notice-<?= h($flashType) ?>">
      <?= h($flashMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($isUnreg && !$hideRegisterNudge): ?>
    <div class="notice notice-warn">
      今は未登録モードで利用中です。端末変更やQR紛失でデータが見られなくなる可能性があります。
      <a href="register.php" class="link-strong">登録する</a>
      <button type="button" class="btn btn-sm" style="margin-left:8px"
        onclick="this.parentElement.style.display='none';document.cookie='dismiss_reg=1; path=/; max-age=2592000'">
        閉じる
      </button>
    </div>
  <?php endif; ?>
</div>