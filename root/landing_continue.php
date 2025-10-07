<?php
// landing_continue.php（匿名ではじめる専用）
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

// すでにログイン済みなら戻す
if (is_logged_in()) redirect('index.php');

// 登録済みフラグがある人がここに来た場合、未登録バナーは出したくないなら ↓の行をスキップ
if (empty($_COOKIE['has_account'])) {
  mark_unregistered_mode(); // 登録済みじゃない人だけ未登録モードcookieを立てる
}

redirect('index.php');