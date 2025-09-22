<?php
require_once __DIR__ . '/anon_session.php';
require_once __DIR__ . '/funcs.php';

$anonymous_user_id = current_anon_user_id(); // 例: int を返す想定
if (empty($anonymous_user_id)) {
  exit('匿名ユーザーIDが取得できませんでした。');
}
// 必須データ確認
if (
  !isset($_POST['body']) || $_POST['body'] === '' ||
  !isset($_POST['mental']) || $_POST['mental'] === '' ||
  !isset($_POST['activity_type']) || $_POST['activity_type'] === '' 
) {
  exit('必要なデータが送信されていません');
}

// データ受け取り
$body = $_POST['body'];
$mental = $_POST['mental'];
$memo = $_POST['memo'];
$weather = $_POST['weather'];

// チェックボックスの値を文字列にまとめる
$activity_type = '';
if(isset($_POST['activity_type']) && is_array($_POST['activity_type'])){
  $activity_type = implode(',', $_POST['activity_type']);
}

// 日付・時刻
$log_date = date('Y-m-d');
$log_time = date('H:i:s');

// DB接続
$pdo = db_conn();

// SQL作成
$sql = 'INSERT INTO daily_logs (anonymous_user_id, log_date, log_time, weather, body_condition, mental_condition, activity_type, memo, created_at, updated_at)
        VALUES (:uid, :log_date, :log_time, :weather, :body, :mental, :activity_type, :memo, NOW(), NOW())';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid',  $anonymous_user_id, PDO::PARAM_INT);
$stmt->bindValue(':log_date', $log_date, PDO::PARAM_STR);
$stmt->bindValue(':log_time', $log_time, PDO::PARAM_STR);
$stmt->bindValue(':weather', $weather, PDO::PARAM_STR);
$stmt->bindValue(':body', $body, PDO::PARAM_INT);
$stmt->bindValue(':mental', $mental, PDO::PARAM_INT);
$stmt->bindValue(':activity_type', $activity_type, PDO::PARAM_STR);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);

// SQL実行
try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

set_flash('記録を保存しました'); 

// 入力画面に戻る
header('Location:input.php');
exit();
?>