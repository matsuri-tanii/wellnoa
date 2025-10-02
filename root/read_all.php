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
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* 右端揃え＆画像ボタンの見た目 */
    .card.card-flex { display: grid; grid-template-columns: 1fr 72px; gap: 12px; align-items: center;}
    .card-body { min-width: 0;}
    .card-body .meta { color: var(--muted); font-size: .9rem; margin-bottom: .2rem; }
    .card-body { overflow-wrap: anywhere; }
    .support-area { display: flex; flex-direction: column; align-items: center; justify-content: center; row-gap: 6px; width: 72px; min-width: 72px;}
    .support-count { font-size:14px; color:#666; min-width:4.5em; text-align:right; }
    .support-btn { border: 0; background: transparent; padding: 0; cursor: pointer; line-height: 0; touch-action: manipulation;}
    .support-btn img { display: block; width: 44px; height: auto; transition: transform .15s ease, opacity .15s ease;}
    .support-btn:active img { transform: scale(.92); }
    .support-btn.is-on img   { opacity: .85; }
    .support-count { font-size: 12px; color: var(--muted); text-align: center;}
    @media (max-width: 360px) {
      .card.card-flex { grid-template-columns: 1fr 64px; }
      .support-area { width: 64px; min-width: 64px; }
      .support-btn img { width: 40px; }
    }
  </style>
</head>
<body>
<header class="app-header">
  <img class="app-logo" src="images/title_logo.png" alt="Wellnoa" width="320">
  <p class="tagline">みんなのきろく</p>
</header>

<main class="feed">
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

<footer class="app-footer">
  <a href="index.php" class="btn"><img src="images/home.png" alt="ホーム" width="48"></a>
  <a href="input.php" class="btn"><img src="images/memo.png" alt="入力" width="48"></a>
  <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="48"></a>
  <a href="points.php" class="btn"><img src="images/plants.png" alt="成長" width="48"></a>
</footer>

<script src="js/main.js"></script>
<script src="js/cheers.js"></script>
</body>
</html>