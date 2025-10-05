<?php
require_once __DIR__.'/funcs.php';
adopt_incoming_code();
session_start();

$uid = current_anon_user_id();
if (empty($uid)) {
  exit('匿名ユーザーIDが取得できません。');
}

// 必須チェック
if (
  empty($_POST['id']) ||
  empty($_POST['log_date']) ||
  empty($_POST['log_time']) ||
  $_POST['body_condition'] === '' ||
  $_POST['mental_condition'] === ''
) {
  set_flash('入力内容に不備があります。', 'error');
  header('Location: read.php');
  exit();
}

// 値の取得
$id                = (int)$_POST['id'];
$log_date          = $_POST['log_date'];
$log_time          = $_POST['log_time'];
$weather           = $_POST['weather'];
$body_condition    = (int)$_POST['body_condition'];
$mental_condition  = (int)$_POST['mental_condition'];
$memo              = $_POST['memo'] ?? '';

$activity_type = '';
if (!empty($_POST['activity_type']) && is_array($_POST['activity_type'])) {
  $activity_type = implode(',', $_POST['activity_type']);
}

// DB接続
$pdo = db_conn();

$sql = 'UPDATE daily_logs
        SET log_date = :log_date,
            log_time = :log_time,
            weather = :weather,
            body_condition = :body_condition,
            mental_condition = :mental_condition,
            activity_type = :activity_type,
            memo = :memo,
            updated_at = NOW()
        WHERE id = :id AND anonymous_user_id = :uid';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':log_date', $log_date, PDO::PARAM_STR);
$stmt->bindValue(':log_time', $log_time, PDO::PARAM_STR);
$stmt->bindValue(':weather', $weather, PDO::PARAM_STR);
$stmt->bindValue(':body_condition', $body_condition, PDO::PARAM_INT);
$stmt->bindValue(':mental_condition', $mental_condition, PDO::PARAM_INT);
$stmt->bindValue(':activity_type', $activity_type, PDO::PARAM_STR);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

try {
  $stmt->execute();
  if ($stmt->rowCount() > 0) {
    set_flash('記録を更新しました', 'success');
  } else {
    set_flash('更新対象の記録が見つかりません。', 'error');
  }
} catch (PDOException $e) {
  set_flash('更新に失敗しました：' . $e->getMessage(), 'error');
}

// 一覧に戻る
header('Location: read.php');
exit();