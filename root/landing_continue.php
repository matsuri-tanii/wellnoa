<?php
// landing_continue.php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

// 匿名コードを確実に持たせる（無ければ発行）
$code = current_anon_code();

// 「未登録モード」クッキー（1年）
mark_unregistered_mode();

// 初回は使い方が分かりやすいよう index へ
// （チュートリアルを出したければ tutorial.php にしてOK）
redirect('index.php');