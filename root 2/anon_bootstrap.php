<?php
<?php
require_once __DIR__ . '/../secure/anon_bootstrap.php';
// 既存の env.php や funcs.php などもこの後でOK
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__ . '/funcs.php';
// secure/anon_bootstrap.php
session_start();

/**
 * 1) フラッシュメッセージ（1回だけ表示）
 */
function set_flash($msg, $type='success') {
    $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type];
}
function pop_flash(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/**
 * 2) 匿名ID（cookie: guest_id）
 * - 既に cookie があればそれを使用
 * - URLに ?g=xxxx があれば「配布QR」からの来訪として上書き＆保存
 * - 未所持なら新規発行（6桁〜9桁の数値）して保存
 */
function ensure_guest_id(): int {
    if (isset($_GET['g']) && preg_match('/^\d{6,12}$/', $_GET['g'])) {
        $gid = (int)$_GET['g'];
        setcookie('guest_id', (string)$gid, time()+60*60*24*365, '/');
        $_COOKIE['guest_id'] = (string)$gid; // 今リクエストでも使えるよう反映
        return $gid;
    }
    if (!empty($_COOKIE['guest_id']) && ctype_digit($_COOKIE['guest_id'])) {
        return (int)$_COOKIE['guest_id'];
    }
    // 新規発行
    $gid = random_int(100000, 999999);
    setcookie('guest_id', (string)$gid, time()+60*60*24*365, '/');
    $_COOKIE['guest_id'] = (string)$gid;
    return $gid;
}

/**
 * 3) 行動カウント（cookie: act_cnt）
 * - 登録/投稿/閲覧などのイベント時に increment_action()
 * - 5回以上になったら pop_action_hint() が true → 登録誘導表示
 */
function current_action_count(): int {
    return isset($_COOKIE['act_cnt']) && ctype_digit($_COOKIE['act_cnt']) ? (int)$_COOKIE['act_cnt'] : 0;
}
function increment_action(int $step=1): int {
    $cnt = max(0, current_action_count()) + max(0,$step);
    setcookie('act_cnt', (string)$cnt, time()+60*60*24*365, '/');
    $_COOKIE['act_cnt'] = (string)$cnt;
    return $cnt;
}
function pop_action_hint(): bool {
    // 5回以上で一度だけ強めに誘導（セッションで1回制御）
    if (!isset($_SESSION['reg_hint_shown'])) {
        if (current_action_count() >= 5) {
            $_SESSION['reg_hint_shown'] = 1;
            return true;
        }
    }
    return false;
}

// 起動時に必ず匿名IDを確保
$CURRENT_GID = ensure_guest_id();