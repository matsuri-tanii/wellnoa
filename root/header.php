<?php
// inc/header.php
$flash = pop_flash();
$show_hint = pop_action_hint();
?>
<div style="max-width:960px;margin:10px auto 16px;padding:8px 12px;">
  <?php if ($flash): ?>
    <div style="padding:10px;border:1px solid #b2f2bb;background:#e6ffed;color:#2b8a3e;border-radius:6px;margin-bottom:8px;">
      <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($show_hint): ?>
    <div style="padding:10px;border:1px solid #ffe066;background:#fff9db;color:#704800;border-radius:6px;">
      継続ありがとうございます！アカウント登録をすると端末を変えても記録を引き継げます。<br>
      <a href="/register.php">メール/パスワードで登録</a> ｜ 
      <a href="/qr.php">QRで匿名IDを保存</a>
    </div>
  <?php endif; ?>
</div>