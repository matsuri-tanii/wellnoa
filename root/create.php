<?php
require_once __DIR__.'/funcs.php';
adopt_incoming_code();
session_start();

$anonymous_user_id = current_anon_user_id();
if (empty($anonymous_user_id)) {
  exit('匿名ユーザーIDが取得できませんでした。');
}

// 入力チェック
if (
  !isset($_POST['body']) || $_POST['body'] === '' ||
  !isset($_POST['mental']) || $_POST['mental'] === '' ||
  empty($_POST['activity_type']) || !is_array($_POST['activity_type'])
) {
  set_flash('必要なデータが足りません。', 'error');
  header('Location: record_hub.php');
  exit();
}

// データ受け取り
$body     = (int)$_POST['body'];
$mental   = (int)$_POST['mental'];
$memo     = trim($_POST['memo'] ?? '');
$weather  = trim($_POST['weather'] ?? '');

// チェックボックスをカンマ区切りに
$activity_type = implode(',', $_POST['activity_type']);

// 日時
$log_date = date('Y-m-d');
$log_time = date('H:i:s');

// DB接続
$pdo = db_conn();

// SQL
$sql = 'INSERT INTO daily_logs (
          anonymous_user_id, log_date, log_time, weather,
          body_condition, mental_condition, activity_type, memo,
          created_at, updated_at
        )
        VALUES (
          :uid, :log_date, :log_time, :weather,
          :body, :mental, :activity_type, :memo,
          NOW(), NOW()
        )';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $anonymous_user_id, PDO::PARAM_INT);
$stmt->bindValue(':log_date', $log_date, PDO::PARAM_STR);
$stmt->bindValue(':log_time', $log_time, PDO::PARAM_STR);
$stmt->bindValue(':weather', $weather, PDO::PARAM_STR);
$stmt->bindValue(':body', $body, PDO::PARAM_INT);
$stmt->bindValue(':mental', $mental, PDO::PARAM_INT);
$stmt->bindValue(':activity_type', $activity_type, PDO::PARAM_STR);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);

try {
  $stmt->execute();
  set_flash('記録しました！', 'success');
} catch (PDOException $e) {
  set_flash('記録に失敗しました：' . $e->getMessage(), 'error');
}

// 戻る
header('Location: record_hub.php');
exit();