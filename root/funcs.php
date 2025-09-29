<?php
declare(strict_types=1);

/**
 * secure/env.php を探して読み込む
 * - ローカル:   __DIR__.'/../secure/env.php'
 * - 本番(さくら): '/home/wellnoa/secure/env.php'
 */
$__env_paths = [
    __DIR__ . '/../secure/env.php',
    '/home/wellnoa/secure/env.php',
    (($_SERVER['HOME'] ?? '') . '/secure/env.php'),
];
$__env_loaded = false;
foreach ($__env_paths as $p) {
    if ($p && is_readable($p)) {
        require_once $p;
        $__env_loaded = true;
        break;
    }
}
if (!$__env_loaded) {
    // ログだけ残し、ユーザーには短いエラー
    @ini_set('log_errors', '1');
    @ini_set('error_log', __DIR__ . '/_logs/php_errors.log');
    if (!is_dir(__DIR__ . '/_logs')) @mkdir(__DIR__ . '/_logs', 0775, true);
    error_log('env.php missing. tried: ' . implode(' | ', array_filter($__env_paths)));
    http_response_code(500);
    exit('env.php missing');
}

/* ---- 共通初期化 ---- */
date_default_timezone_set('Asia/Tokyo');
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // フラッシュメッセージ等で利用
}

/* ---- ユーティリティ ---- */
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function base_url(): string {
    if (defined('BASE_URL') && BASE_URL) {
        return rtrim(BASE_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function redirect(string $pathOrUrl, int $code = 302): never {
    if (!preg_match('~^https?://~i', $pathOrUrl)) {
        $pathOrUrl = base_url() . '/' . ltrim($pathOrUrl, '/');
    }
    header('Location: ' . $pathOrUrl, true, $code);
    exit;
}

/* ---- DB接続 ---- */
function db_conn(): PDO {
    if (!function_exists('sakura_db_info')) {
        throw new RuntimeException('sakura_db_info() が env.php にありません。');
    }
    $i = sakura_db_info();
    $dsn  = 'mysql:host=' . $i['db_host'] . ';dbname=' . $i['db_name'] . ';charset=utf8mb4';
    $user = $i['db_id'];
    $pass = $i['db_pw'];
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

/* ---- フラッシュメッセージ ---- */
function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}
function get_flash(): ?array {
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/* ---- 匿名コード（cookie: anon_code） ----
 * - 端末を跨いでも同一人物として扱いたい要件に対応
 * - 未設定なら 12桁HEX を自動発行
 */
function current_anon_code(): string {
    $code = $_COOKIE['anon_code'] ?? '';
    $code = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$code));
    if ($code === '') {
        try {
            $code = bin2hex(random_bytes(6)); // 12桁HEX
        } catch (Throwable $e) {
            $code = substr(strtolower(md5(uniqid('', true))), 0, 12);
        }
        setcookie('anon_code', $code, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['anon_code'] = $code; // 同一リクエストで参照可
    }
    return $code;
}

/* ---- ?code=xxx を受けたときに cookie を上書きする補助 ----
 * 各ページの先頭で adopt_incoming_code(); を一行呼ぶだけでOK
 */
function adopt_incoming_code(): void {
    if (!isset($_GET['code'])) return;
    $incoming = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$_GET['code']));
    if ($incoming === '') return;
    setcookie('anon_code', $incoming, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['anon_code'] = $incoming;
}

/* ---- 互換：古いコードで呼ばれている関数名に合わせる ---- */
if (!function_exists('current_guest_id')) {
    function current_guest_id(): string { return current_anon_code(); }
}
if (!function_exists('current_anon_user_id')) {
    // 互換用（必要なら6桁数値が欲しい場面で使う：anon_codeから決定的に生成）
    function current_anon_user_id(): int {
        $code = current_anon_code();
        $n = hexdec(substr(md5($code), 0, 6)) % 900000 + 100000; // 100000〜999999
        return $n;
    }
}

if (!function_exists('sql_error')) {
    function sql_error($stmt): void {
        if ($stmt instanceof PDOStatement) {
            $info = $stmt->errorInfo(); // [SQLSTATE, driver_code, message]
            throw new RuntimeException('SQL ERROR: '.($info[2] ?? 'unknown'));
        }
        throw new RuntimeException('SQL ERROR (no statement)');
    }
}

/** フラッシュを“取り出して消す”簡易API（古いテンプレ互換） */
if (!function_exists('pop_flash')) {
    function pop_flash(): ?array {
        return get_flash();
    }
}
/** 名前違いの互換（header.php などで使用） */
if (!function_exists('pop_action_hint')) {
    function pop_action_hint(): ?array {
        return get_flash();
    }
}

/** セッションに匿名ユーザーを必ず持たせる（旧 ensure_anon_user 互換） */
if (!function_exists('ensure_anon_user')) {
    function ensure_anon_user(): int {
        return current_anon_user_id(); // 6桁数値を発行・保持
    }
}

/** クッキーに匿名コードを保存（旧 set_guest_cookie 互換） */
if (!function_exists('set_guest_cookie')) {
    function set_guest_cookie(?string $code = null): void {
        // 既定はセッションID（数値）を文字列化して使う
        $val = $code ?? (string) current_anon_user_id();
        setcookie('anon_code', $val, [
            'expires'  => time() + 60*60*24*365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['anon_code'] = $val; // 直後のリクエストでも参照できるように
    }
}

/** ポイント計算（points.php 互換）
 *  素朴な例: 記事既読=1pt, 日記=3pt, チア=1pt
 *  必要なら重みは調整してください
 */
if (!function_exists('calc_points_for_user')) {
    function calc_points_for_user(PDO $pdo, int $uid): array {
        // article_reads: anonymous_user_id
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid');
        $stmt->execute([':uid'=>$uid]);
        $reads = (int) $stmt->fetchColumn();

        // daily_logs: anonymous_user_id
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid');
        $stmt->execute([':uid'=>$uid]);
        $logs = (int) $stmt->fetchColumn();

        // cheers: anonymous_user_id（存在しないなら0扱い）
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM cheers WHERE anonymous_user_id = :uid');
            $stmt->execute([':uid'=>$uid]);
            $cheers = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $cheers = 0;
        }

        $points = $reads*1 + $logs*3 + $cheers*1;

        return [
            'reads'  => $reads,
            'logs'   => $logs,
            'cheers' => $cheers,
            'total'  => $points,
        ];
    }
}

/** URLヘルパ（未定義警告の抑止と利便性） */
if (!function_exists('base_url')) {
    function base_url(): string {
        if (defined('BASE_URL')) return rtrim(BASE_URL, '/');
        $sch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $sch.'://'.$host;
    }
}
if (!function_exists('url')) {
    function url(string $path = ''): string {
        return base_url().'/'.ltrim($path, '/');
    }
}

/* ---- 画面用の軽い通知（任意） ---- */
function pop_action_hint(?string $msg): void {
    if (!$msg) return;
    echo '<div style="margin:12px 0;padding:10px;background:#F0FFF4;border:1px solid #9AE6B4;color:#22543D;border-radius:6px;">'
       . h($msg) . '</div>';
}