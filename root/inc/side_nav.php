<?php
if (!function_exists('nav_on')) {
  function nav_on(string $file){ return basename($_SERVER['PHP_SELF']) === $file ? ' is-active' : ''; }
}
?>
<nav aria-label="サイドナビ">
  <ul>
    <li><a class="nav-link<?= nav_on('index.php') ?>" href="index.php"><img src="images/home.png" width="20" alt="">ホーム</a></li>
    <li><a class="nav-link<?= nav_on('record_hub.php') ?>" href="record_hub.php"><img src="images/memo.png" width="20" alt="">記録を書く・見る</a></li>
    <li><a class="nav-link<?= nav_on('articles.php') ?>" href="articles.php"><img src="images/book.png" width="20" alt="">記事を読む</a></li>
    <li><a class="nav-link<?= nav_on('points.php') ?>" href="points.php"><img src="images/plants.png" width="20" alt="">成長を見る</a></li>
    <li style="margin-top:6px; opacity:.8">— コミュニティ —</li>
    <li><a class="nav-link<?= nav_on('read_all.php') ?>" href="read_all.php"><img src="images/ouen.png" width="20" alt="">みんなの記録（応援）</a></li>
  </ul>
</nav>