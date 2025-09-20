<?php

include 'anon_session.php';
include_once 'funcs.php';

if (
  !isset($_POST['log_date']) || $_POST['log_date'] === '' ||
  !isset($_POST['log_time']) || $_POST['log_time'] === '' ||
  !isset($_POST['weather']) || $_POST['weather'] === '' ||
  !isset($_POST['body_condition']) || $_POST['body_condition'] === '' ||
  !isset($_POST['mental_condition']) || $_POST['mental_condition'] === '' ||
  !isset($_POST['memo']) || $_POST['memo'] === '' ||
  !isset($_POST['id']) || $_POST['id'] === ''
) {
  exit('paramError');
}

$uid = current_anon_user_id();
$log_date = $_POST['log_date'];
$log_time = $_POST['log_time'];
$weather = $_POST['weather'];
$body_condition = $_POST['body_condition'];
$mental_condition = $_POST['mental_condition'];
$memo = $_POST['memo'];
$id = $_POST['id'];

$activity_type = '';
if(isset($_POST['activity_type']) && is_array($_POST['activity_type'])){
  $activity_type = implode(',', $_POST['activity_type']);
}

// DB接続
$pdo = db_conn();

$sql = 'UPDATE daily_logs
        SET    log_date=:log_date, 
               log_time=:log_time,
               weather=:weather,
               body_condition=:body_condition, 
               mental_condition=:mental_condition, 
               activity_type=:activity_type, 
               memo=:memo, 
               updated_at=now() 
        WHERE  id = :id AND anonymous_user_id = :uid';

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
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

set_flash('記録を更新しました');
header("Location:read.php");
exit();
