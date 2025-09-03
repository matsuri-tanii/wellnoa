<?php
session_start();
include('funcs.php');
check_session_id();

if (
  !isset($_POST['record_date']) || $_POST['record_date'] === '' ||
  !isset($_POST['record_time']) || $_POST['record_time'] === '' ||
  !isset($_POST['record_type']) || $_POST['record_type'] === '' ||
  !isset($_POST['weather']) || $_POST['weather'] === '' ||
  !isset($_POST['body_condition']) || $_POST['body_condition'] === '' ||
  !isset($_POST['mental_condition']) || $_POST['mental_condition'] === '' ||
  !isset($_POST['memo']) || $_POST['memo'] === '' ||
  !isset($_POST['id']) || $_POST['id'] === ''
) {
  exit('paramError');
}

$record_date = $_POST['record_date'];
$record_time = $_POST['record_time'];
$record_type = $_POST['record_type'];
$weather = $_POST['weather'];
$body_condition = $_POST['body_condition'];
$mental_condition = $_POST['mental_condition'];
$want_to_do = $_POST['want_to_do'];
$memo = $_POST['memo'];
$id = $_POST['id'];

$want_to_do = '';
if (isset($_POST['want_to_do']) && is_array($_POST['want_to_do'])) {
  $want_to_do = implode(',', $_POST['want_to_do']);
}

// DB接続
$pdo = db_conn();

$sql = 'UPDATE records
        SET    record_date=:record_date, 
               record_time=:record_time,
               record_type=:record_type,
               weather=:weather,
               body_condition=:body_condition, 
               mental_condition=:mental_condition, 
               want_to_do=:want_to_do, 
               memo=:memo, 
               updated_at=now() 
        WHERE  id = :id AND user_id = :user_id';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':record_date', $record_date, PDO::PARAM_STR);
$stmt->bindValue(':record_time', $record_time, PDO::PARAM_STR);
$stmt->bindValue(':record_type', $record_type, PDO::PARAM_STR);
$stmt->bindValue(':weather', $weather, PDO::PARAM_STR);
$stmt->bindValue(':body_condition', $body_condition, PDO::PARAM_INT);
$stmt->bindValue(':mental_condition', $mental_condition, PDO::PARAM_INT);
$stmt->bindValue(':want_to_do', $want_to_do, PDO::PARAM_STR);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

header("Location:read.php");
exit();
