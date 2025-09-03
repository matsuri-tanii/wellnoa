<?php
session_start();
include("funcs.php");
check_session_id();
check_admin();

// DB接続
$pdo = db_conn();

// SQL作成&実行
$sql = 'SELECT * FROM records ORDER BY record_date DESC, record_time DESC';
$stmt = $pdo->prepare($sql);
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>管理者用ページ</h1>
</body>
</html>