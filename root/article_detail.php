<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();
$pdo = db_conn();

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
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
    img { max-width: 100%; height: auto; }
    footer {
      position: fixed;
      bottom: 0;
      width: 100%;
    }
    .footerMenuList {
      background-color: #a7d7c5;
      padding: 5px;
      display: flex;
      justify-content: space-between;
    }
    .btn{
      display: inline-block;
    }
    .btn img{
      display: block;
    }
  </style>
</head>
<body>
  <div id="toast" class="toast" style="display:none;"></div>

<style>
.toast{
  position: fixed;
  left: 50%;
  bottom: 24px;
  transform: translateX(-50%);
  background: rgba(0,0,0,.8);
  color:#fff;
  padding:10px 14px;
  border-radius:8px;
  box-shadow:0 6px 20px rgba(0,0,0,.2);
  z-index: 9999;
  opacity:0;
  transition: opacity .3s ease, transform .3s ease;
}
.toast.show{
  display:block;
  opacity:1;
  transform: translateX(-50%) translateY(0);
}
</style>

<header class="app-header">
    <img class="app-logo" src="images/title_logo.png" alt="Wellnoa" width="320">
    <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
  </header>

  <main class="container article-main">
    <h1><?= h($article['title']) ?></h1>
    <p class="article-meta">
      カテゴリ：<?= h($article['category']) ?> ｜ 公開日：<?= h($article['published_at']) ?>
      <?php if (!empty($article['source_name'])): ?>
        ｜ 出典：<?= h($article['source_name']) ?>
      <?php endif; ?>
    </p>
    <?php if (!empty($article['thumbnail_url'])): ?>
      <img class="article-thumb" src="<?= h($article['thumbnail_url']) ?>" alt="">
    <?php endif; ?>
    <p><?= nl2br(h($article['description'])) ?></p>

    <?php if (!empty($article['url'])): ?>
      <p><a class="btn btn-outline" href="<?= h($article['url']) ?>" target="_blank" rel="noopener">
        元記事<?= !empty($article['source_name']) ? '（'.h($article['source_name']).'）' : '' ?>を開く
      </a></p>
    <?php endif; ?>

    <!-- スクロール判定スペーサ -->
    <div id="bottom" class="page-bottom-spacer"></div>
  </main>

  <footer class="app-footer">
    <a href="index.php" class="btn"><img src="images/home.png"   alt="ホーム" width="48"></a>
    <a href="input.php" class="btn"><img src="images/memo.png"   alt="入力"   width="48"></a>
    <a href="articles.php" class="btn"><img src="images/book.png" alt="記事"   width="48"></a>
    <a href="points.php" class="btn"><img src="images/plants.png" alt="成長"   width="48"></a>
    <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="みんな" width="48"></a>
    <a href="read.php" class="btn"><img src="images/calender.png" alt="記録一覧" width="48"></a>
  </footer>

  <script src="js/article-read.js"></script>
</body>
</html>