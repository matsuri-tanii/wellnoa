<?php
session_start();
include('funcs.php');
check_session_id();

// DB接続
$pdo = db_conn();

// SQL作成&実行
$sql = 'SELECT * FROM records WHERE deleted_at IS NULL AND user_id=:user_id ORDER BY record_date DESC, record_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
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
    <div class='btn_box'>
      <div class='btn'><a href='edit.php?id={$record["id"]}'>編集</a></div>
      <div class='btn'><a href='delete.php?id={$record["id"]}' onclick=\"return confirm('本当に削除しますか？');\">削除</a></div>
    </div>
    <div class='card'>
      <div class='card-header'>{$record['record_date']} {$record['record_time']} - {$record['record_type']}</div>
      <div class='card-body'>
        <p><strong>ニックネーム:</strong> {$record['nickname']}</p>
        <p><strong>天気:</strong> {$record['weather']}</p>
        <p><strong>体調:</strong> " . conditionText($record['body_condition']) . "</p>
        <p><strong>心調:</strong> " . conditionText($record['mental_condition']) . "</p>
        <p><strong>やりたい/やったこと:</strong> {$record['want_to_do']}</p>
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
  <style>
    body {
      background: #f5f5f5;
      padding: 20px;
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
    .btn_box {
      display: flex;
      justify-content: space-between;
      line-height: 30px;
    }
    .btn {
      width: 50%;
      height: 30px;
      text-align: center;
      background:rgb(222, 231, 240);
      border-radius: 6px;
      box-shadow: 0 0 3px rgba(0,0,0,0.1);
      margin: 2px;
    }
    .btn a {
      display: block;
      color:rgb(44, 44, 44);
    }
    .btn:hover {
      background: gray;

    }
    .btn a:hover {
      color: white;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <fieldset>
    <legend><?=$_SESSION['username']?>さんの今までのきろく一覧</legend>
    <a href="input.php">入力画面に戻る</a>
    <a href="logout.php">ログアウトする</a>
    <a href="index.php">メインページに戻る</a>
    <?= $elements ?>
  </fieldset>
</body>
</html>