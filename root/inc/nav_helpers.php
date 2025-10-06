<?php
if (!function_exists('current_nav_active')) {
  function current_nav_active(): string {
    return $GLOBALS['nav_active'] ?? '';
  }
}

if (!function_exists('nav_active_class')) {
  function nav_active_class(string $key, string $class = 'is-active'): string {
    return current_nav_active() === $key ? $class : '';
  }
}