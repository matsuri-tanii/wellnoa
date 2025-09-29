<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

// DB接続
$pdo = db_conn();

$uid = current_anon_user_id();
$gid = current_guest_id();
if (!$gid) {
  $gid = ensure_anon_user($pdo, null);
  set_guest_cookie($gid);
}


// SQL作成&実行
$sql = 'SELECT * FROM daily_logs WHERE anonymous_user_id = :uid ORDER BY log_date DESC, log_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

// データ取得
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

function conditionText($num) {
  if ($num >= 80) return "良い ({$num})";
  if ($num >= 60) return "やや良い ({$num})";
  if ($num >= 40) return "まあまあ ({$num})";
  if ($num >= 20) return "やや悪い ({$num})";
  return "悪い ({$num})";
}

// HTML生成
$elements = '';
foreach ($results as $record) {
  $elements .= "
    <div class='button_box'>
      <div class='button'><a href='edit.php?id={$record["id"]}'>編集</a></div>
      <div class='button'><a href='delete.php?id={$record["id"]}' onclick=\"return confirm('本当に削除しますか？');\">削除</a></div>
    </div>
    <div class='card'>
      <div class='card-header'>{$record['log_date']} {$record['log_time']}</div>
      <div class='card-body'>
        <p><strong>天気:</strong> {$record['weather']}</p>
        <p><strong>体調:</strong> " . conditionText($record['body_condition']) . "</p>
        <p><strong>心調:</strong> " . conditionText($record['mental_condition']) . "</p>
        <p><strong>やったこと:</strong> {$record['activity_type']}</p>
        <p><strong>ひとこと:</strong> {$record['memo']}</p>
      </div>
    </div>
  ";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>今までのきろく一覧</title>
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
    a {
      display: inline-block;
      margin-bottom: 15px;
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .card {
      background: #fdfdfd;
      border: 1px solid #ddd;
      border-radius: 6px;
      margin-bottom: 15px;
      box-shadow: 0 0 3px rgba(0,0,0,0.05);
    }
    .card-header {
      background: #e9ecef;
      padding: 8px 12px;
      font-weight: bold;
      border-bottom: 1px solid #ddd;
    }
    .card-body {
      padding: 10px 12px;
    }
    .card-body p {
      margin: 4px 0;
    }
    .button_box {
      display: flex;
      justify-content: space-between;
      line-height: 30px;
    }
    .button {
      width: 50%;
      height: 30px;
      text-align: center;
      background:rgb(222, 231, 240);
      border-radius: 6px;
      box-shadow: 0 0 3px rgba(0,0,0,0.1);
      margin: 2px;
    }
    .button a {
      display: block;
      color:rgb(44, 44, 44);
    }
    .button:hover {
      background: gray;

    }
    .button a:hover {
      color: white;
      text-decoration: none;
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
  <fieldset>
    <legend>あなたの今までのきろく一覧</legend>
    <?= $elements ?>
  </fieldset>
<footer>
  <div class="footerMenuList">
    <div>
      <a href="index.php" class="btn"><img src="images/home.png" alt="ホームのアイコン" width="60px"></a>
    </div>
    <div>
      <a href="input.php" class="btn"><img src="images/memo.png" alt="入力のアイコン" width="60px"></a>
    </div>
    <div>
      <a href="articles.php" class="btn"><img src="images/book.png" alt="記事のアイコン" width="60px"></a>
    </div>
    <div>
      <a href="points.php" class="btn"><img src="images/plants.png" alt="成長のアイコン" width="60px"></a>
    </div>
    <div>
      <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="応援のアイコン" width="60px"></a>
    </div>
  </div>
</footer>
</body>
</html>