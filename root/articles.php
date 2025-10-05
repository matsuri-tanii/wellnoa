<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$pdo = db_conn();
$uid = (int) current_anon_user_id();   // 念のため数値化
$daysNew = 7;
$newThreshold = date('Y-m-d H:i:s', strtotime("-{$daysNew} days"));

// 読了判定は EXISTS で簡潔に／新着判定は PHP で計算した日時を使う
$sql = "
  SELECT
    a.id,
    a.title,
    a.thumbnail_url,
    a.published_at,
    a.source_name,
    /* 読了フラグ（該当行が存在するか） */
    EXISTS(
      SELECT 1
      FROM article_reads ar
      WHERE ar.article_id = a.id
        AND ar.anonymous_user_id = :uid
    ) AS is_read,
    /* 新着フラグ（しきい値以降の公開日） */
    CASE
      WHEN a.published_at IS NOT NULL AND a.published_at >= :threshold THEN 1
      ELSE 0
    END AS is_new
  FROM articles a
  /* NULL を後ろに、その後 新しい順、最後に id 降順 */
  ORDER BY (a.published_at IS NULL), a.published_at DESC, a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->bindValue(':threshold', $newThreshold, PDO::PARAM_STR);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fallbackThumb = 'images/article_placeholder.png';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wellnoa - あなたの小さな健康習慣</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
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
    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>

      <div class="container center">
        <h2>articles</h2>
        <h3>健康記事を読む</h3>
      </div>

      <div class="grid">
        <?php foreach ($articles as $a): ?>
          <?php
            $thumb  = $a['thumbnail_url'] ? h($a['thumbnail_url']) : $fallbackThumb;
            $dateJp = $a['published_at'] ? date('Y/m/d', strtotime($a['published_at'])) : '—';
            $isRead = ((int)$a['is_read'] === 1);
            $isNew  = ((int)$a['is_new']  === 1);
          ?>
          <a class="card_link" href="article_detail.php?id=<?= h($a['id']) ?>">
            <article class="card article">
              <img class="thumb" src="<?= $thumb ?>" alt="" referrerpolicy="no-referrer" loading="lazy" decoding="async">
              <div class="body">
                <h2 class="title"><?= h($a['title']) ?></h2>
                <?php if (!empty($a['source_name'])): ?>
                  <div class="meta">出典: <?= h($a['source_name']) ?></div>
                <?php endif; ?>
                <div class="meta">公開日: <?= h($dateJp) ?></div>

                <div class="badges">
                  <?php if ($isRead): ?>
                    <span class="badge badge-read">読了</span>
                  <?php else: ?>
                    <span class="badge badge-unread">未読</span>
                  <?php endif; ?>
                  <?php if ($isNew): ?>
                    <span class="badge badge-new">新着</span>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          </a>
        <?php endforeach; ?>
      </div>

    <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>

</body>
</html>