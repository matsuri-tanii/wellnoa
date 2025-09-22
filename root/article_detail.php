<?php
require_once __DIR__ . '/funcs.php';
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

  <h1><?= h($article['title']) ?></h1>
  <p><em>カテゴリ：<?= h($article['category']) ?> ｜ 作成日：<?= h($article['published_at']) ?><?php if (!empty($article['source_name'])): ?> ｜ 出典: <?= h($article['source_name']) ?><?php endif; ?></em></p>
  <?php if (!empty($article['thumbnail_url'])): ?>
    <img src="<?= h($article['thumbnail_url']) ?>" alt="thumbnail">
  <?php endif; ?>
  <p><?= nl2br(h($article['description'])) ?></p>
  <div class="ext">
  <?php if (!empty($article['url'])): ?>
    <a class="btn" href="<?= h($article['url']) ?>" target="_blank" rel="noopener">
      元記事<?= !empty($article['source_name']) ? '（'.h($article['source_name']).'）' : '' ?>を開く
    </a>
  <?php endif; ?>
</div>

  <div id="bottom"></div>

  <script>
  function showToast(msg){
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => { el.style.display='none'; }, 300);
    }, 1800);
    el.style.display='block';
  }
  
  let sent = false;

  function postRead(articleId) {
    const params = new URLSearchParams();
    params.append('article_id', articleId);

    fetch('save_article_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
    .then(r => r.text())
    .then(t => console.log('読了記録:', t))
    .catch(e => console.error('読了送信エラー', e));
  }

  window.addEventListener('scroll', () => {
    const bottom = document.getElementById('bottom').getBoundingClientRect().top;
    if (bottom < window.innerHeight && !sent) {
      sent = true;
      fetch('save_article_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ article_id: <?= json_encode($id) ?> })
      })
      .then(res => res.text())
      .then(text => {
        if (text.trim() === 'OK' || text.includes('already')) {
          showToast('読了を記録しました');
        } else {
          showToast('読了記録に失敗しました');
          console.log(text);
        }
      })
      .catch(e => {
        showToast('通信エラーが発生しました');
        console.error(e);
      });
    }
  });

  // もし記事が短くてスクロール不要なら初回に即送る
  window.addEventListener('load', () => {
    const bottom = document.getElementById('bottom').getBoundingClientRect().top;
    if (bottom <= window.innerHeight && !sent) {
      sent = true;
      postRead(<?= (int)$id ?>);
    }
  });
</script>
</body>
</html>