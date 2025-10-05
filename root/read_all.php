<?php
require_once __DIR__.'/funcs.php';
adopt_incoming_code(); // QRで来たコードをクッキー採用

$pdo = db_conn();
$me  = current_anon_user_id();

// フィード + 応援集計
$sql = "
SELECT
  feed.item_type,
  feed.item_id,
  feed.anonymous_user_id,
  feed.d,
  feed.t,
  feed.activity_type,
  feed.article_title,
  COALESCE(cc.cheer_count, 0) AS cheer_count,
  COALESCE(mc.my_cheered, 0)  AS my_cheered
FROM (
  SELECT 
    'daily' AS item_type,
    dl.id   AS item_id,
    dl.anonymous_user_id,
    dl.log_date AS d, 
    dl.log_time AS t,
    dl.activity_type,
    NULL AS article_title
  FROM daily_logs AS dl

  UNION ALL

  SELECT
    'read' AS item_type,
    ar.id  AS item_id,
    ar.anonymous_user_id,
    ar.read_date AS d,
    '00:00:00'    AS t,
    NULL AS activity_type,
    a.title AS article_title
  FROM article_reads AS ar
  INNER JOIN articles AS a ON a.id = ar.article_id
) AS feed
LEFT JOIN (
  SELECT target_type, target_id, COUNT(*) AS cheer_count
  FROM cheers
  GROUP BY target_type, target_id
) AS cc
  ON cc.target_type = feed.item_type AND cc.target_id = feed.item_id
LEFT JOIN (
  SELECT target_type, target_id, 1 AS my_cheered
  FROM cheers
  WHERE anonymous_user_id = :me
  GROUP BY target_type, target_id
) AS mc
  ON mc.target_type = feed.item_type AND mc.target_id = feed.item_id
ORDER BY feed.d DESC, feed.t DESC
LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':me' => $me]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>みんなのきろく</title>
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
    <main class="main feed">
      <?php require __DIR__.'/inc/notices.php'; ?>
      <?php foreach ($rows as $r):
        $cheerCount = (int)($r['cheer_count'] ?? 0);
        $myCheered  = !empty($r['my_cheered']);
      ?>
        <div class="card_box">
          <article class="card card-flex">
            <div class="card-body">
              <div class="meta"><?= h($r['d']) ?> <?= h($r['t']) ?></div>

              <?php if ($r['item_type'] === 'daily'): ?>
                <div><strong>記録：『<?= h($r['activity_type'] ?: '（未入力）') ?>』</strong>をやった！</div>
              <?php else: ?>
                <div><strong>記事：『<?= h($r['article_title'] ?: '（不明）') ?>』</strong>を読んだ！</div>
              <?php endif; ?>
            </div>

            <div class="support-area">
              <button
                class="support-btn<?= $myCheered ? ' is-on' : '' ?>"
                data-type="<?= h($r['item_type']) ?>"
                data-id="<?= (int)$r['item_id'] ?>"
                aria-pressed="<?= $myCheered ? 'true' : 'false' ?>"
                aria-label="<?= $myCheered ? '応援をやめる' : '応援する' ?>"
                title="<?= $myCheered ? '応援をやめる' : '応援する' ?>"
              >
                <img src="images/ouen.png" alt="<?= $myCheered ? '応援中' : '応援する' ?>">
              </button>
              <span class="support-count" aria-live="polite">応援 <?= $cheerCount ?></span>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </main>

    <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>

<script src="js/main.js"></script>
<script src="js/cheers.js"></script>
</body>
</html>