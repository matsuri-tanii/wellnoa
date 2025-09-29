<?php
// admin_auth.php
session_start();
require_once __DIR__ . '/secure/env.php';

function require_admin() {
  if (empty($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    // 未ログインならログインページへ
    header('Location: admin_login.php');
    exit;
  }
}