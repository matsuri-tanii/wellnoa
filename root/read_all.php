<?php
require_once __DIR__ . '/anon_session.php';
require_once __DIR__ . '/funcs.php';

$pdo = db_conn();
$uid = current_anon_user_id();

// 行動記録（やったことのみ）と、読了記録（記事タイトル付き）をまとめて取得
$sql = "
  SELECT * FROM (
    SELECT 
      'daily' AS item_type,
      dl.id   AS item_id,
      dl.anonymous_user_id,
      dl.log_date AS d, 
      dl.log_time AS t,
      dl.activity_type,
      NULL AS article_title
    FROM daily_logs dl

    UNION ALL

    SELECT
      'read' AS item_type,
      ar.id  AS item_id,
      ar.anonymous_user_id,
      ar.read_date AS d,
      '00:00:00'    AS t,
      NULL AS activity_type,
      a.title AS article_title
    FROM article_reads ar
    INNER JOIN articles a ON a.id = ar.article_id
  ) AS feed
  ORDER BY d DESC, t DESC
  LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>みんなのきろく</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="app-header">
  <img class="app-logo" src="images/title_logo.png" alt="Wellnoa" width="320">
  <p class="tagline">みんなのきろく</p>
</header>

<main class="feed">
  <?php foreach ($rows as $r): ?>
    <div class="card_box">
      <article class="card card-flex">
        <div class="card-body">
          <div class="meta"><?= h($r['d']) ?> <?= h($r['t']) ?></div>

          <?php if ($r['item_type']==='daily'): ?>
            <div><strong>記録：『<?= h($r['activity_type'] ?: '（未入力）') ?>』</strong>をやった！</div>
          <?php else: ?>
            <div><strong>記事：『<?= h($r['article_title']) ?>』</strong>を読んだ！</div>
          <?php endif; ?>
        </div>

        <div class="support-area">
          <button 
            class="support-btn" 
            data-type="<?= h($r['item_type']) ?>" 
            data-id="<?= (int)$r['item_id'] ?>"
            aria-label="応援する">
            <img src="images/ouen.png" alt="応援する">
          </button>
          <span class="support-text"></span>
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

<script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.support-btn');
  if (!btn) return;

  const type = btn.dataset.type;    // 'daily' or 'read'
  const id   = btn.dataset.id;
  const text = btn.nextElementSibling; // .support-text

  // 二重送信防止
  if (btn.dataset.loading === '1') return;
  btn.dataset.loading = '1';

  try {
    const params = new URLSearchParams();
    params.append('target_type', type);
    params.append('target_id', id);

    const res = await fetch('save_support.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: params.toString()
    });
    const t = await res.text();

    if (t.trim() === 'OK' || t.includes('already')) {
      // 画像切替 & テキスト表示
      const img = btn.querySelector('img');
      img.src = 'images/ouen_active.png';
      img.alt = '応援しました';
      text.textContent = '応援しました！';
      text.classList.add('support-done');

      // 以降押せないように
      btn.disabled = true;
      btn.style.cursor = 'default';
    } else {
      alert('応援に失敗しました。時間をおいて再度お試しください。');
      console.log(t);
    }
  } catch (err) {
    console.error(err);
    alert('通信エラーが発生しました');
  } finally {
    btn.dataset.loading = '0';
  }
});
</script>
</body>
</html>