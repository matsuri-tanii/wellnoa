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
  <link rel="stylesheet" href="css/style.css">

  <style>
    /* ——— 追加の最小スタイル（必要ならstyle.cssに移動） ——— */
    body { font-family: system-ui, -apple-system, sans-serif; padding: 20px; line-height: 1.6; }
    img  { max-width: 100%; height: auto; }
    .article-main { max-width: 880px; margin: 0 auto; }
    .article-meta { color:#666; font-size:14px; margin: 6px 0 16px; }

    /* バッジ（articles.php 風の丸タグ） */
    .read-badge {
      display:inline-flex; align-items:center; gap:6px;
      padding: 4px 10px; border-radius: 9999px;
      font-size: 12px; font-weight: 600; line-height: 1;
      vertical-align: middle; user-select: none;
    }
    .read-badge[data-state="notyet"] { background:#fff5f5; color:#c53030; border:1px solid #fecaca; }
    .read-badge[data-state="done"]   { background:#ecfdf5; color:#047857; border:1px solid #bbf7d0; }

    .read-badge .dot {
      width:8px; height:8px; border-radius:50%;
      background: currentColor; opacity:.7;
    }

    /* 下部アクション（本文内の戻るボタン群） */
    .article-actions {
      margin: 28px 0 36px; display:flex; flex-wrap:wrap; gap:10px;
    }
    .article-actions .btn {
      appearance: none; border:1px solid #ddd; background:#fff;
      padding:10px 14px; border-radius:10px; font-weight:600;
      display:inline-flex; align-items:center; gap:8px; text-decoration:none; color:#222;
      box-shadow:0 1px 2px rgba(0,0,0,.05);
    }
    .article-actions .btn:hover { border-color:#bbb; }
    .article-actions .btn img { width:22px; height:auto; display:block; }

    /* トースト（既存） */
    .toast{
      position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%);
      background: rgba(0,0,0,.8); color:#fff; padding:10px 14px;
      border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.2);
      z-index: 9999; opacity:0; transition: opacity .3s ease, transform .3s ease;
    }
    .toast.show{ display:block; opacity:1; transform: translateX(-50%) translateY(0); }

    /* 固定フッター（既存） */
    footer.app-footer {
      position: fixed; bottom: 0; width: 100%;
    }
    .footerMenuList {
      background-color: #a7d7c5; padding: 5px;
      display: flex; justify-content: space-between;
    }
    .btn{ display: inline-block; }
    .btn img{ display: block; }
  </style>
</head>
<body>
  <header class="app-header" style="text-align:center; margin-bottom:10px;">
    <img class="app-logo" src="images/title_logo.png" alt="Wellnoa" width="320">
    <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
  </header>

  <main class="container article-main">
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
      <img class="article-thumb" src="<?= h($article['thumbnail_url']) ?>" alt="">
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
      <a class="btn" href="index.php"><img src="images/home.png" alt="">ホームに戻る</a>
    </div>

    <!-- スクロール判定スペーサ（読了検知に利用） -->
    <div id="bottom" class="page-bottom-spacer" style="height: 40vh;"></div>
    <div id="toast" class="toast" style="display:none;" aria-live="polite"></div>
  </main>

  <!-- 既存の固定フッター（そのまま） -->
  <footer class="app-footer">
    <a href="index.php" class="btn"><img src="images/home.png"   alt="ホーム" width="48"></a>
    <a href="input.php" class="btn"><img src="images/memo.png"   alt="入力"   width="48"></a>
    <a href="articles.php" class="btn"><img src="images/book.png" alt="記事"   width="48"></a>
    <a href="points.php" class="btn"><img src="images/plants.png" alt="成長"   width="48"></a>
    <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="みんな" width="48"></a>
    <a href="read.php" class="btn"><img src="images/calender.png" alt="記録一覧" width="48"></a>
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