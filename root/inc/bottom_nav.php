<?php
if (!function_exists('nav_on')) {
  function nav_on(string $file){ return basename($_SERVER['PHP_SELF']) === $file ? ' is-active' : ''; }
}
?>
<nav class="bottom-nav" aria-label="アプリの主要ナビ">
  <a class="bottom-link<?= nav_on('index.php') ?>" href="index.php">
    <img src="images/home.png" alt="" width="24"> <div>ホーム</div>
  </a>

  <!-- “記録”はボトムからは隠す（モバイルは右下FABで代替） -->
  <a class="bottom-link bottom-link--record<?= nav_on('record_hub.php') ?>" href="record_hub.php">
    <img src="images/memo.png" alt="" width="24"> <div>記録</div>
  </a>

  <a class="bottom-link<?= nav_on('articles.php') ?>" href="articles.php">
    <img src="images/book.png" alt="" width="24"> <div>記事</div>
  </a>
  <a class="bottom-link<?= nav_on('points.php') ?>" href="points.php">
    <img src="images/plants.png" alt="" width="24"> <div>成長</div>
  </a>
</nav>

<!-- 右下に浮かぶ“記録する”ボタン（モバイルのみ表示） -->
  <a href="record_hub.php" class="fab-chip" aria-label="記録する">
    <img src="images/memo.png" alt="" width="20" height="20">
    <span class="fab-text">記録する</span>
  </a>