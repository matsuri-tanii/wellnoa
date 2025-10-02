<?php
// admin_guard.php
declare(strict_types=1);

require_once __DIR__.'/admin_csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 管理者ログイン中か？
 */
function admin_is_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * ログイン必須。未ログインならログイン画面へ。
 */
function admin_require(): void {
    if (!admin_is_logged_in()) {
        header('Location: admin_login.php');
        exit;
    }
}

/**
 * ログインする（admin_login.php から使用）
 */
function admin_login(): void {
    $_SESSION['admin_logged_in'] = true;
}

/**
 * ログアウト（admin_logout.php から使用）
 */
function admin_logout(): void {
    $_SESSION['admin_logged_in'] = false;
    unset($_SESSION['admin_logged_in']);
    session_regenerate_id(true);
}