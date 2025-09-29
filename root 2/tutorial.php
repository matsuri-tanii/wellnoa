<?php
require_once __DIR__ . '/../secure/anon_bootstrap.php';
// g= があれば ensure_guest_id が cookie に保存済み
set_flash('ようこそ！この端末に匿名IDを保存しました。さっそく使い始めましょう。');
increment_action(1); // カウントも1回進める（任意）
header('Location: /index.php');
exit;