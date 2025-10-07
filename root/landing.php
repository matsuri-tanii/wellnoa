<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';
adopt_incoming_code();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ようこそ Wellnoa へ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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

<?php require __DIR__.'/inc/header.php'; ?>

<main class="landing-wrap">
  <section class="hero">
    <h1>ようこそ Wellnoa へ！</h1>
    <p>Wellnoa は「小さな一歩」を積み重ねるための、かんたん健康ログアプリです。</p>

    <ul class="features">
      <li>匿名で気軽に始められる</li>
      <li>毎日の「やったこと」を1分でメモ</li>
      <li>グラフで体と心の変化を見える化</li>
      <li>役立つ記事を読んでポイントGET</li>
      <li>みんなの記録に「応援」できる</li>
      <li>集めたポイントで風景が成長</li>
    </ul>

    <div class="cta">
      <a class="btn btn-primary" href="register.php">🌟登録してはじめる（おすすめ）</a>
      <a class="btn btn-secondary" href="landing_continue.php">登録せずに今すぐ使う</a>
    </div>

    <p class="note">
      ※登録せずに始める場合、<strong>端末変更やQR紛失でデータが見られなくなる</strong>可能性があります。
    </p>

  </section>
</main>

</body>
</html>