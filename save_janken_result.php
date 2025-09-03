<?php
// DB接続
require_once('db_connect.php');

$correct = $_POST['correct'];
$wrong = $_POST['wrong'];
$duration = $_POST['duration'];
$anonymose_user_id = $_POST['anonymose_user_id'];

$sql = "INSERT INTO janken_logs (anonymose_user_id, play_date, correct_count, wrong_count, duration_seconds) VALUES (?, CURDATE(), ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$anonymose_user_id, $correct, $wrong, $duration]);

echo "OK";
?>
