<?php
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__ . '/anon_session.php';
require_once __DIR__ . '/funcs.php';

// やりたいことの選択肢
$options = ['散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wellnoa -記録画面</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      background: #f5f5f5;
    }
    header {
      background: #a7d7c5;
      padding: 1.5em;
      text-align: center;
    }
    header img {
      align-items: center;
      mix-blend-mode: multiply;
    }
    p.tagline {
      font-size: 1.2em;
      color: #555;
    }
    .form-row {
      display: flex;
      align-items: center;
      line-height:30px;
    }
    .form-row label{
      width: 120pt;
      margin: 0;
    }
    fieldset {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border: none;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    legend {
      font-size: 1.2em;
      margin-bottom: 10px;
      font-weight: bold;
    }
    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    input[type="text"], select, textarea {
      width: 100%;
      padding: 6px;
      margin-top: 4px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .range {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      grid-template-rows: repeat(1, 30px);
    }
    .range_bad {
      grid-area: 1/1/2/2;
      place-self: center;
    }
    .range_input {
      grid-area: 1/2/2/7;
    }
    .range_good {
      grid-area: 1/7/2/8;
      place-self: center;
    }
    input[type="range"] {
      width: 100%;
      appearance:none;
      height:20px;
    }
    input[type="range"]::-webkit-slider-runnable-track {
      height:20px;
      background:linear-gradient(90deg,#FFFFCC,#87CEFA);
      border-radius:5px;
    }
    input[type="range"]::-webkit-slider-thumb {
      appearance:none;
      width:20px;
      height:20px;
      border-radius:50%;
      background:#fff;
      border:solid 2px coral;
    }
    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }
    .checkbox-group label {
      font-weight: normal;
    }
    button {
      margin-top: 15px;
      width: 100%;
      padding: 8px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1em;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    a {
      display: inline-block;
      margin-bottom: 10px;
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    footer {
      position: fixed;
      bottom: 0;
      width: 100%;
    }
    .footerMenuList {
      background-color: #a7d7c5;
      padding: 5px;
      display: flex;
      justify-content: space-between;
    }
    .btn{
      display: inline-block;
    }
    .btn img{
      display: block;
    }
  </style>
</head>
<body>
<?php $flash = pop_flash(); ?>
<?php if ($flash): ?>
  <div class="flash <?= h($flash['type']) ?>" id="flashBox">
    <?= h($flash['message']) ?>
  </div>
  <script>
    // 2.0秒でフェードアウト→消す
    setTimeout(() => {
      const box = document.getElementById('flashBox');
      if (!box) return;
      box.style.opacity = '0';
      setTimeout(() => box.remove(), 400); // フェード後にDOMから消す
    }, 2000);
  </script>
<?php endif; ?>
<header>
  <img src="images/title_logo.png" alt="アプリロゴ画像" width="380px">
  <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
</header>
  <form action="create.php" method="POST">
    <fieldset>
      <legend>記録する</legend>
      <a href="read.php">今までのきろくを見る</a>

      <input type="hidden" name="weather" id="weather" />

      <label>体の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="body" min="0" max="100"></div>
        <div class="range_good">良い</div>
      </div>

      <label>心の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="mental" min="0" max="100"></div>
        <div class="range_good">良い</div>
      </div>

      <label>やったこと（複数選択可）：</label>
      <div class="checkbox-group">
        <?php foreach($options as $opt): ?>
          <label>
            <input type="checkbox" name="activity_type[]" value="<?= $opt ?>"> <?= $opt ?>
          </label>
        <?php endforeach; ?>
      </div>

      <label>ひとこと：</label>
      <textarea name="memo"></textarea>

      <button type="submit">記録する</button>
    </fieldset>
  </form>
  <div class="page-bottom-spacer"></div>

<footer>
  <div class="footerMenuList">
    <div>
      <a href="index.php" class="btn"><img src="images/home.png" alt="ホームのアイコン" width="60px"></a>
    </div>
    <div>
      <a href="articles.php" class="btn"><img src="images/book.png" alt="記事のアイコン" width="60px"></a>
    </div>
    <div>
      <a href="points.php" class="btn"><img src="images/plants.png" alt="成長のアイコン" width="60px"></a>
    </div>
    <div>
      <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="応援" width="60"></a>
    </div>
    <div>
      <a href="read.php" class="btn"><img src="images/calender.png" alt="カレンダーのアイコン" width="60px"></a>
    </div>
  </div>
</footer>

  <script src="js/main.js"></script>
</body>
</html>