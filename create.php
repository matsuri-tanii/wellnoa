<?php
session_start();
include('funcs.php');
check_session_id();

// 必須データ確認
if (
  !isset($_POST['record_type']) || $_POST['record_type'] === '' ||
  !isset($_POST['weather']) || $_POST['weather'] === '' ||
  !isset($_POST['body']) || $_POST['body'] === '' ||
  !isset($_POST['mental']) || $_POST['mental'] === '' ||
  !isset($_POST['want_to_do']) || $_POST['want_to_do'] === '' ||
  !isset($_POST['memo']) || $_POST['memo'] === ''
  ) {
  exit('必要なデータが送信されていません');
}

// データ受け取り
$record_type = $_POST['record_type'];
$nickname = $_POST['nickname'];
$body = $_POST['body'];
$mental = $_POST['mental'];
$memo = $_POST['memo'];
$weather = $_POST['weather'];

// チェックボックスの値を文字列にまとめる
$want_to_do = '';
if(isset($_POST['want_to_do']) && is_array($_POST['want_to_do'])){
  $want_to_do = implode(',', $_POST['want_to_do']);
}

// 日付・時刻
$record_date = date('Y-m-d');
$record_time = date('H:i:s');
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');

// DB接続
$pdo = db_conn();

// SQL作成
$sql = 'INSERT INTO records (record_date, record_time, record_type, nickname, weather, body_condition, mental_condition, want_to_do, memo, created_at, updated_at, deleted_at, user_id)
        VALUES (:record_date, :record_time, :record_type, :nickname, :weather, :body, :mental, :want_to_do, :memo, :created_at, :updated_at, :deleted_at, :user_id)';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':record_date', $record_date, PDO::PARAM_STR);
$stmt->bindValue(':record_time', $record_time, PDO::PARAM_STR);
$stmt->bindValue(':record_type', $record_type, PDO::PARAM_STR);
$stmt->bindValue(':nickname', $nickname, PDO::PARAM_STR);
$stmt->bindValue(':weather', $weather, PDO::PARAM_STR);
$stmt->bindValue(':body', $body, PDO::PARAM_INT);
$stmt->bindValue(':mental', $mental, PDO::PARAM_INT);
$stmt->bindValue(':want_to_do', $want_to_do, PDO::PARAM_STR);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
$stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
$stmt->bindValue(':updated_at', $updated_at, PDO::PARAM_STR);
$stmt->bindValue(':deleted_at', $deleted_at, PDO::PARAM_STR);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

// SQL実行
try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

// 入力画面に戻る
header('Location:input.php');
exit();
?>