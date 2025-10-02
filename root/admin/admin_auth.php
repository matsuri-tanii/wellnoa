<?php
require_once __DIR__.'/secure/env.php';

function admin_try_login(string $user, string $pass): bool {
    if ($user !== ADMIN_USER) return false;
    if (!password_verify($pass, ADMIN_PASSWORD_HASH)) return false;

    // OKならログイン状態にする
    admin_login();
    return true;
}