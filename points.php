<?php
include 'anon_session.php';
include_once 'funcs.php';
include 'points_lib.php';

$pdo = db_conn();
$uid = current_anon_user_id();
if (!$uid) { exit('匿名ユーザーIDがありません'); }

$info = calc_points_for_user($pdo, $uid);
$level = $info['level'];
$prog  = $info['progress'];
$ratioPercent = floor($prog['ratio'] * 100);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>成長の記録 - Wellnoa</title>
<link rel="stylesheet" href="css/reset.css">
<link rel="stylesheet" href="css/style.css">
<style>
  body { font-family: system-ui, sans-serif; background:#f0f9f7; margin:0; padding:20px; color:#333; }
  header { background:#a7d7c5; padding:1.2em; text-align:center; margin:-20px -20px 20px; }
  .card { background:#fff; border:1px solid #e5f1ec; border-radius:12px; padding:16px; margin:16px auto; max-width:640px; box-shadow:0 4px 16px rgba(0,0,0,.05); }
  .level { font-size:1.2rem; margin-bottom:8px; }
  .bar { background:#eaf6f2; border-radius:999px; height:18px; overflow:hidden; }
  .bar > .fill { height:100%; width:0; background:#76c7b0; transition:width .6s ease; }
  .meta { display:flex; justify-content:space-between; margin-top:8px; font-size:.9rem; color:#555; }
  .nums { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px; }
  .nums .box { background:#f7fffb; border:1px solid #e1f3ee; border-radius:8px; padding:10px; text-align:center; }
  .big { font-size:1.4rem; font-weight:700; }
  a.btn { display:inline-block; padding:.6em 1.2em; border-radius:8px; background:#76c7b0; color:#fff; text-decoration:none; }
  a.btn:hover{ background:#5db9a1; }
  .footer { text-align:center; margin-top:16px; }
</style>
</head>
<body>

<header>
  <h1>成長の記録</h1>
  <div>あなたの景色：<strong><?= htmlspecialchars($level['label']) ?></strong></div>
</header>

<div class="card">
  <div class="level">
    累積ポイント：<span class="big"><?= number_format($info['total_points']) ?></span> pt
  </div>

  <div class="bar">
    <div class="fill" style="width: <?= $ratioPercent ?>%;"></div>
  </div>

  <div class="meta">
    <div>現在の段階: <?= $prog['current_threshold'] ?> pt 〜</div>
    <div>
      <?php if ($prog['next_threshold']): ?>
        次の段階まで：<?= max(0, $prog['to_next']) ?> pt
      <?php else: ?>
        最高段階に到達しています 🎉
      <?php endif; ?>
    </div>
  </div>

  <div class="nums">
    <div class="box">
      <div>行動ポイント</div>
      <div class="big"><?= number_format($info['activity_points']) ?></div>
    </div>
    <div class="box">
      <div>記事ポイント（読了 <?= $info['read_count'] ?> 件）</div>
      <div class="big"><?= number_format($info['article_points']) ?></div>
    </div>
  </div>

  <div class="footer">
    <a class="btn" href="input.php">記録を追加する</a>
    <a class="btn" href="articles.php">記事を読む</a>
    <a class="btn" href="index.php">ホームへ</a>
  </div>
</div>

</body>
</html>