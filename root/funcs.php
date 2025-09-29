<?php
// --- TEMP DEBUG (remove before production) ---
ini_set('log_errors', '1');
ini_set('error_log', '/home/wellnoa/www/php_error.log');
error_reporting(E_ALL);
// 本番では画面に出さない
ini_set('display_errors', '0');
// --- /TEMP DEBUG ---

// funcs.php — 共通関数
// ---------------------------------------------------------------

declare(strict_types=1);

session_start();

// タイムゾーン
date_default_timezone_set('Asia/Tokyo');

// セッション開始（多重開始エラー回避）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------
// 匿名ユーザーID（6桁の数字）をセッションで保証
// ---------------------------------------------------------------
function current_anon_user_id(): int {
    if (!isset($_SESSION['anonymous_user_id'])) {
        $_SESSION['anonymous_user_id'] = random_int(100000, 999999);
    }
    return (int) $_SESSION['anonymous_user_id'];
}

// ---------------------------------------------------------------
// env.php 読み込み（ローカル／本番両対応）
// ---------------------------------------------------------------
$envCandidates = [
    __DIR__ . '/../secure/env.php',   // ローカル（wellnoa/root/secure/env.php）
    __DIR__ . '/secure/env.php',      // 念のため
    '/home/wellnoa/secure/env.php',   // さくら本番
];
$envLoaded = false;
foreach ($envCandidates as $p) {
    if (is_readable($p)) {
        require_once $p;
        $envLoaded = true;
        break;
    }
}
if (!$envLoaded) {
    throw new RuntimeException("secure/env.php が見つかりません。");
}

// ---------------------------------------------------------------
// DB接続（PDO）
// ---------------------------------------------------------------
function db_conn(): PDO {
    try {
        // env.php 内の sakura_db_info() を呼び出し
        $info = sakura_db_info();
        $dsn = 'mysql:dbname='.$info['db_name'].';charset=utf8mb4;host='.$info['db_host'];
        return new PDO($dsn, $info['db_id'], $info['db_pw'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        exit('DB Connection Error: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------
// XSS対策用（出力時に利用）
// ---------------------------------------------------------------
if (!defined('ENT_QUOTES')) { define('ENT_QUOTES', 3); } // 一部古い環境対策

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// フラッシュメッセージ
// ---------------------------------------------------------------
function set_flash(string $message, string $type='success'): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['_flash'] = ['type'=>$type, 'message'=>$message];
}

function get_flash(): ?array {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

// ---------------------------------------------------------------
// 既存コード互換: current_anon_code が呼ばれても動くようにする
// ---------------------------------------------------------------
if (!function_exists('current_anon_code')) {
    function current_anon_code(): string {
        // まずは簡易に「数値IDを文字列として返す」運用
        return (string) current_anon_user_id();
    }
}
?>
