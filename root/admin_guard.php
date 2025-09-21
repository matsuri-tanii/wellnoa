<?php
// 管理画面用ガード（全ての admin_* ページの先頭で include ）
session_start();
require_once __DIR__ . '/env.php';

if (
  !isset($_SESSION['admin_login']) ||
  $_SESSION['admin_login'] !== true
) {
  header('Location: admin_login.php');
  exit;
}