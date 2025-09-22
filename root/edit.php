<?php
require_once __DIR__ . '/anon_session.php';
require_once __DIR__ . '/funcs.php';

$options = ['散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'];

$pdo = db_conn();
$uid = current_anon_user_id();

$id = $_GET['id'];

$sql = 'SELECT * FROM daily_logs WHERE id = :id AND anonymous_user_id = :uid';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

$record = $stmt->fetch(PDO::FETCH_ASSOC);

// 追加：保存されている値を配列に変換
$checked_options = explode(',', $record['activity_type']);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>今までのきろく（編集）</title>
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
    form {
      background: #fff;
      max-width: 500px;
      margin: auto;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    .form-row {
      display: flex;
      align-items: center;
      line-height:30px;
    }
    .form-row label{
      width: 75pt;
      margin: 0;
    }
    fieldset {
      max-width: 500px;
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
    input[type="text"], textarea {
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

<body class="page-has-footer">
<header>
  <img src="images/title_logo.png" alt="アプリロゴ画像" width="380px">
  <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
</header>
  <form action="update.php" method="POST">
    <fieldset>
      <legend>今までの記録（編集）</legend>

      <div class="form-row">
        <label>記録日時：</label>
        <input type="date" name="log_date" value="<?= htmlspecialchars($record['log_date']) ?>">
        <input type="time" name="log_time" value="<?= htmlspecialchars(substr($record['log_time'],0,5)) ?>"> 
      </div>
      
      <div class="form-row">
        <label>天気：</label>
        <input type="text" name="weather" value="<?= $record['weather'] ?>">
      </div>
      </div>

      <label>体の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="body_condition" value="<?= $record['body_condition'] ?>"></div>
        <div class="range_good">良い</div>
      </div>
      
      <label>心の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="mental_condition" value="<?= $record['mental_condition'] ?>"></div>
        <div class="range_good">良い</div>
      </div>

      <label>やったこと：</label>
      <div class="checkbox-group">
        <?php foreach($options as $opt): ?>
          <label>
            <input 
              type="checkbox" 
              name="activity_type[]" 
              value="<?= $opt ?>" 
              <?= in_array($opt, $checked_options) ? 'checked' : '' ?>
              > <?= $opt ?>
          </label>
        <?php endforeach; ?>
      </div>

      <label>ひとこと：</label>
      <textarea name="memo"><?= htmlspecialchars($record['memo']) ?></textarea>

      <button type="submit">記録する</button>
    
      <input type="hidden" name="id" value="<?= $record['id']?>">
    </fieldset>
  </form>
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
      <img src="images/ouen.png" alt="応援のアイコン" width="60px">
    </div>
  </div>
</footer>
</body>

</html>