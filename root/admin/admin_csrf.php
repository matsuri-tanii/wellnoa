<?php
declare(strict_types=1);

/**
 * 管理画面用 CSRF/Origin ヘルパ
 * - トークン発行:   admin_csrf_token(): string
 * - トークン検証:   admin_csrf_check(string $token): void（NGなら 400 で終了）
 * - 同一オリジン:   admin_same_origin_check(): bool
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** CSRFトークンを取得（無ければ発行） */
function admin_csrf_token(): string {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

/** CSRFトークンを検証（NGなら 400 を返して終了） */
function admin_csrf_check(?string $token): void {
    $ok = is_string($token)
        && isset($_SESSION['admin_csrf'])
        && hash_equals($_SESSION['admin_csrf'], $token);
    if (!$ok) {
        http_response_code(400);
        exit('Bad Request: invalid CSRF token');
    }
}

/** 同一オリジンチェック（Origin 優先、無ければ Referer で判定） */
function admin_same_origin_check(): bool {
    // 許容オリジン（ホスト名）をサーバから取得
    $serverHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($serverHost === '') return false; // ホスト不明なら弾く

    // まず Origin で判定
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $o = parse_url($origin);
        if (!is_array($o) || empty($o['host'])) return false;
        // スキームも基本 https を期待
        $schemeOk = !empty($o['scheme']) && in_array(strtolower($o['scheme']), ['https','http'], true);
        return $schemeOk && strcasecmp($o['host'], parse_url(('http://'.$serverHost), PHP_URL_HOST) ?: $serverHost) === 0;
    }

    // 次に Referer で判定
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $r = parse_url($ref);
        if (!is_array($r) || empty($r['host'])) return false;
        return strcasecmp($r['host'], parse_url(('http://'.$serverHost), PHP_URL_HOST) ?: $serverHost) === 0;
    }

    // どちらもない（古い環境/手作りクライアント等）→ 厳格に false
    return false;
}