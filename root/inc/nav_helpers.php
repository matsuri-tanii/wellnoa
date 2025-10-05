<?php
// inc/nav_helpers.php
// どのページでも安全に読み込めるように関数は一度だけ定義

if (!function_exists('current_nav_active')) {
  function current_nav_active(): string {
    // 各ページで $nav_active = 'home' などをセットしてからナビを include してください
    return $GLOBALS['nav_active'] ?? '';
  }
}

if (!function_exists('nav_active_class')) {
  function nav_active_class(string $key, string $class = 'is-active'): string {
    return current_nav_active() === $key ? $class : '';
  }
}