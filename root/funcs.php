<?php
declare(strict_types=1);

/**
 * funcs.php — Wellnoa 共通ユーティリティ（完全安定版）
 * - env.php 自動読込
 * - DB接続・ログ
 * - 匿名ユーザー管理（未登録でも自動作成）
 * - set_guest_cookie / mark_unregistered_mode 互換
 * - フラッシュ / リダイレクト
 * - SQLエラー補助
 * - 全関数を function_exists で定義 → エディタ警告ゼロ
 */

// ================================================================
// 1. env.php 読み込み
// ================================================================
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
    @ini_set('log_errors', '1');
    $logDir = __DIR__ . '/_logs';
    if (!is_dir($logDir)) mkdir($logDir, 0775, true);
    ini_set('error_log', $logDir . '/php_errors.log');
    error_log('env.php missing. tried: ' . implode(' | ', array_filter($__env_paths)));
    http_response_code(500);
    exit('env.php missing');
}

date_default_timezone_set('Asia/Tokyo');
if (session_status() === PHP_SESSION_NONE) session_start();

// ================================================================
// 2. ログ出力
// ================================================================
if (!function_exists('app_log')) {
    function app_log(string $msg): void {
        $dir = __DIR__ . '/_logs';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @file_put_contents($dir . '/app_trace.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND);
    }
}

// ================================================================
// 3. 基本ユーティリティ
// ================================================================
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(): string {
        if (defined('BASE_URL') && BASE_URL) return rtrim(BASE_URL, '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $pathOrUrl, int $code = 302): never {
        if (!preg_match('~^https?://~i', $pathOrUrl)) {
            $pathOrUrl = base_url() . '/' . ltrim($pathOrUrl, '/');
        }
        header('Location: ' . $pathOrUrl, true, $code);
        exit;
    }
}

// ================================================================
// 4. DB接続 / SQLヘルパ
// ================================================================
if (!function_exists('db_conn')) {
    function db_conn(): PDO {
        $i = sakura_db_info();
        $dsn  = 'mysql:host='.$i['db_host'].';dbname='.$i['db_name'].';charset=utf8mb4';
        return new PDO($dsn, $i['db_id'], $i['db_pw'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

if (!function_exists('sql_error')) {
    function sql_error(PDOStatement $stmt): never {
        $info = $stmt->errorInfo();
        throw new RuntimeException('SQL ERROR: ' . ($info[2] ?? 'unknown'));
    }
}

// ================================================================
// 5. フラッシュメッセージ
// ================================================================
if (!function_exists('set_flash')) {
    function set_flash(string $message, string $type = 'success'): void {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }
}
if (!function_exists('pop_flash')) {
    function pop_flash(): ?array {
        if (!empty($_SESSION['_flash'])) {
            $f = $_SESSION['_flash'];
            unset($_SESSION['_flash']);
            return $f;
        }
        return null;
    }
}

// ================================================================
// 6. 匿名ユーザー管理
// ================================================================
if (!function_exists('ensure_anon_identity')) {
    function ensure_anon_identity(PDO $pdo): array {
        // 1) Cookieの anon_code を取得または新規生成
        $code = $_COOKIE['anon_code'] ?? '';
        if ($code === '') {
            $code = bin2hex(random_bytes(16));
            setcookie('anon_code', $code, time()+3600*24*365, '/');
            $_COOKIE['anon_code'] = $code;
        }

        // 2) anonymous_id（7桁固定）を生成
        $anonId = (string)((hexdec(substr(md5($code), 0, 7)) % 9000000) + 1000000);

        // 3) anonymous_users に登録 or 更新
        $stmt = $pdo->prepare("
            INSERT INTO anonymous_users (anonymous_id, anon_code, total_points)
            VALUES (:aid, :c, 0)
            ON DUPLICATE KEY UPDATE anon_code = VALUES(anon_code)
        ");
        $stmt->execute([':aid' => $anonId, ':c' => $code]);

        // 4) 数値IDを取得してセッションに保存
        $uid = (int)$pdo->query("SELECT id FROM anonymous_users WHERE anon_code=" . $pdo->quote($code))->fetchColumn();
        $_SESSION['anon_uid'] = $uid;

        return [$uid, $code];
    }
}

if (!function_exists('current_anon_user_id')) {
    function current_anon_user_id(): ?int {
        if (!empty($_SESSION['anon_uid'])) return (int)$_SESSION['anon_uid'];
        $pdo = db_conn();
        [$uid, ] = ensure_anon_identity($pdo);
        return $uid ?: null;
    }
}

if (!function_exists('current_anon_code')) {
    function current_anon_code(): string {
        if (!empty($_COOKIE['anon_code'])) return $_COOKIE['anon_code'];
        $pdo = db_conn();
        [, $code] = ensure_anon_identity($pdo);
        return $code;
    }
}

if (!function_exists('adopt_incoming_code')) {
    function adopt_incoming_code(): void {
        if (!isset($_GET['code'])) return;
        $incoming = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$_GET['code']));
        if ($incoming === '') return;
        setcookie('anon_code', $incoming, time()+3600*24*365, '/');
        $_COOKIE['anon_code'] = $incoming;
    }
}

// ================================================================
// 7. 未登録モード / Cookie互換
// ================================================================
if (!function_exists('set_guest_cookie')) {
    function set_guest_cookie(?string $code = null): void {
        $val = $code ?? current_anon_code();
        setcookie('anon_code', $val, time()+3600*24*365, '/');
        $_COOKIE['anon_code'] = $val;
    }
}

if (!function_exists('mark_unregistered_mode')) {
    function mark_unregistered_mode(): void {
        setcookie('unregistered', '1', [
            'expires'  => time() + 60*60*24*365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['unregistered'] = '1';
    }
}

if (!function_exists('is_unregistered_mode')) {
    function is_unregistered_mode(): bool {
        if (!empty($_SESSION['user_id'])) return false; // ログイン中は未登録扱いにしない
        if (!empty($_COOKIE['has_account']) && $_COOKIE['has_account'] === '1') {
            return false; // ★登録済みフラグがあれば未登録扱いにしない
        }
        return isset($_COOKIE['unregistered']) && $_COOKIE['unregistered'] === '1';
    }
}

// ================================================================
// 8. 登録誘導ヒント
// ================================================================
if (!function_exists('pop_action_hint')) {
    function pop_action_hint(): bool {
        if (!is_unregistered_mode()) return false;
        $today = date('Y-m-d');
        if (($_SESSION['_hint_last_shown'] ?? '') === $today) return false;

        try {
            $cfg = require __DIR__.'/points_config.php';
            $limit = (int)($cfg['thresholds']['suggest_register_after'] ?? 5);

            $pdo = db_conn();
            $uid = current_anon_user_id();

            $logs  = (int)$pdo->query("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id=$uid")->fetchColumn();
            $reads = (int)$pdo->query("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id=$uid")->fetchColumn();

            if ($logs + $reads >= $limit) {
                $_SESSION['_hint_last_shown'] = $today;
                return true;
            }
        } catch (Throwable $e) { /* 無視 */ }
        return false;
    }
}

// ================================================================
// 9. ログインユーザー関連（将来用）
// ================================================================
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return !empty($_SESSION['user_id']);
    }
}
if (!function_exists('current_user_id')) {
    function current_user_id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
}
if (!function_exists('require_login')) {
    function require_login(): void {
        if (!is_logged_in()) { redirect('login.php'); }
    }
}
if (!function_exists('link_current_anon_to_user')) {
    function link_current_anon_to_user(PDO $pdo, int $userId): void {
        $code = current_anon_code();
        $sql = "INSERT IGNORE INTO user_anon_links(user_id, anon_code)
                VALUES(:uid, :code)";
        $st  = $pdo->prepare($sql);
        $st->execute([':uid'=>$userId, ':code'=>$code]);
    }
}

// ========== 匿名→登録の橋渡し & データ引き継ぎ ==========

if (!function_exists('get_anon_uid_by_code')) {
    function get_anon_uid_by_code(PDO $pdo, string $anonCode): ?int {
        $st = $pdo->prepare("SELECT id FROM anonymous_users WHERE anon_code=:c");
        $st->execute([':c'=>$anonCode]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }
}

if (!function_exists('ensure_user_anon_link')) {
    function ensure_user_anon_link(PDO $pdo, int $userId, string $anonCode): void {
        $sql = "INSERT IGNORE INTO user_anon_links(user_id, anon_code) VALUES(:uid, :code)";
        $st  = $pdo->prepare($sql);
        $st->execute([':uid'=>$userId, ':code'=>$anonCode]);
    }
}

/**
 * 同一ユーザーに紐付いた複数の匿名IDを「主ID」に統合する。
 * - 主ID: 最古(最小id)の anonymous_users.id
 * - 行動テーブルの anonymous_user_id を主IDへ更新
 * - total_points を合算し、主IDへ転記
 * - 余剰の anonymous_users 行は残しても良いが、重複参照を避けるため削除する実装にしている
 */
if (!function_exists('merge_anonymous_identities_for_user')) {
    function merge_anonymous_identities_for_user(PDO $pdo, int $userId): void {
        // 1) このユーザーに紐付いた anon_code 一覧
        $rows = $pdo->prepare("SELECT anon_code FROM user_anon_links WHERE user_id=:uid");
        $rows->execute([':uid'=>$userId]);
        $codes = $rows->fetchAll(PDO::FETCH_COLUMN);
        if (!$codes) return;

        // 2) コード→匿名ユーザーIDの一覧
        $in = implode(',', array_fill(0, count($codes), '?'));
        $st = $pdo->prepare("SELECT id, anon_code, total_points FROM anonymous_users WHERE anon_code IN ($in)");
        $st->execute($codes);
        $anonRows = $st->fetchAll();

        if (count($anonRows) <= 1) return; // 統合不要

        // 3) 主IDを決定（最小id）
        usort($anonRows, fn($a,$b)=>$a['id']<=>$b['id']);
        $primary = $anonRows[0];
        $primaryId = (int)$primary['id'];

        // 4) 合算ポイントを計算
        $sumPoints = 0;
        $ids = [];
        foreach ($anonRows as $r) {
            $sumPoints += (int)$r['total_points'];
            $ids[] = (int)$r['id'];
        }

        // 5) マージ（トランザクション）
        $pdo->beginTransaction();
        try {
            // 5-1) 行動テーブルを主IDに寄せる
            $tables = ['article_reads','daily_logs','cheers'];
            foreach ($tables as $t) {
                $sql = "UPDATE $t SET anonymous_user_id = :primaryId WHERE anonymous_user_id IN (".
                        implode(',', array_fill(0, count($ids), '?')).")";
                $st = $pdo->prepare($sql);
                $params = array_merge([':primaryId'=>$primaryId], $ids);
                // PDOは名前付き&位置指定を混ぜられないので分ける:
                $st = $pdo->prepare(
                    "UPDATE $t SET anonymous_user_id = ? WHERE anonymous_user_id IN (".
                    implode(',', array_fill(0, count($ids), '?')).")"
                );
                $st->execute(array_merge([$primaryId], $ids));
            }

            // 5-2) 主IDに合算ポイント反映
            $st = $pdo->prepare("UPDATE anonymous_users SET total_points=:pt WHERE id=:id");
            $st->execute([':pt'=>$sumPoints, ':id'=>$primaryId]);

            // 5-3) 余剰 anonymous_users を削除（主IDは残す）
            $others = array_values(array_diff($ids, [$primaryId]));
            if ($others) {
                $st = $pdo->prepare("DELETE FROM anonymous_users WHERE id IN (".
                        implode(',', array_fill(0, count($others), '?')).")");
                $st->execute($others);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

/**
 * ログイン直後/登録直後に呼ぶだけでOK:
 * - 現在の anon_code を user_anon_links に紐付け
 * - 同ユーザーに結びつく複数の匿名IDを統合
 */
if (!function_exists('link_and_merge_current_anon_for_user')) {
    function link_and_merge_current_anon_for_user(PDO $pdo, int $userId): void {
        $code = current_anon_code();      // Cookieのanon_code
        ensure_user_anon_link($pdo, $userId, $code);
        merge_anonymous_identities_for_user($pdo, $userId);
    }
}