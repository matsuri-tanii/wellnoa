<?php
// 匿名利用用の最小ブートストラップ
// すべてのページの先頭で読み込んでください
session_start();

// 未発行なら匿名IDを発行（32桁のHEX）
function current_anon_user_id() {
    if (!isset($_SESSION['anonymous_user_id'])) {
        // ランダムなIDを生成（例: 100000〜999999の数字）
        $_SESSION['anonymous_user_id'] = random_int(100000, 999999);
    }
    return $_SESSION['anonymous_user_id'];
}
