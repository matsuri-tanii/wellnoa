<?php
declare(strict_types=1);

/**
 * funcs.php — 共通ユーティリティ（決定版）
 * - env.php 自動読込（ローカル/本番）
 * - セッション/タイムゾーン初期化
 * - DB接続, XSSエスケープ, フラッシュ, 匿名ID(anon_code)管理
 * - 旧コード互換: ensure_anon_user(), set_guest_cookie()
 * - ヘッダー用: is_unregistered_mode(), pop_action_hint()
 */

/* ----------------------------------------------------------------
 * env.php を探して読み込む
 *  - ローカル:   __DIR__.'/../secure/env.php'
 *  - 本番(さくら): '/home/wellnoa/secure/env.php'
 * -------------------------------------------------------------- */
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
    // ログを残し、ユーザーには短いメッセージ
    @ini_set('log_errors', '1');
    $logDir = __DIR__ . '/_logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    @ini_set('error_log', $logDir . '/php_errors.log');
    error_log('env.php missing. tried: ' . implode(' | ', array_filter($__env_paths)));
    http_response_code(500);
    exit('env.php missing');
}

/* ----------------------------------------------------------------
 * 共通初期化
 * -------------------------------------------------------------- */
date_default_timezone_set('Asia/Tokyo');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ----------------------------------------------------------------
 * ユーティリティ
 * -------------------------------------------------------------- */
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** BASE_URL があれば優先、なければ現在ホストから作る */
function base_url(): string {
    if (defined('BASE_URL') && BASE_URL) return rtrim(BASE_URL, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/** 相対でも絶対でもOKなリダイレクト */
function redirect(string $pathOrUrl, int $code = 302): never {
    if (!preg_match('~^https?://~i', $pathOrUrl)) {
        $pathOrUrl = base_url() . '/' . ltrim($pathOrUrl, '/');
    }
    header('Location: ' . $pathOrUrl, true, $code);
    exit;
}

/** URL生成（お好みで） */
function url(string $path = ''): string {
    return base_url() . '/' . ltrim($path, '/');
}

/* ----------------------------------------------------------------
 * DB接続
 * -------------------------------------------------------------- */
function db_conn(): PDO {
    if (!function_exists('sakura_db_info')) {
        throw new RuntimeException('sakura_db_info() が env.php にありません。');
    }
    $i = sakura_db_info();
    $dsn  = 'mysql:host='.$i['db_host'].';dbname='.$i['db_name'].';charset=utf8mb4';
    $user = $i['db_id'];
    $pass = $i['db_pw'];
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

/** SQLエラーを例外化（必要箇所で使用） */
function sql_error(PDOStatement $stmt): never {
    $info = $stmt->errorInfo(); // [SQLSTATE, driver_code, message]
    throw new RuntimeException('SQL ERROR: ' . ($info[2] ?? 'unknown'));
}

/* ----------------------------------------------------------------
 * フラッシュメッセージ
 *  - set_flash('メッセージ', 'success|warning|error')
 *  - $flash = pop_flash(); // 取り出して消す（['type','message']）
 * -------------------------------------------------------------- */
function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}
function pop_flash(): ?array {
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/* ----------------------------------------------------------------
 * 匿名コード（Cookie: anon_code）
 *  - 端末をまたいで同一人物として扱う用
 *  - 未設定なら 12桁HEX を自動発行
 * -------------------------------------------------------------- */
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
            'expires'  => time() + 60*60*24*365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['anon_code'] = $code;
    }
    return $code;
}

/** ?code=xxx で来たら Cookie を上書き */
function adopt_incoming_code(): void {
    if (!isset($_GET['code'])) return;
    $incoming = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$_GET['code']));
    if ($incoming === '') return;
    setcookie('anon_code', $incoming, [
        'expires'  => time() + 60*60*24*365,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['anon_code'] = $incoming;
}

/**
 * 数値の匿名ID（旧コード互換が必要な場面向け）
 * - anon_code から決定的に 100000〜999999 を生成
 */
function current_anon_user_id(): int {
    $code = current_anon_code();
    $n = hexdec(substr(md5($code), 0, 6)) % 900000 + 100000;
    return $n;
}

/** 旧：ensure_anon_user() 互換（呼ぶだけで数値IDが確実に使える） */
function ensure_anon_user(): int {
    return current_anon_user_id();
}

if (!function_exists('current_guest_id')) {
    function current_guest_id(): string {
        return current_anon_code(); // 旧名称の互換
    }
}

function set_guest_cookie(?string $code = null): void {
    // 引数がなければ「今の anon_code」をそのまま再セットするだけ
    $val = $code ?? current_anon_code();
    setcookie('anon_code', $val, [
        'expires'  => time() + 60*60*24*365,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['anon_code'] = $val;
}

/* ----------------------------------------------------------------
 * 未登録モード/登録誘導（ヘッダー用）
 * -------------------------------------------------------------- */

/** 未登録モードの簡易フラグを付ける（ランディングで“登録せずに使う”を選んだ時など） */
function mark_unregistered_mode(): void {
    setcookie('unregistered', '1', [
        'expires'  => time() + 60*60*24*365, // 1年
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['unregistered'] = '1';
}

/** 未登録モード判定（将来は正式ログイン判定で置き換え可） */
function is_unregistered_mode(): bool {
    // 例：将来は $_SESSION['user_id'] 有無で判定
    return isset($_COOKIE['unregistered']) && $_COOKIE['unregistered'] === '1';
}

/**
 * 登録誘導ヒントを出すか？
 * - 未登録モードの時のみ
 * - きろく数 + 読了数 が 5 以上
 * - 1日1回まで
 */
function pop_action_hint(): bool {
    if (!is_unregistered_mode()) return false;

    $today = date('Y-m-d');
    if (($_SESSION['_hint_last_shown'] ?? '') === $today) return false;

    try {
        $pdo = db_conn();
        $uid = current_anon_user_id();

        $st1 = $pdo->prepare('SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid');
        $st1->execute([':uid'=>$uid]);
        $logs = (int)$st1->fetchColumn();

        $st2 = $pdo->prepare('SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid');
        $st2->execute([':uid'=>$uid]);
        $reads = (int)$st2->fetchColumn();

        if ($logs + $reads >= 5) {
            $_SESSION['_hint_last_shown'] = $today;
            return true;
        }
    } catch (Throwable $e) {
        // 失敗しても黙って非表示（致命的にしない）
    }
    return false;
}

function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}
function current_user_id(): ?int {
  return $_SESSION['user_id'] ?? null;
}
function require_login(): void {
  if (!is_logged_in()) { redirect('login.php'); }
}

/** 登録直後・ログイン直後に、今の anon_code を自分にひも付け */
function link_current_anon_to_user(PDO $pdo, int $userId): void {
  $code = current_anon_code(); // いまのcookie
  $sql = "INSERT IGNORE INTO user_anon_links(user_id, anon_code) VALUES(:uid, :code)";
  $st  = $pdo->prepare($sql);
  $st->execute([':uid'=>$userId, ':code'=>$code]);
}