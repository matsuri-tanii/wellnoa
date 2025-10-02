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
  <link rel="stylesheet" href="css/style.css">

  <style>
    :root{
      --mint: #a7d7c5;
      --lavender: #e6e6fa;
      --lavender-dark: #c5b3e6;
      --header-h: 140px;
    }
    body{
      font-family: system-ui,-apple-system,Segoe UI,Roboto,"Noto Sans JP",Meiryo,sans-serif;
      background: var(--lavender);
      color: #333;
    }

    .landing-wrap{
      padding: calc(var(--header-h) + 16px) 16px 24px;
      max-width: 960px; margin: 0 auto;
    }

    .hero{
      background: #fff;
      border: 2px solid var(--mint);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 10px rgba(0,0,0,.05);
      display: grid; gap: 16px;
    }
    .hero h1{
      font-size: 22px; margin: 0; color:#2c2c2c;
    }
    .hero p{ margin: 0; color:#444; }

    .features{
      margin: 12px 0; padding:0; list-style:none;
      display:grid; gap:8px;
    }
    .features li{
      padding-left: 1.2em; position: relative;
    }
    .features li::before{
      content:"✔"; position:absolute; left:0; color: var(--mint);
      font-weight:bold;
    }

    .cta{
      display: grid; gap: 12px; margin-top: 10px;
      grid-template-columns: 1fr;
    }
    @media (min-width:640px){
      .cta{ grid-template-columns: 1fr 1fr; }
    }

    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      gap:8px; padding: 14px; border-radius: 12px; text-decoration:none;
      font-weight:700; font-size:15px;
      border: 2px solid transparent;
      transition: all .2s ease;
      text-align:center;
    }
    .btn-primary{
      background: #5aa58b;
      color:#fff;
      border-color: #5aa58b;
    }
    .btn-primary:hover{ background:#4a8c76; }
    .btn-secondary{
      background: var(--lavender);
      border-color: var(--lavender-dark);
      color:#333;
    }
    .btn-secondary:hover{ background: var(--lavender-dark); color:#fff; }

    .note{
      font-size: 13px;
      color:#444;
      background:#fff7ed;
      border:1px solid #fed7aa;
      border-radius: 10px;
      padding:10px;
      margin-top: 10px;
    }

    .sub-links{ margin-top: 14px; font-size: 14px; color:#555; }
    .sub-links a{ color: var(--lavender-dark); font-weight:600; }
  </style>
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

    <p class="sub-links">
      すでに匿名QRをお持ちですか？ → <a href="qr.php">QRを表示</a> ／ <a href="qr_bulk.php">配布用QRを作る</a>
    </p>
  </section>
</main>

<footer class="app-footer">
  <a href="index.php" class="btn"><img src="images/home.png" alt="ホーム" width="32"></a>
  <a href="input.php" class="btn"><img src="images/memo.png" alt="入力" width="32"></a>
  <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="32"></a>
  <a href="points.php" class="btn"><img src="images/plants.png" alt="成長" width="32"></a>
  <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="みんな" width="32"></a>
</footer>

</body>
</html>