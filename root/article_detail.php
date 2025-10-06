<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();
$pdo = db_conn();

$uid       = current_anon_user_id();
$articleId = (int)($_GET['id'] ?? 0);

// すでに読了しているか？（過去いつでもOK判定）
$st = $pdo->prepare("
  SELECT 1
  FROM article_reads
  WHERE anonymous_user_id = :uid
    AND article_id = :aid
  LIMIT 1
");
$st->execute([':uid'=>$uid, ':aid'=>$articleId]);
$isRead = (bool)$st->fetch();

// GETでarticle_idを受け取る
if (!isset($_GET['id']) || $_GET['id'] === '') {
    exit('ParamError');
}
$id = $_GET['id'];

// 記事データ取得
$sql = 'SELECT * FROM articles WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status === false) {
    sql_error($stmt);
} else {
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?= h($article['title']) ?></title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/page-overrides.css">
</head>
<body>
  <div class="layout">
    <!-- 1) 常時表示ヘッダー 共通お知らせ（未登録警告・フラッシュ・登録誘導） -->
    <?php require __DIR__.'/inc/header.php'; ?>
    <!-- 2) サイドナビ（PC/タブ横のみCSSで表示） -->
    <aside class="side-nav">
      <?php require __DIR__.'/inc/side_nav.php'; ?>
    </aside>
    <!-- 3) メイン -->
    <main class="main article-main">
      <?php require __DIR__.'/inc/notices.php'; ?>
      <!-- ここにバッジを“上部”表示 -->
      <div class="read-badge" id="readBadge"
          data-state="<?= $isRead ? 'done' : 'notyet' ?>"
          aria-live="polite">
        <span class="dot" aria-hidden="true"></span>
        <span class="label"><?= $isRead ? '読了' : '未読' ?></span>
      </div>

      <h1 style="word-break:break-word;"><?= h($article['title']) ?></h1>

      <p class="article-meta">
        カテゴリ：<?= h($article['category']) ?> ｜ 公開日：<?= h($article['published_at']) ?>
        <?php if (!empty($article['source_name'])): ?>
          ｜ 出典：<?= h($article['source_name']) ?>
        <?php endif; ?>
      </p>

      <?php if (!empty($article['thumbnail_url'])): ?>
        <img class="article-thumb" src="<?= h($article['thumbnail_url']) ?>" alt="" referrerpolicy="no-referrer" loading="lazy" decoding="async">
      <?php endif; ?>

      <p><?= nl2br(h($article['description'])) ?></p>

      <?php if (!empty($article['url'])): ?>
        <p><a class="btn btn-outline" href="<?= h($article['url']) ?>" target="_blank" rel="noopener">
          元記事<?= !empty($article['source_name']) ? '（'.h($article['source_name']).'）' : '' ?>を開く
        </a></p>
      <?php endif; ?>

      <!-- 本文内の戻るボタン群（フッターとは別に “本文の終わり” にも設置） -->
      <div class="article-actions">
        <a class="btn" href="articles.php"><img src="images/book.png" alt="">記事一覧へ戻る</a>
      </div>

      <!-- スクロール判定スペーサ（読了検知に利用） -->
      <div id="bottom" class="page-bottom-spacer" style="height:40vh;"></div>

      <!-- 読了トースト（そのままでOK） -->
      <div id="toast" class="toast" aria-live="polite"></div>
    </main>

    <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>

  <script src="js/article-read.js"></script>
  <script>
  (function(){
    const footer = document.querySelector('footer.app-footer');
    const toast  = document.getElementById('toast');

    function adjustToastBottom(){
      if (!toast) return;
      const fh = footer ? footer.getBoundingClientRect().height : 0;
      const safe = (window.visualViewport ? 0 : 0); // 必要なら端末ごとに追加
      toast.style.bottom = (fh + 16 + safe) + 'px';
    }

    window.addEventListener('load', adjustToastBottom);
    window.addEventListener('resize', adjustToastBottom);
  })();
  </script>
</body>
</html>