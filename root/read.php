<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

// DB接続
$pdo = db_conn();

$uid = current_anon_user_id();

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
  <title>Wellnoa - すべての記録</title>
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
<div class="layout">
    <!-- 1) 常時表示ヘッダー 共通お知らせ（未登録警告・フラッシュ・登録誘導） -->
    <?php require __DIR__.'/inc/header.php'; ?>
    <!-- 2) サイドナビ（PC/タブ横のみCSSで表示） -->
    <aside class="side-nav">
      <?php require __DIR__.'/inc/side_nav.php'; ?>
    </aside>
    <!-- 3) メイン -->
    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>
    <fieldset>
      <legend>あなたの今までのきろく一覧</legend>
      <?= $elements ?>
    </fieldset>

    <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>
</body>
</html>