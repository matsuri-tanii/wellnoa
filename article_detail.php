<?php
include('funcs.php');
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
  <style>
    body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
    img { max-width: 100%; height: auto; }
  </style>
</head>
<body>
  <h1><?= h($article['title']) ?></h1>
  <p><em>カテゴリ：<?= h($article['category']) ?> ｜ 投稿日：<?= h($article['published_at']) ?></em></p>
  <?php if (!empty($article['thumbnail_url'])): ?>
    <img src="<?= h($article['thumbnail_url']) ?>" alt="thumbnail">
  <?php endif; ?>
  <p><?= nl2br(h($article['description'])) ?></p>
  <p><a href="<?= h($article['url']) ?>" target="_blank">元記事を見る</a></p>

  <div id="bottom"></div>

  <script>
    // ページの一番下までスクロールしたら読了APIを叩く
    let sent = false;
    window.addEventListener('scroll', () => {
      const bottom = document.getElementById('bottom').getBoundingClientRect().top;
      if (bottom < window.innerHeight && !sent) {
        sent = true;
        fetch('record_article_read.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ article_id: <?= json_encode($id) ?> })
        }).then(res => res.json()).then(data => {
          console.log('読了記録:', data);
        });
      }
    });
  </script>
</body>
</html>