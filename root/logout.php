<?php
// logout.php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
session_destroy();

// anon_code クッキーは残す（匿名として継続利用可能）
set_flash('ログアウトしました。');
redirect('index.php');