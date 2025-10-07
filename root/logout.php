<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
@session_destroy();

// 未登録モードCookieは消す（重要）
setcookie('unregistered', '', time()-3600, '/');

// has_account は残す（登録済みフラグは維持）
set_flash('ログアウトしました。', 'info');
redirect('index.php');